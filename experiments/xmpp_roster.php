<?php

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
recv();

send("<starttls xmlns='urn:ietf:params:xml:ns:xmpp-tls'/>");
recv();
var_dump( stream_socket_enable_crypto($con, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) );
// Restart stream since it is in a new state (an a new stream with a new server side id…)
send("<?xml version='1.0' ?><stream:stream to='messi.mi.hdm-stuttgart.de' xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams' version='1.0'>");
recv();

// Basic idea from http://stackoverflow.com/questions/1216427/xmpp-sasl-authentication-on-ejabberd-with-php
// But there is an error there: \u0000 does not work in PHP strings, \0 has to be used instead
// Official PLAIN auth RFC sample: http://tools.ietf.org/html/rfc4616#section-4
list($user, $pass) = require('user_credentials.php');
$domain = 'messi.mi.hdm-stuttgart.de';
$auth = base64_encode("$user@$domain\0$user\0$pass");
send("<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='PLAIN'>$auth</auth>");
recv();
// Restart stream again
send("<?xml version='1.0' ?><stream:stream to='messi.mi.hdm-stuttgart.de' xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams' version='1.0'>");
recv();

// Resource Binding - Success Case: http://xmpp.org/rfcs/rfc6120.html#rfc.section.7.6.1
send("<iq id='1' type='set'><bind xmlns='urn:ietf:params:xml:ns:xmpp-bind'><resource>sammelkrake</resource></bind></iq>");
recv();

// Managing the Roster - Roster Get: http://xmpp.org/rfcs/rfc6121.html#rfc.section.2.1.3
send("<iq id='2' type='get'><query xmlns='jabber:iq:roster'/></iq>");
recv();

// Exchanging Presence Information: http://xmpp.org/rfcs/rfc6121.html#presence
// negative priority of prevents us from receiving any offline messages: http://xmpp.org/extensions/xep-0160.html#flow
send("<presence><priority>-1</priority></presence>");
do {
	list($read, $write, $except) = array(array($con), null, null);
	echo("waiting on stream_select\n");
	$changed = stream_select($read, $write, $except, 0, 200*1000);
	if ($changed == 1){
		echo("recving\n");
		recv();
	}
} while ($changed == 1);

send("<presence type='unavailable' />");

send("</stream:stream>");
echo( stream_get_contents($con) . "\n" );

?>