<?php

require_once(ROOT_PATH . '/include/config.php');

$user = $_SERVER['PHP_AUTH_USER'];
$pass = $_SERVER['PHP_AUTH_PW'];

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
$html_source = file_get_contents($_CONFIG['schedule']['url'], false, $context);
$doc = @DOMDocument::loadHTML($html_source);
$xpath = new DOMXPath($doc);
$schedule_node = $xpath->query("//div[@id='center_content']/div/table")->item(0);

$blocks = array();
$schedule = simplexml_import_dom($schedule_node);

for($row_idx = 1; $row_idx < count($schedule->tr); $row_idx++){
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
				'room' => trim($table->tr->td, "() \t\n")
			);
		}
	}
	
	$blocks[] = $block;
}

?>
<article id="schedule" class="official changing" data-width="4" data-height="2">
	<h2><a href="<?= ha($_CONFIG['schedule']['url']) ?>">Persönlicher Stundenplan für diese Woche</a></h2>
	<table>
		<tr>
			<th></th>
			<th>Montag</th>
			<th>Dienstag</th>
			<th>Mittwoch</th>
			<th>Donnerstag</th>
			<th>Freitag</th>
			<th>Samstag</th>
		</tr>
<?		foreach($blocks as $block): ?>
		<tr>
			<th><?= reset(explode(' ', $block['time'])) ?></th>
<?			for($i = 1; $i <= 6; $i++): ?>
<?				$lectures = $block[$i] ?>
<?				if ( count($lectures) == 1 ): ?>
<?# class "changed" to highlight moved lectures ?>
				<td>
<?				foreach($lectures as $lecture): ?>
					<a href="<?= $_CONFIG['schedule']['url'] . '/' . $lecture['url'] ?>" title="<?= $lecture['name'] ?>"><?= $lecture['name'] ?></a>
					<small>in <?= $lecture['room'] ?></small>
<?				endforeach ?>
				</td>
<?				elseif ( count($lectures) > 1 ): ?>
				<td class="conflict">
<?				foreach($lectures as $lecture): ?>
					<a href="<?= $_CONFIG['schedule']['url'] . '/' . $lecture['url'] ?>" title="<?= $lecture['name'] ?> in <?= $lecture['room'] ?>"><?= $lecture['room'] ?>: <?= $lecture['name'] ?></a>
<?				endforeach ?>
				</td>
<?				else: ?>
				<td></td>
<?				endif ?>
<?			endfor ?>
		</tr>
<?		endforeach ?>
	</table>
</article>