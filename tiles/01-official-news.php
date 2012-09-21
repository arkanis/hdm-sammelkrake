<?php

require_once(ROOT_PATH . '/include/imap_connection.php');
require_once(ROOT_PATH . '/include/nntp_connection.php');
require_once(ROOT_PATH . '/include/mail_parser.php');
require_once(ROOT_PATH . '/include/config.php');

$user = $_SERVER['PHP_AUTH_USER'];
$pass = $_SERVER['PHP_AUTH_PW'];
$messages = array();
$imap_messages = 0;
$nntp_messages = 0;

$imap = new ImapConnection($_CONFIG['imap']['url'], $_CONFIG['imap']['timeout'], $_CONFIG['imap']['options']);

// TODO: properly escape/encode string fields (most importantly the password)
$imap->with_sensitive_data($pass, function() use($imap, $user, $pass) {
	$imap->command(sprintf('login %s "%s"', $user, str_replace('"', '\"', $pass)));
});
$imap->command('select inbox');

list($search_resp) = $imap->command('search unseen');
$numbers = explode(' ', $search_resp);
array_shift($numbers); // throw away the "SEARCH"

$resps = $imap->command('fetch ' . join(',', $numbers) . ' envelope');
$imap->close();

//echo("Unread messages:\n");
foreach($resps as $resp){
	list($number, $fetch, $data) = explode(' ', $resp, 3);
	$structure = ImapConnection::parse_assoc_imap_struct($data);
	// Envelope structure from http://tools.ietf.org/html/rfc3501#page-85
	list($date, $subject, $from, $sender, $reply_to, $to, $cc, $bcc, $in_reply_to, $message_id) = $structure['envelope'];
	//printf("- %s: %s from %s at %s\n", $number, MailParser::decode_words($subject), MailParser::decode_words($from[0][0]), date('Y-m-d G:i', strtotime($date)));
	$messages[] = array(
		'date' => strtotime($date),
		'subject' => MailParser::decode_words($subject),
		'from' => MailParser::decode_words($from[0][0]),
		'imap_message_num' => $number
	);
}
$imap_messages = count($messages);


$nntp = new NntpConnection($_CONFIG['nntp']['url'], $_CONFIG['nntp']['timeout'], $_CONFIG['nntp']['options']);
$nntp->authenticate($user, $pass);

$start_date = date('Ymd His', time() - 60*60*24*7);
$nntp->command('newnews hdm.mi.*-offiziell ' . $start_date, 230);
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
$nntp_messages = count($messages) - $imap_messages;



// Sort message ids by date
uasort($messages, function($a, $b){
	if ($a['date'] == $b['date'])
		return 0;
	return ($a['date'] > $b['date']) ? -1 : 1;
});

?>
<article id="official-news" class="official changing" data-width="2" data-height="1">
	<h2>Wichtige Meldungen</h2>
	<ul>
<?	foreach($messages as $message): ?>
<?		if ( isset($message['imap_message_num']) ): ?>
		<li><a href="imap/<?= urlencode($message['imap_message_num']) ?>" title="<?= ha($message['subject']) ?>, am <?= date('d.m. G:i', $message['date']) ?> Uhr von <?= ha($message['from']) ?>"><?= h($message['subject']) ?></a></li>
<?		else: ?>
		<li><a href="newsgroup/<?= urlencode($message['nntp_message_id']) ?>" title="<?= ha($message['subject']) ?>, am <?= date('d.m. G:i', $message['date']) ?> Uhr von <?= ha($message['from']) ?>"><?= h($message['subject']) ?></a></li>
<?		endif ?>
<?	endforeach ?>
		<li><a href="#" title="Geplanter Vortrag von Herrn Kampe fällt aus, am 18.6 15:33 Uhr von Prof. Walter Kriha">Geplanter Vortrag von Herrn Kampe fällt aus</a></li>
	</ul>
	<ul>
		<li><a href="https://mail.hdm-stuttgart.de/" title="<?= ha($imap_messages) ?> ungelesene Nachrichten">HdM Mails <span class="count"><?= h($imap_messages) ?></span></a></li>
		<li><a href="https://news.hdm-stuttgart.de/" title="<?= ha($nntp_messages) ?> ungelesene Nachrichten">Newsgroups <span class="count"><?= h($nntp_messages) ?></span></a></li>
		<li><a href="https://www.hdm-stuttgart.de/studienangebot/pers_stundenplan/meldungen/" title="2 Meldungen seit letzter Vorlesung">Persönlicher Stundenplan <span class="count">2</span></a></li>
	</ul>
</article>