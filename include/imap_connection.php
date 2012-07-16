<?php

require( dirname(__FILE__) . '/scanner.php');

class ImapException extends Exception {
	public $status = null, $status_msg = null;
	function __construct($msg, $status = null, $status_msg = null){
		parent::__construct($msg);
		$this->status = $status;
		$this->status_msg = $status_msg;
	}
}

class ImapConnection {
	private $tag_counter = 1;
	private $stream = null;
	private $sensitive_data = null;
	private $log_file = null;
	
	/**
	 * Opens a socket connection to the IMAP server at `$uri`. The URI can be one
	 * of the socket transports supported by PHP [1]. The `$timeout` parameter gives
	 * the number of seconds PHP waits for the connection. `$options` can be used
	 * to set a log file (set the `log_file` key to the path you want the IMAP conversation
	 * to be logged to). It can also be used to set additional stream context options [2]
	 * (e.g don't verify certificates for encrypted connections).
	 * 
	 * 	$imap = new ImapConnection('tcp://localhost:143', 1);
	 * 
	 * 	$imap = new ImapConnection('tls://mail.hdm-stuttgart.de:993', 1, array(
	 * 		'log_file' => 'imap.log',
	 * 		'ssl' => array( 'verify_peer' => false )
	 * 	));
	 * 
	 * [1]: http://php.net/transports.inet
	 * [2]: http://php.net/context
	 */
	function __construct($uri, $timeout, $options = array()){
		if ( isset($options['log_file']) ){
			$this->log_file = $options['log_file'];
			unset($options['log_file']);
		}
		
		$stream_context = stream_context_create($options);
		$this->stream = stream_socket_client($uri, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $stream_context);
		
		if ($this->stream === false)
			throw new ImapException("Could not open IMAP connection: $errstr ($errno)");
		
		// Consume the initial server greeting response
		list($tag, $rest) = $this->get_response();
	}
	
	/**
	 * Make sure we do a proper logout on the connection.
	 */
	function __destruct(){
		$this->close();
	}
	
	/**
	 * Do a proper logout and then close the socket connection.
	 */
	function close(){
		if ( ! is_resource($this->stream) )
			return;
		
		$this->command('logout');
		fclose($this->stream);
		$this->stream = null;
	}
	
	/**
	 * Sends a command to the IMAP server and returns all responses. If the returned
	 * status code is not the `$expected_status` an `ImapException` is thrown. All status
	 * codes from the server are automatically converted to lower case so specify your
	 * expected code in lowercase (defaults to 'ok'). The exceptions `status` and `status_msg`
	 * members contain the data returned by the server.
	 * 
	 * 	$imap->command('select inbox');
	 * 	=> array(
	 * 		"FLAGS (\Answered \Flagged \Draft \Deleted \Seen $MDNSent $Forwarded $Label7 NonJunk Junk)",
	 * 		"OK [PERMANENTFLAGS (\Answered \Flagged \Draft \Deleted \Seen $MDNSent $Forwarded $Label7 NonJunk Junk \*)]  ",
	 * 		"458 EXISTS",
	 * 		"0 RECENT",
	 * 		"OK [UNSEEN 387]  ",
	 * 		"OK [UIDVALIDITY 1219060687]  ",
	 * 		"OK [UIDNEXT 3641]  "
	 * 	)
	 */
	function command($command, $expected_status = 'ok'){
		$full_cmd = sprintf("a%03d %s\r\n", $this->tag_counter++, $command);
		$this->log($full_cmd);
		fwrite($this->stream, $full_cmd);
		
		$responses = array();
		do {
			list($tag, $rest) = $this->get_response();
			$responses[] = $rest;
		} while( $tag == '*' );
		
		$status_response = array_pop($responses);
		list($status, $message) = explode(' ', $status_response, 2);
		$status = strtolower($status);
		
		if ( is_array($expected_status) ) {
			if ( !in_array($status, $expected_status) )
				throw new ImapException("Unexpected status code. Expected one of " . join(', ', $expected_status) . ", got $status: $message", $status, $message);
		} else {
			if ($status != $expected_status)
				throw new ImapException("Unexpected status code. Expected $expected_status, got $status: $message", $status, $message);
		}
		
		return $responses;
	}
	
