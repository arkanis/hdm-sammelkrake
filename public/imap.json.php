<?php

function exit_with_error($status_code, $message){
	header('Content-Type: application/json', true, $status_code);
	echo(json_encode(array('message' => $message)));
	exit();
}

const ROOT_PATH = '..';

require_once(ROOT_PATH . '/include/imap_connection.php');
require_once(ROOT_PATH . '/include/mail_parser.php');

$user = $_SERVER['PHP_AUTH_USER'];
$pass = $_SERVER['PHP_AUTH_PW'];
$num = isset($_GET['num']) ? intval($_GET['num']) : null;

if ($num === null)
	exit_with_error(422, 'No message number specified');


$imap = new ImapConnection('tls://mail.hdm-stuttgart.de:993', 1, array(
	'log_file' => ROOT_PATH . '/logs/imap.log',
	'ssl' => array( 'verify_peer' => false )
));

// TODO: properly escape/encode string fields (most importantly the password)
$imap->with_sensitive_data($pass, function() use($imap, $user, $pass) {
	$imap->command(sprintf('login %s "%s"', $user, str_replace('"', '\"', $pass)));
});
$imap->command('select inbox');


$resps = $imap->command("fetch $num bodystructure");
//print_r($resps);
list(, , $data) = explode(' ', $resps[0], 3);
$struct = ImapConnection::parse_assoc_imap_struct($data);
print_r($struct);

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