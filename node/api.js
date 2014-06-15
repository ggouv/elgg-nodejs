
module.exports = function (router, io) {


	router.route('/clients')

	// get all the users (accessed at GET http://localhost:7890/api/clients)
	.get(function(request, response) {
		var output = {};

		_.each(Users, function(user) {
			output[user.guid] = _.extend(_.omit(user, ['sockets']), {nbr_sockets: _.size(user.sockets)});
		});
		response.json(output);
	})

	// post to sockets (accessed at POST http://localhost:7890/api/clients)
	.post(function(request, response) {
		var count = 0;

		if (request.body.guids) {
			_.each(request.body.guids, function(guid) {
				var user = _.find(Users, function(user) {
					return user.guid == guid;
				});

				if (user) {
					_.invoke(user.sockets, 'emit', 'message', request.body.data);
					count++;
				}
			});
		} else {
			io.emit('message', request.body.data);
		}
		response.json(count);
	});



	router.route('/rooms')

	// get all rooms (accessed at GET http://localhost:7890/api/rooms)
	.get(function(request, response) {
		response.json(rooms);
	})

	// post to clients (accessed at POST http://localhost:7890/api/clients)
	.post(function(request, response) {
		var count = 0;

		_.each(request.body.names, function(name) {
			//var room = io.rooms[name];

			if (name) {
				io.sockets.in(name).emit('message', request.body.data);
				count++;
			}
		});
		response.json(count);
	});



	// Expose information about API request
	router.route('/services').get(function(request, response) {
		var output = {
				get_clients: "Return all online clients",
				get_rooms: "Return all rooms"
			};

		response.json(output);
	});
};