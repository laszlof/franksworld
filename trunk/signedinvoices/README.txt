Signed Invoices v1.0
Written by Frank Laszlo (frank@franksworld.org)

DESCRIPTION
--------------------
This module will allow you to digitally sign PDF invoices being sent out from your
WHMCS installation. A valid signing certificate and key is required for this module
to function correctly. You can generate a self signed key/certificate using the following
commands:

openssl req -x509 -nodes -days 365000 -newkey rsa:1024 -keyout selfsigned.key -out selfsigned.crt

The following fields are available within the modules admin interface:

Certificate: <Paste your certificate here>
Private Key: <Paste your private key here>
Intermediate Certificate: (Optional) <Paste any additional certificates required for validation here>
Private Key Passphrase: (Optional) <Paste your keys passphrase here if one has been configured>



INSTALLATION
--------------------
1) Copy all files into your whmcs installation directory
2) Navigate to Setup->Addon Modules in the WHMCS admin interface
3) Activate the "Signed Invoices" module
4) Setup your administrator role access to the module
5) Navigate to Addons->Signed Invoices
6) Fill out required fields and save the changes
7) Add the following code to templates/YOURTEMPLATE/invoicepdf.tpl just ABOVE the last line:
		(The last line should contain ?> )
	
/* BEGIN SIGN INVOICES CODE BLOCK */
require_once ROOTDIR.'/modules/addons/signedinvoices/si_include.php';
$signdata = signedinvoicedata();
if ($signdata['status'] == 'success') {
	$pdf->setSignature($signdata['cert'], $signdata['key'], $signdata['keypass'], $signdata['extra'], 2, array());
} else {
	logActivity($signdata['message']);
}
/* END SIGN INVOICES CODE BLOCK */


SUPPORT
--------------------

Ticket support will be provided on an as-needed basis. Updates/bug fixes will be provided
free of charge to all purchasers for the duration of the product lifetime.

The lifecycle of this product may be terminated at any time, at which point all existing support
obligations will be null and void, and full sourcecode will be released to those who have purchased 
the module.

To receive support, please either log a ticket on my website (https://secure.franksworld.org/whmcs/clientarea.php)
or email sales@franksworld.org.

