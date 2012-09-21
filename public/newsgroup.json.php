<?php

const ROOT_PATH = '..';
require_once(ROOT_PATH . '/include/nntp_connection.php');
require_once(ROOT_PATH . '/include/mail_parser.php');

$user = $_SERVER['PHP_AUTH_USER'];
$pass = $_SERVER['PHP_AUTH_PW'];
$id = strtr($_GET['id'], array("\n" => ''));

$nntp = new NntpConnection('tls://news.hdm-stuttgart.de:563', 1, array(
	'ssl' => array( 'verify_peer' => false )
));
$nntp->authenticate($user, $pass);

$nntp->command('article ' . $id, 220);
$message = $nntp->get_text_response();

/*
header('Content-Type: application/json', true, 500);
exit();
*/

// Output the JSON data
header('Content-Type: application/json');
/*
echo(json_encode(array(
	'subject' => '',
	'body' => '',
	'attachments' => array()
)));
*/

?>