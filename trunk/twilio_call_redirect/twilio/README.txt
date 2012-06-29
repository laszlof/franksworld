Twilio Call-Redirect v1.0
Author: Frank Laszlo (http://www.franksworld.org)

DESCRIPTION
---------------
This module will allow you to receive calls on your Twilio (http://www.twilio.com)
number, and have a scripted response parsed from your WHMCS database. Once the caller
has finished navigating the menu, they will be forwarded to any number(s) of your choosing.
In addition to this, any logged in admins with access to the addon module, will be prompted
in WHMCS to navigate to the clients summary page, or ticket page, depending on the input
provided.

Version 1.0 is rather limited in functionality, and only supports a specific use-case, but
there are plans for many new and exciting features in the next release.


FEATURES
---------------
- Text-to-Speech input
- MP3 file support for playback to clients
- Variable data insertion at certain stages (Insert clients name, ticket number, etc in the response)
- node.js admin PUSH script to push admins to client summary page, or ticket page
- Unlimited phone number redirects
- If calls go unanswered, it records a VM and emails it to a specified address.


CALL EXAMPLE
---------------
(client calls into Twilio number)
Twilio: Hello, Thank you for calling Widgets Incorporated. If you're calling regarding an existing ticket, please press 1, for all other issues, please press 2.
(client selects option 1)
Twilio: Please enter your ticket ID number, followed by the pound sign
(client enters ticket ID)
Twilio: Hello John Doe! One of our operators would be happy to assist you with Ticket number 1234. Please stay on the line.
(WHMCS Admin notification is sent. Administrator "Tom" notices a popup on his screen, and when clicked, is forwarded to the ticket page)
(Twilio forwards the caller to the first number in the config, which is "Tom")
(Tom receives the call, and has all the information he needs right in front of him)


REQUIREMENTS
---------------
WHMCS > 5.0
Twilio Account (http://www.twilio.com)
Node.js (http://nodejs.org/)
 - NPM (http://npmjs.org/)
 - socket.io (http://socket.io/)


INSTALLATION
---------------

1) Install node.js. Being that this process is going to vary greatly depending on your system, I'm not going to go into much detail here.
	Theres plenty of documentation online on how to install it.
2) Install NPM, and the socket.io nodejs module. Again, installing NPM is fairly straight forward, read the docs online on how to do it.
	To install socket.io, you simply need to run "npm install socket.io". You'll probably want to run this as the user who owns the WHMCS installation
	files, so that the module is available for the connector script.
3) Open up modules/addons/twilio/node/server.js.
4) This is the file that controls the push notifications for your browser. There are only a couple things to configure in here if you'd like.
	- CLIENT_PORT: This port needs to be opened in the firewall for external access. Your admins will need to be able to access this.
	- SERVER_PORT: This port only needs to be available to localhost.
	- SSL: The script uses SSL to communicate with your admin's browsers. I've included a self-signed certificate with this package. If you wish
		you're welcome to replace the existing certificate/key files (modules/addons/twilio/node/ssl) with your own.
5) Execute the node server:
	# nodejs server.js
	Please note, this script needs to run in the background. Starting it this way will ensure that its working properly. It should not throw any errors.
	To start the server in the background, execute the following command:
	# nohup nodejs server.js >> /dev/null &
6) Login to WHMCS admin, navigate to Setup -> Addon Modules.
7) Enable Twilio Call-Redirect. Be sure to select all the administrator roles in the access control who you wish to receive the push notifications.
8) Go to Addons -> Twilio Call-Redirect
9) Go through EVERY configuration option on the first tab. Most of the defaults should be fine, but there will be some you need to modify. Also
	note that if you changed the client/server ports in the node.js script, you'll want to update them here. The Current Status should say "Online"
	for both if the node server is running properly, and is accessible.
10) Phone Numbers. This is where you configure the numbers you want Twilio to forward the calls to. Its fairly self-explanitory.
11) Upload Audio files. This is where you can upload custom messages to play to your callers. Please be aware that large files will likely take 
	a long time to load, and will cause delays in your caller experience.
12) Login to your Twilio account.
13) I do not have a live account, so I'm not exactly sure how this works. But basically, you need to enter the following URL as the "Voice URL" for
	the number you purchased from Twilio:
	http://www.yourdomain.com/whmcs/modules/addons/twilio/calls/call_handler.php
	Be sure to select "GET" rather than "POST" for the method.
	You can test this out without having a live account by entering this URL in the sandbox App details on the dashboard, then calling the sandbox number
	and entering your sandbox pin.
14) Thats it! Everything should be working at this point!

SUPPORT
--------------------

Support will be provided on an as-needed basis. Updates/bug fixes will be provided
free of charge to all purchasers for the duration of the product lifetime.

The lifecycle of this product may be terminated at any time, at which point all existing support
obligations will be null and void.

Please email sales@franksworld.org or open a ticket on my website, http://www.franksworld.org/ for support on this product.

