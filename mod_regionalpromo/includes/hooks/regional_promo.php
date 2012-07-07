<?php

function verify_promocode_region($vars) {
	global $errormessage;
	if (isset($_SESSION['cart']['promo'])) {
		$promocode = $_SESSION['cart']['promo'];
		$query = "SELECT r.type, r.value FROM mod_regionalpromo r, tblpromotions p WHERE p.code = '$promocode' AND p.id = r.coupon";
		$data = mysql_query($query);
		if (mysql_num_rows($data)) {
			$country = $_SESSION['cart']['user']['country'];
			$city = str_replace(" ", "", strtoupper($_SESSION['cart']['user']['city']));
			$state = str_replace(" ", "", strtoupper($_SESSION['cart']['user']['state']));
			$postcode = str_replace(" ", "", strtoupper($_SESSION['cart']['user']['postcode']));
			$isinvalid = 0;
			while ($r = mysql_fetch_array($data)) {
				$type = $r[0];
				$value = explode(",", $r[1]);
				switch ($type) {
					case "country":
						if (array_search($country, $value) != "") {
							$isinvalid = $isinvalid + 1;
						}
						break;
					case "city":
						if (array_search($city, $value) != "") {
							$isinvalid = $isinvalid + 1;
						}
						break;
					case "state":
						if (array_search($state, $value) != "") {
							$isinvalid = $isinvalid + 1;
						}
						break;
					case "zipcode":
						if (array_search($postcode, $value) != "") {
							$isinvalid = $isinvalid + 1;
						}
						break;
				}
			}
			if ($isinvalid > 0) {
				$_SESSION['cart']['promo'] = "";
				$errormessage = "# You do not meet the regional requirements for this promotion code #<br /> # Promotion code has been removed!";
			}
		}
	}
}

add_hook("ShoppingCartValidateCheckout",1,"verify_promocode_region","");

?>
