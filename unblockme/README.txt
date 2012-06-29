UnblockMe v2.0 (CSF/cPanel Unblock Addon)
Written by Frank Laszlo (frank@franksworld.org)

DESCRIPTION
--------------------
This module will allow your clients to unblock themselves from
CSF if they happen to exceed the failed login threshhold on their
hosting account. Unlike other modules designed created for this task,
this one requires virtually NO CONFIGURATION. Simply upload the 
included files and add included code to your clientareaproductdetails
template as well as a couple LANG entries and you're all set!

INSTALLATION
--------------------
1) Copy all files into your whmcs installation directory
2) Add the following code somewhere within your sites "clientareaproductdetails.tpl" file.

<!-- BEGIN UNBLOCKME CODE -->
{if $unblockme_isactive}
<script language="javascript">
	$(document).ready(function(){ldelim}
		$('#unblockloading').hide();
		$('#unblocksuccess').hide();
		$('#unblockfailure').hide();
		$('#unblockme').click(function() {ldelim}
			$('#unblocksuccess').hide();
			$('#unblockfailure').hide();
			$('#unblockloading').show();
			var myip = $('input[name=myip]');
			var data = "ip=" + encodeURIComponent(myip.val()) + "&id={$id}";
			$.ajax( {ldelim}
				type: "GET",
				data: data,
				cache: false,
				dataType: 'json',
				url: "unblockme.php",
				beforeSend: function(x) {ldelim}
					if (x && x.overrideMimeType) {ldelim}
						x.overrideMimeType("application/j-son;charset=UTF-8");
					{rdelim}
				{rdelim},
				success: function (res) {ldelim}
					$('#unblockloading').hide();
					if (res.status == "success") {ldelim}
						$('#unblocksuccess').show();
						$('#unblocksuccess').html(res.message);
					{rdelim} else if (res.status == "failure") {ldelim}
						$('#unblockfailure').show();
						$('#unblockfailure').html(res.message);
					{rdelim} else {ldelim}
						var msg = "{$LANG.unblockme_unknownerror}";
						$('#unblockfailure').show();
						$('#unblockfailure').html(msg);
					{rdelim}
				{rdelim}
			{rdelim});
			return false;
		{rdelim});
		$('#unblockthem').click(function() {ldelim}
			$('#unblocksuccess').hide();
			$('#unblockfailure').hide();
			$('#unblockloading').show();
			var theirip = $('input[name=theirip]');
			var data = "ip=" + encodeURIComponent(theirip.val()) + "&id={$id}";
			$.ajax( {ldelim}
				type: "GET",
				data: data,
				cache: false,
				dataType: 'json',
				url: "unblockme.php",
				beforeSend: function(x) {ldelim}
					if (x && x.overrideMimeType) {ldelim}
						x.overrideMimeType("application/j-son;charset=UTF-8");
					{rdelim}
				{rdelim},
				success: function (res) {ldelim}
					$('#unblockloading').hide();
					if (res.status == "success") {ldelim}
						$('#unblocksuccess').show();
						$('#unblocksuccess').html(res.message);
					{rdelim} else if (res.status == "failure") {ldelim}
						$('#unblockfailure').show();
						$('#unblockfailure').html(res.message);
					{rdelim} else {ldelim}
						var msg = "{$LANG.unblockme_unknownerror}";
						$('#unblockfailure').show();
						$('#unblockfailure').html(msg);
					{rdelim}
				{rdelim}
			{rdelim});
			return false;
		{rdelim});
	{rdelim});
</script>
<br />
<table cellpadding="3" cellspacing="1" class="frame">
	<tr class="clientareatableheading">
		<td colspan="2" align="center">
			<strong>{$LANG.unblockme_header}</strong>
		</td>
	</tr>
	<tr class="clientareatablesuspended">
		<td align="center"><strong>{$LANG.unblockme_unblockyou}</strong></td>
		<td align="center"><strong>{$LANG.unblockme_unblockthem}</strong></td>
	</tr>
	<tr>
		<td align="center" valign="top">
			{$LANG.unblockme_yourip}<br />
			<form>
				<input type="text" size="25" name="myip" value="{$smarty.server.REMOTE_ADDR}" style="text-align: center;" readonly /><br />
				<input type="button" id="unblockme" value="{$LANG.unblockme_unblock}" class="button" />
			</form>
		</td>
		<td align="center" valign="top">
				{$LANG.unblockme_theirip}<br />
				<form>
					<input type="text" size="25" name="theirip" style="text-align: center;" /><br />
					<input type="button" id="unblockthem" value="{$LANG.unblockme_unblock}" class="button" />
				</form>
		</td>
	</tr>
	<tr>
		<td colspan="2" align="center">
			<div id="unblockloading"><img src="images/loading.gif"></div>
			<div class="successbox" id="unblocksuccess"></div>
			<div class="errorbox" id="unblockfailure"></div>
		</td>
	</tr>
</table>
{/if}
<!-- END UNBLOCKME CODE -->


3) Add the following language entries to lang/English.txt (lang/english.php on WHMCS => 5.x)

$_LANG['unblockme_header'] = 'Receiving a firewall error when accessing your website? Use the form below.';
$_LANG['unblockme_unblockyou'] = 'Unblock You';
$_LANG['unblockme_unblockthem'] = 'Unblock Them'; 
$_LANG['unblockme_yourip'] = 'Your IP Address';
$_LANG['unblockme_theirip'] = 'Their IP Address';
$_LANG['unblockme_unblock'] = 'Unblock';
$_LANG['unblockme_unknown'] = 'An unknown error has occurred';
$_LANG['unblockme_invalidip'] = 'Invalid IP address';
$_LANG['unblockme_notblocked'] = 'IP address does not exist on the servers block list';
$_LANG['unblockme_unblocked'] = 'IP address has been removed from the servers block list';

(These can/should be translated to the languages your clients use)

4) Customize to your liking!

ADDITIONAL INFORMATION
--------------------
- This addon will only work with cPanel servers, with the CSF cPanel plugin
  installed.

SUPPORT
--------------------

Direct email support will be provided on an as-needed basis. Updates/bug fixes will be provided
free of charge to all purchasers for the duration of the product lifetime.

The lifecycle of this product may be terminated at any time, at which point all existing support
obligations will be null and void.

Please email frank@franksworld.org for support on this product.
