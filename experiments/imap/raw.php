<?php

/**
 * This file uses a raw connection to the IMAP server. The responses are parsed
 * directly with the scanner.
 */

require('../scanner/scanner.php');

$ssl_context = stream_context_create(array(
	'ssl' => array( 'verify_peer' => false )
));
$con = stream_socket_client('tls://mail.hdm-stuttgart.de:993', $errno, $errstr, 1, STREAM_CLIENT_CONNECT, $ssl_context);
$scan = new Scanner($con);

list($line, $end) = $scan->until("\r\n");
$scan->one_of("\r\n");
echo("S: $line\n");

$user = 'you';
$pass = 'your_password';
$cmd = "a001 login $user $pass\r\n";
echo("C: $cmd");
fwrite($con, $cmd);

list($line, $end) = $scan->until("\r\n");
$scan->one_of("\r\n");
echo("S: $line\n");

$cmd = "a002 select inbox\r\n";
echo("C: $cmd");
fwrite($con, $cmd);

do {
	list($line, $end) = $scan->until("\r\n");
	$scan->one_of("\r\n");
	echo("S: $line\n");
} while( substr($line, 0, 1) == '*' );

$cmd = "a003 search unseen\r\n";
echo("C: $cmd");
fwrite($con, $cmd);

do {
	list($line, $end) = $scan->until("\r\n");
	$scan->one_of("\r\n");
	echo("S: $line\n");
} while( substr($line, 0, 1) == '*' );

$cmd = "a004 logout\r\n";
echo("C: $cmd");
fwrite($con, $cmd);

do {
	list($line, $end) = $scan->until("\r\n");
	$scan->one_of("\r\n");
	echo("S: $line\n");
} while( substr($line, 0, 1) == '*' );

fclose($con);

?>