<?php

function unblockme_show($vars) {
	global $smarty;
	$module = $smarty->get_template_vars('modulename');
	$server = $smarty->get_template_vars('server');
	$status = $smarty->get_template_vars('status');
	$lang_arr = $smarty->get_template_vars('LANG');
	$activetxt = $lang_arr['clientareaactive'];
	if (($module == "cpanel") && ($server) && ($status == $activetxt)) {
		$isactive = 1;
	} else {
		$isactive = 0;
	}
	$smarty->assign("unblockme_isactive", $isactive);
}

add_hook("ClientAreaPage", 1, "unblockme_show");

?>
