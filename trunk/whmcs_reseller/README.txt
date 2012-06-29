WHMCS Reseller v2.0p1
Written by Frank Laszlo (frank@asmallorange.com)

DESCRIPTION
--------------------
This module will allow you to resell licenses for WHMCS through your website.
The module will automatically import the licenses on your whmcs.com client area
and store them for allocation to your customers. The module will provide the
functionality to reissue the license and provide detailed information within
the client area and admin area.


INSTALLATION
--------------------
1) Copy all files into your whmcs installation directory
2) Create a new product for your licenses, under the "Other" product type, and set the Module
	Settings to use the "whmcsreseller" module. Select the license type, and set price and other applicable
	information.
3) Add a Custom Field to the product with the following attributes:
	Field Name: License
	Field Type: Text Box
	- Check Admin Only
4) Navigate to Utilities -> Addon Modules -> Whmcs Reseller and click "Install"
5) Go to the configuration tab for the module, and provide the requested information.
6) Go to the "Import Licenses" tab and click the "Import" button.
7) Add the following cron job to your account:
	*/15 *   *   *   *   /usr/bin/wget -O /dev/null http://www.YOURDOMAIN.COM/WHMCS/modules/admin/whmcs_reseller/cron.php >> /dev/null 2>&1
	(This cronjob will update the license statuses in the admin area. Doing
	this on the fly made the admin interface very sluggish for a large amount
	of licenses. I recommend not setting this any lower than every 15 minutes
	so it does not hammer the whmcs.com client area with requests. Obviously
	you'll want to replace YOURDOMAIN.COM/WHMCS with the actual values)
	


*** IF YOU'RE USING THE 1.X VERSION OF THIS MODULE, PLEASE READ BELOW ***

Due to the extensive changes in the way the module functions, it is not directly upgradable. Here are some
steps that SHOULD work for an upgrade, but I have not tested them.

1) Delete the "whmcsresellerconf" table using PHPMyAdmin or simular.
2) Add a new field to the end of the whmcsresellerlicenses table with the following attributes:
	Field: type
	Type: VARCHAR
	Length: 10
	Default: NULL
	Null: True
3) Remove reissue.php, as well as modules/admin/whmcs_reseller and modules/servers/whmcsreseller directories
4) Copy over the newly supplied files.
5) Navigate to Utilities -> Addon Modules -> Whmcs Reseller and click "Install".
6) Force a cron run to update the licenses.

This should work properly, but as I said, I have not been able to fully test it.


ADDITIONAL INFORMATION
--------------------
- The "Delete" icon on the "Import Licenses" page will delete the license from your list, making it no 
	longer able to be allocated. It will probably break if its already assigned to a client.
- The import function will not import duplicates. You can click it as many times as you like and it will
	only import new licenses from your client area.
- The import function will only import Active licenses. Expired or otherwise not available licenses
	will not be imported.
- Cancelling/Suspending a customers license product will remove the license information from their
	client area, and will not allow them to reissue the license. However, there is no way to actually 
	"suspend" or "cancel" a license unless you cancel it through whmcs.com.
- The ability to supply license exemptions for the import process will be added in a later release.


SUPPORT
--------------------

Direct email support will be provided on an as-needed basis. Updates/bug fixes will be provided
free of charge to all purchasers for the duration of the product lifetime.

The lifecycle of this product may be terminated at any time, at which point all existing support
obligations will be null and void.

Please email frank@asmallorange.com for support on this product.
