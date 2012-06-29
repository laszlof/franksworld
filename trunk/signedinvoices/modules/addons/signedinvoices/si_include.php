<?php


require_once ROOTDIR.'/dbconnect.php';
require_once ROOTDIR.'/includes/functions.php';

function signedinvoicedata() {
	$data = select_query("mod_signedinvoices", "name, value", array());
	if (mysql_num_rows($data)) {
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
		if ((isset($cert)) && (isset($key))) {
			$status = "success";
			return array('status'=>'success', 'cert'=>$cert, 'key'=>$key, 'keypass'=>$keypass, 'extra'=>$extra);
		} else {
			return array('status'=>'failure', 'message'=>'SIGNEDINVOICES: Missing private key and/or certificate!');
		}
	} else {
		return array('status'=>'failure', 'message'=>'SIGNEDINVOICES: Something went wrong, the mod_signedinvoices table does not contain data!');
	}
}
		
