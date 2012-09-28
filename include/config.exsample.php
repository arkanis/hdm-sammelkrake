<?php

$_CONFIG = array(
	'nntp' => array(
		'url' => 'tls://news.hdm-stuttgart.de:563',
		'timeout' => 1,
		'options' => array(
			'log_file' => ROOT_PATH . '/logs/nntp.log',
			'ssl' => array( 'verify_peer' => false )
		),
		// The cron job that fetches the latest news posts (same list for all users)
		'prefetch' => array(
			'user' => '...',
			'password' => '...',
			'cache_file' => ROOT_PATH . '/cache/nntp_newest_messages'
		),
		
		// The groups messages will be shown from. Can't be a wildmat since the unread tracker
		// also has to update the tracker data for each group.
		'groups' => array('hdm.mi.mib-offiziell', 'hdm.mi.mmb-offiziell', 'hdm.mi.csm-offiziell'),
		// Max age of the messages in seconds. Older messages are not shown.
		'max_age' => 60*60*24*7  // 7 days
	),
	
	'imap' => array(
		'url' => 'tls://mail.hdm-stuttgart.de:993',
		'timeout' => 1,
		'options' => array(
			'log_file' => ROOT_PATH . '/logs/imap.log',
			'ssl' => array( 'verify_peer' => false )
		)
	),
	
	'messi' => array(
		'url' => 'tcp://messi.mi.hdm-stuttgart.de:5222',
		'timeout' => 1,
		'options' => array(
			'log_file' => ROOT_PATH . '/logs/xmpp.log',
			'ssl' => array( 'verify_peer' => false )
		)
	),
	
	'schedule' => array(
		'url' => 'https://www.hdm-stuttgart.de/studenten/stundenplan/pers_stundenplan/stundenplanfunktionen/wochenansicht',
	)
);

?>