<?php

/**
 * Twilio Call-Redirect WHMCS module
 *
 * @author Frank Laszlo <frank@franksworld.org>
 * @version 1.0
 * @package twilio-whmcs
 */

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");


function twilio_config() {
	$configarray = array(
			"name" => "Twilio Call-Redirect",
			"description" => "This module is designed to redirect you to a client page when receiving a call via Twilio",
			"version" => "1.0",
			"author" => "<a href='http://www.franksworld.org/'>Frank Laszlo</a>",
			"language" => "english");
    return $configarray;
}

function twilio_activate() {
	$query[0] = "CREATE TABLE IF NOT EXISTS `mod_twilio_numbers` (
					`id` int(11) NOT NULL auto_increment,
					`number` varchar(32) NOT NULL,
					`timeout` int(11) NOT NULL,
					`order` int(11) NOT NULL,
					PRIMARY KEY  (`id`))";
	$query[1] = "CREATE TABLE IF NOT EXISTS `mod_twilio_config` (
					`id` INT NOT NULL auto_increment,
					`setting` VARCHAR( 64 ) NOT NULL,
					`val` TEXT default NULL,
					PRIMARY KEY (`id`))";
	$query[2] = "INSERT INTO `mod_twilio_config` (`id`, `setting`, `val`) VALUES
					(1, 'intro_mode', 'text'),
					(2, 'intro_text', 'Please press 1 to speak with a representative regarding an existing ticket, press 2 for all other inquiries.'),
					(3, 'intro_file', ''),
					(4, 'step1_client_mode', 'text'),
					(5, 'step1_client_text', 'Please enter your client ID number, followed by the pound sign.'),
					(6, 'step1_client_file', ''),
					(7, 'step1_ticket_mode', 'text'),
					(8, 'step1_ticket_text', 'Please enter your ticket ID number, followed by the pound sign.'),
					(9, 'step1_ticket_file', ''),
					(10, 'step2_client_mode', 'text'),
					(11, 'step2_client_text', 'Hello, %FIRSTNAME% %LASTNAME%. You will be connected with one of our operators shortly.'),
					(12, 'step2_client_file', ''),
					(13, 'step2_client_sorry_mode', 'text'),
					(14, 'step2_client_sorry_text', 'Sorry, I could not locate your account.'),
					(15, 'step2_client_sorry_file', ''),
					(16, 'step2_ticket_mode', 'text'),
					(17, 'step2_ticket_text', 'Hello, %FIRSTNAME% %LASTNAME%. You will be connected with one of our operators shortly.'),
					(18, 'step2_ticket_file', ''),
					(19, 'step2_ticket_sorry_mode', 'text'),
					(20, 'step2_ticket_sorry_text', 'Sorry, I could not locate the ticket you requested.'),
					(21, 'step2_ticket_sorry_file', ''),
					(22, 'invalid_request_mode', 'text'),
					(23, 'invalid_request_text', 'Sorry, I did not understand your request.'),
					(24, 'invalid_request_file', ''),
					(25, 'voicemail_email', ''),
					(26, 'voicemail_mode', 'text'),
					(27, 'voicemail_text', 'Please leave a message after the beep.'),
					(28, 'voicemail_file', ''),
					(29, 'gather_timeout', '30'),
					(30, 'client_port', '9090'),
					(31, 'server_port', '9999');";
	foreach ($query as $q) {
		full_query($q);
	}
	return array('status'=>'success');
}

