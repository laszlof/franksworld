<?php

require_once ROOTDIR.'/includes/cpanel_xmlapi.php';


function ga_getUserDomains($xmlapi, $cpuser) {
	$domains = array();
	
	// Get Primary Domain
	$primary = $xmlapi->accountsummary($cpuser);
	$domains[] = (string)$primary->acct->domain;

	// Get Addon Domains
	$addons = $xmlapi->api2_query($cpuser, 'Park', 'listaddondomains');
	foreach ($addons->data as $key => $val) {
		$domains[] = (string)$val->domain;
	}

	// Get Parked Domains
	$parked = $xmlapi->api2_query($cpuser, 'Park', 'listparkeddomains');
	foreach ($parked->data as $key => $val) {
		$domains[] = (string)$val->domain;
	}

	return $domains;

}

function googleapps_show($vars) {
	global $smarty;
	$lang_arr = $smarty->get_template_vars('LANG');
	$module = $vars['modulename'];
	$cpuser = $vars['username'];
	$srv_host = $vars['serverip'];
	$srv_user = $vars['serverusername'];
	$srv_pass = $vars['serverpassword'];
	$srv_hash = $vars['serveraccesshash'];
	$srv_secure = $vars['serversecure'];
	$status = $vars['rawstatus'];
	if (($module != 'cpanel') || ($status != 'active')) {
		$isvalid = 0;
	} else {
		$isvalid = 1;
	}

	if ($isvalid) {
		// Setup API connection
		$xmlapi = new xmlapi($srv_host);
		if ($srv_secure) {
			$srv_port = 2087;
		} else {
			$srv_port = 2086;
		}
		if (!$srv_hash) {
			$xmlapi->password_auth($srv_user, $srv_pass);
		} else {
			$xmlapi->hash_auth($srv_user, preg_replace("'(\r|\n)'","", $srv_hash));
		}
		$xmlapi->set_port($srv_port);

		$accounts = array();
		$accounts[] = $cpuser;
		$domains = ga_getUserDomains($xmlapi, $cpuser);

		// Check if reseller
		$reseller = $xmlapi->resellerstats($cpuser);
		if (isset($reseller->result->accts)) {
			// This is a reseller
			foreach ($reseller->result->accts as $key => $val) {
				if ($val->user != $cpuser) {
					$accounts[] = (string)$val->user;
				}
			}
		}
		
		// handle event to populate domain select
		if (isset($_REQUEST['domlookup'])) {
			$curcpuser = $_REQUEST['user'];
			$res = ga_getUserDomains($xmlapi, $curcpuser);
			print json_encode($res);
			exit(1);
		}

		// handle event to change mode between local and google
		if (isset($_REQUEST['googleit'])) {
			$errorcount = 0;
			$config = $_REQUEST['config'];
			$cur_cpuser = $_REQUEST['user'];
			$cur_domain = $_REQUEST['domain'];
			$cur_domain_dot = $cur_domain.".";
			$doms_for_user = ga_getUserDomains($xmlapi, $cur_cpuser);

			// check if user actually owns domain, and user
			if ((in_array($cur_domain, $doms_for_user)) && (in_array($cur_cpuser, $accounts))) {
				$rmRec = array();
				$zdata = $xmlapi->dumpzone($cur_domain);

				// step through zone records and find MX, google CNAMEs, mail. CNAME, and SPF record
				foreach($zdata->result->record as $key => $val) {
					if ($val->type == 'MX') {
						$rmRec[] = array(
										'line' => (string)$val->Line,
										'name'	=> (string)$val->exchange);
					} elseif (($val->type == 'CNAME') && (($val->name == 'mail.'.$cur_domain_dot) ||
														($val->name == 'calendar.'.$cur_domain_dot) ||
														($val->name == 'start.'.$cur_domain_dot) ||
														($val->name == 'sites.'.$cur_domain_dot) ||
														($val->name == 'docs.'.$cur_domain_dot))) {
						$rmRec[] = array(
										'line' => (string)$val->Line,
										'name' => (string)$val->name);

					} elseif (($val->type == 'TXT') && (preg_match('/v=spf/', (string)$val->txtdata))) {
						$rmRec[] = array(
										'line' => (string)$val->Line,
										'name' => (string)$val->name);
					}
				}

				// we must reverse sort them so the line numbers do not when they are removed
				rsort($rmRec);
				$gmail_smtp = array(
								'aspmx.l.google.com' => '0', 'alt1.aspmx.l.google.com' => '10',
								'alt2.aspmx.l.google.com' => '10', 'aspmx2.googlemail.com' => '20',
								'aspmx3.googlemail.com' => '20', 'aspmx4.googlemail.com' => '20',
								'aspmx5.googlemail.com' => '20');
				$ghshost = "ghs.google.com";
				$cnlist = array("calendar", "start", "sites", "docs");

				// delete lines in zone file found in previous statements
				foreach ($rmRec as $del) {
					$rmline = $del['line'];
					$rmname = $del['name'];
					$rmdata = $xmlapi->removezonerecord($cur_domain, $rmline);
					if ($rmdata->result->status == 0) {
						$errorcount++;
						$error .= "Removal of $rmname failed. -- ".$rmdata->result->statusmsg."<br />";
					}
				}
				$mxname = "mail.$cur_domain.";
				if ($config == 'google') {
					$cname = $ghshost;
				} else {
					$cname = $cur_domain;
				}
				$cnameArr = array('name'=>$mxname, 'type'=>'CNAME', 'cname'=>$cname);

				// Add main "mail." CNAME record back as google or local
				$cnamedata = $xmlapi->addzonerecord($cur_domain, $cnameArr);
				if ($cnamedata->result->status == 0) {
					$errorcount++;
					$error .= "Failed to add $mxname. -- ".$cnamedata->result->statusmsg."<br />";
				}
				if ($config == 'google') {

					// Add google specific CNAME's
					foreach ($cnlist as $cur_cname) {
						$cn = $cur_cname.'.'.$cur_domain.'.';
						$addArr = array('name' => $cn, 'type'=>'CNAME', 'cname'=>$ghshost);
						$adddata = $xmlapi->addzonerecord($cur_domain, $addArr);
						if ($adddata->result->status == 0) {
							$errorcount++;
							$error .= "Failed to add $cn -- ".$adddata->result->statusmsg."<br />";
						}
					}

					// Add google SPF
					$txtdata = '"v=spf1 include:'.$cur_domain.' include:_spf.google.com ~all"';
					$spfArr = array('name'=>$cur_domain_dot, 'type'=>'TXT', 'txtdata'=>$txtdata);
					$spfdata = $xmlapi->addzonerecord($cur_domain, $spfArr);
					if ($spfdata->result->status == 0) {
						$errorcount++;
						$error .= "Failed to add SPF record -- ".$spfdata->result->statusmsg."<br />";
					}
				}

				if ($config == 'google') {
					$mx = $gmail_smtp;
				} else {
					$mx = array($cur_domain => 0);
				}

				// Add MX records
				foreach ($mx as $key => $val) {
					$exchange = $key;
					$pref = $val;
					$addArr = array('name'=>$cur_domain_dot, 'type'=>'MX', 'exchange'=>$exchange, 'preference'=>$pref);
					$adddata = $xmlapi->addzonerecord($cur_domain, $addArr);
					if ($adddata->result->status == 0) {
						$errorcount++;
						$error .= "Failed to add $exchange MX record -- ".$adddata->result->statusmsg."<br />";
					}
				}
				if ($config == 'google') {
					$mxcheck = 'remote';
				} else {
					$mxcheck = 'local';
				}

				// Set mxcheck to local or remote
				$sdataArr = array('domain'=>$cur_domain, 'mxcheck'=>$mxcheck);
				$sdata = $xmlapi->api2_query($cpuser, 'Email', 'setalwaysaccept', $sdataArr);
				if ($sdata->data->status == 0) {
					$errorcount++;
					$error .= "Could not set MX accept status to $mxcheck -- ".$sdata->data->statusmsg."<br />";
				}
				if (!$errorcount) {
					$fullmsg = $lang_arr['googleapps_noerror'].'<br />';
				} else {
					$fullmsg = $lang_arr['googleapps_error'].'<br />';
				}
			} else {
				$fullmsg = $lang_arr['googleapps_error'].'<br />';
				$error .= $lang_arr['googleapps_notowned'].'<br />';
			}
			$resultsArr = array(
							'msg' => $fullmsg,
							'error' => $error);
			print json_encode($resultsArr);
			exit(1);
		}
	}


	$smarty->assign('googleapps_isvalid', $isvalid);
	$smarty->assign('googleapps_domains', $domains);
	$smarty->assign('googleapps_users', $accounts);
}

add_hook("ClientAreaPage", 1, "googleapps_show");

	?>
