<?php

/* WHMCS Time Tracker v1.0.0
 * Author: Frank Laszlo
 * License: see MITLICENSE.txt
*/

include("dbconnect.php");
include("includes/functions.php");
$xml = new SimpleXMLElement('<root/>');

header ("Content-Type:text/xml"); 

if ((!isset($_REQUEST['username'])) || (!isset($_REQUEST['password']))) {
	$res = array('status'=>'failure', 'data'=>'Missing username or password');
	array_to_xml($res, $xml);
	print $xml->asXML();
	exit(1);
}

$username = $_REQUEST['username'];
$password = $_REQUEST['password'];

$r = checkUPW($username, $password);
if ($r < 0) {
	$res = array('status'=>'failure', 'data'=>'Invalid username/password');
	array_to_xml($res, $xml);
	print $xml->asXML();
	exit(1);
} else {
	$adminid = $r;
}

switch ($_REQUEST['action']) {
	case "getclients":
		$xmlout = '<?xml version="1.0"?><root><status>success</status><data>';
		$d = full_query("SELECT id, firstname, lastname FROM tblclients WHERE `status`='Active' ORDER BY lastname ASC");
		while ($r = mysql_fetch_array($d)) {
			$cid = $r[0];
			$fname = $r[1];
			$lname = $r[2];
			$xmlout .= '<client><id>'.$cid.'</id><fname>'.$fname.'</fname><lname>'.$lname.'</lname></client>';

		}
		$xmlout .= '</data></root>';
		print $xmlout;
		break;
	case "submittime":
		$client = $_REQUEST['cid'];
		$rate = number_format((float)$_REQUEST['rate'], 2);
		$hours = number_format((float)$_REQUEST['hours'], 2);
		$notes = $_REQUEST['notes'];
		$desc = $notes.' - '.$hours.' Hours @ '.$rate.'/Hour';
		$amount = $rate*$hours;
		$postfields = array(
						'clientid'		=>$client,
						'description'	=> $desc,
						'amount'		=>$amount,
						'hours'			=> $hours,
						'invoiceaction'	=> 'noinvoice');
		$d = localApi('addbillableitem', $postfields, $adminid);
		if ($d['result'] == 'success') {
			$ret = array('status'=>'success', 'id'=>$d['billableid']);
		} else {
			$ret = array('status'=>'failure', 'data'=>$d['message']);
		}
		array_to_xml($ret, $xml);
		print $xml->asXML();
		break;
}

function checkUPW($username, $password) {
	// Check if password is good, and get ID for further API calls.
	$d = select_query('tbladmins', 'id', array('username'=>$username, 'password'=>$password));
	if (!mysql_num_rows($d)) {
		return -1;
	} else {        
		$resp = mysql_fetch_array($d);
		return $resp[0];
	}
}

function array_to_xml($arr_in, &$xml_out) {
	foreach($arr_in as $key => $value) {
		if(is_array($value)) {
			if(!is_numeric($key)){
				$subnode = $xml_out->addChild("$key");
				array_to_xml($value, $subnode);
			}
			else{
				array_to_xml($value, $xml_out);
			}
		}
		else {
			$xml_out->addChild("$key","$value");
		}
	}
}
?>
	
