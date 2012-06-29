<?php

function validate_ip($ip_addr) {
	if (preg_match("/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/", $ip_addr)) {
		$parts=explode(".",$ip_addr);
		foreach ($parts as $ip_parts) {
			if (intval($ip_parts) > 255 || intval($ip_parts) < 0) {
				return false;
			}
		}
		return true;
	} else {
		return false;
	}
}

function unblockme_cpdo($url, $whmuser, $whmauth, $authmethod) {
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	if ($authmethod == "basic") {
		$authstr = 'Authorization: Basic ' . base64_encode($whmuser .':'. $whmauth) . "\r\n";
	} else {
		$authstr = 'Authorization: WHM ' . $whmuser . ':' . $whmauth . "\r\n";
	}
	$header[0] = $authstr;
	curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
	curl_setopt($curl, CURLOPT_URL, $url);
	$r = curl_exec($curl);
	if ($r == false) {
		error_log("curl_exec threw error \"" . curl_error($curl) . "\" for $url");
	}
	curl_close($curl);
	return $r;
}

define("CLIENTAREA",true);
require("dbconnect.php");
require("includes/functions.php");
require("includes/clientareafunctions.php");

$invalidip = $_LANG['unblockme_invalidip'];
$notblocked = $_LANG['unblockme_notblocked'];
$unblocked = $_LANG['unblockme_unblocked'];
if ((!isset($_GET['id'])) && (!isset($_GET['ip']))) {
	$output = array("status"=>"failure", "message"=>"Unauthorized Access", "page"=>1);
	print json_encode($output);
	exit();
}

$serviceid = $_GET['id'];
$ip = $_GET['ip'];

if (!validate_ip($ip)) {
	$output = array("status"=>"failure", "message"=>$invalidip, "page"=>2);
	print json_encode($output);
	exit();
}

$query = "SELECT s.ipaddress, s.username, s.password, s.accesshash, s.secure
			FROM tblservers s, tblhosting h
			WHERE s.id = h.server AND h.id = $serviceid";
$data = mysql_query($query);

if (!mysql_num_rows($data)) {
	$output = array("status"=>"failure", "message"=>"Service ID not found", "page"=>3);
	print json_encode($output);
	exit();
}

$r = mysql_fetch_array($data);
$srv_ip = $r[0];
$srv_user = $r[1];
$srv_pass = $r[2];
$srv_hash = $r[3];
$srv_secure = $r[4];

if ($srv_hash) {
	$authhash = preg_replace("'(\r|\n)'","",$srv_hash);
	$authmethod = "accesshash";
} elseif ($srv_pass) {
	$authmethod = "basic";
	$authhash = decrypt($srv_pass);
} else {
	$output = array("status"=>"failure", "message"=>"Bad server configuration", "page"=>4);
	print json_encode($output);
	exit();
}

if ($srv_secure) {
	$url = "https://$srv_ip:2087/cgi/addon_csf.cgi";
} else {
	$url = "http://$srv_ip:2086/cgi/addon_csf.cgi";
}
$query_url = $url.'?action=kill&ip='.$ip;
$data = unblockme_cpdo($query_url, $srv_user, $authhash, $authmethod);

$matches = array();
$pattern = '/<p><pre style=\'font-family: Courier New, Courier; font-size: 12px\'>(.*?)<\/p>/s';
preg_match($pattern, $data, $matches);

$notfoundRE = '/csf:.*?not found in.*?/';
if (preg_match($notfoundRE, $matches[1]) > 0) {

	// Attempt a temprm
	$temp_query = $url.'?action=temprm&ip='.$ip;
	$tempdata = unblockme_cpdo($temp_query, $srv_user, $authhash, $authmethod);
	$tempmatches = array();
	$tempnotfoundRE = '/csf:.*?not found.*?/';
	$notempblockRE = '/csf.*?no temporary IP bans.*?/';
	preg_match($pattern, $tempdata, $tempmatches);
	if ((preg_match($tempnotfoundRE, $tempmatches[1]) > 0) || (preg_match($notempblockRE, $tempmatches[1]) > 0)) {
		$output = array("status"=>"failure", "message"=>$notblocked, "page"=>5);
		print json_encode($output);
	} else {
		$output = array("status"=>"success", "message"=>$unblocked, "page"=>6);
		print json_encode($output);
	}
} else {
	$output = array("status"=>"success", "message"=>$unblocked, "page"=>7);
	print json_encode($output);
}

?>
