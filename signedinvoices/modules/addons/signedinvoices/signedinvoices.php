<?php

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");

function signedinvoices_config() {
    $configarray = array(
    "name" => "Signed Invoices",
    "description" => "This module will allow you to digitally sign your invoices sent by WHMCS.",
    "version" => "1.0",
    "author" => "<a href=\"http://www.franksworld.org\">Frank Laszlo</a>",
    "language" => "english");
    return $configarray;
}

function signedinvoices_activate() {

	$query = array();
	$query[0] = "CREATE TABLE `mod_signedinvoices` (
			`id` int(11) NOT NULL auto_increment,
			`name` varchar(32) NOT NULL,
			`value` varchar(4096) default NULL,
			PRIMARY KEY (`id`));";
	$query[1] = "INSERT INTO `mod_signedinvoices` (`id`, `name`, `value`) VALUES
					(1, 'cert', NULL),
					(2, 'key', NULL),
					(3, 'keypass', NULL),
					(4, 'extra', NULL);";

	foreach ($query as $q) {
		$r = full_query($q);
	}
    return array('status'=>'success','description'=>'Please enable access for your administrator role below, then access the configuration from the Addons menu.');

}

function signedinvoices_deactivate() {

    $query = "DROP TABLE `mod_signedinvoices`";
	$r = full_query($query);
    return array('status'=>'success','description'=>'Signed Invoices has been successfully removed.');

}

function signedinvoices_upgrade($vars) {

}

function signedinvoices_output($vars) {

    $modulelink = $vars['modulelink'];
    $version = $vars['version'];
	
	if (isset($_REQUEST['a'])) {
		foreach ($_POST as $k => $v) {
			if ($k != "token") {
				if ($k == "keypass") {
					$v = encrypt($v);
				}
				$d = update_query("mod_signedinvoices", array("value"=>$v), "name='$k'");
			}
		}
		$successmsg = "Changes Saved.";
	}
	$data = select_query("mod_signedinvoices", "name, value", array());
	while ($r = mysql_fetch_array($data)) {
		switch ($r['name']) {
			case "cert":
				$cert = $r['value'];
				break;
			case "key":
				$key = $r['value'];
				break;
			case "keypass":
				$keypass = decrypt($r['value']);
				break;
			case "extra":
				$extra = $r['value'];
				break;
		}
	}
	if (isset($successmsg)) {
		print '<div class="successbox">'.$successmsg.'</div>';
	}
	print '<div id="tab0box" class="tabbox">
				<div id="tab_content">
					<h3>Configuration</h3>
					<form method="POST" action="'.$modulelink.'&a=save" name="configfrm">
						<table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
							<tr>
								<td class="fieldarea">
									<strong>Signing Certificate</strong><br />
									<textarea rows="20" cols="40" name="cert">'.$cert.'</textarea>
								<td class="fieldarea">
									<strong>Private Key</strong><br />
									<textarea rows="20" cols="40" name="key">'.$key.'</textarea>
								</td>
								<td class="fieldarea">
									<strong>Intermediate Certificates (Optional)</strong><br />
									<textarea rows="20" cols="40" name="extra">'.$extra.'</textarea>
								</td>
							</tr>
							<tr>
								<td colspan="3" align="center">
									<strong>Private Key Passphase (Optional):</strong><input type="password" name="keypass" value="'.$keypass.'" />
								</td>
							</tr>
							<tr>
								<td colspan="3" align="center">
									<input type="submit" value="Save Changes" class="button" />
								</td>
							</tr>
						</table>
					</form>
				</div>
			</div>
			<br />
			<p align="left"><h5>Signed Invoices was written by <a href="mailto:frank@franksworld.org">Frank Laszlo</a><br />Version '.$version.'</h5></p>';



}

function signedinvoices_sidebar($vars) {

	$sidebar = '';
	return $sidebar;
}

?>
