<?php

define('ROOT_PATH', dirname(__FILE__) . '/..');

require_once(ROOT_PATH . '/include/nntp_connection.php');
require_once(ROOT_PATH . '/include/mail_parser.php');
require_once(ROOT_PATH . '/include/config.php');


$nntp = new NntpConnection($_CONFIG['nntp']['url'], $_CONFIG['nntp']['timeout'], $_CONFIG['nntp']['options']);
$nntp->authenticate($_CONFIG['nntp']['prefetch']['user'], $_CONFIG['nntp']['prefetch']['password']);

$start_date = date('Ymd His', time() - $_CONFIG['nntp']['max_age']);
$nntp->command('newnews ' . join(',', $_CONFIG['nntp']['groups']) . ' ' . $start_date, 230);
$new_message_ids = $nntp->get_text_response();

// Query the dates of all new messages
foreach(explode("\n", $new_message_ids) as $id){
	$nntp->command('hdr subject ' . $id, 225);
	list(,$subject) = explode(' ', $nntp->get_text_response(), 2);
	$nntp->command('hdr date ' . $id, 225);
	list(,$date) = explode(' ', $nntp->get_text_response(), 2);
	$nntp->command('hdr from ' . $id, 225);
	list(,$from) = explode(' ', $nntp->get_text_response(), 2);
	
	$messages[] = array(
		'date' => MailParser::parse_date($date),
		'subject' => MailParser::decode_words($subject),
		'from' => reset(MailParser::split_from_header(MailParser::decode_words($from))),
		'nntp_message_id' => $id
	);
}
$nntp->close();

file_put_contents($_CONFIG['nntp']['prefetch']['cache_file'], serialize($messages));

?>