	/**
	 * Reads one response from the socket connection. This can be a simple one line response
	 * or a response with a string literal in it (where we have to read a specific numer of bytes).
	 * Returns an array with the tag of the response and the rest of the response data.
	 */
	private function get_response(){
		$data = fgets($this->stream);
		if ($data === false)
			throw new ImapException("Failed to read response line from the IMAP connection");
		$this->log($data);
		
		list($tag, $rest) = explode(' ', $data, 2);
		while ( substr($rest, -3) == "}\r\n" ) {
			// As long as our line ends with an byte count read that number of
			// bytes and then the rest of the line
			$bytes_to_read = substr($rest, strrpos($rest, '{') + 1, -3);
			$data = stream_get_contents($this->stream, $bytes_to_read);
			if ($data === false)
				throw new ImapException("Failed to read response line from the IMAP connection");
			$this->log($data);
			$rest .= $data;
			
			$data = fgets($this->stream);
			if ($data === false)
				throw new ImapException("Failed to read response line from the IMAP connection");
			$this->log($data);
			$rest .= $data;
		}
		
		return array($tag, rtrim($rest, "\r\n"));
	}
	
	
	
	/**
	 * Informs the connection that you're working with a piece of sensitive data. While `$action`
	 * executes this data is removed from all logging that might is done by the connection. After
	 * `$action` has finished the connection completly forgets about the sensitive data.
	 * 
	 * The `$data` parameter can either be a string or an array of strings. In case of the array
	 * all values are removed from the log.
	 * 
	 * Use this for example when sending an `login` command. Otherwise the user credentials
	 * might be logged to a file if logging is enabled (see the `log_file` option of the constructor).
	 * 
	 * 	$user = 'someone';
	 * 	$pass = 'secret';
	 * 	$imap->with_sensitive_data(array($user, $pass), function() use($imap, $user, $pass) {
	 * 		$imap->command("login $user $pw");
	 * 	});
	 */
	function with_sensitive_data($data, $action){
		$this->sensitive_data = $data;
		$action();
		$this->sensitive_data = null;
	}
	
	/**
	 * Appends `$data` to the file specified in the constructors `log_file` option. If it was not
	 * set no logging is done. Sensitive data stored in `$this->sensitive_data` is removed from
	 * the log (replaced with '[removed]').
	 */
	private function log($data){
		if (!$this->log_file)
			return;
		if ($this->sensitive_data){
			if ( is_string($this->sensitive_data) )
				$data = str_replace($this->sensitive_data, '[removed]', $data);
			elseif ( is_array($this->sensitive_data) )
				foreach($this->sensitive_data as $piece)
					$data = str_replace($piece, '[removed]', $data);
		}
		file_put_contents($this->log_file, $data, FILE_APPEND);
	}
	
	
	
	/**
	 * Some responses of IMAP return "structured data", e.g. a string like this:
	 * 
	 * 	(BODYSTRUCTURE ("TEXT" "PLAIN" ("CHARSET" "ISO-8859-15") NIL NIL "8BIT" 375 16 NIL NIL NIL))
	 * 
	 * This function parses such a string and returns a nested PHP array. Note that the IMAP spec
	 * says that the atoms (all the unquoted words) are case insensitive. Therefore they are automatically
	 * converted to lower case. The `nil` atom is automatically converted to PHP's `null`. For the above
	 * "structured data" the following array is returned:
	 * 
	 * 	array( "bodystructure", array("TEXT", "PLAIN", array("CHARSET", "ISO-8859-15"), null, null, "8BIT", "375", "16", null, null, null) )
	 */
	static function parse_imap_struct($structure){
		$scan = new Scanner($structure);
		return self::parse_imap_struct_read($scan);
	}
	
	/**
	 * Same as `parse_imap_struct()` but uses `to_assoc_array()` directly afterwards. You can use
	 * this function if you know that the strctured data in `$structure` represents key-value pairs.
	 */
	static function parse_assoc_imap_struct($structure){
		return self::to_assoc_array( self::parse_imap_struct($structure) );
	}
	
