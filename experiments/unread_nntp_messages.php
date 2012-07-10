<?php

include('nntp_connection.php');
include('message_parser.php');

$nntp = new NntpConnection('tls://news.hdm-stuttgart.de:563', 1, array(
	'ssl' => array( 'verify_peer' => false )
));

list($user, $pass) = require('user_credentials.php');
$nntp->authenticate($user, $pass);

$start_date = date('Ymd His', time() - 60*60*24*7);
$nntp->command('newnews hdm.mi.*-offiziell ' . $start_date, 230);
$new_message_ids = $nntp->get_text_response();

// Query the dates of all new messages
$messages = array();
foreach(explode("\n", $new_message_ids) as $id){
	$nntp->command('hdr subject ' . $id, 225);
	list(,$subject) = explode(' ', $nntp->get_text_response(), 2);
	$nntp->command('hdr date ' . $id, 225);
	list(,$date) = explode(' ', $nntp->get_text_response(), 2);
	$nntp->command('hdr from ' . $id, 225);
	list(,$from) = explode(' ', $nntp->get_text_response(), 2);
	
	$messages[$id] = array(
		'subject' => MessageParser::decode_words($subject),
		'date' => MessageParser::parse_date($date),
		'from' => MessageParser::decode_words($from)
	);
}

// Sort message ids by date and limit the number to the configured feed limit
uasort($messages, function($a, $b){
	if ($a['date'] == $b['date'])
		return 0;
	return ($a['date'] > $b['date']) ? -1 : 1;
});
//$message_dates = array_slice($message_dates, 0, $feed_config['limit']);

echo("Official messages of the last 7 days:\n");
foreach($messages as $id => $message)
	printf("- %s: %s from %s at %s\n", $id, $message['subject'], $message['from'], date('Y-m-d G:i', $message['date']));


$id = reset(array_keys($messages));
echo("\nMessage $id (first text part):\n");

// Storage area for message parser event handlers
$message_data = array(
	'newsgroup' => null,
	'content' => null,
	'attachments' => array()
);

// Setup the parser. We need a newsgroup the message is posted in, the first text/plain part found and
// all attachments. The subject and author information is extracted from the overview information of the
// message tree later one.
$message_parser = MessageParser::for_text_and_attachments($message_data);


// Fetch the article source
$nntp->command('article ' . $id, 220);
// Parse it. The parser event handlers store the message information in $message_data.
$nntp->get_text_response_per_line(array($message_parser, 'parse_line'));
$message_parser->end_of_message();

echo("Newsgroup: " . $message_data['newsgroup'] . "\n");
echo($message_data['content']);
echo("Attachments:\n");
var_dump($message_data['attachments']);

$nntp->close();


?>