<?php

/**
 * Twilio Call-Redirect WHMCS module
 *
 * @author Frank Laszlo <frank@franksworld.org>
 * @version 1.0
 * @package twilio-whmcs
 */

function twilio_adminheader_hook($vars) {
	// Get config values
	$config = array();
	$d = select_query('mod_twilio_config', '*', array());
	while ($res = mysql_fetch_assoc($d)) {
		$setting = $res['setting'];
		$value = $res['val'];
		$config[$setting] = $value;
	}
	
	// Get module access
	$access = get_query_val('tbladdonmodules', 'value', 'module="twilio" AND setting="access"');
	$access = explode(',', $access);
	
	// Get admin role
	$adminid = $_SESSION['adminid'];
	$adminrole = get_query_val('tbladmins', 'roleid', 'id='.$adminid);
	if (in_array($adminrole, $access, true)) {
		// First we need to check if the port is even open, otherwise the admin page will take forever to load.
		$timeout = '5.00';
		if ($fp = fsockopen($_SERVER['HTTP_HOST'], $config['client_port'], $errno, $errstr, (float)$timeout)) {
			$socketio = $_SERVER['HTTP_HOST'].':'.$config['client_port'];
			$code = '
<script src="https://'.$socketio.'/socket.io/socket.io.js"></script>
<script>
	var socket = io.connect("'.$socketio.'", {secure: true});
	socket.on("data", function (data) {
		var d = JSON.parse(data);
		switch (d.type) {
			case "client":
				var ok = confirm("You have an incoming call for Client ID: "+d.clientid+". Would you like to navigate to this clients page?");
				var url = "clientssummary.php?userid="+d.clientid;
				break;
			case "ticket":
				var ok = confirm("You have an incoming call regarding Ticket ID: "+d.ticketid+". Would you like to navigate to this ticket?");
				var url = "supporttickets.php?action=viewticket&id="+d.ticketid;
				break;
		}
		if (ok) {
			document.location = url;
		}
	});
</script>';
			return $code;
		} else {
			return '<!-- Cannot connect to node.js server -->';
		}
	} else {
		return '<!-- No access to twilio call-redirect -->';
	}
	
}

add_hook('AdminAreaHeaderOutput', 999, 'twilio_adminheader_hook');

?>
