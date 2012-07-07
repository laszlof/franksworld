<p>This list shows all of our servers uptime percentages.</p>
<table width="100%" border="0" align="center" cellpadding="10" cellspacing="0" class="data">
<tr>
<th><strong>Server Name</strong></th><th><strong>Cumulative Uptime</strong></th><th><strong>Uptime This Month</strong></th></tr>
{foreach key=num item=uptime from=$serveruptime}
<tr><td>{$uptime.name}</td><td>{$uptime.ttluptime}%</td><td>{$uptime.muptime}%</td></tr>
{foreachelse}
<tr>
<td colspan="3">No servers have been configured.</td>
</tr>
{/foreach}
</table>
