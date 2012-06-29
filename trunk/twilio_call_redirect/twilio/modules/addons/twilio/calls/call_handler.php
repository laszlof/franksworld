<?php

/**
 * Twilio Call-Redirect WHMCS module
 *
 * @author Frank Laszlo <frank@franksworld.org>
 * @version 1.0
 * @package twilio-whmcs
 */

require_once '../../../../dbconnect.php';
require_once '../../../../includes/functions.php';
require_once '../libs/Twilio.php';

global $CONFIG;

$whmcsurl = ($CONFIG['SystemSSLURL'] ? $CONFIG['SystemSSLURL'] : $CONFIG['SystemURL']);

// Get configured numbers
$numbers = array();
$d = select_query('mod_twilio_numbers', '*', array(), 'order','ASC');
while ($res = mysql_fetch_assoc($d)) {
	$numbers[] = array(
					'id'		=> $res['id'],
					'number'	=> $res['number'],
					'timeout'	=> $res['timeout'],
					'order'		=> $res['order']);
}
// Get config values
$config = array();
$d = select_query('mod_twilio_config', '*', array());
while ($res = mysql_fetch_assoc($d)) {
	$setting = $res['setting'];
	$value = $res['val'];
	$config[$setting] = $value;
}

$resp = new Services_Twilio_Twiml();

if (!isset($_REQUEST['step'])) {
	$resp = new Services_Twilio_Twiml();
	if ($config['intro_mode'] == 'text') {
		$resp->say($config['intro_text']);
	} else {
		$resp->play('get_audio.php?f=intro_file');
	}
	$gather = $resp->gather(array('timeout'=>$config['gather_timeout'], 'numDigits'=>'1', 'action'=>'call_handler.php?step=1'));
	if ($config['invalid_request_mode'] == 'text') {
		$resp->say($config['invalid_request_text']);
	} else {
		$resp->play('get_audio.php?f=invalid_request_file');
	}
	$resp->redirect('call_handler.php');
} elseif ($_REQUEST['step'] == '1') {
	if ($_REQUEST['Digits'] == '1') {
		$_SESSION['type'] = 'ticket';
		if ($config['step1_ticket_mode'] == 'text') {
			$resp->say($config['step1_ticket_text']);
		} else {
			$resp->play('get_audio.php?f=step1_ticket_file');
		}
		$gather = $resp->gather(array('timeout'=>$config['gather_timeout'], 'finishOnKey'=>'#', 'action'=>'call_handler.php?step=2'));
		if ($config['invalid_request_mode'] == 'text') {
			$resp->say($config['invalid_request_text']);
		} else {
			$resp->play('get_audio.php?f=invalid_request_file');
		}
		$resp->redirect('call_handler.php');
	} elseif ($_REQUEST['Digits'] == '2') {
		$_SESSION['type'] = 'client';
		if ($config['step1_client_mode'] == 'text') {
			$resp->say($config['step1_client_text']);
		} else {
			$resp->play('get_audio.php?f=step1_client_file');
		}
		$gather = $resp->gather(array('timeout'=>$config['gather_timeout'], 'finishOnKey'=>'#', 'action'=>'call_handler.php?step=2'));
		if ($config['invalid_request_mode'] == 'text') {
			$resp->say($config['invalid_request_text']);
		} else {
			$resp->play('get_audio.php?f=invalid_request_file');
		}
		$resp->redirect('call_handler.php');
	} else {
		if ($config['invalid_request_mode'] == 'text') {
			$resp->say($config['invalid_request_text']);
		} else {
			$resp->play('get_audio.php?f=invalid_request_file');
		}
		$resp->redirect('call_handler.php');
	}
} elseif ($_REQUEST['step'] == '2') {
	if (($_SESSION['type'] == 'client') || ($_REQUEST['type'] == 'client')) {
		$cid = $_REQUEST['Digits'];
		$d = select_query('tblclients', 'id', array('id'=>$cid));
		if (!mysql_num_rows($d)) {
			if ($config['step2_client_sorry_mode'] == 'text') {
				$resp->say($config['step2_client_sorry_text']);
			} else {
				$resp->play('get_audio.php?f=step2_client_sorry_file');
			}
			$resp->redirect('call_handler.php');
		} else {
			if ($config['step2_client_mode'] == 'text') {
				$say = convertText($config['step2_client_text'], 'client', $cid);
				$resp->say($say);
			} else {
				$resp->play('get_audio.php?f=step2_client_file');
			}
			ob_start();
			foreach ($numbers as $key => $number) {
				print_r($number);
				$resp->dial($number['number'], array('timeout'=>$number['timeout']));
			}
			$out = ob_get_clean();
			$fp = fopen('/tmp/twilio.out', 'a');
			fwrite($fp, $out);
			// Send data to node.js to pass along to WHMCS admins
			$data = array('type'=>'client', 'clientid'=>$cid);
			sendData($data, $config['server_port']);

			// Just in case no one answers, forward to VM
			if ($config['voicemail_mode'] == 'text') {
				$message = urlencode($config['voicemail_text']);
			} else {
				$message = $whmcsurl.'/modules/addons/twilio/calls/get_audio.php?f=voicemail_file';
			}
			$resp->redirect('http://twimlets.com/voicemail?Email='.$config['voicemail_email'].'&Message='.$message);
		}
	} elseif (($_SESSION['type'] == 'ticket') || ($_REQUEST['type'] == 'ticket')) {
		$tid = $_REQUEST['Digits'];
		$d = select_query('tbltickets', 'id', array('tid'=>$tid));
		if (!mysql_num_rows($d)) {
			if ($config['step2_ticket_sorry_mode'] == 'text') {
				$resp->say($config['step2_ticket_sorry_text']);
			} else {
				$resp->play('get_audio.php?f=step2_ticket_sorry_file');
			}
			$resp->redirect('call_handler.php');
		} else {
			if ($config['step2_ticket_mode'] == 'text') {
				$say = convertText($config['step2_ticket_text'], 'ticket', $tid);
				$resp->say($say);
			} else {
				$resp->play('get_audio.php?f=step2_ticket_file');
			}
			foreach ($numbers as $key => $number) {
				$resp->dial($number['number'], array('timeout'=>$number['timeout']));
			}
			
			// Send data to node.js to pass along to WHMCS admins
			$ticket = mysql_fetch_assoc($d);
			$data = array('type'=>'ticket', 'ticketid'=>$ticket['id']);
			sendData($data, $config['server_port']);
			
			// Just in case no one answers, forward to VM
			$resp->redirect('http://twimlets.com/voicemail?Email='.$config['voicemail_email']);
		}
	} else {
		if ($config['invalid_request_mode'] == 'text') {
			$resp->say($config['invalid_request_text']);
		} else {
			$resp->play('get_audio.php?f=invalid_request_file');
		}
		$resp->redirect('call_handler.php');
	}
}

