<?php

require_once(ROOT_PATH . '/include/config.php');
require_once(ROOT_PATH . '/include/imap_connection.php');
require_once(ROOT_PATH . '/include/nntp_connection.php');
require_once(ROOT_PATH . '/include/mail_parser.php');

$user = $_SERVER['PHP_AUTH_USER'];
$pass = $_SERVER['PHP_AUTH_PW'];
$messages = array();
$imap_messages = 0;
$nntp_messages = 0;

//
// First load the latest newsgroup messages fetched by the cron job
//
$serialized_data = file_get_contents($_CONFIG['nntp']['prefetch']['cache_file']);
$messages = $serialized_data ? unserialize($serialized_data) : array();
$nntp_messages = count($messages);


//
// Now fetch the IMAP messages
//
$imap = new ImapConnection($_CONFIG['imap']['url'], $_CONFIG['imap']['timeout'], $_CONFIG['imap']['options']);

// TODO: properly escape/encode string fields (most importantly the password)
$imap->with_sensitive_data($pass, function() use($imap, $user, $pass) {
	$imap->command(sprintf('login %s "%s"', $user, str_replace('"', '\"', $pass)));
});
$imap->command('select inbox');

list($search_resp) = $imap->command('search unseen');
$numbers = explode(' ', $search_resp);
array_shift($numbers); // throw away the "SEARCH" in the response

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
$imap_messages = count($messages) - $nntp_messages;


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
		<li><a href="mail/<?= urlencode($message['imap_message_num']) ?>" title="<?= ha($message['subject']) ?>, am <?= date('d.m. G:i', $message['date']) ?> Uhr von <?= ha($message['from']) ?>"><?= h($message['subject']) ?></a></li>
<?		else: ?>
		<li><a href="newsgroup/<?= urlencode($message['nntp_message_id']) ?>" title="<?= ha($message['subject']) ?>, am <?= date('d.m. G:i', $message['date']) ?> Uhr von <?= ha($message['from']) ?>"><?= h($message['subject']) ?></a></li>
<?		endif ?>
<?	endforeach ?>
		<li><a href="#" title="Geplanter Vortrag von Herrn Kampe fällt aus, am 18.6 15:33 Uhr von Prof. Walter Kriha">Geplanter Vortrag von Herrn Kampe fällt aus</a></li>
	</ul>
	<ul>
		<li><a href="https://mail.hdm-stuttgart.de/" title="<?= ha($imap_messages) ?> ungelesene Nachrichten">HdM Mails <span class="count"><?= h($imap_messages) ?></span></a></li>
		<li><a href="https://news.hdm-stuttgart.de/" title="<?= ha($nntp_messages) ?> ungelesene Nachrichten">Newsgroups <span class="count"><?= h($nntp_messages) ?></span></a></li>
		<li><a href="https://www.hdm-stuttgart.de/studenten/stundenplan/pers_stundenplan/stundenplanfunktionen/meldungen" title="2 Meldungen seit letzter Vorlesung">Persönlicher Stundenplan <span class="count">2</span></a></li>
	</ul>
	<script>
		$(document).ready(function(){
			$('#official-news > ul:first-of-type > li > a').click(function(){
				$.ajax($(this).attr('href') + '.json', {dataType: 'json'}).done(function(data){
					var d = new Date(data.date * 1000);
					$('#official-news > article.template').clone().removeClass('template')
						.find('h2').text(data.subject).end()
						.find('p.details').text('Von ' + data.from + ' am ' + d.getDate() + '.' + d.getMonth() + '. ' + d.getHours() + ':' + d.getMinutes() + ' Uhr').end()
						.find('div').html(data.body).end()
						.replaceAll('#details > article');
					
					console.log(data);
					$('#details').removeClass('inactive');
				});
				
				return false;
			});
			
			$('#details').click(function(e){
				if (this === e.target)
					$(this).addClass('inactive');
			});
			$('#details > article > p:last-child > a').live('click', function(){
				$('#details').addClass('inactive');
				return false;
			});
		});
	</script>
	<article class="template">
		<h2></h2>
		<p class="details">Von Stefan Radicke am 20.09. 11:58 Uhr</p>
		<div>
			<p>Er hörte leise Schritte hinter sich. Das bedeutete nichts Gutes. Wer würde ihm schon folgen, spät in der Nacht und dazu noch in dieser engen Gasse mitten im übel beleumundeten Hafenviertel? Gerade jetzt, wo er das Ding seines Lebens gedreht hatte und mit der Beute verschwinden wollte! Hatte einer seiner zahllosen Kollegen dieselbe Idee gehabt, ihn beobachtet und abgewartet, um ihn nun um die Früchte seiner Arbeit zu erleichtern?</p>
			<p>Oder gehörten die Schritte hinter ihm zu einem der unzähligen Gesetzeshüter dieser Stadt, und die stählerne Acht um seine Handgelenke würde gleich zuschnappen?</p>
			<p>Er konnte die Aufforderung stehen zu bleiben schon hören. Gehetzt sah er sich um. Plötzlich erblickte er den schmalen Durchgang.</p>
		</div>
		<p class="actions">
			<a href="#">fertig gelesen</a>
		</p>
	</article>
</article>