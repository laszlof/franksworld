<?php

include('../../../configuration.php');
$db = mysql_connect($db_host, $db_username, $db_password) or die ("Error connecting to database.");
mysql_select_db($db_name, $db) or die ("Couldn't select the database.");

$conf_data = mysql_query("SELECT * FROM serveruptimeconf");
while ($r = mysql_fetch_array($conf_data)) {
	switch ($r['name']) {
		case "groups":
			$srv_groups = explode(",", $r['value']);
			break;
		case "timeout":
			$timeout = $r['value'];
			break;
	}
}

foreach ($srv_groups as $g) {
	if (!$groups_sql) {
		$groups_sql = 'WHERE (gr.groupid='.$g;
	} else {
		$groups_sql .= ' OR gr.groupid='.$g;
	}
}


$sql = "SELECT s.id, s.statusaddress FROM tblservers s, tblservergroupsrel gr $groups_sql) AND s.statusaddress != '' AND gr.serverid=s.id";
$srv_data = mysql_query($sql);
while ($r = mysql_fetch_array($srv_data)) {
	$ch = curl_init();
	$srv_id = $r[0];
	$srv_url = $r[1];
	curl_setopt($ch, CURLOPT_URL, $srv_url);
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD');
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_NOBODY, 1);
	$data = curl_exec($ch);
	$resp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	$srv_resp[] = array("id"=>$srv_id, "resp"=>$resp);
}

$month = date("n");
foreach ($srv_resp as $k => $v) {
	$srv_id = $v['id'];
	$srv_resp = $v['resp'];
	/*
	if ($resp != "200") {
		$status = 0;
	} else {
		$status = 1;
	}
	*/
	$sql = "INSERT INTO serveruptimedata (`srv_id`, `resp`, `timestamp`) VALUES ($srv_id, $srv_resp, NOW())";
	mysql_query($sql);
	$sql = "SELECT * FROM serveruptimedata WHERE srv_id = $srv_id";
	$data = mysql_query($sql);
	$ttlcount = 0;
	$ttlmcount = 0;
	$mupcount = 0;
	$ttlupcount = 0;
	while ($r = mysql_fetch_array($data)) {
		$resp = $r['resp'];
		$timestamp = $r['timestamp'];
		$tmonth = date("n", strtotime($timestamp));
		if ($tmonth == $month) {
			$ttlmcount++;
			if ($resp == "200") {
				$mupcount++;
				$ttlupcount++;
			}
		} else {
			if ($resp == "200") {
				$ttlupcount++;
			}
		}
		$ttlcount++;

	}
	$uptimearr[] = array("id"=>$srv_id, "ttlcount"=>$ttlcount, "ttlupcount"=>$ttlupcount, "mcount"=>$ttlmcount, "mupcount"=>$mupcount);
}


foreach ($uptimearr as $k => $v) {
	$srv_id = $v['id'];
	$ttlcount = $v['ttlcount'];
	$ttlupcount = $v['ttlupcount'];
	$mcount = $v['mcount'];
	$mupcount = $v['mupcount'];
	$ttluptime = round(floatval($ttlupcount / $ttlcount)*100, 3);
	$muptime = round(floatval($mupcount / $mcount)*100, 3);
	if ($ttluptime < 100) {
		$ttluptime = number_format($ttluptime, 3);
	}
	if ($muptime < 100) {
		$muptime = number_format($muptime, 3);
	}
	$data = mysql_query("SELECT srv_id FROM serveruptime WHERE srv_id=$srv_id");
	if (!mysql_num_rows($data)) {
		$sql = "INSERT INTO serveruptime (`srv_id`, `ttluptime`, `muptime`) VALUES ($srv_id, $ttluptime, $muptime)";
	} else {
		$sql = "UPDATE serveruptime SET ttluptime=$ttluptime, muptime=$muptime WHERE srv_id=$srv_id";
	}
	mysql_query($sql);
}

?>
