<?php

/*

Useful resources:

- Extensible Messaging and Presence Protocol (XMPP) - Core: http://xmpp.org/rfcs/rfc6120.html
- Extensible Messaging and Presence Protocol (XMPP) - Instant Messaging and Presence: http://xmpp.org/rfcs/rfc6121.html
- XEP-0160 - Best Practices for Handling Offline Messages: http://xmpp.org/extensions/xep-0160.html

*/

require('scanner/scanner.php');

class XmppException extends Exception {}

class XmppConnection {
	public $stream;
	private $log_file = null;
	private $domain = null;
	private $sensitive_data = null;
	
	function __construct($uri, $timeout, $options = array()){
		if ( isset($options['log_file']) ){
			$this->log_file = $options['log_file'];
			unset($options['log_file']);
		}
		
		$stream_context = stream_context_create($options);
		$this->stream = stream_socket_client($uri, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $stream_context);
		
		if ($this->stream === false)
			throw new XmppException("Could not open XMPP socket connection: $errstr ($errno)");
		
		$this->domain = parse_url($uri, PHP_URL_HOST);
		$this->start_xml_stream();
	}
	
	function start_xml_stream(){
		$this->send("<?xml version='1.0' ?><stream:stream to='{$this->domain}' xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams' version='1.0'>");
		$xml_header = $this->receive();
		$stream_header = $this->receive(true);
		$stream_features = $this->receive();
		return array($stream_header, $stream_features);
	}
	
	function end_xml_stream(){
		$this->send("</stream:stream>");
		$stanzas = array();
		while( !feof($this->stream) )
			$stanzas[] = $this->receive();
		fclose($this->stream);
		// Remove the </stream:stream> closing tag
		array_pop($stanzas);
		return $stanzas;
	}
	