function twilio_output($vars) {
	$modulelink = $vars['modulelink'];
	global $attachments_dir;

	if (isset($_POST)) {
		switch ($_POST['_a']) {
			case "modconfig":
				$POST = $_POST;
				unset($POST['token']);
				unset($POST['_a']);
				foreach ($POST as $keyname => $value) {
					$currentval = get_query_val('mod_twilio_config', 'val', 'setting="'.$keyname.'"');
					if ($currentval != $value) {
						update_query('mod_twilio_config', array('val'=>$value), array('setting'=>$keyname));
					}
				}
				$msg = 'The configuration settings have been successfully saved.';
				break;
			case "modnumbers":
				foreach ($_POST['numberid'] as $nid) {
					$number = $_POST['number'][$nid];
					$order = $_POST['order'][$nid];
					$timeout = $_POST['timeout'][$nid];
					$delete = $_POST['delete'][$nid];
					if ($nid != 'NEW') {
						if ($delete == '1') {
							delete_query('mod_twilio_numbers', array('id'=>$nid));
						} else {
							update_query('mod_twilio_numbers', array('number'=>$number, 'timeout'=>$timeout, 'order'=>$order), array('id'=>$nid));
						}
					} else {
						if ($number != '') {
							insert_query('mod_twilio_numbers', array('number'=>$number, 'timeout'=>$timeout, 'order'=>$order));
						}
					}
				}
				$msg = 'Your numbers have been successfully modified.';
				break;
			case "uploadaudio":
				if (isset($_FILES)) {
					$destdir = $attachments_dir.'/twilio';
					if (!file_exists($destdir)) {
						mkdir($destdir);
					}
					foreach ($_FILES as $keyname => $val) {
						if ($val['name'] != '') {
							$curerr = 0;
							// Check file size
							if ((int)$val['size'] > 2048000) {
								$error .= '<br />'.$val['name'].' is too large.';
								$curerr = 1;
							// Check file type
							} elseif (($val['type'] != 'audio/mp3') && ($val['type'] != 'audio/mpeg')) {
								$error .= '<br />'.$val['name'].' is not a valid MP3 file.';
								$curerr = 1;
							} else {
								// File is good
								$file = $destdir.'/'.$keyname.'.mp3';
								if (file_exists($file)) {
									unlink($file);
								}
								if (move_uploaded_file($val['tmp_name'], $file)) {
									update_query('mod_twilio_config', array('val'=>$file), array('setting'=>$keyname));
								} else {
									$error .= '<br />There was an error uploading '.$val['name'].'. Please check your error logs.';
									$curerr = 1;
								}
							}
							if (!$curerr) {
								$msg .= '<br />'.$val['name'].' has been successfully uploaded.';
							}
						}
					}
				}
				break;
		}
	}
	
	
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
	// Return to last page
	if (isset($_REQUEST['last'])) {
		$last = $_REQUEST['last'];
	} else {
		$last = '0';
	}
	
	// Get server.js daemon status
	$timeout = '5.00';
	if ($fp = fsockopen($_SERVER['HTTP_HOST'], $config['client_port'], $errno, $errstr, (float)$timeout)) {
		$client_status = '<em>Current Status:</em> <span style="color: green; font-weight: bold;">Online</span>';
	} else {
		$client_status = '<em>Current Status:</em> <span style="color: red; font-weight: bold;">Unreachable</span>';
	}
	if ($fp = fsockopen('localhost', $config['server_port'], $errno, $errstr, (float)$timeout)) {
		$server_status = '<em>Current Status:</em> <span style="color: green; font-weight: bold;">Online</span>';
	} else {
		$server_status = '<em>Current Status:</em> <span style="color: red; font-weight: bold;">Online</span>';
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

				selectedTab = "tab'.$last.'";
				$("#tab'.$last.'").addClass("tabselected");
				$("#tab'.$last.'box").css("display", "");
			});
			</script>
			<div id="content_padded">';
	if ($msg) {
		print '<div class="infobox">'.$msg.'</div>';
	}
	if ($error) {
		print '<div class="errorbox">'.$error.'</div>';
	}
	print '<div id="tabs">
				<ul>
					<li id="tab0" class="tab"><a href="javascript:;">Configurations</a></li>
					<li id="tab1" class="tab"><a href="javascript:;">Phone Numbers</a></li>
					<li id="tab2" class="tab"><a href="javascript:;">Upload Audio Files</a></li>
				</ul>
			</div>
			
			<div id="tab0box" class="tabbox">
				<div id="tab_content">
					<h3>Module Configurations</h3>
					<p align="left">
						<strong>Instructions:</strong> Please configure ALL of the options below. Leaving options unset will likely cause unexpected errors to occur.<br /><br />
						<strong>Audio Mode:</strong> This option chooses whether or not you\'d like to use the built in text to speech engine, or record your own message. If you select Audio File, please ensure you have uploaded the coresponding audio file in the "Upload Audio Files" tab.<br />
						<strong>Text:</strong> If you selected Text to Speech in the previous option, this will be what is read to your callers.<br />
						<strong>Invalid Input Audio Mode:</strong> Much like the Audio Mode above, this option lets you select how you wish to let the caller know their input was not recognized.<br />
						<strong>Invalid Input Text:</strong> This will be what is read to the caller if you selected the Text to Speech option above.
					</p>
					<br />
					<br />
					<form method="POST" action="'.$modulelink.'&last=0">
						<input type="hidden" name="_a" value="modconfig" />
						<table class="form" border="0" cellpadding="3" cellspacing="1" width="75%" align="center">
						<!-- " -->
							<tr>
								<td colspan="2" align="center">
									<h4>Global Configurations</h4>
									These are the global configurations for the module.
								</td>
							</tr>
							<tr>
								<td class="fieldlabel"><strong>Client Port</strong></td>
								<td class="fieldarea">
									<input type="text" size="10" name="client_port" value="'.$config['client_port'].'" /> Client Port configured in server.js
									<div style="float: right; padding-right: 10px;">'.$client_status.'</div>
								</td>
							</tr>
							<tr>
								<td class="fieldlabel"><strong>Server Port</strong></td>
								<td class="fieldarea">
									<input type="text" size="10" name="server_port" value="'.$config['server_port'].'" /> Server Port configured in server.js
									<div style="float: right; padding-right: 10px;">'.$server_status.'</div>
								</td>
							</tr>
							<tr>
								<td class="fieldlabel"><strong>Gather Timeout</strong></td>
								<td class="fieldarea"><input type="text" size="10" name="gather_timeout" value="'.$config['gather_timeout'].'" /> Amount of time to wait for caller input</td>
							</tr>
							<tr>
								<td class="fieldlabel"><strong>Voicemail Email Address</strong></td>
								<td class="fieldarea"><input type="text" size="30" name="voicemail_email" value="'.$config['voicemail_email'].'" /> Email Address to send voicemails to</td>
							</tr>
							<tr>
								<td class="fieldlabel"><strong>Voicemail Audio Mode</strong></td>
								<td class="fieldarea">';
	echo twilio_modeselect('voicemail_mode', $config);
	print '						</td>
							</tr>
							<tr>
								<td class="fieldlabel"><strong>Voicemail Text</strong></td>
								<td class="fieldarea"><textarea name="voicemail_text" rows="10" cols="80">'.$config['voicemail_text'].'</textarea></td>
							</tr>
							<tr>
								<td class="fieldlabel"><strong>Invalid Input Audio Mode</strong></td>
								<td class="fieldarea">';
	echo twilio_modeselect('invalid_request_mode', $config);
	print '						</td>
							</tr>
							<tr>
								<td class="fieldlabel"><strong>Invalid Input Text</strong></td>
								<td class="fieldarea"><textarea name="invalid_request_text" rows="10" cols="80">'.$config['invalid_request_text'].'</textarea></td>
							</tr>
						</table>
						<table class="form" border="0" cellpadding="3" cellspacing="1" width="75%" align="center">
						<!-- " -->
							<tr>
								<td colspan="2" align="center">
									<h4>Introduction</h4>
									These settings are relevent to when the caller first calls into the phone system. It should say something along the lines of: <br /><em>"Thank you for calling Widgets Incorporated. Please press 1 if this call is regarding an existing support ticket, please press 2 for all other inqueries."</em>
								</td>
							</tr>
							<tr>
								<td class="fieldlabel"><strong>Audio Mode</strong></td>
								<td class="fieldarea">';
	echo twilio_modeselect('intro_mode', $config);
	print '						</td>
							</tr>
							<tr>
								<td class="fieldlabel"><strong>Text</strong></td>
								<td class="fieldarea"><textarea name="intro_text" rows="10" cols="80">'.$config['intro_text'].'</textarea></td>
							</tr>
						</table>
						<table class="form" border="0" cellpadding="3" cellspacing="1" width="75%" align="center">
						<!-- " -->
							<tr>
								<td colspan="2" align="center">
									<h4>Step 1 (Ticket)</h4>
									These settings are relevent to when the caller selects option 1 from the introduction. It should say something along the lines of: <br /><em>"Please enter your ticket ID number, followed by the pound sign."</em>
								</td>
							</tr>
							<tr>
								<td class="fieldlabel"><strong>Audio Mode</strong></td>
								<td class="fieldarea">';
	echo twilio_modeselect('step1_ticket_mode', $config);
	print '						</td>
							</tr>
							<tr>
								<td class="fieldlabel"><strong>Text</strong></td>
								<td class="fieldarea"><textarea name="step1_ticket_text" rows="10" cols="80">'.$config['step1_ticket_text'].'</textarea></td>
							</tr>
						</table>
						<table class="form" border="0" cellpadding="3" cellspacing="1" width="75%" align="center">
						<!-- " -->
							<tr>
								<td colspan="2" align="center">
									<h4>Step 1 (Client)</h4>
									These settings are relevent to when the caller selects option 2 from the introduction. It should say something along the lines of: <br /><em>"Please enter your client ID number, followed by the pound sign."</em>
								</td>
							</tr>
							<tr>
								<td class="fieldlabel"><strong>Audio Mode</strong></td>
								<td class="fieldarea">';
	echo twilio_modeselect('step1_client_mode', $config);
	print '						</td>
							</tr>
							<tr>
								<td class="fieldlabel"><strong>Text</strong></td>
								<td class="fieldarea"><textarea name="step1_client_text" rows="10" cols="80">'.$config['step1_client_text'].'</textarea></td>
							</tr>
						</table>
						<table class="form" border="0" cellpadding="3" cellspacing="1" width="75%" align="center">
						<!-- " -->
							<tr>
								<td colspan="2" align="center">
									<h4>Step 2 (Ticket)</h4>
									These settings are relevent to when the caller enters his or her ticket ID number.<br />You can use custom variables here to insert client details into your message. Please see the <a href="#variables">table</a> at the bottom of this page.
								</td>
							</tr>
							<tr>
								<td class="fieldlabel"><strong>Audio Mode</strong></td>
								<td class="fieldarea">';
	echo twilio_modeselect('step2_ticket_mode', $config);
	print '						</td>
							</tr>
							<tr>
								<td class="fieldlabel"><strong>Text</strong></td>
								<td class="fieldarea"><textarea name="step2_ticket_text" rows="10" cols="80">'.$config['step2_ticket_text'].'</textarea></td>
							</tr>
							<tr>
								<td class="fieldlabel"><strong>Invalid Input Audio Mode</strong></td>
								<td class="fieldarea">';
	echo twilio_modeselect('step2_ticket_sorry_mode', $config);
	print '					</tr>
							<tr>
								<td class="fieldlabel"><strong>Invalid Input Text</strong></td>
								<td class="fieldarea"><textarea name="step2_ticket_sorry_text" rows="10" cols="80">'.$config['step2_ticket_sorry_text'].'</textarea></td>
							</tr>
						</table>
						<table class="form" border="0" cellpadding="3" cellspacing="1" width="75%" align="center">
						<!-- " -->
							<tr>
								<td colspan="2" align="center">
									<h4>Step 2 (Client)</h4>
									These settings are relevent to when the caller enters his or her client ID number.<br />You can use custom variables here to insert client details into your message. Please see the <a href="#variables">table</a> at the bottom of this page.
								</td>
							</tr>
							<tr>
								<td class="fieldlabel"><strong>Audio Mode</strong></td>
								<td class="fieldarea">';
	echo twilio_modeselect('step2_client_mode', $config);
	print '						</td>
							</tr>
							<tr>
								<td class="fieldlabel"><strong>Text</strong></td>
								<td class="fieldarea"><textarea name="step2_client_text" rows="10" cols="80">'.$config['step2_client_text'].'</textarea></td>
							</tr>
							<tr>
								<td class="fieldlabel"><strong>Invalid Input Audio Mode</strong></td>
								<td class="fieldarea">';
	echo twilio_modeselect('step2_client_sorry_mode', $config);
	print '					</tr>
							<tr>
								<td class="fieldlabel"><strong>Invalid Input Text</strong></td>
								<td class="fieldarea"><textarea name="step2_client_sorry_text" rows="10" cols="80">'.$config['step2_client_sorry_text'].'</textarea></td>
							</tr>
						</table>
						<p align="center"><input type="submit" value="Save Changes" /></p>
					</form>
					<br />
					<a name="variables"></a><h3>Step 2 Variables</h3>
					<div class="tablebg" align="center">
						<table class="datatable" cellspacing="1" cellpadding="1" width="50%">
							<tr><th>Variable</th><th>Provides</th></tr>
							
							<tr><td align="center">%FIRSTNAME%</td><td align="center">Clients Firstname</td></tr>
							<tr><td align="center">%LASTNAME%</td><td align="center">Clients Lastname</td></tr>
							<tr><td align="center">%COMPANY%</td><td align="center">Clients Company Name</td></tr>
							<tr><td align="center">%EMAIL%</td><td align="center">Clients Email</td></tr>
							<tr><td align="center">%ADDRESS1%</td><td align="center">Clients Address Line 1</td></tr>
							<tr><td align="center">%ADDRESS2%</td><td align="center">Clients Address Line 2</td></tr>
							<tr><td align="center">%CITY%</td><td align="center">Clients City</td></tr>
							<tr><td align="center">%STATE%</td><td align="center">Clients State</td></tr>
							<tr><td align="center">%POSTCODE%</td><td align="center">Clients Postcode</td></tr>
							<tr><td align="center">%PHONE%</td><td align="center">Clients Phone Number</td></tr>
							<tr><td align="center">%STATUS%</td><td align="center">Clients Account Status</td></tr>
							<tr><td align="center">%TICKET_TITLE%</td><td align="center">Tickets Title Text (only applicable for ticket mode)</td></tr>
							<tr><td align="center">%TICKET_URGENCY%</td><td align="center">Tickets Urgency (only applicable for ticket mode)</td></tr>
							<tr><td align="center">%TICKET_STATUS%</td><td align="center">Tickets Status (only applicable for ticket mode)</td></tr>
						</table>
					</div>
				</div>
			</div>
			<div id="tab1box" class="tabbox">
				<div id="tab_content">
					<h3>Phone Numbers</h3>
					<p align="left">
						<strong>Instructions:</strong> Enter the phone numbers below you wish to dial after the user has called the Twilio number. These will typically be support operators phone numbers.<br /><br />
					</p>
					<p align="left">
						<strong>Phone Number:</strong> Format: +12223334444. This is the number the user will be redirected to after following the phone prompts.<br />
						<strong>Timeout:</strong> This is the amount of time (in seconds) to wait before moving on to the next number. If the number has voicemail, its important that this number is lower than the amount of time before the user is redirected to VM.<br />
						<strong>Order:</strong> This is the order in which the numbers are dialed. Lower numbers are dialed first. Leaving this blank will default to "0" and therefore be called first.<br />
						<strong>Delete:</strong> Check this box to delete an existing number.<br />
					</p>
					<br />
					<form method="POST" action="' . $modulelink . '&last=1">
						<input type="hidden" name="_a" value="modnumbers" />
						<div class="tablebg" align="center">
							<table class="datatable" cellspacing="1" cellpadding="1" width="400">
								<tr>
									<th>Phone Number</th>
									<th>Timeout</th>
									<th>Order</th>
									<th>Delete</th>
								</tr>';
	foreach ($numbers as $key => $val) {
		$nid = $val['id'];
		print '					<tr>
									<td align="center"><input type="text" size="40" name="number['.$nid.']" value="'.$val['number'].'" /></td>
									<td align="center"><input type="text" size="10" name="timeout['.$nid.']" value="'.$val['timeout'].'" /></td>
									<td align="center"><input type="text" size="10" name="order['.$nid.']" value="'.$val['order'].'" /></td>
									<td align="center">
										<input type="checkbox" name="delete['.$nid.']" value="1" />
										<input type="hidden" name="numberid['.$nid.']" value="'.$nid.'" />
									</td>
								</tr>';
	}
	print '						<tr>
									<td align="center"><input type="text" size="40" name="number[\'NEW\']" value="" /></td>
									<td align="center"><input type="text" size="10" name="timeout[\'NEW\']" value="" /></td>
									<td align="center"><input type="text" size="10" name="order[\'NEW\']" value="" /></td>
									<td><input type="hidden" name="numberid[\'NEW\']" value="NEW" /></td>
								</tr>
							</table>
						</div>
						<input type="submit" value="Save Changes" name="save" />
					</form>
				</div>
			</div>
			<div id="tab2box" class="tabbox">
				<div id="tab_content">
					<h3>Upload Audio Files</h3>
					<p align="left">
						<strong>Instructions:</strong> These files are used in place of the default Twilio text-to-speech engine. You can record your own voice, or hire a professional to record them for you.
					</p>
					<p align="left">
						<strong>Notes:</strong>
						<ul align="left">
							<li>- These files will ONLY be used if you have selected "Audio File" from the selection on the Configuration page.</li>
							<li>- Keep in mind that these files will have to be downloaded from your server, to Twilio, then streamed to the caller. Large files will likely cause delay in the system.</li>
							<li>- The max file size is set to 2MB, however, it is recommended that you keep the file sizes as small as possible. 72Kbps mono recordings will likely yield the best results.</li>
							<li>- ONLY MP3 files are supported. If you have your audio in another format, you\'ll need to convert it to MP3 before uploading.</li>
						</ul>
					</p>
					<br />
					<form method="POST" action="'.$modulelink.'&last=2" enctype="multipart/form-data">
						<input type="hidden" name="_a" value="uploadaudio" />
						<table class="form" width="50%" border="0" cellspacing="2" cellpadding="3" align="center">
							<tr>
								<td class="fieldlabel"><strong>Introduction</strong></td>
								<td class="fieldarea"><input type="file" name="intro_file" /></td>
								<td class="fieldarea" align="center"><a href="../modules/addons/twilio/calls/get_audio.php?f=intro_file" target="_blank"><img src="../modules/addons/twilio/images/play.png" /></a></td>
							</tr>
							<tr>
								<td class="fieldlabel"><strong>Step 1 (Client)</strong></td>
								<td class="fieldarea"><input type="file" name="step1_client_file" /></td>
								<td class="fieldarea" align="center"><a href="../modules/addons/twilio/calls/get_audio.php?f=step1_client_file" target="_blank"><img src="../modules/addons/twilio/images/play.png" /></a></td>
							</tr>
							<tr>
								<td class="fieldlabel"><strong>Step 1 (Ticket)</strong></td>
								<td class="fieldarea"><input type="file" name="step1_ticket_file" /></td>
								<td class="fieldarea" align="center"><a href="../modules/addons/twilio/calls/get_audio.php?f=step1_ticket_file" target="_blank"><img src="../modules/addons/twilio/images/play.png" /></a></td>
							</tr>
							<tr>
								<td class="fieldlabel"><strong>Step 2 (Client)</strong></td>
								<td class="fieldarea"><input type="file" name="step2_client_file" /></td>
								<td class="fieldarea" align="center"><a href="../modules/addons/twilio/calls/get_audio.php?f=step2_client_file" target="_blank"><img src="../modules/addons/twilio/images/play.png" /></a></td>
							</tr>
							<tr>
								<td class="fieldlabel"><strong>Step 2 (Client) - Invalid Input</strong></td>
								<td class="fieldarea"><input type="file" name="step2_client_sorry_file" /></td>
								<td class="fieldarea" align="center"><a href="../modules/addons/twilio/calls/get_audio.php?f=step2_client_sorry_file" target="_blank"><img src="../modules/addons/twilio/images/play.png" /></a></td>
							</tr>
							<tr>
								<td class="fieldlabel"><strong>Step 2 (Ticket)</strong></td>
								<td class="fieldarea"><input type="file" name="step2_ticket_file" /></td>
								<td class="fieldarea" align="center"><a href="../modules/addons/twilio/calls/get_audio.php?f=step2_ticket_file" target="_blank"><img src="../modules/addons/twilio/images/play.png" /></a></td>
							</tr>
							<tr>
								<td class="fieldlabel"><strong>Step 2 (Ticket) - Invalid Input</strong></td>
								<td class="fieldarea"><input type="file" name="step2_ticket_sorry_file" /></td>
								<td class="fieldarea" align="center"><a href="../modules/addons/twilio/calls/get_audio.php?f=step2_ticket_sorry_file" target="_blank"><img src="../modules/addons/twilio/images/play.png" /></a></td>
							</tr>
							<tr>
								<td class="fieldlabel"><strong>General Invalid Input</strong></td>
								<td class="fieldarea"><input type="file" name="invalid_request_file" /></td>
								<td class="fieldarea" align="center"><a href="../modules/addons/twilio/calls/get_audio.php?f=invalid_request_file" target="_blank"><img src="../modules/addons/twilio/images/play.png" /></a></td>
							</tr>
							<tr>
								<td class="fieldlabel"><strong>Voicemail</strong></td>
								<td class="fieldarea"><input type="file" name="voicemail_file" /></td>
								<td class="fieldarea" align="center"><a href="../modules/addons/twilio/calls/get_audio.php?f=voicemail_file" target="_blank"><img src="../modules/addons/twilio/images/play.png" /></a></td>
							</tr>
						</table>
						<p align="center">
							<input type="submit" value="Upload Files" />
						</p>
					</form>
					
				</div>
			</div>
		</div>';
}

function twilio_modeselect($varname, $config) {
	$code = '<select name="'.$varname.'"><option value="text"';
	if ($config[$varname] == 'text') {
		$code .= ' selected';
	}
	$code .= '>Text to Speech</option><option value="file"';
	if ($config[$varname] == 'file') {
		$code .= ' selected';
	}
	$code .= '>Audio File</option></select>';
	return $code;
}
?>