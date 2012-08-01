<?php

const ROOT_PATH = '..';

// The SBar page contains stange HTML. Therefore load it with the DOM
// parser that can handle broken HTML.
$dom = new DOMDocument();
$dom->loadHTMLFile('http://www.s-bar.de/s-bar-hdm/speiseplan.html');
$xpath = new DOMXPath($dom);

// Right now there is no menu. Just a placeholder text. Fetch that text
// and strip non blocking spaces around it (they inserted them to provoke
// line breaks…).
$sbar_text = $xpath->evaluate('string(//div[@class="content"])');
// A Unicode aware trim. Required to get rid of stupid non-breaking spaces. Removes
// any leading and trailing characters with the separator and "other" (control, format, etc.)
// property. The docs state that this is slow, but well.
// See http://de.php.net/manual/en/regexp.reference.unicode.php
$sbar_text = preg_replace('/^[\pZ\pC]*|[\pZ\pC]*$/u', '', $sbar_text);

// Capture the output we want to write into the tile
ob_start();

?>
<article id="sbar" class="social changing" data-width="2" data-height="1">
	<h2><a href="http://www.s-bar.de/s-bar-hdm/speiseplan.html">S-Bar</a></h2>
	
	<p><?= $sbar_text ?></p>
</article>
<?php

// And store the captured output into the tile
file_put_contents(ROOT_PATH . '/tiles/12-sbar.php', ob_get_clean());

?>