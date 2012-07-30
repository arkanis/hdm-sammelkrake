<?php

$rss = simplexml_load_file('http://www.studentenwerk-stuttgart.de/speiseangebot_rss');
//$rss = simplexml_load_file('mensa-rss.xml');
$item = $rss->channel->item[0];

$doc = new DOMDocument();
// The loadHTML() function does not use utf-8 by default and encodings set on
// the document are ignored. The xml encoding gives loadHTML() the correct
// encoding. Source: http://de.php.net/manual/en/domdocument.loadhtml.php#95251
@$doc->loadHTML('<?xml encoding="UTF-8">' . $item->description);
$xpath = new DOMXPath($doc);

$names = $xpath->query('//tbody/tr/td[not(@class)]');
?>
<article id="mensa" class="social changing" data-width="2" data-height="1">
	<h2>Mensa <a href="<?= $item->link ?>"><?= $item->title ?></a></h2>
	
	<ul>
	<? foreach($names as $name): ?>
		<li><?= trim( $xpath->evaluate('string(.)', $name) ) ?></li>
	<? endforeach ?>
	</ul>
</article>