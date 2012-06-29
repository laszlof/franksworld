GoogleApps WHMCS Addon 2.0
Written by Frank Laszlo (Franksworld Solutions, LLC) <frank@franksworld.org>

DESCRIPTION
--------------------
This module will allow your clients to switch between Local and Google Apps
hosted email. It will also add the default google SPF record, as well as the
applicable CNAME entries for mail, calendar, start, and docs when Google Apps 
is selected. This module only works with cPanel servers. Resellers will also
have the ability to modify their clients domain settings.

INSTALLATION
--------------------
1) Copy includes/hooks/googleapps.php into your WHMCS/includes/hooks/ directory
2) Copy templates/portal/googleapps.tpl into your WHMCS/templates/YOURTEMPLATE/ directory
3) Add the following to your lang/overrides/english.php (WHMCS >= 5.x) or lang/English.txt (WHMCS < 5.x)
 (You may also translate these to other languages if required)

	$_LANG["googleapps_noerror"] = "Change has been completed without errors";
	$_LANG["googleapps_error"] = "Change has been completed with errors (see below)";
	$_LANG["googleapps_notowned"] = "You do not own this domain name or user";

4) Add the following to the bottom of your clientareaproductdetails.tpl template file:

	{include file="$template/googleapps.tpl"}
	

ADDITIONAL INFORMATION
--------------------
- This module only works with cPanel servers.
- I've included a sample clientareaproductdetails.tpl file to show what needs
  to be added.
- You're welcome to modify googleapps.tpl to suit your website style, but I
  only support the default configuration.


SUPPORT
--------------------

Please visit my website for support (http://www.franksworld.org). Updates/bug fixes will be provided
free of charge to all purchasers for the duration of the product lifetime.

The lifecycle of this product may be terminated at any time, at which point all existing support
obligations will be null and void.
