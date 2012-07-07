<?php

global $CONFIG;

if (!mysql_num_rows(mysql_query("SHOW TABLES LIKE 'serveruptime'"))) {
	if (!$_GET['install']) {
		print "
				<p><strong>Not Yet Installed</strong></p>
				<p>This addon will display your server(s) uptime in the client area.</p>
				<p>To install it, click on the button below.</p>
				<p><input type=\"button\" value=\"Install Server Uptime\" onclick=\"window.location='$modulelink&install=true'\"></p>";
	} else {
		$query = array();
		$query[0] = "CREATE TABLE IF NOT EXISTS `serveruptime` (
					`srv_id` int(11) NOT NULL,
					`ttluptime` varchar(10) NOT NULL,
					`muptime` varchar(10) NOT NULL,
					PRIMARY KEY  (`srv_id`));";
		$query[1] = "CREATE TABLE IF NOT EXISTS `serveruptimedata` (
					`id` int(11) NOT NULL auto_increment,
					`srv_id` int(11) NOT NULL,
					`resp` varchar(10) NOT NULL,
					`timestamp` datetime NOT NULL,
					PRIMARY KEY  (`id`));";
		$query[2] = "CREATE TABLE IF NOT EXISTS `serveruptimeconf` (
					`id` int(11) NOT NULL auto_increment,
					`name` varchar(64) NOT NULL,
					`value` varchar(1024) NULL,
					PRIMARY KEY  (`id`));";
		$query[3] = "INSERT INTO `serveruptimeconf` (`id`, `name`, `value`) VALUES
					(1, 'groups', ''),
					(2, 'timeout', '10')";
		foreach ($query as $q) {
			$r = mysql_query($q);
		}
		header("Location: $modulelink");
		exit;
	}
} else {
	$conf_data = select_query("serveruptimeconf", "*", array());
	while ($res = mysql_fetch_array($conf_data)) {
		switch ($res[1]) {
			case "groups":
				$mygroups = $res[2];
				break;
			case "timeout":
				$mytimeout = $res[2];
				break;
		}
	}
	if (isset($_REQUEST['a'])) {
		switch ($_REQUEST['a']) {
			case "savechanges":
				$timeout = $_POST['timeout'];
				$groups = implode(",", $_POST['groups']);
				update_query("serveruptimeconf", array("value"=>"$timeout"), "name='timeout'");
				update_query("serveruptimeconf", array("value"=>"$groups"), "name='groups'");
				$msg .= 'Configuration changes have been saved';
				$mygroups = $groups;
				$mytimeout = $_POST['timeout'];
				break;
		}
	}
	$groups = array();
	$group_data = select_query("tblservergroups", "id, name", array());
	while ($r = mysql_fetch_array($group_data)) {
		$gid = $r[0];
		$gname = $r[1];
		$groups[] = array("id"=>$gid, "name"=>$gname);
	}
	print '<div id="content_padded">';
	if ($msg) {
		print '<div class="errorbox">'.$msg.'</div>';
	}

	print '<div id="tab_content" width="200">
			<h3>Instructions</h3>
			<p align="left">
			<h4>Add the following crontab and select your options below</h4>
			<input type="text" size="150" value="*/5 *   *   *   *   /usr/bin/wget -O /dev/null '.$CONFIG['SystemURL'].'/modules/admin/serveruptime/cron.php >> /dev/null 2>&1"><br />
			<h5>(This cronjob entry can be adjusted to run more/less often, but as a general rule of thumb you shouldn\'t make it lower than (number_of_servers * curl_timeout)).</h5>
			</p></div>';
	print '<div id="box" class=tabbox"><div id="tab_content">
			<h3>Configuration</h3>
			<form method="post" action="'.$modulelink.'&a=savechanges" name="configfrm">
			<table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
			<tr><td class="fieldlabel">cURL Timeout</td><td class="fieldarea"><input type="text" name="timeout" size="5" value="'.$mytimeout.'" /></td><td>Timeout for each	HTTP check</td></tr>
			<tr><td class="fieldlabel">Server Groups</td><td class="fieldarea" valign="middle"><select multiple name="groups[]" size="4">';
	foreach ($groups as $k => $v) {
		$gid = $v['id'];
		$gname = $v['name'];
		if (preg_match("/$gid/", $mygroups)) {
			print '<option value="'.$gid.'" selected>'.$gname.'</option>';
		} else {
			print '<option value="'.$gid.'">'.$gname.'</option';
		}
	}
	print '</select></td><td align="left">Groups to monitor uptime</td></tr></table>
			<p align="center"><input type="submit" value="Save Changes" class="button"></p>
			</form></div></div></div><br />';
	print "<p align=\"left\"><h5>Server Uptime was written by <a href=\"mailto:frank@asmallorange.com\">Frank Laszlo</a><br />Version: 1.0</h5></p>";
}

?>
