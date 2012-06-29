<?php

require_once ROOTDIR.'/modules/gateways/samurai/Samurai.php';

function samurai_config() {
	
	$configarray = array(
				"FriendlyName"	=> array("Type"=>"System", "Value"=>"Samurai"),
				"merchantKey"	=> array("FriendlyName"=>"Merchant Key", "Type"=>"text", "Size"=>"25"),
				"merchantPass"	=> array("FriendlyName"=>"Merchant Password", "Type"=>"password", "Size"=>"25"),
				"procToken"		=> array("FriendlyName"=>"Processor Token", "Type"=>"text", "Size"=>"25"),
				"testMode"		=> array("FriendlyName"=>"Test Mode", "Type"=>"yesno"));
	return $configarray;
}

function samurai_storeremote($params) {
	$setup = Samurai::setup(array(
						'sandbox'			=> $params['testMode'],
						'merchantKey'		=> $params['merchantKey'],
						'merchantPassword'	=> $params['merchantPass'],
						'processorToken'	=> $params['procToken']));

	$clientid = $params['clientdetails']['userid'];
	$payMethod = Samurai_PaymentMethod::create(array(
												'card_number'	=> $params['cardnum'],
												'card_type'		=> $params['cardtype'],
												'expiry_month'	=> substr($params['cardexp'], 0, 2),
												'expiry_year'	=> substr($params['cardexp'], 2, 2),
												'cvv'			=> $params['cccvv'],
												'first_name'	=> $params['clientdetails']['firstname'],
												'last_name'		=> $params['clientdetails']['lastname'],
												'address_1'		=> $params['clientdetails']['address1'],
												'address_2'		=> $params['clientdetails']['address2'],
												'city'			=> $params['clientdetails']['city'],
												'state'			=> $params['clientdetails']['state'],
												'zip'			=> $params['clientdetails']['postcode']));
	if (!$payMethod->hasErrors()) {
		$payMethod->save();
		$payMethod->retain();
		$token = $payMethod->token;
		return array('status'=>'success', 'gatewayid'=>$token);
	} else {
		$allerrors = "This transaction could not be processed:\n";
		foreach ($payMethod->errors as $context => $errors) {
			foreach ($errors as $error) {
				$allerrors .= $error->description."\n";
			}
		}
		return array('status'=>'error', 'rawdata'=>$allerrors);
	}
										
}

function samurai_capture($params) {
	global $CONFIG;
	$setup = Samurai::setup(array(
							'sandbox'			=> $params['testMode'],
							'merchantKey'		=> $params['merchantKey'],
							'merchantPassword'	=> $params['merchantPass'],
							'processorToken'	=> $params['procToken']));
	$clientid = $params['clientdetails']['userid'];
	$gatewayid = $params['gatewayid'];
	$companyName = $CONFIG['CompanyName'];
	$payMethod = Samurai_PaymentMethod::find($gatewayid);
	if (!$payMethod->hasErrors()) {
		$processor = Samurai_Processor::theProcessor();
		$purchase = $processor->purchase(
					$payMethod->token,
					$params['amount'],
					array(
							'billing_reference'		=> $params['invoiceid'],
							'customer_reference'	=> $clientid,
							'description'			=> $params['description'],
							'descriptor_name'		=> $companyName,
							'custom'				=> 'Samurai WHMCS Module'));
		if ($purchase->isSuccess()) {
			$refid = $purchase->attributes['reference_id'];
			return array('status'=>'success', 'transid'=>$refid);
		} else {
			$allerrors = "This transaction could not be processed:\n";
			foreach ($purchase->errors as $context => $errors) {
				foreach ($errors as $error) {
					$allerrors .= $error->description."\n";
				}
			}
			return array('status'=>'error', 'rawdata'=>$allerrors);
		}
	} else {
		$allerrors = "This transaction could not be processed:\n";
		foreach ($payMethod->errors as $context => $errors) {
			foreach ($errors as $error) {
				$allerrors .= $error->description."\n";
			}
		}
		return array('status'=>'error', 'rawdata'=>$allerrors);
	}
}

function samurai_refund($params) {
	$setup = Samurai::setup(array(
							'sandbox'			=> $params['testMode'],
							'merchantKey'		=> $params['merchantKey'],
							'merchantPassword'	=> $params['merchantPass'],
							'processorToken'	=> $params['procToken']));
	$gatewayid = $params['gatewayid'];
	$transid = $params['transid'];
	$amount = $params['amount'];
	$trans = Samurai_Transaction::find($transid);
	if ($amount) {
	$trans->reverse($amount);
	} else {
		$trans->reverse();
	}
	if ($trans->isSuccess()) {
		$refid = $trans->attributes['reference_id'];
		return array('status'=>'success', 'transid'=>$refid);
	} else {
		$allerrors = "This transaction could not be processed:\n";
		foreach ($trans->errors as $context => $errors) {
			foreach ($errors as $error) {
				$allerrors .= $error->description."\n";
			}
		}
		return array('status'=>'error', 'rawdata'=>$allerrors);
	}
}

?>


					
