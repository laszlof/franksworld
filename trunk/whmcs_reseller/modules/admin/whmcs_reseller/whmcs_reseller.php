<?php

if (!mysql_num_rows(mysql_query("SHOW TABLES LIKE 'whmcsresellerconf'"))) {
	if (!$_GET['install']) {
		print "
				<p><strong>Not Yet Installed</strong></p>
				<p>This addon will allow you to give free WHMCS licenses to your clients.</p>
				<p>To install it, click on the button below.</p>
				<p><input type=\"button\" value=\"Install WHMCS Reseller\" onclick=\"window.location='$modulelink&install=true'\"></p>";
	} else {
		$query = array();
		$query[0] = "CREATE TABLE `whmcsresellerconf` (
						`id` int(11) NOT NULL auto_increment,
						`name` varchar(32) NOT NULL,
						`value` varchar(1024) default NULL,
						PRIMARY KEY  (`id`));";
		$query[1] = "INSERT INTO `whmcsresellerconf` (`id`, `name`, `value`) VALUES
						(1, 'email', NULL),
						(2, 'password', NULL)";
		$query[2] = "CREATE TABLE `whmcsresellerlicenses` (
						`id` int(11) NOT NULL auto_increment,
						`user_id` int(11) default NULL,
						`prod_id` int(11) default NULL,
						`license_id` int(11) NOT NULL,
						`license` varchar(64) NOT NULL,
						`status` varchar(64) default NULL,
						`domains` varchar(128) default NULL,
						`ips` varchar(128) default NULL,
						`path` varchar(128) default NULL,
						`type` varchar(10) default NULL,
						PRIMARY KEY  (`id`),
						UNIQUE (license_id, license));";
		foreach ($query as $q) {
			$r = mysql_query($q);
		}
		header("Location: $modulelink");
		exit;
	}
} else {
	include('../modules/servers/whmcsreseller/functions.php');
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
	if (isset($_REQUEST['a'])) {
		switch ($_REQUEST['a']) {
			case "savechanges":
				$pemail = $_POST['email'];
				$ppass = base64_encode($_POST['password']);
				update_query("whmcsresellerconf", array("value"=>$pemail), "name='email'");
				update_query("whmcsresellerconf", array("value"=>$ppass), "name='password'");
				$myemail = $pemail;
				$mypass = base64_decode($ppass);
				$msg = 'Configuration changes have been saved.';
				break;
			case "import":
				$ch = curl_init();
				$data = wr_dologin($ch, $myemail, $mypass);
				if (!$data) {
					$r = wr_get_licenses($ch);
				} else {
					$msg = 'Error logging in to WHMCS.COM client area';
					break;
				}
				curl_close($ch);
				$imp_count = 0;
				foreach ($r as $k => $v) {
					$myid = $v['id'];
					$mylic = $v['license'];
					$data = select_query("whmcsresellerlicenses", "id", array("license_id"=>$myid));
					if (!mysql_num_rows($data)) {
						insert_query("whmcsresellerlicenses", array("license_id"=>$myid, "license"=>$mylic));
						$imp_count++;
					}
				}
				$ch = curl_init();
				$content = wr_dologin($ch, $myemail, $mypass);
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

				$msg = "Imported $imp_count licenses.";
				break;
			case "reissue":
				$lic_id = $_REQUEST['lic_id'];
				$ch = curl_init();
				$data = wr_dologin($ch, $myemail, $mypass);
				if (!$data) {
					wr_reissue_lic($ch, $lic_id);
					update_query("whmcsresellerlicenses", array("status"=>"Reissued", "domains"=>"", "ips"=>"", "path"=>""), array("license_id"=>$lic_id));
					$msg = 'License ID: '.$lic_id.' has been reissued.';
				} else {
					$msg = 'Error logging in to WHMCS.COM client area';
					break;
				}
				curl_close($ch);
				break;
			case "del":
				$lic_id = $_REQUEST['lic_id'];
				delete_query("whmcsresellerlicenses", "license_id='$lic_id'");
				$msg = 'License ID: '.$lic_id.' has been deleted.';
				break;
		}
	}
	$licenses = array();
	$lic_data = select_query("whmcsresellerlicenses", "*", array());
	while ($r = mysql_fetch_array($lic_data)) {
		$licenses[$r['id']]['user_id'] = $r['user_id'];
		$licenses[$r['id']]['prod_id'] = $r['prod_id'];
		$licenses[$r['id']]['license_id'] = $r['license_id'];
		$licenses[$r['id']]['license'] = $r['license'];
		$licenses[$r['id']]['status'] = $r['status'];
		$licenses[$r['id']]['domains'] = $r['domains'];
		$licenses[$r['id']]['ips'] = $r['ips'];
		$licenses[$r['id']]['path'] = $r['path'];
		switch ($r['type']) {
			case "nobranding":
				$lictype = "No Branding";
				break;
			case "branding":
				$lictype = "Branding";
				break;
		}
		$licenses[$r['id']]['type'] = $lictype;
	}
	print '<script>
			$(document).ready(function(){
				$(".tabbox").css("display","none");
				var selectedTab;
				$(".tab").click(function(){
					var elid = $(this).attr("id");
					$(".tab").removeClass("tabselected");
					$("#"+elid).addClass("tabselected");
					if (elid != selectedTab) {
						$(".tabbox").slideUp();
						$("#"+elid+"box").slideDown();
						selectedTab = elid;
					}
					$("#tab").val(elid.substr(3));
				});
				selectedTab = "tab0";
				$("#tab0").addClass("tabselected");
				$("#tab0box").css("display","");
			});
			</script>
			<div id="content_padded">';
	if ($msg) {
		print '<div class="errorbox">'.$msg.'</div>';
	}
	print '<div id="tabs"><ul>
			<li id="tab0" class="tab"><a href="javascript:;">Assigned Licenses</a></li>
			<li id="tab1" class="tab"><a href="javascript:;">Import Licenses</a></li>
			<li id="tab2" class="tab"><a href="javascript:;">Configuration</a></li>
			</ul></div>

			<!-- Assigned Licenses Tab -->
			<div id="tab0box" class="tabbox"><div id="tab_content">
			<h3>Currently Assigned Licenses</h3>
			<div class="tablebg"><table class="datatable" cellspacing="1" cellpadding="3">
			<tr><th>Reissue</th><th>License</th><th>Type</th><th>Client</th><th>License Status</th><th>Domains</th></tr>';
	$ch = curl_init();
	$data = wr_dologin($ch, $myemail, $mypass);
	foreach ($licenses as $k => $v) {
		$lic_id = $v['license_id'];
		$lic_str = $v['license'];
		$lic_userid = $v['user_id'];
		$lic_status = $v['status'];
		$lic_domains = $v['domains'];
		$lic_ips = $v['ips'];
		$lic_path = $v['path'];
		$lic_type = $v['type'];
		if ($lic_userid) {
			print '<tr><td align="center"><a href="'.$modulelink.'&a=reissue&lic_id='.$lic_id.'"><img src="images/icons/cleanup.png" /></a></td>
					<td align="center">'.$lic_str.'</td>
					<td align="center">'.$lic_type.'</td>
					<td align="center"><a href="clientssummary.php?userid='.$lic_userid.'">'.$lic_userid.'</a></td>
					<td align="center">'.$lic_status.'</td>
					<td align="center">'.$lic_domains.'</td></tr>';
		}
	}
	curl_close($ch);
	print '</table></div></div></div>		

			<!-- Import Licenses -->
			<div id="tab1box" class="tabbox"><div id="tab_content">
			<h3>Import Licenses</h3>
			<p align="center">Click the import button below to import licenses from your WHMCS client area</p>
			<form method="post" action="'.$modulelink.'&a=import" name="configfrm">
			<p align="center"><input type="submit" value="Import" class="button"></p>
			<input type="hidden" name="tab" id="tab" value="" /></form><br />';
	if ($_REQUEST['a'] == "import") {
		print '<p align="center"><strong>Number of licenses imported: '.$imp_count.'</strong></p>';
	}

	print '<h3>All Licenses</h3>
			<div class="tablebg"><table class="datatable" cellspacing="1" cellpadding="3">
			<tr><th>Delete</th><th>Reissue</th><th>License</th><th>Type</th><th>Client</th><th>License Status</th><th>Domains</th></tr>';
	$ch = curl_init();
	$data = wr_dologin($ch, $myemail, $mypass);
	foreach ($licenses as $k => $v) {
		$lic_id = $v['license_id'];
		$lic_str = $v['license'];
		$lic_userid = $v['user_id'];
		$lic_status = $v['status'];
		$lic_domains = $v['domains'];
		$lic_ips = $v['ips'];
		$lic_path = $v['path'];
		$lic_type = $v['type'];
		print '<tr><td align="center"><a href="'.$modulelink.'&a=del&lic_id='.$lic_id.'"><img src="images/icons/delete.png" /></a></td>
				<td align="center"><a href="'.$modulelink.'&a=reissue&lic_id='.$lic_id.'"><img src="images/icons/cleanup.png" /></a></td>
				<td align="center">'.$lic_str.'</td>
				<td align="center">'.$lic_type.'</td>';
		if ($lic_userid) {
			print '<td align="center"><a href="clientssummary.php?userid='.$lic_userid.'">'.$lic_userid.'</a></td>';
		} else {
			print '<td align="center"><strong>unassigned</strong></td>';
		}
		print '<td align="center">'.$lic_status.'</td>
				<td align="center">'.$lic_domains.'</td></tr>';
	}
	curl_close($ch);
	print '</table></div></div></div>

			<!-- Configuration Tab -->
			<div id="tab2box" class="tabbox"><div id="tab_content">
			<h3>Configuration</h3>
			<form method="post" action="'.$modulelink.'&a=savechanges" name="configfrm">
			<table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
			<tr><td class="fieldlabel">WHMCS Email Address</td><td class="fieldarea"><input type="text" name="email" value="'.$myemail.'" size="35" /> The email address you use to login to the WHMCS client area</td></tr>
			<tr><td class="fieldlabel">WHMCS Password</td><td class="fieldarea"><input type="password" name="password" value="'.$mypass.'" size="35" /> The password to login to the WHMCS client area</td></tr>
			</table><p align="center"><input type="submit" value="Save Changes" class="button"></p>
			<input type="hidden" name="tab" id="tab" value="" /></form></div></div></div><br />
			<p align="left"><h5>WHMCS Reseller was written by <a href="mailto:frank@asmallorange.com">Frank Laszlo</a><br />Version: 2.0p1</h5></p>';

}

?>
