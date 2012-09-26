<?php

list($user, $pass) = require('user_credentials.php');

$context = stream_context_create(array(
	'http' => array(
		'header' => array(
			'Accept: text/html',
			'Authorization: Basic ' . base64_encode("$user:$pass")
		),
		'user_agent' => 'HdM Sammelkrake/1.0'
	),
	'ssl' => array(
		'verify_peer' => false
	)
));
$html_source = file_get_contents('https://www.hdm-stuttgart.de/studenten/stundenplan/pers_stundenplan/stundenplanfunktionen/wochenansicht', false, $context);
$doc = @DOMDocument::loadHTML($html_source);
$xpath = new DOMXPath($doc);
$schedule_node = $xpath->query("//div[@id='center_content']/div/table")->item(0);

$blocks = array();
$schedule = simplexml_import_dom($schedule_node);

for($row_idx = 0; $row_idx < count($schedule->tr); $row_idx++){
	$tr = $schedule->tr[$row_idx];
	$block = array(
		'time' => (string) $tr->td[0]
	);
	
	for($col_idx = 1; $col_idx < count($tr->td); $col_idx++){
		$td = $tr->td[$col_idx];
		$block[$col_idx] = array();
		foreach($td->table as $table){
			$lecture_link = $table->tr->td->a[0];
			$block[$col_idx][] = array(
				'name' => (string) $lecture_link,
				'url' => (string) $lecture_link['href'],
				'room' => trim($td->table->tr->td, "() \t\n")
			);
		}
	}
	
	$blocks[] = $block;
	
	var_dump($block);
}

?>