<?php

require('scanner/scanner.php');

function read_elem($scan, $only_read_opening_tag = false){
	
	// scan for '<'
	// if followed by an '/'
		// we're an end tag, set end tag flag
	// scan tag name
	// if we're an end tag (end tag flag set)
		// scan '>'
		// return tag name
	// scan spaces
	// loop until token is '/' or '>' or '?'
		// scan spaces
		// scan attrb name and '='
		// scan value (quoted string)
	// if token is '/' or '?
		// we're an one tag element or processing instruction (e.g. XML header), so there is no end tag comming
		// scan '/' or '?'
		// scan '>'
		// return
	// if token is '>'
		// we're an opening tag
		// scan further elements recursively until we find a matching end tag
		// return
	
	$spaces = function($t){ return ctype_space($t); };
	
	$scan->until_and('<');
	$is_end_tag = ( $scan->one_of('/', false) == '/' );
	
	list($tag_name, $token) = $scan->until('>', '/', $spaces);
	echo("NAME: $tag_name\n");
	if ($is_end_tag){
		$scan->one_of('>');
		return $tag_name;
	}
	
	// Parse attributes until we're at the end of the tag
	list(, $token) = $scan->as_long_as($spaces);
	while($token != '>' and $token != '/' and $token != '?'){
		list($attr_name, ) = $scan->until_and('=');
		$quote = $scan->one_of('"', "'");
		$attr_value = $scan->until_and($quote);
		list(, $token) = $scan->as_long_as($spaces);
	}
	
	// One tag element or processing instruction, scan the ending and return
	if ($token == '/' or $token == '?'){
		$scan->one_of($token);
		$scan->one_of('>');
		return null;
	}
	
	// Opening tag of an element
	$scan->one_of('>');
	
	if ($only_read_opening_tag)
		return null;
	
	do {
		$end_tag = read_elem($scan);
	} while($end_tag != $tag_name);
	
	return null;
}

$fd = fopen('test.xml', 'rb');
$scan = new Scanner($fd);

$code = $scan->capture(function() use($scan) {
	read_elem($scan);
});
var_dump($code);

$code = $scan->capture(function() use($scan) {
	read_elem($scan, true);
});
var_dump($code);

$code = $scan->capture(function() use($scan) {
	read_elem($scan);
});
var_dump($code);

fclose($fd);

?>