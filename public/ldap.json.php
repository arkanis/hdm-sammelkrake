<?php

function exit_with_error($status_code, $message){
	header('Content-Type: application/json', true, $status_code);
	echo(json_encode(array('message' => $message)));
	exit();
}

const ROOT_PATH = '..';
require_once(ROOT_PATH . '/include/config.php');


$user = $_SERVER['PHP_AUTH_USER'];
$pass = $_SERVER['PHP_AUTH_PW'];
// Remove braches from the search term to avoid users from breaking out of the LDAP filter
$search_term = isset($_GET['search']) ? strtr(trim($_GET['search']), array('(' => '', ')' => '')) : null;

if ($search_term === null)
	exit_with_error(422, 'No search term specified');


$con = ldap_connect($_CONFIG['ldap']['host']);
$dn = sprintf($_CONFIG['ldap']['dn'], $user);
ldap_bind($con, $dn, $pass);

// Useful fields of LDAP users
// 	sn		surname
// 	cn		full name
// 	uid		short form of username, e.g. xy123
// 	mail		full mail address
// 	homedirectory
// Individual filter components:
// 	(uid=$name)		Search for a uid match (e.g. xy123)
// 	(cn=*$last_name)	Matches full names ending in $last_name
//	(cn=$first_name*)	Matches full names starting with $first_name
// We take those filters and combine them depending on the number of words
// (name parts) the user entered.
$term_parts = preg_split('/\s+/', $search_term, 2);
if ( count($term_parts) == 1 ) {
	// For one word look for a matching uid or a full name starting or ending with the word
	$filter = "(|(uid=$search_term)(cn=$search_term*)(cn=*$search_term))";
} else {
	// For two words look for a name starting with the first part and ending with the second part
	list($first_name, $last_name) = $term_parts;
	$filter = "(&(cn=$first_name*)(cn=*$last_name))";
}

// Do the search thing
$match_res = ldap_search($con, 'ou=userlist,dc=hdm-stuttgart,dc=de', $filter);
$data = ldap_get_entries($con, $match_res);
ldap_unbind($con);

$users = array();
for($i = 0; $i < $data['count']; $i++){
	// Do not use middle names for the display name. Only first and last name.
	// Some corner cases make this more complex. We have to handle titles before
	// the first name and last names with mutliple "prefixes" (lower case words
	// before them).
	// Examples: Dr. Jens-Uwe Hahn, Guenter van der Kamp
	$name_parts = preg_split('/\s+/', $data[$i]['cn'][0]);
	
	$first_name = array();
	while( ($part = array_shift($name_parts)) !== null ){
		// We either have a title or the first name, so append it
		$first_name[] = $part;
		// If we get the first name (first word not ending with a dot) abort the loop
		if ( !preg_match('/\.$/', $part) )
			break;
	}
	
	$last_name = array();
	// Take the last name (last part) for sure
	array_unshift($last_name, array_pop($name_parts));
	while ( ($part = array_pop($name_parts)) !== null ){
		// If a word starts with lower case it's probably a prefix, add it to the last
		// name. If not it's the middle name. We don't want it, so break the loop.
		if ( preg_match('/^[[:lower:]]/', $part) )
			array_unshift($last_name, $part);
		else
			break;
	}
	
	$users[] = array(
		'id' => $data[$i]['uid'][0],
		'name' => join(' ', $first_name) . ' ' . join(' ', $last_name),
		'full_name' => $data[$i]['cn'][0],
		'mail' => strtolower($data[$i]['mail'][0])
	);
}

// Sort them alphabetical by name. In case we get the same name (e.g. bachelor and master
// student) reverse sort by the id (show the newer id first).
usort($users, function($a, $b){
	$name_comp = strcasecmp($a['name'], $b['name']);
	return ($name_comp != 0) ? $name_comp : strcasecmp($a['id'], $b['id']) * -1;
});

// Output the JSON data
header('Content-Type: application/json');

echo(json_encode($users));

?>