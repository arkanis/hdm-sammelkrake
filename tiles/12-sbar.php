<?php

$dom = new DOMDocument();
$dom->loadHTMLFile('http://www.s-bar.de/s-bar-hdm/speiseplan.html');
//$dom->loadHTMLFile('sbar-speiseplan.html');
$xpath = new DOMXPath($dom);
$sbar_text = $xpath->evaluate('string(//div[@class="content"])');
$sbar_text = preg_replace('/^\pZ*|\pZ*$/u', '', $sbar_text);

?>
<article id="sbar" class="social changing" data-width="2" data-height="1">
	<h2><a href="http://www.s-bar.de/s-bar-hdm/speiseplan.html">S-Bar</a></h2>
	
	<p><?= $sbar_text ?></p>
</article>