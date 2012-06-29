<!-- BEGIN GOOGLE APPS ADDON CODE -->

{if $googleapps_isvalid}
{literal}
<script language="javascript">
	$(document).ready(function(){
		$('#googleapps_results').hide();
		$('#googleapps_loading').hide();
		$('#googleapps_cpuser').change(function() {
			$('#googleapps_loading').show();
			var user = $(this).val();
			var id = $('#googleapps_id').val();
			var data = "domlookup=1&id=" + encodeURIComponent(id) + "&user=" + encodeURIComponent(user);
			$.ajax({
				type: "POST",
				data: data,
				dataType: "json",
				cache: false,
				url: "clientarea.php?action=productdetails",
				success: function (res, textStatus, jqXHR) {
					$('#googleapps_loading').hide();
					var options = '';
					for (var i=0;i<res.length;i++) {
						options += '<option value="' + res[i] + '">' + res[i] + '</option>';
					}
					$('#googleapps_domain').html(options);
				}
			});
		});
		$('#googleapps_btn').click(function() {
			$('#googleapps_loading').show();
			$('#googleapps_msg').hide();
			$('#googleapps_error').hide();
			$('#googleapps_btn').attr('disabled', 'disabled');
			var id = $('#googleapps_id').val();
			var domain = $('#googleapps_domain').val();
			var user = $('#googleapps_cpuser').val();
			var config = $('#googleapps_config').val();
			var data = "googleit=1&id=" + encodeURIComponent(id) + "&user=" + encodeURIComponent(user) + "&domain=" + encodeURIComponent(domain) + "&config=" + encodeURIComponent(config);
			$.ajax({
				type: "POST",
				data: data,
				dataType: 'json',
				cache: false,
				url: "clientarea.php?action=productdetails",
				success: function (res, textStatus, jqXHR) {
					$('#googleapps_loading').hide();
					$('#googleapps_results').show();
					if (res.error) {
						$('#googleapps_error').show();
						$('#googleapps_error').html(res.error);
					}
					$('#googleapps_msg').show();
					$('#googleapps_msg').html(res.msg);
					$('#googleapps_btn').removeAttr('disabled');
				}
			});
		});
	});
</script>
{/literal}
<br />
<form>
	<input type="hidden" id="googleapps_id" value="{$id}" />
	<table width="100%" cellspacing="0" cellpadding="0" class="frame">
		<tr><td>
			<table cellpadding="3" cellspacing="1" width="100%">
				<tr>
					<td colspan="2" align="center"><strong>Google Apps Settings</strong></td>
				</tr>
				<tr>
					<td class="fieldarea">cPanel User:</td>
					<td>
						<select id="googleapps_cpuser">
						{foreach key=k item=user from=$googleapps_users}
							<option value="{$user}">{$user}</option>
						{/foreach}
						</select>
					</td>
				</tr>
				<tr>
					<td class="fieldarea">Domain Name:</td>
					<td>
						<select id="googleapps_domain">
						{foreach key=k item=domain from=$googleapps_domains}
							<option value="{$domain}">{$domain}</option>
						{/foreach}
						</select>
					</td>
				</tr>
				<tr>
					<td class="fieldarea">MX Mode:</td>
					<td>
						<select id="googleapps_config">
							<option value="local">Local</option>
							<option value="google">Google Apps</option>
						</select>
					</td>
				</tr>
				<tr>
					<td colspan="2" align="center">
						<input id="googleapps_btn" type="button" class="button" value="Apply Changes" />
					</td>
				</tr>
				<tr>
					<td colspan="2" align="center"><div id="googleapps_loading"><img src="images/loading.gif" /></div></td>
				</tr>
				<tr>
					<td colspan="2" align="center">
						<div id="googleapps_results" class="hide">
							<div class="successbox" id="googleapps_msg"></div>
							<div class="errorbox" id="googleapps_error"></div>
						</div>
					</td>
				</tr>
			</table>
		</td></tr>
	</table>
</form>
<br />
{/if}

<!-- END GOOGLE APPS ADDON CODE -->
