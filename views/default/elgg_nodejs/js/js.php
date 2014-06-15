
elgg.provide('elgg.nodejs');

var socket; // make socket global var.


/**
 * Helper: return a json of my info
 * @return {[type]} [description]
 */
elgg.nodejs.me = function() {
	var user = elgg.get_logged_in_user_entity();
	return {
		guid: user.guid,
		name: user.name,
		username: user.username,
		avatar: user.avatar
	};
};


elgg.nodejs.init = function() {

	// Don't open socket.io connection for non-logged-in user.
	if (!elgg.is_logged_in()) return;

	require(['socket.io-client'], function(io) {
		var user = elgg.get_logged_in_user_entity();

		// Send user data at websocket connection.
		socket = io('http://' + location.hostname + ':7890', {query: {
			user: JSON.stringify(elgg.nodejs.me())
		}});

		// Fired on websocket connect. There is no data to send.
		socket.on('connect', function(){
			elgg.trigger_hook('nodejs', 'connect', null, true);
		});

		// Fired on reconnection. data is reconnection attempt number.
		socket.on('reconnect', function(data){
			elgg.trigger_hook('nodejs', 'reconnect', data, true);
		});

		// Filed when reconnection failed. No data.
		socket.on('reconnect_failed', function(){
			elgg.trigger_hook('nodejs', 'reconnect_failed', null, true);
		});

		// Fired on disconnect. No data.
		socket.on('disconnect', function(){
			elgg.trigger_hook('nodejs', 'disconnect', null, true);
		});

		// Fired upon a connection error parameters.
		socket.on('error', function(err) {
			console.log('io error: ' + err);
		});


		/* Elgg_nodejs customs events */

		// Sended by server after connect, when socket is connected and server ready to send data.
		socket.on('connected', function(data) {
			elgg.trigger_hook('nodejs', 'connected', data, true);
		});

		// Sended by server when new user is connected.
		socket.on('add_online_user', function(data) {
			elgg.trigger_hook('nodejs', 'add_online_user', data, true);
		});

		// Sended by server when an user leave site.
		socket.on('remove_online_user', function(data) {
			elgg.trigger_hook('nodejs', 'remove_online_user', data, true);
		});

		socket.on('message', function(data) {
			elgg.trigger_hook('nodejs', 'message:all', data, true);
			if (data.type) {
				elgg.trigger_hook('nodejs', 'message:'+data.type, data, true);
			}
		});

		socket.on('join_room', function(data) {
			elgg.trigger_hook('nodejs', 'join_room', data, true);
		});


		socket.on('getRooms', function(data) {
			console.log('rooms:', data);
		});

	}, function(error) { // error on load socket.io
		console.log(error);
		elgg.trigger_hook('nodejs', 'cannot_load', error, true);
	});

};
elgg.register_hook_handler('init', 'system', elgg.nodejs.init);









elgg.nodejs.system_message = function(hook, type, params, value) {
	elgg.system_message(params.message);
};
elgg.register_hook_handler('nodejs', 'message:system_message', elgg.nodejs.system_message);




elgg.nodejs.getRooms = function() {
	socket.emit('getRooms');
};


/**
 * Event when client lose connection with socket.io, or has been disconneted.
 * @return {boolean}        true to continue execute this hook handlers type.
 */
elgg.nodejs.disconnect = function(hook, type, params, value) {
	//elgg.system_message(elgg.echo('nodejs:disconnect'));
	return value;
};
elgg.register_hook_handler('nodejs', 'disconnect', elgg.nodejs.disconnect);



/**
 * Event when client cannot load socket.io
 * @return {boolean}        true to continue execute this hook handlers type.
 */
elgg.nodejs.cannot_load = function(hook, type, params, value) {
	//elgg.system_message(elgg.echo('nodejs:cannot_load', [params.message]));
	return value;
};
elgg.register_hook_handler('nodejs', 'cannot_load', elgg.nodejs.cannot_load);



/*
elgg.nodejs.ggouv_history = function() {
	if (!elgg.isUndefined(socket)) {
		console.log('iiii');
		var data = {
			user: elgg.get_logged_in_user_entity().name,
			page: window.document.location.pathname
		}
		socket.emit('location', data);
	}
};
elgg.register_hook_handler('ggouv_history', 'success', elgg.nodejs.ggouv_history);*/


