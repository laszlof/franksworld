<?php

require("dbconnect.php");
require("includes/functions.php");

// Get whitelisted IP's

$data = select_query("tbladminwhitelist", "ip", array());

$whitelist = array();
while ($res = mysql_fetch_array($data)) {
	$ip = $res['ip'];
	$whitelist[] = $ip;
}

// Step through whitelisted IP's and remove from tblbannedips
foreach ($whitelist as $wl_ip) {
	print "Checking $wl_ip...";
	$d = select_query("tblbannedips", "*", array("ip"=>$wl_ip));
	if (mysql_num_rows($d)) {
		print "FOUND\n";
		$r = mysql_fetch_array($d);
		$id = $r['id'];
		delete_query("tblbannedips", array("ip"=>$wl_ip));
	} else {
		print "NOT FOUND\n";
	}
}

?>
