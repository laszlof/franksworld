<?php

require_once 'MCAPI.class.php';

function mailchimp_hook_check($vars) {
	// Load config
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
	if (($apikey) && ($clientfield)) {
		$d = select_query("tblcustomfieldsvalues", "value", array("fieldid"=>$clientfield, "relid"=>$vars['userid']));
		if (mysql_num_rows($d)) {
			$r = mysql_fetch_array($d);
			$res = explode("|", $r[0]);
			$listid = $res[0];
			$listname = $res[1];
		}
		$api = new MCAPI($apikey);
		$lst = $api->lists();
		$lists = $lst['data'];
		if (isset($vars['olddata'])) {
			$cfarr = array(
							$vars['olddata']['customfields1'],
							$vars['olddata']['customfields2'],
							$vars['olddata']['customfields3'],
							$vars['olddata']['customfields4'],
							$vars['olddata']['customfields5'],
							$vars['olddata']['customfields6'],
							$vars['olddata']['customfields7'],
							$vars['olddata']['customfields8'],
							$vars['olddata']['customfields9'],
							$vars['olddata']['customfields10']);
			$firstname = $vars['olddata']['firstname'];
			$lastname = $vars['olddata']['lastname'];
			foreach ($lists as $k => $v) {
				if (in_array($v['name'], $cfarr)) {
					$oldlist = array('name'=>$v['name'], 'id'=>$v['id']);
					break;
				}
			}
		} else {
			$firstname = $vars['firstname'];
			$lastname = $vars['lastname'];
		}
		$email = $vars['email'];
		if (($listid != 'none') && ($listid != '')) {
			$merge_vars = array('FNAME'=>$firstname, 'LNAME'=>$lastname);
			$retval = $api->listSubscribe( $listid, $email, $merge_vars, null, False );
			if ($api->errorCode) {
				$desc = $api->errorMessage;
				$userid = $vars['userid'];
				$query = "INSERT INTO tblactivitylog (date, description, user, userid) VALUES(now(), '$desc', 'System', '$userid')";
				full_query($query);
			}
		} else {
			$retval = $api->listUnsubscribe($listid, $email, False, False);
			if ($api->errorCode) {
				$desc = $api->errorMessage;
				$userid = $vars['userid'];
				$query = "INSERT INTO tblactivitylog (date, description, user, userid) VALUES(now(), '$desc', 'System', '$userid')";
				full_query($query);
			}
		}
		if ((isset($oldlist)) && ($oldlist['id'] != $listid)) {
			$retval = $api->listUnsubscribe($oldlist['id'], $email, False, False);
			if ($api->errorCode) {
				$desc = $api->errorMessage;
				$userid = $vars['userid'];
				$query = "INSERT INTO tblactivitylog (date, description, user, userid) VALUES(now(), '$desc', 'System', '$userid')";
				full_query($query);
			}
		}

	}
}

function mailchimp_hook_cron() {
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
	$api = new MCAPI($apikey);
	$lists = $api->lists();
	// Set everyone to unsubscribed
	$d = update_query("tblcustomfieldsvalues", array("value"=>""), array("fieldid"=>$clientfield));
	foreach ($lists['data'] as $k => $v) {
		$listid = $v['id'];
		$listname = $v['name'];
		$listarr = $listid."|".$listname;
		$retval = $api->listMembers($listid, "subscribed", null, null, 15000);
		if ($api->errorCode) {
			$desc = $api->errorMessage;
			$query = "INSERT INTO tblactivitylog (date, description, user, userid) VALUES(now(), '$desc', 'System', '')";
			full_query($query);
		} else {
			foreach ($retval['data'] as $k => $v) {
				$email = $v['email'];
				$data = select_query("tblclients", "id", array("email"=>$email));
				while ($res = mysql_fetch_array($data)) {
					$clientid = $res['id'];
					$cs = select_query("tblcustomfieldsvalues", "value", array("fieldid"=>$clientfield, "relid"=>$clientid));
					if (mysql_num_rows($cs)) {
						update_query("tblcustomfieldsvalues", array("value"=>$listarr), array("fieldid"=>$clientfield, "relid"=>$clientid));
					} else {
						insert_query("tblcustomfieldsvalues", array("fieldid"=>$clientfield, "relid"=>$clientid, "value"=>$listarr));
					}
				}
			}
		}
	}
}

add_hook("ClientAdd",1,"mailchimp_hook_check");
add_hook("ClientEdit",1,"mailchimp_hook_check");
add_hook("DailyCronJob",1,"mailchimp_hook_cron");
?>
