<?php

require('scanner/scanner.php');

function read_elem($scan){
	
	
	// scan for '<'
	// if followed by an '/'
		// we're an end tag, set end tag flag
	// scan tag name
	// if we're an end tag (end tag flag set)
		// scan '>'
		// return tag name
	// scan spaces
	// loop until token is '/' or '>'
		// scan spaces
		// scan attrb name and '='
		// scan value (quoted string)
	// if token is '/'
		// we're an one tag element, so there is no end tag comming
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
	if ($is_end_tag){
		$scan->one_of('>');
		return $tag_name;
	}
	
	// Parse attributes until we're at the end of the tag
	list(, $token) = $scan->as_long_as($spaces);
	while($token != '>' and $token != '/'){
		list($attr_name, ) = $scan->until_and('=');
		$quote = $scan->one_of('"', "'");
		$attr_value = $scan->until_and($quote);
		list(, $token) = $scan->as_long_as($spaces);
	}
	
	// One tag element, scan the ending and return
	if ($token == '/') {
		$scan->one_of('/');
		$scan->one_of('>');
		return null;
	}
	
	// Opening tag of an element
	$scan->one_of('>');
	
	do {
		$end_tag = read_elem($scan);
	} while($end_tag == $tag_name);
	
	return null;
}

$fd = fopen('test.xml', 'rb');
$scan = new Scanner($fd);
$code = $scan->capture(function() use($scan) {
	read_elem($scan);
});
var_dump($code);
fclose($fd);

?>