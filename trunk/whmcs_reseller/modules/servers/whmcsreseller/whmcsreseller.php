<?php

function whmcsreseller_ConfigOptions() {
	$configarray = array("License Type" => array("Type"=>"dropdown", "Options"=>"Branding,No Branding"));
	return $configarray;
}

function whmcsreseller_TerminateAccount($params) {
	$serviceid = $params['serviceid'];
	update_query("whmcsresellerlicenses", array("user_id"=>"NULL", "prod_id"=>"NULL"), "prod_id='$serviceid'");
	return "success";
}

function whmcsreseller_CreateAccount($params) {
	$clientid = $params['clientsdetails']['userid'];
	$serviceid = $params['serviceid'];
	$pid = $params['pid'];
	switch ($params['configoption1']) {
		case "Branding":
			$lic_type = "branding";
			break;
		case "No Branding":
			$lic_type = "nobranding";
			break;
	}
	$query = "SELECT id, license FROM whmcsresellerlicenses WHERE user_id IS NULL AND type='$lic_type' LIMIT 1";
	$data = full_query($query);
	if (!mysql_num_rows($data)) {
		return "No licenses available to assign";
	}
	$r = mysql_fetch_array($data);
	$lic_id = $r[0];
	$lic_str = $r[1];

	$res = select_query("tblcustomfields", "*", array("relid"=>$pid, "fieldname"=>"License"));
	if (!mysql_num_rows($res)) {
		return "License field not created for product";
	} else {
		$row = mysql_fetch_assoc($res);
		$customfield = $row['id'];
	}
	update_query("whmcsresellerlicenses", array("user_id"=>$clientid, "prod_id"=>$serviceid), "id='$lic_id'");
	full_query("UPDATE tblcustomfieldsvalues SET value='$lic_str' WHERE fieldid='$customfield' AND relid='$serviceid'");
	
	return "success";
}

function whmcsreseller_ClientArea($params) {
	
	include('functions.php');
	$userid = $params['clientsdetails']['userid'];
	$serviceid = $params['serviceid'];
	$lic_id = wr_get_lic_id_byuser($userid, $serviceid);
	$data = select_query("whmcsresellerconf", "name, value", array());
	while ($r = mysql_fetch_array($data)) {
		switch ($r['name']) {
			case "email":
				$myemail = $r['value'];
				break;
			case "password":
				$mypass = base64_decode($r['value']);
				break;
		}
	}
	if (isset($_REQUEST['_a'])) {
		if (!isset($_POST['lic_id']) && !isset($_POST['id']) && !isset($_POST['user_id'])) {
			print '<div class="errorbox" align="center" width="60%">Unauthorized Access Attempt.</div>';
		} else {
			$mylic_id = $_POST['lic_id'];
			$myservice_id = $_POST['id'];
			$myuser_id = $_POST['user_id'];
			$d = select_query("whmcsresellerlicenses", "*", array("license_id"=>$mylic_id, "user_id"=>$myuser_id, "prod_id"=>$myservice_id));
			if (!mysql_num_rows($data)) {
				print '<div class="errorbox" align="center" width="60%">Unauthorized Access Attempt.</div>';
			} else {
				$data = select_query("whmcsresellerconf", "*");
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
				$data = wr_dologin($ch, $whmcs_email, $whmcs_pass);
				if (!$data) {
					$lic_info = wr_get_lic_info($ch, $lic_id);
					$lic_status = $lic_info['status'];
					if ($lic_status != "Reissued") {
						wr_reissue_lic($ch, $lic_id);
					}
				}
				curl_close($ch);
			}
		}
	}
			
	$prod_data = select_query("tblhosting", "domainstatus", array("id"=>$serviceid, "domainstatus"=>"Active"));
	$isactive = mysql_num_rows($prod_data);
	$ch = curl_init();
	$data = wr_dologin($ch, $myemail, $mypass);
	if (!$data) {
		$lic_info = wr_get_lic_info($ch, $lic_id);
	}
	curl_close($ch);
	$lic_status = $lic_info['status'];
	if ($lic_status == "Reissued") {
		$lic_domains = "";
		$lic_ips = "";
		$lic_path = "";
		$lic_key = $lic_info['license'];
		$code = '<br /><div class="errorbox">The Valid Domains, IPs and Directory will be detected & saved the next time the license is verified.</div><br />';
	} else {
		$lic_domains = $lic_info['domains'];
		$lic_ips = $lic_info['ips'];
		$lic_path = $lic_info['directory'];
		$lic_key = $lic_info['license'];
		switch ($lic_info['type']) {
			case "nobranding":
				$type = "No Branding";
				break;
			case "branding":
				$type = "Branding";
				break;
		}
		$code = '<br />';
	}
	$code = $code .'<table cellspacing="1" cellpadding="0" class="frame"><tr><td>
			<table width="100%" cellpadding="2">
			<tr><td class="fieldarea">License Key:</td><td><input id="tbstyle" type="text" size="40" value="'.$lic_key.'" readonly=true></td></tr>
			<tr><td class="fieldarea">Valid Domains:</td><td><textarea id="tastyle" cols="40" readonly=true>'.$lic_domains.'</textarea></td></tr>
			<tr><td class="fieldarea">Valid IPs:</td><td><textarea id="tastyle" cols="40" readonly=true>'.$lic_ips.'</textarea></td></tr>
			<tr><td class="fieldarea">Valid Directory:</td><td><textarea id="tastyle" cols="40" readonly=true>'.$lic_path.'</textarea></td></tr>
			<tr><td class="fieldarea">License Type:</td><td>'.$type.'</td></tr>
			<tr><td class="fieldarea">Status:</td><td>'.$lic_status.'</td></tr></table></td></tr>';
	if ($lic_status == "Reissued") {
		$code = $code . '<tr><td align="center">&nbsp;</td></tr>';
	} else {
		$code = $code . '<tr><td align="center">
						<form method="POST" action="'.$_SERVER['PHP_SELF'].'?action=productdetails&_a=reissue">
						<input type="hidden" name="id" value="'.$params['serviceid'].'" />
						<input type="hidden" name="lic_id" value="'.$lic_id.'" />
						<input type="hidden" name="user_id" value="'.$userid.'" />
						<input type="submit" value="Reissue License" class="button" />
						</td></tr>';
	}
	$code = $code . '</table>';

	if ((!$lic_key) || (!$isactive)) {
		return '<br /><div class="errorbox">No license is currently assigned.</div>';
	} else {
		return $code;
	}
}
?>
