<?php

$dom = new DOMDocument();
//$dom->loadHTMLFile('http://www.s-bar.de/s-bar-hdm/speiseplan.html');
$dom->loadHTMLFile('sbar-speiseplan.html');
$xpath = new DOMXPath($dom);
$sbar_text = $xpath->evaluate('string(//div[@class="content"])');
// A Unicode aware trim. Required to get rid of stupid non-breaking spaces. Removes
// any leading and trailing characters with the separator property. The docs state that
// this is slow, but well. See http://de.php.net/manual/en/regexp.reference.unicode.php
$sbar_text = preg_replace('/^\pZ*|\pZ*$/u', '', $sbar_text);


//$rss = simplexml_load_file('http://www.studentenwerk-stuttgart.de/speiseangebot_rss');
$rss = simplexml_load_file('mensa-rss.xml');
$item = $rss->channel->item[0];

$doc = new DOMDocument();
// The loadHTML() function does not use utf-8 by default and encodings set on
// the document are ignored. The xml encoding gives loadHTML() the correct
// encoding. Source: http://de.php.net/manual/en/domdocument.loadhtml.php#95251
@$doc->loadHTML('<?xml encoding="UTF-8">' . $item->description);
$xpath = new DOMXPath($doc);

$names = $xpath->query('//tbody/tr/td[not(@class)]');
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>S-Bar &amp; Mensa</title>
</head>
<body>

<h1>S-Bar</h1>

<p><?= $sbar_text ?></p>

<h1>Mensa</h1>

<h2><a href="<?= $item->link ?>"><?= $item->title ?></a></h2>
<ul>
<? foreach($names as $name): ?>
	<li><?= trim( $xpath->evaluate('string(.)', $name) ) ?></li>
<? endforeach ?>
</ul>

</body>
</html>