	/**
	 * Converts an array of the structure `array($key1, $value1, $key2, $value2, …)` into an
	 * associative array: `array($key1 => $value1, $key2 => $value2, …)`. Only the outermost
	 * array is converted, any deeper nested arrays in the values are left untuched. The function
	 * does _not_ work recursively.
	 * 
	 * This is useful since some IMAP responses contain structured data with key-value pairs in
	 * the first (linear) format. With this function you can convert these structures into easier to
	 * handle associative arrays.
	 */
	static function to_assoc_array($linear_array){
		$assoc = array();
		for($i = 0; $i < count($linear_array); $i += 2)
			$assoc[strtolower($linear_array[$i])] = $linear_array[$i + 1];
		return $assoc;
	}
	
	/**
	 * Internal recursive parser function for IMAP's "structured data". Parses atoms, nils, double
	 * quoted strings, length quoted strings and nested lists.
	 */
	private static function parse_imap_struct_read($scan){
		$c = $scan->peek('"', '{', '(', function($t){ return ctype_digit($t); }, false);
		if ($c == '"') {
			$scan->one_of('"');
			list($value, ) = $scan->until('"');
			$scan->one_of('"');
			return $value;
		} elseif ($c == '{') {
			$scan->one_of('{');
			list($length, ) = $scan->until('}');
			$scan->one_of('}');
			$scan->one_of("\n", "\r\n");
			$data = $scan->bytes(intval($length));
			return $data;
		} elseif ($c == '(') {
			$scan->one_of('(');
			$list = array();
			$terminator = '';
			while(true) {
				list(, $terminator) = $scan->as_long_as(function($t){ return ctype_space($t); });
				if ($terminator == ')' or $terminator === null)
					break;
				$list[] = self::parse_imap_struct_read($scan);
			}
			$scan->one_of(')');
			return $list;
		} elseif ( ctype_digit($c) ) {
			list($number, ) = $scan->as_long_as(function($t){ return ctype_digit($t); });
			return $number;
		} else {
			list($atom, ) = $scan->until('(', ')', '{', ' ', function($t){ return ctype_cntrl($t); });
			$atom = strtolower($atom);
			if ( $atom == 'nil' )
				return null;
			return $atom;
		}
	}
	
	/**
	 * Helper function to easily search in an a parsed IMAP bodystruct response and get the matching
	 * section index for a part. This section index (e.g. "2.1" for the first mime subpart in the
	 * second mime part of the message) can then be used with the `fetch body[<section>]` IMAP
	 * command to fetch the corresponding message part.
	 * 
	 * The `$search_func` has to be an anonymous function. It is called for each part of the message.
	 * The first part where this function returns `true` is returned by `find_part_in_bodystruct`. This
	 * makes it easy e.g. to search for the first `text/plain` part of a message.
	 * 
	 * 	$part = ImapConnection::find_part_in_bodystruct($bodystructure, function($part){
	 * 		return ($part['type'] == 'text/plain');
	 * 	});
	 * 	$part['index'];  // => e.g. '1.1'

	 */
	static function find_part_in_bodystruct($bodystruct, $search_func, $tree_pos = array()){
		if ( is_array($bodystruct[0]) ) {
			// We've got a multipart, traverse into its subparts. Break as soon as we get
			// blank message info, we're only interested in the structure.
			foreach($bodystruct as $index => $elem){
				if ( is_array($elem) ) {
					$result = self::find_part_in_bodystruct($elem, $search_func, array_merge($tree_pos, array($index + 1)));
					if ($result !== null)
						return $result;
				} else {
					break;
				}
			}
		} else {
			// Normal message part
			@list($type, $subtype, $params, $id, $desc, $transfer_encoding, $size) = $bodystruct;
			$message = array(
				'index' => count($tree_pos) == 0 ? '1' : join('.', $tree_pos),
				'type' => strtolower($type) . '/' . strtolower($subtype), 'params' => ImapConnection::to_assoc_array($params),
				'id' => $id, 'desc' => $desc, 'transfer_encoding' => $transfer_encoding, 'size' => $size
			);
			if ( $search_func($message) )
				return $message;
		}
		
		return null;
	}
}

?>