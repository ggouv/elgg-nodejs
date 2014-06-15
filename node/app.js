
/*
 * Load required node modules
 */
var http       = require('http'),              // http server core module
	express    = require('express'),           // web framework external module
	io         = require('socket.io'),         // web socket external module
	fs         = require('fs'),                // Read file system
	easyrtc    = require('easyrtc'),           // EasyRTC external module
	bodyParser = require('body-parser'),       // Read json data
	config     = require('./config');          // Read config

// load global modules
_          = require('underscore');        // Tools to manipulate array and object


// Setup and configure Express http server.
var httpApp = express();
httpApp.use(bodyParser());
//httpApp.set('view options', { layout: false });
//httpApp.set('views', __dirname + '/views');

// Start Express http server on port 7890
var webServer = http.createServer(httpApp).listen(7890);

// Start Socket.io so it attaches itself to Express server
var socketServer = io.listen(webServer, {
	'log level': 1
});

// Start EasyRTC server
var rtcServer = easyrtc.listen(httpApp, socketServer);



/*
 * Node modules ready to go !
 */
console.log('\033[1;94m' + 'Server running at: ' + webServer.address().address + ':' + webServer.address().port + '\033[0m');

// Initiate global vars
Users = {},     // used to store info of each user matched by Elgg session cookies
Rooms = {},    // used to store some datas of each rooms
timeouts = {};    // used to store some timeouts



/**
 * Start socket.io and load socket.js files to play with websocket
 */
// load elgg-nodejs/node/socket.js first. There is middleware
require(__dirname+'/socket.js')(socketServer);
// Get all socket.js files in node directories in all elgg plugins. Aka search in elgg/mod/ALL_PLUGINS/node
_.each(config.modules, function(module) {
	var file = __dirname.replace('/mod/elgg-nodejs/node', '/mod/'+module+'/node/socket.js');
		fileExist = fs.existsSync(file);

	if (fileExist) require(file)(socketServer);
});



/**
 * Expose an API for Elgg server. Only internal curl request are allowed.
 * If request address is wrong, it's forward to elgg 404 page
 */
// Define router
var router = express.Router();
// all routes must be prefixed with /api
httpApp.use('/api', router);

// load elgg-nodejs/node/api.js first.
require(__dirname+'/api.js')(router, socketServer);
// Get all api.js files in node directories in all elgg plugins. Aka search in elgg/mod/ALL_PLUGINS/node
_.each(config.modules, function(module) {
	var file = __dirname.replace('/mod/elgg-nodejs/node', '/mod/'+module+'/node/api.js');
		fileExist = fs.existsSync(file);

	if (fileExist) require(file)(router, socketServer);
});

// other routes and root route is not allowed, forward to elgg 404 page
httpApp.get('/*', function(request, response) {
	// Redirect to Elgg 404 page
	response.writeHead(301, {
		Location: config.site_url + '404'
	});
	response.end();
});




/**
 * Define Classes
 */

// Define user class
User = function(data, sessionCookie) {
	var user = this;

	_.each(data, function(value, key) {
		user[key] = value;
	});
	this.sessionCookie = sessionCookie;
	this.sockets = {};
	this.rooms = [];
}

// Add methods like this.  All Person objects will be able to invoke this
User.prototype.tiny = function() {
	return _.omit(this, ['sockets', 'rooms', 'sessionCookie']);
}


// Emit to all sockets of this user.
User.prototype.Emit = function(evt, params) {
	_.each(this.sockets, function(socket) {
		socket.emit(evt, params);
	});
}

// Instantiate new objects with 'new'
//var person = new Person("Bob", "M");

// Invoke methods like this
//person.speak(); // alerts "Howdy, my name is Bob"





/**
 * Helpers
 */



/**
 * Get Elgg session Id from cookie.
 * Non logged in user also got a cookie. So we can set a socket for them.
 *
 * @param  {[type]} socket current socket
 * @return {[type]}        elgg session id
 */
getElggSession = function(socket) {
	return socket.handshake.headers.cookie.match(/Elgg=(\S*);?/)[1];
};

// Make sendable var of all connected users
getAllUsers = function() {
	var users = [];
	_.each(Users, function(user) {
		users.push(user.tiny());
	});

	return users;
};
