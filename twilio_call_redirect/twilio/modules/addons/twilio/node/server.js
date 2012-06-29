/**
 * Twilio Call-Redirect WHMCS module
 *
 * @author Frank Laszlo <frank@franksworld.org>
 * @version 1.0
 * @package twilio-whmcs
 */

/**
  * CLIENT_PORT: This is the port your admins will need to connect to when receiving new call notifications
  * 				It must not be blocked by a firewall for external communication. This should match what
  * 				you have configured in WHMCS.
  * 
  * SERVER_PORT: This port is used to receive new call notifications from twilio. It only needs to be accessible
  * 				from localhost.
  * 
  * SSL_KEY_FILE: The filename for your SSL key, located within the 'ssl' directory. (a self signed key/cert is included)
  * 
  * SSL_CERT_FILE: The filename for your SSL certificate, located within the 'ssl' directory.
  * 
  * SSL_CACERT_FILE: The filename for your intermediate SSL certificate (if required), located within the 'ssl' directory. 
  * 				Leave empty file in place if you do not require an intermediate cert.
  * 
  */

var CLIENT_PORT = 9090;
var SERVER_PORT = 9999; 

var SSL_KEY_FILE = 'server.key';
var SSL_CERT_FILE = 'server.crt';
var SSL_CACERT_FILE = 'ca.crt';

/******************************************/
/* Do not modify anything below this line */
/******************************************/

var fs = require('fs')
  ,	app = require('https').createServer(
  		{
  			key:fs.readFileSync(__dirname + '/ssl/' + SSL_KEY_FILE),
  			cert:fs.readFileSync(__dirname + '/ssl/' + SSL_CERT_FILE),
  			ca:fs.readFileSync(__dirname + '/ssl/' + SSL_CACERT_FILE)
  		}
  	)
  , io = require('socket.io').listen(app)
  , net = require('net')
 

// Start listening server for new events
var server = net.createServer(function(stream) {
	stream.setTimeout(0);
	stream.setEncoding('utf8');
	stream.addListener('data', function(data) {
		io.sockets.emit('data', data);
		stream.end();
	});
});

app.listen(CLIENT_PORT);
server.listen(SERVER_PORT, 'localhost');