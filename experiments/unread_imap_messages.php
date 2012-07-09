<?php

require('imap/imap_connection.php');
require('message_parser.php');


$user = 'you';
$pass = 'secret';


$imap = new ImapConnection('tls://mail.hdm-stuttgart.de:993', 1, array(
	'log_file' => 'imap.log',
	'ssl' => array( 'verify_peer' => false )
));

$imap->with_sensitive_data($pass, function() use($imap, $user, $pass) {
	$imap->command("login $user \"$pass\"");
});
$imap->command('select inbox');

list($search_resp) = $imap->command('search unseen');
$numbers = explode(' ', $search_resp);
array_shift($numbers); // throw away the "SEARCH"

$resps = $imap->command('fetch ' . join(',', $numbers) . ' envelope');
//print_r($resps);
echo("Unread messages:\n");
foreach($resps as $resp){
	list($number, $fetch, $data) = explode(' ', $resp, 3);
	$structure = ImapConnection::parse_assoc_imap_struct($data);
	// Envelope structure from http://tools.ietf.org/html/rfc3501#page-85
	list($date, $subject, $from, $sender, $reply_to, $to, $cc, $bcc, $in_reply_to, $message_id) = $structure['envelope'];
	printf("- %s: %s from %s at %s\n", $number, MessageParser::decode_words($subject), MessageParser::decode_words($from[0][0]), date('Y-m-d G:i', strtotime($date)));
}

$num = $numbers[0];
echo("\nMessage $num (structure and first text part):\n");

$resps = $imap->command("fetch $num bodystructure");
//print_r($resps);
list(, , $data) = explode(' ', $resps[0], 3);
$struct = ImapConnection::parse_assoc_imap_struct($data);

$part = ImapConnection::find_part_in_bodystruct($struct['bodystructure'], function($msg){
	printf("- %s: %s\n", $msg['index'], $msg['type']);
});

$part = ImapConnection::find_part_in_bodystruct($struct['bodystructure'], function($msg){
	return ($msg['type'] == 'text/plain');
});

//print_r($struct['bodystructure']);

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

echo("\n" . $text);

$imap->close();

?>