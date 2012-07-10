<?php

class ScannerException extends Exception { }

class Scanner {
	private $fd = null;
	private $buffer = '';
	private $captures = array();
	
	const MATCH_WHILE = true;
	const MATCH_UNTIL = false;
	
	function __construct($resource_or_string){
		if ( is_resource($resource_or_string) )
			$this->fd = $resource_or_string;
		elseif ( is_string($resource_or_string) )
			$this->buffer = $resource_or_string;
		else
			throw new ScannerException("Don't know how to read data from $resource_or_string");
	}
	
	function capture($action){
		array_push($this->captures, '');
		$action();
		return array_pop($this->captures);
	}
	
	function one_of($tokens){
		list($tokens, $match_eof, $accept_mismatch) = $this->process_token_args(func_get_args());
		$match = $this->match_at(0, $tokens);
		
		// If we got an EOF and the user does not want to match an EOF mark the match as failed
		if ($match === null and !$match_eof)
			$match = false;
		
		if ($match === false and !$accept_mismatch)
			throw new ScannerException('got mismatch! buffer: "' . substr($this->buffer, 0, 10) . '"');
		
		$this->consume_buffer(strlen($match));
		return $match;
	}
	
	function peek($tokens){
		list($tokens, $match_eof, $accept_mismatch) = $this->process_token_args(func_get_args());
		$match = $this->match_at(0, $tokens);
		
		// If we got an EOF and the user does not want to match an EOF mark the match as failed
		if ($match === null and !$match_eof)
			$match = false;
		if ($match === false and !$accept_mismatch)
			throw new ScannerException('got mismatch! buffer: "' . substr($this->buffer, 0, 10) . '"');
		
		return $match;
	}
	
	function bytes($number_of_bytes, $exception_on_missmatch = true){
		$data = $this->token_at(0, $number_of_bytes);
		
		if ($data === false or $data === null) {
			if ($exception_on_missmatch)
				throw new ScannerException('got mismatch! buffer: "' . substr($this->buffer, 0, 10) . '"');
		} else {
			$this->consume_buffer(strlen($data));
		}
		
		return $data;
	}
	
	function as_long_as($tokens){
		list($tokens, $match_eof, $accept_mismatch) = $this->process_token_args(func_get_args());
		list($content, $match) = $this->match_loop($tokens, self::MATCH_WHILE);
		
		// $match will always indicated a failed match (since we scan until it fails...). Therefore we
		// have to extract the peek ourselfs. We return as many characters as the longest token requires.
		$max_token_len = array_reduce($tokens, function($len, $token){
			$token_len = is_string($token) ? strlen($token) : 1;
			return $len > $token_len ? $len : $token_len;
		}, 0);
		
		$match = ($this->buffer === null) ? null : substr($this->buffer, 0, $max_token_len);
		
		return array($content, $match);
	}
	
	function until($tokens){
		list($tokens, $match_eof, $accept_mismatch) = $this->process_token_args(func_get_args());
		list($content, $match) = $this->match_loop($tokens, self::MATCH_UNTIL);
		
		if ($match === null and !$match_eof)
			$match = false;
		
		if ($match === false and !$accept_mismatch)
			throw new ScannerException('got mismatch! buffer: ' . $this->buffer);
		
		return array($content, $match);
	}
	
	/**
	 * Reads stuff from the resource either as long as the stuff matches the `$tokens`
	 * (`$while_or_until` is set to `MATCH_WHILE`) or as long as it does not match
	 * `$tokens` and the first matching token is found (`$while_or_until` to `MATCH_UNTIL`).
	 */
	private function match_loop($tokens, $while_or_until){
		$pos = 0;
		while(true){
			$match = $this->match_at($pos, $tokens);
			if ($match === null)
				break;
			if ( ($match === false) == $while_or_until )
				break;
			$pos += is_string($match) ? strlen($match) : 1;
		}
		
		$content = substr($this->buffer, 0, $pos);
		$this->consume_buffer($pos);
		
		return array($content, $match);
	}
	
	/**
	 * Checks if one of the `$tokens` can be found exactly at `$pos`. Returns `false` if
	 * no token matches or `null` if `$pos` is at EOF.
	 */
	private function match_at($pos, $tokens){
		// If there are no tokens in the list someone is explicitly searching for EOF. If
		// `token_at()` returns `null` we are at EOF and return `null`, too. Otherwise
		// return `false` for a missmatch.
		if ( count($tokens) == 0 and $pos >= strlen($this->buffer) )
			return ($this->token_at($pos, 1) === null) ? null : false;
		
		foreach($tokens as $token){
			$found = $this->token_at($pos, is_string($token) ? strlen($token) : 1);
			if ($found === null)
				return null;
			elseif ($found === false)
				continue;
			elseif ($found === $token)
				return $token;
			elseif ($token instanceof Closure and $token($found))
				return $found;
		}
		
		return false;
	}
	
	private function process_token_args($args){
		$mismatch = false;
		$eof = false;
		$tokens = array();
		
		foreach($args as $arg){
			if ($arg === false)
				$mismatch = true;
			elseif ($arg === null)
				$eof = true;
			else
				$tokens[] = $arg;
		}
		
		return array($tokens, $eof, $mismatch);
	}
	
	private function token_at($pos, $length){
		if ($length < 1)
			throw new ScannerException("You tried to scan for a token with the length $length. The scanner is not made for this, maybe it's an error.");
		
		$required_buffer_size = $pos + $length;
		$missing_bytes = $required_buffer_size - strlen($this->buffer);
		$pos_outside_buffer = $pos >= strlen($this->buffer);
		
		if ($missing_bytes > 0) {
			// Buffer not full enough, get new data
			
			// No resource to read new data from, so this token is either a missmatch
			// (data partially there) or EOF (no data for token there at all).
			if ($this->fd === null)
				return $pos_outside_buffer ? null : false;
			
			$data = stream_get_contents($this->fd, $missing_bytes);
			// Treat an IO error the same as missmatch or EOF. Otherwise we will surely
			// hang in some endless loop somewhere.
			if ($data === false)
				return $pos_outside_buffer ? null : false;
			
			$this->buffer .= $data;
			
			// If we didn't got enough data it's missmatch or EOF for this token
			if (strlen($data) < $missing_bytes)
				return $pos_outside_buffer ? null : false;
		}
		
		// If we're here the requested token is in buffer, return it
		return substr($this->buffer, $pos, $length);
	}
	
	/**
	 * Throws the specified number of bytes away from the start of the buffer.
	 */
	private function consume_buffer($bytes_to_consume){
		$consumed = substr($this->buffer, 0, $bytes_to_consume);
		foreach($this->captures as &$capture)
			$capture .= $consumed;
		
		$this->buffer = substr($this->buffer, $bytes_to_consume);
	}
}

?>