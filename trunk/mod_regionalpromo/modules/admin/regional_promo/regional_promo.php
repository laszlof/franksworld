<?php

if(!mysql_num_rows( mysql_query("SHOW TABLES LIKE 'mod_regionalpromo'"))) {
	if (!$_GET["install"]) {
		print "
				<p><strong>Not Yet Installed</strong></p>
				<p>This addon will allow you to assign regional restrictions to promotion codes.</p>
				<p>To install it, click on the button below.</p>
				<p><input type=\"button\" value=\"Install Regional Promo\" onclick=\"window.location='$modulelink&install=true'\"></p>";
	} else {
		$query = "CREATE TABLE  `mod_regionalpromo` (
				`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`coupon` INT NOT NULL ,
				`type` VARCHAR( 16 ) NOT NULL ,
				`value` VARCHAR( 1024 ) NOT NULL
						)";
		$result=mysql_query($query);
		header("Location: $modulelink");
		exit;
	}
} else {

	if (isset($_REQUEST['cmd'])) {
		switch ($_REQUEST['cmd']) {
			case "del":
				$resid = $_REQUEST['resid'];
				$data = select_query('mod_regionalpromo', 'id', array('id'=>$resid));
				if (mysql_num_rows($data)) {
					delete_query("mod_regionalpromo", "id='$resid'");
				}
				break;
			case "add":
				$couponid = $_POST['coupon'];
				$country = $_POST['country'];
				$zipcode = $_POST['zipcode'];
				$city = $_POST['city'];
				$state = $_POST['state'];

				// Process Countries
				if ($country) {
					foreach ($country as $v) {
						if ($con_country) {
							$con_country = "$v,$con_country";
						} else {
							$con_country = $v;
						}
					}
					insert_query('mod_regionalpromo', array("coupon"=>$couponid, "type"=>"country", "value"=>"$con_country"));
				}

				// Process Postal Code
				if ($zipcode) {
					$zip_replace = array(' ', '-');
					$format_zip = strtoupper(str_replace($zip_replace, "", $zipcode));
					insert_query('mod_regionalpromo', array("coupon"=>$couponid, "type"=>"zipcode", "value"=>"$format_zip"));
				}

				// Process City
				if ($city) {
					$format_city = strtoupper(str_replace(", ", ",", $city));
					$format_city = str_replace(" ,", ",", $format_city);
					$format_city = str_replace(" ", "", $format_city);
					insert_query('mod_regionalpromo', array("coupon"=>$couponid, "type"=>"city", "value"=>"$format_city"));
				}

				// Process State
				if ($state) {
					$format_state = strtoupper(str_replace(", ", ",", $state));
					$format_state = str_replace(" ,", ",", $format_state);
					$format_state = str_replace(" ", "", $format_state);
					insert_query('mod_regionalpromo', array("coupon"=>$couponid, "type"=>"state", "value"=>"$format_state"));
				}

				break;
		}
	}


	print "<h3>Add New Restriction</h3>
			(separate multiple entries with a comma.)
			<form method=\"POST\" action=\"$modulelink&cmd=add\">
			<table class=\"form\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">
			<tr><td width=\"25%\" class=\"fieldlabel\">Coupon Code:</td><td class=\"fieldarea\">
			<select name=\"coupon\">";
	$data = select_query('tblpromotions', 'id, code');
	if (mysql_num_rows($data)) {
		while ($r = mysql_fetch_array($data)) {
			$couponid = $r[0];
			$couponcode = $r[1];
			print "<option value=\"$couponid\">$couponcode</option>";
		}
	}
	print '</select></td></tr>
			<tr><td class="fieldlabel">Restrict Country:</td><td class="fieldarea">
				<select multiple size=\"5\" name="country[]">
				<option value="AF">Afghanistan</option>
				<option value="AX">Aland Islands</option>
				<option value="AL">Albania</option>
				<option value="DZ">Algeria</option>
				<option value="AS">American Samoa</option>
				<option value="AD">Andorra</option>
				<option value="AO">Angola</option>
				<option value="AI">Anguilla</option>
				<option value="AQ">Antarctica</option>
				<option value="AG">Antigua And Barbuda</option>
				<option value="AR">Argentina</option>
				<option value="AM">Armenia</option>
				<option value="AW">Aruba</option>
				<option value="AU">Australia</option>
				<option value="AT">Austria</option>
				<option value="AZ">Azerbaijan</option>
				<option value="BS">Bahamas</option>
				<option value="BH">Bahrain</option>
				<option value="BD">Bangladesh</option>
				<option value="BB">Barbados</option>
				<option value="BY">Belarus</option>
				<option value="BE">Belgium</option>
				<option value="BZ">Belize</option>
				<option value="BJ">Benin</option>
				<option value="BM">Bermuda</option>
				<option value="BT">Bhutan</option>
				<option value="BO">Bolivia</option>
				<option value="BA">Bosnia And Herzegovina</option>
				<option value="BW">Botswana</option>
				<option value="BV">Bouvet Island</option>
				<option value="BR">Brazil</option>
				<option value="IO">British Indian Ocean Territory</option>
				<option value="BN">Brunei Darussalam</option>
				<option value="BG">Bulgaria</option>
				<option value="BF">Burkina Faso</option>
				<option value="BI">Burundi</option>
				<option value="KH">Cambodia</option>
				<option value="CM">Cameroon</option>
				<option value="CA">Canada</option>
				<option value="CV">Cape Verde</option>
				<option value="KY">Cayman Islands</option>
				<option value="CF">Central African Republic</option>
				<option value="TD">Chad</option>
				<option value="CL">Chile</option>
				<option value="CN">China</option>
				<option value="CX">Christmas Island</option>
				<option value="CC">Cocos (Keeling) Islands</option>
				<option value="CO">Colombia</option>
				<option value="KM">Comoros</option>
				<option value="CG">Congo</option>
				<option value="CD">Congo, Democratic Republic</option>
				<option value="CK">Cook Islands</option>
				<option value="CR">Costa Rica</option>
				<option value="CI">Cote D\'Ivoire</option>
				<option value="HR">Croatia</option>
				<option value="CU">Cuba</option>
				<option value="CY">Cyprus</option>
				<option value="CZ">Czech Republic</option>
				<option value="DK">Denmark</option>
				<option value="DJ">Djibouti</option>
				<option value="DM">Dominica</option>
				<option value="DO">Dominican Republic</option>
				<option value="EC">Ecuador</option>
				<option value="EG">Egypt</option>
				<option value="SV">El Salvador</option>
				<option value="GQ">Equatorial Guinea</option>
				<option value="ER">Eritrea</option>
				<option value="EE">Estonia</option>
				<option value="ET">Ethiopia</option>
				<option value="FK">Falkland Islands (Malvinas)</option>
				<option value="FO">Faroe Islands</option>
				<option value="FJ">Fiji</option>
				<option value="FI">Finland</option>
				<option value="FR">France</option>
				<option value="GF">French Guiana</option>
				<option value="PF">French Polynesia</option>
				<option value="TF">French Southern Territories</option>
				<option value="GA">Gabon</option>
				<option value="GM">Gambia</option>
				<option value="GE">Georgia</option>
				<option value="DE">Germany</option>
				<option value="GH">Ghana</option>
				<option value="GI">Gibraltar</option>
				<option value="GR">Greece</option>
				<option value="GL">Greenland</option>
				<option value="GD">Grenada</option>
				<option value="GP">Guadeloupe</option>
				<option value="GU">Guam</option>
				<option value="GT">Guatemala</option>
				<option value="GG">Guernsey</option>
				<option value="GN">Guinea</option>
				<option value="GW">Guinea-Bissau</option>
				<option value="GY">Guyana</option>
				<option value="HT">Haiti</option>
				<option value="HM">Heard Island & Mcdonald Islands</option>
				<option value="VA">Holy See (Vatican City State)</option>
				<option value="HN">Honduras</option>
				<option value="HK">Hong Kong</option>
				<option value="HU">Hungary</option>
				<option value="IS">Iceland</option>
				<option value="IN">India</option>
				<option value="ID">Indonesia</option>
				<option value="IR">Iran, Islamic Republic Of</option>
				<option value="IQ">Iraq</option>
				<option value="IE">Ireland</option>
				<option value="IM">Isle Of Man</option>
				<option value="IL">Israel</option>
				<option value="IT">Italy</option>
				<option value="JM">Jamaica</option>
				<option value="JP">Japan</option>
				<option value="JE">Jersey</option>
				<option value="JO">Jordan</option>
				<option value="KZ">Kazakhstan</option>
				<option value="KE">Kenya</option>
				<option value="KI">Kiribati</option>
				<option value="KR">Korea</option>
				<option value="KW">Kuwait</option>
				<option value="KG">Kyrgyzstan</option>
				<option value="LA">Lao People\'s Democratic Republic</option>
				<option value="LV">Latvia</option>
				<option value="LB">Lebanon</option>
				<option value="LS">Lesotho</option>
				<option value="LR">Liberia</option>
				<option value="LY">Libyan Arab Jamahiriya</option>
				<option value="LI">Liechtenstein</option>
				<option value="LT">Lithuania</option>
				<option value="LU">Luxembourg</option>
				<option value="MO">Macao</option>
				<option value="MK">Macedonia</option>
				<option value="MG">Madagascar</option>
				<option value="MW">Malawi</option>
				<option value="MY">Malaysia</option>
				<option value="MV">Maldives</option>
				<option value="ML">Mali</option>
				<option value="MT">Malta</option>
				<option value="MH">Marshall Islands</option>
				<option value="MQ">Martinique</option>
				<option value="MR">Mauritania</option>
				<option value="MU">Mauritius</option>
				<option value="YT">Mayotte</option>
				<option value="MX">Mexico</option>
				<option value="FM">Micronesia, Federated States Of</option>
				<option value="MD">Moldova</option>
				<option value="MC">Monaco</option>
				<option value="MN">Mongolia</option>
				<option value="ME">Montenegro</option>
				<option value="MS">Montserrat</option>
				<option value="MA">Morocco</option>
				<option value="MZ">Mozambique</option>
				<option value="MM">Myanmar</option>
				<option value="NA">Namibia</option>
				<option value="NR">Nauru</option>
				<option value="NP">Nepal</option>
				<option value="NL">Netherlands</option>
				<option value="AN">Netherlands Antilles</option>
				<option value="NC">New Caledonia</option>
				<option value="NZ">New Zealand</option>
				<option value="NI">Nicaragua</option>
				<option value="NE">Niger</option>
				<option value="NG">Nigeria</option>
				<option value="NU">Niue</option>
				<option value="NF">Norfolk Island</option>
				<option value="MP">Northern Mariana Islands</option>
				<option value="NO">Norway</option>
				<option value="OM">Oman</option>
				<option value="PK">Pakistan</option>
				<option value="PW">Palau</option>
				<option value="PS">Palestinian Territory, Occupied</option>
				<option value="PA">Panama</option>
				<option value="PG">Papua New Guinea</option>
				<option value="PY">Paraguay</option>
				<option value="PE">Peru</option>
				<option value="PH">Philippines</option>
				<option value="PN">Pitcairn</option>
				<option value="PL">Poland</option>
				<option value="PT">Portugal</option>
				<option value="PR">Puerto Rico</option>
				<option value="QA">Qatar</option>
				<option value="RE">Reunion</option>
				<option value="RO">Romania</option>
				<option value="RU">Russian Federation</option>
				<option value="RW">Rwanda</option>
				<option value="BL">Saint Barthelemy</option>
				<option value="SH">Saint Helena</option>
				<option value="KN">Saint Kitts And Nevis</option>
				<option value="LC">Saint Lucia</option>
				<option value="MF">Saint Martin</option>
				<option value="PM">Saint Pierre And Miquelon</option>
				<option value="VC">Saint Vincent And Grenadines</option>
				<option value="WS">Samoa</option>
				<option value="SM">San Marino</option>
				<option value="ST">Sao Tome And Principe</option>
				<option value="SA">Saudi Arabia</option>
				<option value="SN">Senegal</option>
				<option value="RS">Serbia</option>
				<option value="SC">Seychelles</option>
				<option value="SL">Sierra Leone</option>
				<option value="SG">Singapore</option>
				<option value="SK">Slovakia</option>
				<option value="SI">Slovenia</option>
				<option value="SB">Solomon Islands</option>
				<option value="SO">Somalia</option>
				<option value="ZA">South Africa</option>
				<option value="GS">South Georgia And Sandwich Isl.</option>
				<option value="ES">Spain</option>
				<option value="LK">Sri Lanka</option>
				<option value="SD">Sudan</option>
				<option value="SR">Suriname</option>
				<option value="SJ">Svalbard And Jan Mayen</option>
				<option value="SZ">Swaziland</option>
				<option value="SE">Sweden</option>
				<option value="CH">Switzerland</option>
				<option value="SY">Syrian Arab Republic</option>
				<option value="TW">Taiwan</option>
				<option value="TJ">Tajikistan</option>
				<option value="TZ">Tanzania</option>
				<option value="TH">Thailand</option>
				<option value="TL">Timor-Leste</option>
				<option value="TG">Togo</option>
				<option value="TK">Tokelau</option>
				<option value="TO">Tonga</option>
				<option value="TT">Trinidad And Tobago</option>
				<option value="TN">Tunisia</option>
				<option value="TR">Turkey</option>
				<option value="TM">Turkmenistan</option>
				<option value="TC">Turks And Caicos Islands</option>
				<option value="TV">Tuvalu</option>
				<option value="UG">Uganda</option>
				<option value="UA">Ukraine</option>
				<option value="AE">United Arab Emirates</option>
				<option value="GB">United Kingdom</option>
				<option value="US">United States</option>
				<option value="UM">United States Outlying Islands</option>
				<option value="UY">Uruguay</option>
				<option value="UZ">Uzbekistan</option>
				<option value="VU">Vanuatu</option>
				<option value="VE">Venezuela</option>
				<option value="VN">Viet Nam</option>
				<option value="VG">Virgin Islands, British</option>
				<option value="VI">Virgin Islands, U.S.</option>
				<option value="WF">Wallis And Futuna</option>
				<option value="EH">Western Sahara</option>
				<option value="YE">Yemen</option>
				<option value="ZM">Zambia</option>
				<option value="ZW">Zimbabwe</option>
				</select></td></tr>';
	print "<tr><td class=\"fieldlabel\">Restrict Postal Code:</td><td class=\"fieldarea\"><input type=\"text\" name=\"zipcode\" size=\"32\"></td></tr>
			<tr><td class=\"fieldlabel\">Restrict City:</td><td class=\"fieldarea\"><input type=\"text\" name=\"city\" size=\"32\"></td></tr>
			<tr><td class=\"fieldlabel\">Restrict State:</td><td class=\"fieldarea\"><input type=\"text\" name=\"state\" size=\"32\"></td></tr>
			<tr><td class=\"fieldlabel\" colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"Add\" /><input type=\"reset\" value=\"Clear\"></td></tr>";
	print "</table></form>";

	print "<h3>Current Restrictions</h3>
			<div class=\"tablebg\">
			<table class=\"datatable\" cellspacing=\"1\" cellpadding=\"3\">
			<tr><th>&nbsp;</th><th>Coupon Code</th><th>Restriction Type</th><th>Restriction Value</th></tr>";
	$query = "SELECT r.id, p.code, r.type, r.value FROM mod_regionalpromo r, tblpromotions p WHERE p.id = r.coupon";
	$data = mysql_query($query);
	if (mysql_num_rows($data)) {
		while ($r = mysql_fetch_array($data)) {
			$resid = $r[0];
			$couponcode = $r[1];
			$type = $r[2];
			$value = $r[3];
			print "<tr>
					<td><a href=\"$modulelink&cmd=del&resid=$resid\"><img src=\"images/icons/delete.png\"></a></td><td>$couponcode</td><td>$type</td><td>$value</td></tr>";
		}
	} else {
		print "<tr><td colspan=\"4\">No current restrictions configured.</td></tr>";
	}
	print "</table><br />
			<p align=\"left\"><h5>Regional Promotions was written by <a href=\"mailto:frank@asmallorange.com\">Frank Laszlo</a> at <a href=\"http://www.asmallorange.com\">A Small Orange, LLC</a><br />Version: 1.0</h5></p>";
}

?>
