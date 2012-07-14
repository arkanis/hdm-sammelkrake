<?php

/*

Useful resources:

- Extensible Messaging and Presence Protocol (XMPP) - Core: http://xmpp.org/rfcs/rfc6120.html
- Extensible Messaging and Presence Protocol (XMPP) - Instant Messaging and Presence: http://xmpp.org/rfcs/rfc6121.html
- XEP-0160 - Best Practices for Handling Offline Messages: http://xmpp.org/extensions/xep-0160.html

*/

require('scanner/scanner.php');

function read_stanza($fd, $only_opening_tag = false){
	$scan = new Scanner($fd);
	$xml_code = $scan->capture(function() use($scan, $only_opening_tag){
		read_elem($scan, $only_opening_tag);
	});
	
	//echo("\n" . $xml_code . "\n");
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
function read_elem($scan, $only_read_opening_tag = false){
	$spaces = function($t){ return ctype_space($t); };
	
	$scan->until_and('<');
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
	
	if ($only_read_opening_tag)
		return null;
	
	do {
		$end_tag = read_elem($scan);
	} while($end_tag != $tag_name);
	
	return null;
}


$ssl_context = stream_context_create(array(
	'ssl' => array( 'verify_peer' => false )
));
$con = stream_socket_client('tcp://messi.mi.hdm-stuttgart.de:5222', $errno, $errstr, 1, STREAM_CLIENT_CONNECT, $ssl_context);

function send($data){
	global $con;
	echo($data . "\n");
	fwrite($con, $data);
}

function recv(){
	global $con;
	echo(fread($con, 4096*10) . "\n");
}


// Init stream
send("<?xml version='1.0' ?><stream:stream to='messi.mi.hdm-stuttgart.de' xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams' version='1.0'>");
$xml_header = read_stanza($con);
$stream_header = read_stanza($con, true);
$stream_features = read_stanza($con);
//recv();

send("<starttls xmlns='urn:ietf:params:xml:ns:xmpp-tls'/>");
$proceed = read_stanza($con);
//recv();

var_dump( stream_socket_enable_crypto($con, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) );
// Restart stream since it is in a new state (an a new stream with a new server side id…)
send("<?xml version='1.0' ?><stream:stream to='messi.mi.hdm-stuttgart.de' xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams' version='1.0'>");
$xml_header = read_stanza($con);
$stream_header = read_stanza($con, true);
$stream_features = read_stanza($con);
//recv();

// Basic idea from http://stackoverflow.com/questions/1216427/xmpp-sasl-authentication-on-ejabberd-with-php
// But there is an error there: \u0000 does not work in PHP strings, \0 has to be used instead
// Official PLAIN auth RFC sample: http://tools.ietf.org/html/rfc4616#section-4
list($user, $pass) = require('user_credentials.php');
$domain = 'messi.mi.hdm-stuttgart.de';
$auth = base64_encode("$user@$domain\0$user\0$pass");
send("<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='PLAIN'>$auth</auth>");
$success = read_stanza($con);
//recv();
// Restart stream again
send("<?xml version='1.0' ?><stream:stream to='messi.mi.hdm-stuttgart.de' xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams' version='1.0'>");
$xml_header = read_stanza($con);
$stream_header = read_stanza($con, true);
$stream_features = read_stanza($con);
//recv();

// Resource Binding - Success Case: http://xmpp.org/rfcs/rfc6120.html#rfc.section.7.6.1
send("<iq id='1' type='set'><bind xmlns='urn:ietf:params:xml:ns:xmpp-bind'><resource>sammelkrake</resource></bind></iq>");
$iq_bind_result = read_stanza($con);
//recv();

// Managing the Roster - Roster Get: http://xmpp.org/rfcs/rfc6121.html#rfc.section.2.1.3
send("<iq id='2' type='get'><query xmlns='jabber:iq:roster'/></iq>");
$iq_roster_result = read_stanza($con);
$roster = simplexml_load_string($iq_roster_result);
$contacts = count($roster->query->item);
echo("$contacts contacts:\n");
foreach($roster->query->item as $item)
	printf("- {$item['name']} ({$item['jid']})\n");
//recv();

// Exchanging Presence Information: http://xmpp.org/rfcs/rfc6121.html#presence
// negative priority of prevents us from receiving any offline messages: http://xmpp.org/extensions/xep-0160.html#flow
send("<presence><priority>-1</priority></presence>");
$ms_waited = 0;
$started = microtime(true);
$max_wait_time = 0.200;
$pres_count = 0;
do {
	list($read, $write, $except) = array(array($con), null, null);
	//echo("waiting on stream_select\n");
	$elapsed_time = microtime(true) - $started;
	$rest_wait_time = $max_wait_time - $elapsed_time;
	if ($rest_wait_time <= 0)
		break;
	$changed = stream_select($read, $write, $except, 0, $rest_wait_time * 1000000);
	if ($changed == 1){
		//echo("recving\n");
		$presence = read_stanza($con);
		echo("- $presence\n");
		$pres_count++;
		//recv();
	}
} while ($changed == 1);
printf("Received $pres_count presences in %.3fs\n", microtime(true) - $started);

send("<presence type='unavailable' />");

send("</stream:stream>");
echo( stream_get_contents($con) . "\n" );

?>