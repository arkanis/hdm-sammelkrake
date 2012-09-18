<?php

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => "Sammelkrake",
	'description' => "Updates components of the Sammelkrake when articles are changed in the wiki",
	//'descriptionmsg' => "", // Same as above but name of a message, for i18n - string, added in 1.12.0
	'version' => 1,
	'author' => "Stephan Soller"//,
	//'url' => "", // URL of extension (usually instructions) - string
);

$wgHooks['ArticleSaveComplete'][] = 'sammelkrakeOnArticleSaveComplete';


function sammelkrakeOnArticleSaveComplete(&$article, &$user, $text, $summary, $minoredit,
	$watchthis, $sectionanchor, &$flags, $revision, &$status, $baseRevId)
{
	// Only process pages that are in our category
	$category_name = reset(array_keys($article->getTitle()->getParentCategories()));
	if ($category_name != 'Category:Sammelkrake')
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
		
		if ($elem->tagName == 'h2'){
			// We found a heading, now collect the HTML code of all elements until we find the next heading
			$title = trim($xpath->evaluate('string(span[2])', $elem));
			$tiles[] = array('title' => $title, 'content' => '');
		} else if ( count($tiles) > 0 ) {
			// Put all following elements into the latest tiles content
			$tiles[count($tiles)-1]['content'] .= $doc->saveHTML($elem);
		}
	}
	
	
	
	exit(0);
	return true;
}

?>