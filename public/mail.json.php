<?php

function exit_with_error($status_code, $message){
	header('Content-Type: application/json', true, $status_code);
	echo(json_encode(array('message' => $message)));
	exit();
}

const ROOT_PATH = '..';
require_once(ROOT_PATH . '/include/config.php');
require_once(ROOT_PATH . '/include/imap_connection.php');
require_once(ROOT_PATH . '/include/mail_parser.php');
require_once(ROOT_PATH . '/include/markdown.php');

$user = $_SERVER['PHP_AUTH_USER'];
$pass = $_SERVER['PHP_AUTH_PW'];
$num = isset($_GET['id']) ? intval($_GET['id']) : null;

if ($num === null)
	exit_with_error(422, 'No message number specified');


//
// Connect to the mail server, login and select the inbox
//
$imap = new ImapConnection($_CONFIG['imap']['url'], $_CONFIG['imap']['timeout'], $_CONFIG['imap']['options']);

// TODO: properly escape/encode string fields (most importantly the password)
$imap->with_sensitive_data($pass, function() use($imap, $user, $pass) {
	$imap->command(sprintf('login %s "%s"', $user, str_replace('"', '\"', $pass)));
});
$imap->command('select inbox');


// Mark the message as read (seen) and exit if the `mark_read` param is set
if( isset($_POST['mark_read']) ) {
	$resps = $imap->command("store $num +flags (\\Seen)");
	$imap->close();
	header('HTTP/1.1 204 No Content');
	exit(0);
}


//
// Fetch the envelop to get the header information (subject, etc.)
//
$resps = $imap->command("fetch $num envelope");
list($number, $fetch, $data) = explode(' ', $resps[0], 3);
$structure = ImapConnection::parse_assoc_imap_struct($data);
// Envelope structure from http://tools.ietf.org/html/rfc3501#page-85
list($date, $subject, $from, $sender, $reply_to, $to, $cc, $bcc, $in_reply_to, $message_id) = $structure['envelope'];
//printf("- %s: %s from %s at %s\n", $number, MailParser::decode_words($subject), MailParser::decode_words($from[0][0]), date('Y-m-d G:i', strtotime($date)));


//
// Fetch the structure and search the frist body part of text/plain type
//
$resps = $imap->command("fetch $num bodystructure");
//print_r($resps);
list(, , $data) = explode(' ', $resps[0], 3);
$struct = ImapConnection::parse_assoc_imap_struct($data);
//print_r($struct);

//$part = ImapConnection::find_part_in_bodystruct($struct['bodystructure'], function($msg){
//	printf("- %s: %s\n", $msg['index'], $msg['type']);
//});

$part = ImapConnection::find_part_in_bodystruct($struct['bodystructure'], function($msg){
	return ($msg['type'] == 'text/plain');
});


//
// Fetch the body part, decode and convert it to UTF-8
//
$resps = $imap->command("fetch $num body.peek[" . $part['index'] . "]");
//print_r($resps);
list(, , $data) = explode(' ', $resps[0], 3);
$struct = ImapConnection::parse_assoc_imap_struct($data);
$text = reset($struct);
//var_dump($part['transfer_encoding'], $part['params']['charset']);

switch( strtolower($part['transfer_encoding']) ){
	case 'quoted-printable':
		$text = quoted_printable_decode($text);
		break;
	case 'base64':
		$text = base64_decode($text);
		break;
}

if ( isset($part['params']['charset']) )
	$text = iconv($part['params']['charset'], 'UTF-8', $text);


//
// Close the IMAP connection and return the JSON data
//
$imap->close();
echo(json_encode(array(
	'date' => strtotime($date),
	'subject' => MailParser::decode_words($subject),
	'from' => MailParser::decode_words($from[0][0]),
	'body' => Markdown($text),
	'attachments' => array()
)));

?>