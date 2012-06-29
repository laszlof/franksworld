<?php
$script_directory = substr($_SERVER['SCRIPT_FILENAME'], 0, strrpos($_SERVER['SCRIPT_FILENAME'], '/'));

include('../../servers/whmcsreseller/functions.php');
include('../../../configuration.php');

$db = mysql_connect($db_host, $db_username, $db_password) or die ("Error connecting to database.");
mysql_select_db($db_name, $db) or die ("Couldn't select the database.");

$data = mysql_query("SELECT * FROM whmcsresellerconf");
while ($r = mysql_fetch_array($data)) {
	switch ($r['name']) {
		case "email":
			$whmcs_email = $r['value'];
			break;
		case "password":
			$whmcs_pass = base64_decode($r['value']);
			break;
	}
}

$ch = curl_init();
$content = wr_dologin($ch, $whmcs_email, $whmcs_pass);
if (!$content) {
	$data = mysql_query("SELECT * FROM whmcsresellerlicenses");
	while ($r = mysql_fetch_array($data)) {
		$lic_id = $r['license_id'];
		$lic_info = wr_get_lic_info($ch, $lic_id);
		$lic_status = $lic_info['status'];
		$lic_domains = $lic_info['domains'];
		$lic_ips = $lic_info['ips'];
		$lic_path = $lic_info['directory'];
		$lic_type = $lic_info['type'];
		mysql_query("UPDATE whmcsresellerlicenses SET status='$lic_status', domains='$lic_domains', ips='$lic_ips', path='$lic_path', type='$lic_type' WHERE license_id='$lic_id'");
	}
}
curl_close($ch);

?>
