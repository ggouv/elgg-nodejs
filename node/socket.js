

module.exports = function (io) {

	// Middleware. Prevent unknow connexion.
	io.use(function(socket, next){
		if (socket.handshake.query.user) return next();
		next(new Error('Authentication error'));
	});

	/**
	 * Called when socket connect for the first time and socket.io ask for authorization token.
	 */
	io.sockets.on('connect', function(socket) {

		/*
		 * Event at first connect
		 */
		var user = JSON.parse(socket.handshake.query.user),
			sessionCookie = getElggSession(socket);

		// delete timeout if exist (prevent disconnect message)
		clearTimeout(timeouts[sessionCookie]);

		// Check if user is already online ? (a user can open more than one tab, so we check for guid and session cookie)
		if (!Users[sessionCookie]) {
			// emit to all sockets except sender
			socket.broadcast.emit('add_online_user', user);
			// add new user to Users
			Users[sessionCookie] = new User(user, sessionCookie);
		}
		Users[sessionCookie].sockets[socket.id] = socket; // add socket to User

		// If there is rooms, that's mean it's a reconnection. Reconnect this socket to all rooms
		_.each(Users[sessionCookie].rooms, function(room) {
			_.invoke(Users[sessionCookie].sockets, 'join', room); // join room for all sockets of this user
			_.invoke(Users[sessionCookie].sockets, 'emit', 'join_room', Rooms[room]); // send data of this room
			if (!_.contains(Users[sessionCookie].rooms, room)) Users[sessionCookie].rooms.push(room);
		});

		// free memory
		delete user;
		delete sessionCookie;


		/*
		 * Event when socket is disconnected
		 */
		socket.on('disconnect', function () {
			var sessionCookie = getElggSession(socket);

			if (Users[sessionCookie]) {
				delete Users[sessionCookie].sockets[socket.id];
			}

			timeouts[sessionCookie] = setTimeout(function() {
				if (Users[sessionCookie] && _.size(Users[sessionCookie].sockets) == 0) { // no socket ? -> all tabs/socket are closed.
					var user = Users[sessionCookie].guid;

					_.each(Users[sessionCookie].rooms, function(room) {
						if (findClientsSocketByRoomId(room).length == 0) delete Rooms[room];
					});

					delete Users[sessionCookie];
					socket.broadcast.emit('remove_online_user', user); // emit to all socket

				}
			}, 5000); // we wait 5s to see if user will reconnect.
		});


		/*
		 * Event when socket join a room
		 */
		socket.on('join_room', function(data, callback) {
			socket.join(data.room);
			callback();
		});


		/*
		 * Event when socket leave a room
		 */
		socket.on('leave_room', function(room) {
			Users[getElggSession(socket)].rooms = _.without(Users[getElggSession(socket)].rooms, room) ;


			if (findClientsSocketByRoomId(room).length == 0) delete Rooms[room];
			socket.leave(room);
		});

		socket.on('get_rooms', function() {
			var rooms = io.sockets.manager.roomClients[socket.id];
			socket.emit('get_rooms', rooms);
		});

		socket.on('location', function(data, callback) {
			socket.broadcast.emit('broadcast', data);
			callback();
		});

	});

	// socket.broadcast.emit('remove_online_user', user); // emit to all clients except sender

	// io.emit('users_count', clients); // emit to all clients

	// findClientsSocketByRoomId('room'); // get all clients from room `room`

	// io.sockets.manager.rooms; //get all rooms

	// io.sockets.manager.roomClients[socket.id]; // get all rooms of a client
	// socket.rooms ?

	function findClientsSocketByRoomId(roomId) {
var res = []
, room = io.sockets.adapter.rooms[roomId];
if (room) {
    for (var id in room) {
    res.push(io.sockets.adapter.nsp.connected[id]);
    }
}
return res;
}

}