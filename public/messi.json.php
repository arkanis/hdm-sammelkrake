<?php

const ROOT_PATH = '..';
require_once(ROOT_PATH . '/include/config.php');
require_once(ROOT_PATH . '/include/xmpp_connection.php');

try {

// First of connect to the XMPP server, start an encrypted channel and authenticate
// TODO: error handling
$xmpp = new XmppConnection($_CONFIG['messi']['url'], $_CONFIG['messi']['timeout'], $_CONFIG['messi']['options']);
$xmpp->start_tls();
$xmpp->auth_plain($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);


// Bind this connection to a resource, see Resource Binding - Success Case: http://xmpp.org/rfcs/rfc6120.html#rfc.section.7.6.1
// TODO: error handling
$xmpp->send("<iq id='1' type='set'><bind xmlns='urn:ietf:params:xml:ns:xmpp-bind'><resource>sammelkrake</resource></bind></iq>");
$iq_bind_result = $xmpp->receive();

// Get the users contacts, see Managing the Roster - Roster Get: http://xmpp.org/rfcs/rfc6121.html#rfc.section.2.1.3
// TODO: error handling
$contacts = array();
$groups = array();
$xmpp->send("<iq id='2' type='get'><query xmlns='jabber:iq:roster'/></iq>");
$roster = simplexml_load_string($xmpp->receive());
foreach($roster->query->item as $item){
	$jid = (string)$item['jid'];
	$contacts[$jid] = array('name' => (string)$item['name'], 'status' => 'unknown', 'message' => null);
	
	foreach($item->group as $group){
		$group_name = (string) $group;
		if ( ! isset($groups[$group_name]) )
			$groups[$group_name] = array();
		$groups[$group_name][] = $jid;
	}
}

// Get the status of as many contacts as possible in the time set by `$max_wait_time?.
// See Exchanging Presence Information: http://xmpp.org/rfcs/rfc6121.html#presence
// Negative priority of prevents us from receiving any offline messages: http://xmpp.org/extensions/xep-0160.html#flow
$xmpp->send("<presence><priority>-1</priority></presence>");
$started = microtime(true);
$max_wait_time = 0.200;
$presence_stanzas = array();
do {
	list($read, $write, $except) = array(array($xmpp->stream), null, null);
	$elapsed_time = microtime(true) - $started;
	$rest_wait_time = $max_wait_time - $elapsed_time;
	if ($rest_wait_time <= 0)
		break;
	$changed = stream_select($read, $write, $except, 0, $rest_wait_time * 1000000);
	if ($changed == 1)
		$presence_stanzas[] = $xmpp->receive();
} while ($changed == 1);

//printf("Received %d presences in %.3fs\n", count($presence_stanzas), microtime(true) - $started);
$xmpp->send("<presence type='unavailable' />");
$stanzas = $xmpp->end_xml_stream();
$presence_stanzas = array_merge($presence_stanzas, $stanzas);
//printf("Received %d presences in %.3fs\n", count($presence_stanzas), microtime(true) - $started);

// Process prsence stanzas and update the contact list with the data
foreach($presence_stanzas as $stanza){
	$presence = simplexml_load_string($stanza);
	list($jid, ) = explode('/', $presence['from'], 2);
	if ( isset($contacts[$jid]) ){
		if ($presence['type'] == 'unavailable') {
			$contacts[$jid]['status'] = 'unavailable';
		} else {
			if ($presence->show)
				$contacts[$jid]['status'] = (string)$presence->show;
			else
				$contacts[$jid]['status'] = 'available';
			
			if ($presence->status)
				$contacts[$jid]['message'] = (string)$presence->status;
		}
	}
}

} catch(Exception $e) {
	header('Content-Type: application/json', true, 500);
	exit();
}

// Output the JSON data
header('Content-Type: application/json');
echo(json_encode(array(
	'contacts' => $contacts,
	'groups' => $groups
)));

?>