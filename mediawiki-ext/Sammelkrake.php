<?php

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => "Sammelkrake",
	'description' => "Updates components of the Sammelkrake when articles are changed in the wiki",
	'version' => 1,
	'author' => "Stephan Soller"
);

$wgHooks['ArticleSaveComplete'][] = 'sammelkrakeOnArticleSaveComplete';
$wgSammelkrakeCategory = 'Sammelkrake';
$wgSammelkrakeTileDir = '/tmp/sammelkrake/tiles';
$wgSammelkrakeTileSuffix = '.wiki.php';

function sammelkrakeOnArticleSaveComplete(&$article, &$user, $text, $summary, $minoredit,
	$watchthis, $sectionanchor, &$flags, $revision, &$status, $baseRevId)
{
	global $wgSammelkrakeCategory, $wgSammelkrakeTileDir, $wgSammelkrakeTileSuffix;
	
	// Only process pages that are in our category
	$category_name = reset(array_keys($article->getTitle()->getParentCategories()));
	if ($category_name != "Category:$wgSammelkrakeCategory")
		return true;
	
	// Render the page
	$parser_options = $article->makeParserOptions('canonical');
	$parser_output = $article->getParserOutput($parser_options);
	$xhtml = $parser_output->getText();
	
	// Split it into elements and iterate over them
	$doc = DOMDocument::loadHTML($xhtml);
	$xpath = new DOMXPath($doc);
	// Get all elements in the body. There is no actual body element but the loading of HTML code
	// seems to sanitize it into a normal HTML structure.
	$elems = $xpath->evaluate('/html/body/*');
	$tiles = array();
	for($i = 0; $i < $elems->length; $i++){
		$elem = $elems->item($i);
		if ($elem->nodeType != XML_ELEMENT_NODE)
			continue;
		
		if ($elem->tagName == 'h2') {
			// We found a heading, extract
			// The HTML code of all following elements is added to the content until we find the next heading
			$heading_content_node = $xpath->query('span[2]', $elem)->item(0);
			$name = trim($xpath->evaluate('string(.)', $heading_content_node));
			$sanitized_name = preg_replace('/[^\wäöüß]/', '-', strtolower($name));
			
			if ( $xpath->evaluate('count(span) > 0', $heading_content_node) ) {
				$attr_container = $xpath->query('span', $heading_content_node)->item(0);
				if (!$attr_container->hasAttribute('id'))
					$attr_container->setAttribute('id', $sanitized_name);
				
				// Convert the attribute container element (span) to HTML and transform it into an article
				// start tag. Easier than doing it via the DOM API...
				$container_html = $doc->saveHTML($attr_container);
				$container_html = preg_replace('/^\<span/', '<article', $container_html);
				$container_html = preg_replace('/\<\/span\>$/', '', $container_html);
				
				// Remove the span element from the heading so we don't get it in the title HTML snippet
				$heading_content_node->removeChild($attr_container);
			} else {
				$container_html = '<article id="' . $sanitized_name . '" data-width="2" data-height="1">';
			}
			
			$title = $doc->saveHTML($heading_content_node);
			$tiles[] = array('name' => $name, 'title' => $title, 'start_tag' => $container_html, 'content' => '');
		} else if ( count($tiles) > 0 ) {
			// Put the HTML of all following elements into the latest tiles content
			$tiles[count($tiles)-1]['content'] .= $doc->saveHTML($elem);
		}
	}
	
	foreach($tiles as $index => $tile){
		$html = $tile['start_tag'] .
			'<h2>' . utf8_decode($tile['title']) . '</h2>' .
			utf8_decode($tile['content']) .
		'</article>';
		$filename = sprintf('%s/%02d-%s%s', $wgSammelkrakeTileDir, $index + 1, $sanitized_name, $wgSammelkrakeTileSuffix);
		file_put_contents($filename, $html);
	}
	
	return true;
}

?>