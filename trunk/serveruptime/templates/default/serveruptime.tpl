<p>This list shows all of our servers uptime percentages.</p>
<table class="clientareatable" align="center" cellspacing="1">
<tr class="clientareatableheading">
<td>Server Name</td><td>Cumulative Uptime</td><td>Uptime This Month</td></tr>
{foreach key=num item=uptime from=$serveruptime}
<tr class="clientareatableactive"><td>{$uptime.name}</td><td>{$uptime.ttluptime}%</td><td>{$uptime.muptime}%</td></tr>
{foreachelse}
<tr class="clientareatableactive">
<td colspan="3">No servers have been configured.</td>
</tr>
{/foreach}
</table>
