<html>
<head>
<title>Samurai Import Script</title>
</head>
<body>
<div align="center">
	<h1>Samurai Import Script</h1>
	<p><h4>This script will import all existing client data into the Samurai Vault</h4></p>
	<form action="samurai_import.php?go" method="POST" id="import">
		<input type="checkbox" value="1" name="remove_existing"> Check this to remove unnecessary card data from WHMCS (<strong>CANNOT BE UNDONE!</strong>)<br />This option will only remove card data for clients successfully imported.<br /><br />
		<input type="submit" value="Start" onClick="this.disabled=true;document.getElementById('import').submit();" />
	</form>
</div>
<br />
<br />
<?php

require("dbconnect.php");
require("includes/functions.php");
require("includes/gatewayfunctions.php");
require("modules/gateways/samurai/Samurai.php");

if (isset($_REQUEST['go']) && isset($_POST)) {
	
	$removeexisting = (isset($_POST['remove_existing']) ? true : false);

	// Get samurai config values
	$merchantKey = '';
	$merchantPass = '';
	$procToken = '';
	$query = "SELECT
				setting, value
			FROM
				tblpaymentgateways
			WHERE
				gateway = 'samurai' AND 
				(
				 	setting = 'merchantKey' OR
					setting = 'merchantPass' OR
					setting = 'procToken'
				)";
	$d = full_query($query);
	while ($res = mysql_fetch_assoc($d)) {
		switch ($res['setting']) {
			case "merchantKey":
				$merchantKey = $res['value'];
				break;
			case "merchantPass":
				$merchantPass = $res['value'];
				break;
			case "procToken":
				$procToken = $res['value'];
				break;
		}
	}
	if ((empty($merchantKey)) || (empty($merchantPass)) || (empty($procToken))) {
		echo "<strong>Missing Samurai connection details. Please update your samurai configuration with the merchant key, merchant password, and processor token.</strong>";
	} else {
		echo "<pre>";
		// Configuration values good. Start processing clients
		$gatewayids = array();
		$d = select_query('tblclients', '*', 'cardtype != ""', 'id', 'ASC');
		while ($result = mysql_fetch_assoc($d)) {
			$clientid = $result['id'];
			$cchash = md5($cc_encryption_hash.$clientid);
			$firstname = $result['firstname'];
			$lastname = $result['lastname'];
			$address1 = $result['address1'];
			$address2 = $result['address2'];
			$city = $result['city'];
			$state = $result['state'];
			$postcode = $result['postcode'];
			$country = $result['country'];
			echo "Processing $firstname $lastname ($clientid): ";
			$d2 = select_query("tblclients", "cardtype, cardlastfour, AES_DECRYPT(cardnum, '$cchash') as cardnum, AES_DECRYPT(expdate, '$cchash') as expdate", array("id" => $clientid));
			$result2 = mysql_fetch_assoc($d2);
			$cardtype = $result2['cardtype'];
			$cardnum = $result2['cardnum'];
			$expmonth = substr($result2['expdate'], 0, 2);
			$expyear = substr($result2['expdate'], 2, 2);
			if ($cardnum) {
				$setup = Samurai::setup(array(
									'merchantKey'		=> $merchantKey,
									'merchantPassword'	=> $merchantPass,
									'processorToken'	=> $procToken));
				$create = Samurai_PaymentMethod::create(array(
														'card_number'	=> $cardnum,
														'card_type'		=> $cardtype,
														'expiry_month'	=> $expmonth,
														'expiry_year'	=> $expyear,
														'first_name'	=> $firstname,
														'last_name'		=> $lastname,
														'address_1'		=> $address1,
														'address_2'		=> $address2,
														'city'			=> $city,
														'state'			=> $state,
														'zip'			=> $postcode,
														'country'		=> $country));
				if (!$create->hasErrors()) {
					$create->save();
					$create->retain();
					$token = $create->token;
					$gatewayids[$clientid] = $token;
					if ($removeexisting) {
						$delete = update_query('tblclients', array('cardnum' => ''), array('id' => $clientid));
						echo "Done. (Card Data Removed)\n";
					} else {
						echo "Done.\n";
					}
				} else {
					foreach ($create->errors as $context => $errors) {
						foreach ($errors as $error) {
							$allerrors .= $error->description." ";
						}
					}
					echo $allerrors."\n";
				}
			} else {
				echo "No card data present\n";
			}
		}
		echo "</pre><br />";
		echo "<strong>Updating gateway IDs:</strong> ";
		foreach ($gatewayids as $cid => $gid) {
			$d = update_query('tblclients', array('gatewayid'=>$gid), array('id'=>$cid));
		}
		echo "<em>Done.</em><br />";
		echo "<h2>Import Complete. Please review above for errors.</h2>";
	}				
}


?>
</body>
</html>