print $resp;

function sendData($dataArr, $port) {
	$data = json_encode($dataArr);
	$fp = fsockopen('localhost', $port);
	fwrite($fp, $data);
	fclose($fp);
}

function convertText($input, $type, $id) {
	if ($type == 'client') {
		$d = select_query('tblclients', 'firstname, lastname, companyname, email, address1, address2, city, state, postcode, phonenumber, status', array('id'=>$id));
		$result = mysql_fetch_assoc($d);
		foreach (array_keys($result) as $key) {
			switch ($key) {
				case 'firstname':
					$input = str_replace('%FIRSTNAME%', $result['firstname'], $input);
					break;
				case 'lastname':
					$input = str_replace('%LASTNAME%', $result['lastname'], $input);
					break;
				case 'companyname':
					$input = str_replace('%COMPANY%', $result['companyname'], $input);
					break;
				case 'email':
					$input = str_replace('%EMAIL%', $result['email'], $input);
					break;
				case 'address1':
					$input = str_replace('%ADDRESS1%', $result['address1'], $input);
					break;
				case 'address2':
					$input = str_replace('%ADDRESS2%', $result['address2'], $input);
					break;
				case 'city':
					$input = str_replace('%CITY%', $result['city'], $input);
					break;
				case 'state':
					$input = str_replace('%STATE%', $result['state'], $input);
					break;
				case 'postcode':
					$input = str_replace('%POSTCODE%', $result['postcode'], $input);
					break;
				case 'phonenumber':
					$input = str_replace('%PHONE%', $result['phonenumber'], $input);
					break;
				case 'status':
					$input = str_replace('%STATUS%', $result['status'], $input);
					break;
			}
		}
		return $input;
	} elseif ($type == 'ticket') {
		$query = "SELECT 
						c.firstname AS firstname, c.lastname AS lastname, c.companyname AS companyname, c.email AS email, 
						c.address1 AS address1, c.address2 AS address2, c.city AS city, c.state AS state, c.postcode AS postcode,
						c.phonenumber AS phonenumber, c.status AS status, t.title AS ticket_title, t.urgency AS ticket_urgency, t.status AS ticket_status
					FROM
						tblclients c, tbltickets t
					WHERE
						t.tid = $id AND
						t.userid = c.id";
		$d = full_query($query);
		$result = mysql_fetch_assoc($d);
		foreach (array_keys($result) as $key) {
			switch ($key) {
				case 'firstname':
					$input = str_replace('%FIRSTNAME%', $result['firstname'], $input);
					break;
				case 'lastname':
					$input = str_replace('%LASTNAME%', $result['lastname'], $input);
					break;
				case 'companyname':
					$input = str_replace('%COMPANY%', $result['companyname'], $input);
					break;
				case 'email':
					$input = str_replace('%EMAIL%', $result['email'], $input);
					break;
				case 'address1':
					$input = str_replace('%ADDRESS1%', $result['address1'], $input);
					break;
				case 'address2':
					$input = str_replace('%ADDRESS2%', $result['address2'], $input);
					break;
				case 'city':
					$input = str_replace('%CITY%', $result['city'], $input);
					break;
				case 'state':
					$input = str_replace('%STATE%', $result['state'], $input);
					break;
				case 'postcode':
					$input = str_replace('%POSTCODE%', $result['postcode'], $input);
					break;
				case 'phonenumber':
					$input = str_replace('%PHONE%', $result['phonenumber'], $input);
					break;
				case 'status':
					$input = str_replace('%STATUS%', $result['status'], $input);
					break;
				case 'ticket_title':
					$input = str_replace('%TICKET_TITLE%', $result['ticket_title'], $input);
					break;
				case 'ticket_urgency':
					 $input = str_replace('%TICKET_URGENCY%', $result['ticket_urgency'], $input);
					 break;
				case 'ticket_status':
					$input = str_replace('%TICKET_STATUS%', $result['ticket_status'], $input);
					break;
			}
		}
		return $input;
	}
}
?>