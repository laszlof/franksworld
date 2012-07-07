<?php

define("CLIENTAREA",true);
require("dbconnect.php");
require("includes/functions.php");
require("includes/clientareafunctions.php");

$pagetitle = "Uptime Report";
$pageicon = "images/support/clientarea.gif";
$breadcrumbnav = '<a href="index.php">'.$_LANG['globalsystemname'].'</a>';
$breadcrumbnav .= ' > <a href="serveruptime.php">Uptime</a>'; 

initialiseClientArea($pagetitle,$pageicon,$breadcrumbnav);

$data = mysql_query("SELECT s.id, s.name, u.ttluptime, u.muptime FROM serveruptime u, tblservers s WHERE s.id=u.srv_id ORDER BY s.name ASC");
$serveruptime = array();
while ($r = mysql_fetch_array($data)) {
	$sid = $r[0];
	$name = $r[1];
	$ttluptime = $r[2];
	$muptime = $r[3];
	$serveruptime[] = array("name"=>$name, "ttluptime"=>$ttluptime, "muptime"=>$muptime);
}
$smartyvalues["serveruptime"] = $serveruptime; 

$templatefile = "serveruptime"; 
outputClientArea($templatefile);

?>
