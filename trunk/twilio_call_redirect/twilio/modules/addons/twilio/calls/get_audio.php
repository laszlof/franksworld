<?php

/**
 * Twilio Call-Redirect WHMCS module
 *
 * @author Frank Laszlo <frank@franksworld.org>
 * @version 1.0
 * @package twilio-whmcs
 */

require_once '../../../../dbconnect.php';
require_once '../../../../includes/functions.php';

if (isset($_GET['f'])) {
	$f = mysql_real_escape_string($_GET['f']);
	$filepath = get_query_val('mod_twilio_config', "val", "setting='$f'");
	if (file_exists($filepath)) {
		header('Content-Type: audio/mpeg');
		readfile($filepath);
	} else {
		print "File does not exist";
	}
}

?>