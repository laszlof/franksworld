ServerUptime WHMCS Addon 1.0
Written by Frank Laszlo (frank@asmallorange.com)

DESCRIPTION
--------------------
This module will add a page (http://yourdomain.com/whmcs/serveruptime.php) to 
your WHMCS installation and will display the HTTP uptime of all your servers
that have a status page enabled/configured.

INSTALLATION
--------------------
1) Copy all files into your whmcs installation directory
2) Navigate to Utilities -> Addon Modules -> Serveruptime
3) Set the server groups you would like to monitor and cURL timeout as you would like.
4) Add the cronjob listed into your users crontab
5) Access http://yourdomain.com/whmcs/serveruptime.php to view its output.
	

ADDITIONAL INFORMATION
--------------------
- The addon uses a template for its display output. There is a default one
  installed for both the 'default' template and the 'portal' template. You are
  free to edit either of these to match your site layout.
- The cURL timeout setting is how long the script will wait for a response
  from your server before giving up (marking it down). Too low, and you will
  receive a lot of false positives. Too high, and the script may not finish
  before the next cron is set to run.
- The script does an HTTP HEAD request to the status URL listed for each
  server in the group(s) you have configured. If this URL does not exist, the
  server will not be monitored at all. If the URL is in place, but the
  resulting request gives a 404 not found, it will be marked as down.
- Be mindful of the database size when using this module. It has the potential
  to make your database very large if you have a lot of servers and/or your
  cron job is set to run very often.


SUPPORT
--------------------

Direct email support will be provided on an as-needed basis. Updates/bug fixes will be provided
free of charge to all purchasers for the duration of the product lifetime.

The lifecycle of this product may be terminated at any time, at which point all existing support
obligations will be null and void.

Please email frank@asmallorange.com for support on this product.
