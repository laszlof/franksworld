<?php

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");

global $CONFIG;

function adminipwhitelist_config() {
	$configarray = array(
	"name" => "Admin IP Whitelist",
	"version" => "1.0",
	"author" => "<a href='mailto:frank@asmallorange.com'>Frank Laszlo</a>",
	"language" => "english");
	return $configarray;
}

function adminipwhitelist_activate() {
	$query = array();
	$query[0] = "CREATE TABLE `tbladminwhitelist` (
			`id` int(11) NOT NULL auto_increment,
			`label` varchar(64) NOT NULL,
			`ip` varchar(16) NOT NULL,
			`timestamp` datetime NOT NULL,
			PRIMARY KEY  (`id`));";
	foreach ($query as $q) {
		$r = full_query($q);
	}
}

function adminipwhitelist_deactivate() {

	// Not required for now

}

function adminipwhitelist_upgrade($vars) {

	// Not required for now
}

function adminipwhitelist_output($vars) {
	$modulelink = $vars['modulelink'];
	$version = $vars['version'];
	$cwd = dirname(__FILE__);
	$crontab = realpath($cwd.'/../../../whitelist.php');
	if (isset($_REQUEST['add'])) {
		$label = mysql_real_escape_string($_REQUEST['label']);
		$ip = mysql_real_escape_string($_REQUEST['ip']);
		if (($label == "") || ($ip == "")) {
			$errormsg = 'All fields are required.';
		} else {
			// insert data
			$timestamp = date('Y-m-d H:i:s');
			insert_query("tbladminwhitelist", array("label"=>$label, "ip"=>$ip, "timestamp"=>$timestamp));
			$msg = 'IP Address has been saved to whitelist.';
		}
	} elseif (isset($_REQUEST['del'])) {
		$id = mysql_real_escape_string($_REQUEST['id']);
		delete_query("tbladminwhitelist", array("id"=>$id));
		$msg = 'IP Address has been successfully removed from whitelist.';
	}
	
	$d = select_query("tbladminwhitelist", "*", array());
	$curwhitelist = array();
	while ($res = mysql_fetch_array($d)) {
		$id = $res['id'];
		$curwhitelist[$id] = array("label"=>$res['label'], "ip"=>$res['ip'], "timestamp"=>$res['timestamp']);
	}
	// Print header information
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
	if (isset($msg)) {
		print '<div align="center" class="infobox">'.$msg.'</div>';
	}
	if (isset($errormsg)) {
		print '<div align="center" class="errorbox">'.$errormsg.'</div>';
	}
	print '<div id="tabs"><ul>
			<li id="tab0" class="tab"><a href="javascript:;">Whitelist</a></li>
			</ul></div>
			<!-- Whitelist Tab -->
			<div id="tab0box" class="tabbox">
				<div id="tab_content">
				<div align="center">
					Please add the following line to your crontab<br />
					<input type="text" size="64" value="* * * * * php '.$crontab.'" />
					<br /><br />
					<h3>Add New IP</h3>
					<form action="'.$modulelink.'&add=1" method="POST">
						<table class="form" border="0" cellspacing="2" cellpadding="3">
							<tr>
								<td width="25%" class="fieldlabel">Label:</td>
								<td class="fieldarea"><input type="text" name="label" size="25">(i.e. "Home IP")</td>
							</tr>
							<tr>
								<td class="fieldlabel">IP:</td>
								<td class="fieldarea"><input type="text" name="ip" size="25">(i.e. "127.0.0.1")</td>
							</tr>
							<tr>
								<td colspan="2" class="fieldarea">
									<center>
									<input type="submit" value="Add" />
									<input type="reset" value="Clear" />
									</center>
								</td>
							</tr>
						</table>
					</form><br /> <br />';

	if (isset($curwhitelist)) {
		print '		<div class="tablebg">
						<table class="datatable" cellspacing="1" cellpadding="3">
							<tr><th>ID</th><th>Label</th><th>IP Address</th><th>Date Added</th><th>Delete</th></tr>';
		if (count($curwhitelist)) {
			foreach ($curwhitelist as $k => $v) {
				$id = $k;
				$label = $v['label'];
				$ip = $v['ip'];
				$timestamp = $v['timestamp'];
				print '<tr>
							<td>'.$id.'</td>
							<td>'.$label.'</td>
							<td>'.$ip.'</td>
							<td>'.$timestamp.'</td>
							<td><a href="'.$modulelink.'&del=1&id='.$id.'"><img src="images/icons/delete.png" /></a></td>
						</tr>';
			}
		} else {
			print '<tr><td colspan="4" align="center"><strong>No IPs found in whitelist</strong></td></tr>';
		}
		print '			</table>
					</div>';
	}
	print '		</div>
			</div>
			<div align="right">
				<p align="left"><h5>Admin IP Whitelist was written by <a href="mailto:frank@asmallorange.com">Frank Laszlo</a><br />Version: 1.0</h5></p>
				<p align="left">
					<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
						<input type="hidden" name="cmd" value="_s-xclick">
						<input type="hidden" name="hosted_button_id" value="CR46J49DHX7Z2">
						<h5>Find this addon useful? <input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!"></h5>
						<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
					</form>
				</p>
			</div>
		</div>
	</div>';
}

function adminipwhitelist_sidebar($vars) {

}

?>