	function start_tls(){
		$this->send("<starttls xmlns='urn:ietf:params:xml:ns:xmpp-tls'/>");
		$proceed = $this->receive();
		$result = stream_socket_enable_crypto($this->stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
		if ($result)
			$this->start_xml_stream();
		return $result;
	}
	
	// Basic idea from http://stackoverflow.com/questions/1216427/xmpp-sasl-authentication-on-ejabberd-with-php
	// But there is an error there: \u0000 does not work in PHP strings, \0 has to be used instead
	// Official PLAIN auth RFC sample: http://tools.ietf.org/html/rfc4616#section-4
	function auth_plain($user, $password){
		$auth = base64_encode("$user@{$this->domain}\0$user\0$password");
		$xmpp = $this;
		$this->with_sensitive_data($auth, function() use($xmpp, $auth) {
			$xmpp->send("<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='PLAIN'>$auth</auth>");
		});
		$success = $this->receive();
		$this->start_xml_stream();
	}
	
	function send($stanza){
		$this->log($stanza . "\n");
		fwrite($this->stream, $stanza);
	}
	
	function receive($only_opening_tag = false){
		$scan = new Scanner($this->stream);
		$xmpp = $this;
		$xml_code = $scan->capture(function() use($xmpp, $scan, $only_opening_tag){
			$xmpp->scan_elem($scan, $only_opening_tag);
		});
		
		$this->log($xml_code . "\n");
		return $xml_code;
	}
	
	/**
	 * This function just consumes a complete XML element including its sub elements and
	 * end tag. It does not return them, just consumes the bytes from the scanner. This is
	 * meant to be used with scanner capturing so you get the exact string that is consumed
	 * by this function.
	 * 
	 * This function does not read over the end of an end tag. So it will not block a network
	 * connection longer than necessary.
	 */
	function scan_elem($scan, $only_scan_opening_tag = false){
		$spaces = function($t){ return ctype_space($t); };
		
		list(, $token) = $scan->until_and('<');
		$is_end_tag = ( $scan->one_of('/', false) == '/' );
		
		list($tag_name, $token) = $scan->until('>', '/', $spaces);
		if ($is_end_tag){
			$scan->one_of('>');
			return $tag_name;
		}
		
		// Parse attributes until we're at the end of the tag
		list(, $token) = $scan->as_long_as($spaces);
		while($token != '>' and $token != '/' and $token != '?'){
			list($attr_name, ) = $scan->until_and('=');
			$quote = $scan->one_of('"', "'");
			$attr_value = $scan->until_and($quote);
			list(, $token) = $scan->as_long_as($spaces);
		}
		
		// One tag element or processing instruction, scan the ending and return
		if ($token == '/' or $token == '?'){
			$scan->one_of($token);
			$scan->one_of('>');
			return null;
		}
		
		// Opening tag of an element
		$scan->one_of('>');
		
		if ($only_scan_opening_tag)
			return null;
		
		do {
			$end_tag = $this->scan_elem($scan);
		} while($end_tag != $tag_name);
		
		return null;
	}
	
	/**
	 * Informs the connection that you're working with a piece of sensitive data. While `$action`
	 * executes this data is removed from all logging that might is done by the connection. After
	 * `$action` has finished the connection completly forgets about the sensitive data.
	 * 
	 * The `$data` parameter can either be a string or an array of strings. In case of the array
	 * all values are removed from the log.
	 * 
	 * Use this for example when sending an `auth` stanza. Otherwise the user credentials
	 * might be logged to a file if logging is enabled (see the `log_file` option of the constructor).
	 * 
	 * 	$auth = base64_encode("$user@$domain\0$user\0$pass");
	 * 	$xmpp->with_sensitive_data($auth, function() use($imap, $auth) {
	 * 		$xmpp->command("<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='PLAIN'>$auth</auth>");
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
}

$xmpp = new XmppConnection('tcp://messi.mi.hdm-stuttgart.de:5222', 1, array(
	'log_file' => 'xmpp.log',
	'ssl' => array( 'verify_peer' => false )
));
$xmpp->start_tls();
list($user, $pass) = require('user_credentials.php');
$xmpp->auth_plain($user, $pass);


// Resource Binding - Success Case: http://xmpp.org/rfcs/rfc6120.html#rfc.section.7.6.1
$xmpp->send("<iq id='1' type='set'><bind xmlns='urn:ietf:params:xml:ns:xmpp-bind'><resource>sammelkrake</resource></bind></iq>");
$iq_bind_result = $xmpp->receive();

// Managing the Roster - Roster Get: http://xmpp.org/rfcs/rfc6121.html#rfc.section.2.1.3
$contacts = array();
$xmpp->send("<iq id='2' type='get'><query xmlns='jabber:iq:roster'/></iq>");
$iq_roster_result = $xmpp->receive();
$roster = simplexml_load_string($iq_roster_result);
printf("%s contacts\n", count($roster->query->item));
foreach($roster->query->item as $item){
	$contacts[(string)$item['jid']] = array('name' => $item['name'], 'show' => 'unknown', 'status' => null);
}


// Exchanging Presence Information: http://xmpp.org/rfcs/rfc6121.html#presence
// negative priority of prevents us from receiving any offline messages: http://xmpp.org/extensions/xep-0160.html#flow
$xmpp->send("<presence><priority>-1</priority></presence>");
$started = microtime(true);
$max_wait_time = 0.020;
$presence_stanzas = array();
do {
	list($read, $write, $except) = array(array($xmpp->stream), null, null);
	//echo("waiting on stream_select\n");
	$elapsed_time = microtime(true) - $started;
	$rest_wait_time = $max_wait_time - $elapsed_time;
	if ($rest_wait_time <= 0)
		break;
	$changed = stream_select($read, $write, $except, 0, $rest_wait_time * 1000000);
	if ($changed == 1)
		$presence_stanzas[] = $xmpp->receive();
} while ($changed == 1);

$xmpp->send("<presence type='unavailable' />");
$stanzas = $xmpp->end_xml_stream();
$presence_stanzas = array_merge($presence_stanzas, $stanzas);
printf("Received %d presences in %.3fs\n", count($presence_stanzas), microtime(true) - $started);

foreach($presence_stanzas as $stanza){
	$presence = simplexml_load_string($stanza);
	list($jid, ) = explode('/', $presence['from'], 2);
	if ( isset($contacts[$jid]) ){
		if ($presence['type'] == 'unavailable') {
			$contacts[$jid]['show'] = 'unavailable';
		} else {
			if ($presence->show)
				$contacts[$jid]['show'] = $presence->show;
			else
				$contacts[$jid]['show'] = 'available';
			
			if ($presence->status)
				$contacts[$jid]['status'] = $presence->status;
		}
	}
}

foreach($contacts as $jid => $contact){
	if ($contact['show'] == 'unavailable' or $contact['show'] == 'unknown')
		continue;
	
	echo("- {$contact['name']} ({$contact['show']}, $jid)\n");
	if ($contact['status'])
		echo("  {$contact['status']}\n");
}

?>