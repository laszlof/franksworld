<?php

function wr_dologin($ch, $user, $pass) {
	$cookie = sys_get_temp_dir().'/whmcsr_cookies.txt';
	$url = "https://www.whmcs.com/members/dologin.php";
	$postfields['username'] = $user;
	$postfields['password'] = $pass;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 300);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST , false);
	$data = curl_exec($ch);
	if (curl_errno($ch)) {
		return 1;
	} else {
		return 0;
	}
}

function wr_get_licenses($ch) {
	$url = "https://www.whmcs.com/members/clientarea.php?action=products&itemlimit=all";
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_TIMEOUT, 300);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST , false);
	$content = curl_exec($ch);

	$all_licenses = array();
	$pattern = '/<tr class="clientareatableactive">.*?<td>Licenses(.*?)<\/tr>/s';
	preg_match_all($pattern, $content, $matches, PREG_PATTERN_ORDER);
	foreach ($matches[1] as $k => $v) {
		$lic_pattern = '/<a href="\/members\/clientarea.php\?action=productdetails&id=.*?">(.*?)<\/a><\/td>/';
		$id_pattern = '/<input type="hidden" name="id" value="(.*?)" \/>/';
		preg_match($lic_pattern, $v, $license);
		preg_match($id_pattern, $v, $id);
		$all_licenses[$k] = array("id"=>$id[1], "license"=>$license[1]);
	}
	return $all_licenses;
}

function wr_get_lic_id_byuser($userid, $serviceid) {
	$data = select_query("whmcsresellerlicenses", "license_id", array("user_id"=>$userid, "prod_id"=>$serviceid));
	if (mysql_num_rows($data)) {
		$r = mysql_fetch_array($data);
		return $r[0];
	}
}

function wr_get_lic_info($ch, $id) {
	$url = "https://www.whmcs.com/members/clientarea.php?action=productdetails";
	$postfields['id'] = $id;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 300);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST , false);
	$content = curl_exec($ch);
	$lic_pat = '/<tr><td class="fieldarea">License Key:<\/td><td><input type="text" size="40" value="(.*?)" readonly=true><\/td><\/tr>/';
	$domain_pat = '/<tr><td class="fieldarea">Valid Domains:<\/td><td><textarea rows=2 cols=80 readonly=true>(.*?)<\/textarea><\/td><\/tr>/';
	$ips_pat = '/<tr><td class="fieldarea">Valid IPs:<\/td><td><textarea rows=2 cols=80 readonly=true>(.*?)<\/textarea><\/td><\/tr>/';
	$directory_pat = '/<tr><td class="fieldarea">Valid Directory:<\/td><td><textarea rows=2 cols=80 readonly=true>(.*?)<\/textarea><\/td><\/tr>/';
	$status_pat = '/<tr><td class="fieldarea">Status:<\/td><td>(.*?)<\/td><\/tr>/';
	$type_pat = '/<tr><td class="fieldarea">License Type:<\/td><td>(.*?)<\/td><\/tr>/';
	preg_match($lic_pat, $content, $license);
	preg_match($domain_pat, $content, $domain);
	preg_match($ips_pat, $content, $ips);
	preg_match($directory_pat, $content, $directory);
	preg_match($status_pat, $content, $status);
	preg_match($type_pat, $content, $type);
	if (preg_match('/No Branding/', $type[1]) > 0) {
		$lic_type = "nobranding";
	} else {
		$lic_type = "branding";
	}
	$license_info = array("domains"=>$domain[1], "ips"=>$ips[1], "directory"=>$directory[1], "status"=>$status[1], "license"=>$license[1], "type"=>$lic_type);
	return $license_info;
}

function wr_reissue_lic($ch, $id) {
	$url = "https://www.whmcs.com/members/clientarea.php?action=productdetails";
	$postfields['id'] = $id;
	$postfields['serveraction'] = "custom";
	$postfields['a'] = "reissue";
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 300);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST , false);
	$content = curl_exec($ch);
}
