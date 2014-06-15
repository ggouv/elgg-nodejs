<?php
/**
 * elgg-nodejs plugin
 * Provide tools for interaction with node.js (socket.io, easyrtc) and elgg
 *
 * @package elgg-nodejs
 */

elgg_register_event_handler('init', 'system', 'elgg_nodejs');


function elgg_nodejs() {

	// Extend js view
	elgg_extend_view('js/elgg', 'elgg_nodejs/js/tools');
	elgg_extend_view('js/elgg', 'elgg_nodejs/js/js');

	// Register external javascript for require.js
	elgg_define_js('socket.io-client', array(
		'src' => $_SERVER['HTTP_HOST'] . ':7890/socket.io/socket.io'
	));
	elgg_define_js('easyrtc', array(
		'src' => $_SERVER['HTTP_HOST'] . ':7890/easyrtc/easyrtc',
		'deps' => array('socket.io')
	));
	elgg_register_js('handlebars', '/mod/elgg-nodejs/vendors/handlebars-v1.3.0');
	elgg_load_js('handlebars');

	elgg_register_plugin_hook_handler('to:object', 'entity', 'elgg_nodejs_user_to_object');

}


function elgg_nodejs_user_to_object($hook, $type, $return, $params) {
	if ($params['entity'] instanceof ElggUser) {
		$return->avatar = array(
			'tiny' => $params['entity']->getIconURL('tiny'),
			'small' => $params['entity']->getIconURL('small')
		);
	}
	return $return;
}


/**
 * Get all connected clients of socket.io
 * @return [array]   All connected clients
 */
function elgg_nodejs_get_clients() {
	$url="http://localhost:7890/api/clients";

	//  Initiate curl
	$ch = curl_init();
	// Set the url
	curl_setopt($ch, CURLOPT_URL, $url);
	// Disable SSL verification
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	// Will return the response, if false it print the response
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// Execute
	$result = curl_exec($ch);

	return json_decode($result, true);
}



/**
 * Send data to all clients
 * @param  [type]             $data      Data to send
 * @return [integer]          Number of clients reached
 */
function elgg_nodejs_broadcast($data) {
	$url="http://localhost:7890/api/clients";

	$query = array(
		'data' => $data
	);

	//  Initiate curl
	$ch = curl_init();
	// Set the url
	curl_setopt($ch, CURLOPT_URL, $url);
	// Disable SSL verification
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	// Will return the response, if false it print the response
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// Add query
	curl_setopt($ch,CURLOPT_POST, count($query));
	curl_setopt($ch,CURLOPT_POSTFIELDS, http_build_query($query));
	// Execute
	$result = curl_exec($ch);

	return json_decode($result, true);
}



/**
 * Send data to specifics clients
 * @param  [string|array]     $guids     ElggGUID of users we want to send data
 * @param  [type]             $data      Data to send
 * @return [integer]          Number of clients reached
 */
function elgg_nodejs_post_clients($guids, $data) {
	$url="http://localhost:7890/api/clients";

	if (!is_array($guids)) $guids = array($guids);

	$query = array(
		'guids' => $guids,
		'data' => $data
	);

	//  Initiate curl
	$ch = curl_init();
	// Set the url
	curl_setopt($ch, CURLOPT_URL, $url);
	// Disable SSL verification
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	// Will return the response, if false it print the response
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// Add query
	curl_setopt($ch,CURLOPT_POST, count($query));
	curl_setopt($ch,CURLOPT_POSTFIELDS, http_build_query($query));
	// Execute
	$result = curl_exec($ch);

	return json_decode($result, true);
}



/**
 * Get all rooms of socket.io
 * @return [array]   All rooms
 */
function elgg_nodejs_get_rooms() {
	$url="http://localhost:7890/api/rooms";

	//  Initiate curl
	$ch = curl_init();
	// Set the url
	curl_setopt($ch, CURLOPT_URL, $url);
	// Disable SSL verification
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	// Will return the response, if false it print the response
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// Execute
	$result = curl_exec($ch);

	return json_decode($result, true);
}



/**
 * Send data to sockets rooms
 * @param  [string|array]     $names     Name of the rooms we want to send data
 * @param  [type]             $data      Data to send
 * @return [integer]          Number of rooms reached
 */
function elgg_nodejs_post_rooms($names, $data) {
	$url="http://localhost:7890/api/rooms";

	if (!is_array($names)) $names = array($names);

	$query = array(
		'names' => $names,
		'data' => $data
	);

	//  Initiate curl
	$ch = curl_init();
	// Set the url
	curl_setopt($ch, CURLOPT_URL, $url);
	// Disable SSL verification
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	// Will return the response, if false it print the response
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// Add query
	curl_setopt($ch,CURLOPT_POST, count($query));
	curl_setopt($ch,CURLOPT_POSTFIELDS, http_build_query($query));
	// Execute
	$result = curl_exec($ch);

	return json_decode($result, true);
}