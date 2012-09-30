<?php

require_once(dirname(__FILE__) . '/../include/view_helpers.php');

// The SBar page contains stange HTML. Therefore load it with the DOM
// parser that can handle broken HTML.
$dom = new DOMDocument();
$dom->loadHTMLFile('http://www.s-bar.de/s-bar-hdm/speiseplan.html');
$xpath = new DOMXPath($dom);

/*
// Right now there is no menu. Just a placeholder text. Fetch that text
// and strip non blocking spaces around it (they inserted them to provoke
// line breaks…).
$sbar_text = $xpath->evaluate('string(//div[@class="content"])');
// A Unicode aware trim. Required to get rid of stupid non-breaking spaces. Removes
// any leading and trailing characters with the separator and "other" (control, format, etc.)
// property. The docs state that this is slow, but well.
// See http://de.php.net/manual/en/regexp.reference.unicode.php
$sbar_text = preg_replace('/^[\pZ\pC]*|[\pZ\pC]*$/u', '', $sbar_text);
*/

$week_plan = array();
$para = $xpath->query('//div[@class="content"]//p')->item(0);
$para->normalize();
foreach($para->childNodes as $node){
	if ($node->nodeType == XML_ELEMENT_NODE and $node->tagName == 'strong') {
		//echo("elem " . $node->tagName . ": " . $node->nodeValue . "\n");
		$week_plan[trim($node->nodeValue)] = array();
	} elseif ($node->nodeType == XML_TEXT_NODE) {
		//echo("text: " . $node->nodeValue . "\n");
		$latest_day = end(array_keys($week_plan));
		$week_plan[$latest_day][] = trim($node->nodeValue);
	}
}

// Capture the output we want to write into the tile
ob_start();

?>
<article id="sbar" class="misc changing" data-width="2" data-height="1">
	<h2><a href="http://www.s-bar.de/s-bar-hdm/speiseplan.html">S-Bar Wochenkarte</a></h2>
	
	<ul>
<?		foreach($week_plan as $day => $dishes): ?>
		<li>
			<span><?= h($day) ?></span>
			<ul>
<?				foreach($dishes as $dish): ?>
				<li title="<?= ha($dish) ?>"><?= h($dish) ?></li>
<?				endforeach ?>
			</ul>
		</li>
<?		endforeach ?>
	</ul>
</article>
<?php

// And store the captured output into the tile
file_put_contents(dirname(__FILE__) . '/../tiles/12-sbar.php', ob_get_clean());

?>