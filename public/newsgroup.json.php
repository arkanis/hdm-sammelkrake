<?php

const ROOT_PATH = '..';
require_once(ROOT_PATH . '/include/config.php');
require_once(ROOT_PATH . '/include/nntp_connection.php');
require_once(ROOT_PATH . '/include/mail_parser.php');
require_once(ROOT_PATH . '/include/markdown.php');

$user = $_SERVER['PHP_AUTH_USER'];
$pass = $_SERVER['PHP_AUTH_PW'];
$id = strtr($_GET['id'], array("\n" => ''));

// Mark the message as read and exit if the `mark_read` param is set
if( isset($_POST['mark_read']) ) {
	$context = stream_context_create(array(
		'ssl' => array('verify_peer' => false),
		'http' => array(
			'method' => 'POST',
			'user_agent' => 'HdM Sammelkrake/1.0',
			'header' => array(
				'Authorization: Basic ' . base64_encode("$user:$pass"),
				'Content-type: application/x-www-form-urlencoded'
			),
			'content' => http_build_query(array('id' => $id, 'group' => $_CONFIG['nntp']['groups']))
		)
	));
	file_get_contents('https://news.hdm-stuttgart.de/mark_read', false, $context);
	header('HTTP/1.1 204 No Content');
	exit(0);
}


$nntp = new NntpConnection($_CONFIG['nntp']['url'], $_CONFIG['nntp']['timeout'], $_CONFIG['nntp']['options']);
$nntp->authenticate($user, $pass);

// Storage area for message parser event handlers
$message_data = array(
	'subject' => null,
	'date' => null,
	'from' => null,
	'newsgroup' => null,
	'content' => null,
	'attachments' => array()
);

// Setup the parser. We need a newsgroup the message is posted in, the first text/plain part found and
// all attachments. The subject and author information is extracted from the overview information of the
// message tree later one.
$message_parser = MailParser::for_text_and_attachments($message_data);

// Fetch the article source
$nntp->command('article ' . $id, 220);
// Parse it. The parser event handlers store the message information in $message_data.
$nntp->get_text_response_per_line(array($message_parser, 'parse_line'));
$nntp->close();
$message_parser->end_of_message();

// Output the JSON data
header('Content-Type: application/json');

echo(json_encode(array(
	'date' => $message_data['date'],
	'subject' => $message_data['subject'],
	'from' => $message_data['from'],
	'body' => Markdown($message_data['content']),
	'attachments' => $message_data['attachments']
)));

?>