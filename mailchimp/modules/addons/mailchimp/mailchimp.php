<?php

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");


require_once 'MCAPI.class.php';

function mailchimp_config() {
	$configarray = array(
	"name" => "MailChimp",
	"version" => "1.0",
	"author" => "Frank Laszlo <frank@asmallorange.com>",
	"language" => "english",
	"fields" => array(),
	);
	return $configarray;
}

function mailchimp_activate() {
	$query = array();
	$query[0] = "CREATE TABLE `tblmailchimpconf` (
			`id` int(11) NOT NULL auto_increment,
			`name` varchar(32) NOT NULL,
			`value` varchar(1024) default NULL,
			PRIMARY KEY  (`id`));";
	$query[1] = "INSERT INTO `tblmailchimpconf` (`id`, `name`, `value`) VALUES
			(1, 'apikey', NULL),
			(3, 'clientfield', NULL)";
	foreach ($query as $q) {
		$r = full_query($q);
	}

}

function mailchimp_deactivate() {

}

function mailchimp_upgrade($vars) {

}

function mailchimp_output($vars) {

	$modulelink = $vars['modulelink'];
	$data = select_query("tblmailchimpconf", "name, value", array());
	while ($r = mysql_fetch_array($data)) {
		switch ($r['name']) {
			case "apikey":
				$apikey = $r['value'];
				break;
			case "clientfield":
				$clientfield = $r['value'];
				break;
		}
	}
	if (isset($_REQUEST['_save'])) {
		$apikey = $_POST['apikey'];
		$clientfield = $_POST['clientfield'];
		update_query("tblmailchimpconf", array("value"=>$apikey), "name='apikey'");
		update_query("tblmailchimpconf", array("value"=>$clientfield), "name='clientfield'");

		$msg = "Configuration changes have been saved.";
	}

	$d = select_query("tblcustomfields", "id, fieldname, description", array("type"=>"client"));
	$allclientfields = array();
	while($r = mysql_fetch_array($d)) {
		$allclientfields[] = array(
								'fieldname' => $r['fieldname'],
								'id' => $r['id'],
								'description' => $r['description']);
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
		print '<div class="infobox">'.$msg.'</div>';
	}
	if ($error) {
		print '<div class="errorbox">'.$error.'</div>';
	}
	print '<div id="tabs"><ul>
			<li id="tab0" class="tab"><a href="javascript:;">Configuration</a></li>
			</ul></div>
			<div id="tab0box" class="tabbox"><div id="tab_content">
			<h3>Configuration</h3>
			<form method="POST" action="'.$modulelink.'&_save=1" name="configfrm">
				<table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
				<tr><td class="fieldlabel">MailChimp API Key</td><td class="fieldarea"><input type="text" name="apikey" value="'.$apikey.'" size="35" /> API Key from MailChimp</td></tr>
			<tr><td class="fieldlabel">Client Field</td><td class="fieldarea"><select name="clientfield">';
	foreach ($allclientfields as $k => $v) {
		$cfieldname = $v['fieldname'];
		$cfielddesc = $v['description'];
		$cfieldid = $v['id'];
		if ($cfieldid == $clientfield) {
			print '<option value="'.$cfieldid.'" selected>'.$cfieldname.' - '.$cfielddesc.'</option>';
		} else {
			print '<option value="'.$cfieldid.'">'.$cfieldname.' - '.$cfielddesc.'</option>';
		}
	}
	print '</select> Client field associated with the subscription</td></tr>
			</table><p align="center"><input type="submit" value="Save Changes" class="button"></p>
			<input type="hidden" name="tab" id="tab" value="" /></form></div></div></div><br />
			<p align="left"><h5>MailChimp WHMCS Addon was written by <a href="mailto:frank@asmallorange.com">Frank Laszlo</a><br />Version: 1.0</h5></p>';

}

function mailchimp_sidebar($vars) {

}

?>
