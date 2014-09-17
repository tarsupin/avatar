<?php
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");
	
	if (!isset($fetch_avatar['id']))
	{
		header("Location: index.php");
		exit;
	}
?>
<!DOCTYPE HTML>
<html>
	<head>
		<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
		<title>Avatar Preview</title>
		
		<!-- stylesheets -->
		<link rel='stylesheet' href='css/reset.css' type='text/css' media='screen' charset='utf-8'>
		<link rel='stylesheet' href='css/avatar.css' type='text/css' media='screen' charset='utf-8'>
		<style>html { background-image:none; min-width:620px; }</style>
		
		<!-- favicon -->
		<link rel='icon' href='favicon.ico' type='image/x-icon'>
		<link rel='shortcut icon' href='favicon.ico' type='image/x-icon'> 
		
		<!-- rss -->
		<link rel='alternate' type='application/rss+xml' title='RSS' href='http://forum.unifaction.com/rss/news.xml'/>
		
		<!-- javascript -->
		<script src='javascript/jquery.js' type='text/javascript' charset='utf-8'></script>
		<script src='javascript/jquery-ui.js' type='text/javascript' charset='utf-8'></script>
		
		<!-- javascript for touch devices, source: http://touchpunch.furf.com/ -->
		<script src='javascript/jquery.ui.touch-punch.min.js' type='text/javascript' charset='utf-8'></script>
	</head>
	<body>
<?php
	foreach ($_GET as $key => $val)
		$_GET[$key] = trim($val);
	foreach ($_POST as $key => $val)
		$_POST[$key] = trim($val);
		
	$bases = array("white", "pacific", "light", "tan", "dark");
	
	$shops2 = array();
	$q = mysql_query("SELECT `id` FROM `shop_listings` WHERE `clearance`<='" . ($fetch_account['clearance'] + 0) . "'");
	while ($row = mysql_fetch_assoc($q))
		$shops2[] = $row['id'];
	
	function purchase($id)
	{
		global $fetch_account;
		global $fetch_avatar;
		global $shops2;
		global $siteName;
		global $siteKey;
		$q = "SELECT `clothingID`, `clothing`, `position`, `used_by`, `exoticPackage`, `shopID`, `cost`, `cost_credits`, `purchase_yes` FROM `clothing_images` WHERE `clothingID`='" . ($id + 0) . "' LIMIT 1";
		$result = mysql_query($q);
		if ($fetch_purchase = mysql_fetch_assoc($result))
		{
			if ((($fetch_purchase['purchase_yes'] == "" && $fetch_purchase['exoticPackage'] == 0) || $fetch_account['clearance'] >= 5) && in_array($fetch_purchase['shopID'], $shops2))
			{
				$q = "SELECT `clothingID` FROM `avatar_clothing` WHERE `account`='" . protectSQL($fetch_account['account']) . "' AND `position`='" . protectSQL($fetch_purchase['position']) . "' AND `clothingID`='" . ($fetch_purchase['clothingID'] + 0) . "'";
				$result = mysql_query($q);
				if (mysql_num_rows($result) > 0 && !isset($_GET['forcePurchase']))
				{
					return "<div class='message-neutral'>You are about to purchase an item you already own!<br/>If you are sure that this is what you want to do, please confirm by clicking <a href='preview_avi.php?buy=" . $fetch_purchase['clothingID'] . "&forcePurchase=true'>here</a>.</div>";
				}
				elseif ($fetch_purchase['used_by'] != "both" && $fetch_purchase['used_by'] != $fetch_avatar['gender'] && !isset($_GET['forcePurchase']))
				{
					return "<div class='message-neutral'>You are about to purchase " . $fetch_purchase['clothing'] . ". This item can only be used by avatars of the opposite gender!<br/>If you are sure that this is what you want to do, please confirm by clicking <a href='preview_avi.php?buy=" . $fetch_purchase['clothingID'] . "&forcePurchase=true'>here</a>.</div>";
				}
				else
				{
					// check and, if okay, spend Auro and Credits
					if ($fetch_purchase['cost'] == 0 && $fetch_purchase['cost_credits'] == 0)
						$value = "SUCCESS";
					else
						$value = file_get_contents("http://auth.unifaction.com/API_autoSpendBoth.php?account=" . $fetch_account['account'] . "&auro=" . ($fetch_purchase['cost'] + 0) . "&credits=" . ($fetch_purchase['cost_credits']+0) . "&site=" . $siteName . "&hash=" . hash("sha256", "@uto$37^&" . $fetch_purchase['cost'] . "*(futa)credits003#" . $siteName . $siteKey . $fetch_account['account'] . $fetch_purchase['cost_credits']));
					if ($value == "SUCCESS")
					{
						mysql_query("INSERT INTO `avatar_clothing` (`account`,`clothingID`,`position`) VALUES ('" . protectSQL($fetch_account['account']) . "', '" . ($fetch_purchase['clothingID'] + 0) . "', '" . protectSQL($fetch_purchase['position']) . "')");
						if (mysql_num_rows($result) == 0)
							return "<div class='message-success'>You have purchased " . $fetch_purchase['clothing'] . "!</div>";
						else
							return "<div class='message-success'>You now have " . (mysql_num_rows($result) + 1) . " sets of " . $fetch_purchase['clothing'] . ".</div>";
					}
					else
						return "<div class='message-error'>You need more Auro and/or Credits to purchase " . $fetch_purchase['clothing'] . "!</div>";
				}
			}
			elseif (isset($_GET['buy']))
				return "<div class='message-error'>" . $fetch_purchase['clothing'] . " has been set to PREVIEW ONLY. You cannot purchase it.</div>";
		}
		return false;
	}

	define("EXIST", "maysee");
	require("fanctions/check_and_draw.php");
	
	$base = $fetch_avatar['base'];
	$total = 0;
	$total_credits = 0;
	$cost = 0;
	$credits = 0;
	$outfit = get_preview_outfit($fetch_account['account'], $fetch_avatar['base']);
	foreach ($outfit as $key => $val)
		if ($val[0] == 0)
		{
			$base = $val[1];
			break;
		}
	if (!in_array($base, $bases))
		$base = $fetch_avatar['base'];

	if ($fetch_avatar['gender'] == "female")
		$oppositeGender = "male";
	else
		$oppositeGender = "female";
	
	if (isset($_GET['clothingID']) && isset($_GET['recolor']))
	{
		foreach ($outfit as $key => $val)
		if ($val[0] == $_GET['clothingID'])
		{
			unset($outfit[$key]);
			break;
		}
		$outfit[] = array($_GET['clothingID'], $_GET['recolor']);
	}
	elseif (isset($_GET['delete']))
	{
		foreach ($outfit as $key => $val)
			if ($val[0] == $_GET['delete'])
			{
				unset($outfit[$key]);
				break;
			}
	}
	elseif (isset($_GET['delete_all']))
	{
		$outfit = array();
		$outfit[] = array(0, $base);
	}
	elseif (isset($_GET['current']))
		$outfit = get_outfit($fetch_avatar['id'], $fetch_avatar['base']);
	elseif (isset($_GET['buy']))
	{
		if ($test = purchase($_GET['buy']))
			$messages[] = $test;
	}
	elseif (isset($_GET['buyall']))
	{
		foreach ($outfit as $o)
			if ($o[0] > 0 && !owned_item($o[0], $fetch_account['account']))
			{
				if ($test = purchase($o[0]))
					$messages[] = $test;
			}
	}
	elseif (isset($_POST['order']))
	{
		$outfit = array();
		$items = explode(",", $_POST['order']);
		foreach ($items as $item)
		{
			$id = substr($item, 1);
			if (substr($item, 0, 1) == "o" && is_numeric($id))
			{
				if (isset($_POST['i' . $id]))
					$outfit[] = array($id, $_POST['i' . $id]);
				if ($id == 0)
					$base = $_POST['i0'];
			}
		}
	}
	
	if (!isset($_GET['current']))
		$outfit = wrapper($outfit, $fetch_avatar['gender'], $base, $fetch_account['account'], 0);
	else
		$outfit = wrapper($outfit, $fetch_avatar['gender'], $fetch_avatar['base'], $fetch_account['account'], 0);
		
	foreach ($bases as $k => $v)
		$bases[$k] = "<option value='" . $v . "'" . ($base == $v ? " selected='selected'" : "") . ">" . ucfirst($v) . "</option>";

	foreach ($messages as $m)
		echo "		" . $m . "
";
?>

		<table style='width:100%;'>
			<tr>
				<td style='vertical-align:top; width:205px; font-size:14px;'>
					<img src='characters/<?php echo $fetch_account['account']; ?>/avi_preview.png?t=<?php echo time(); ?>'/>
					<ul style='list-style:none; margin:0px;'>
						<li><a href='preview_avi.php?delete_all=true'>Unequip All</a></li>
						<li><a href='preview_avi.php?current'>Replace with Avatar Image</a></li>
					</ul>
				</td>
				<td style='vertical-align:top;'>
					<form id='sortable' action='preview_avi.php' method='post'>
						<ul id='equipped' class='dragndrop'>
<?php
	$outfit = array_reverse($outfit);
	foreach ($outfit as $s)
	{
		if ($s[0] > 0)
		{
			$fetch_detail = fetch_item_details($s[0]);
			$dir = "avatars/" . $fetch_detail['position'] . "/" . $fetch_detail['clothing'];
			if (!file_exists($dir))
			{
				$fetch_detail = renew_item_details($s[0]);
				$dir = "avatars/" . $fetch_detail['position'] . "/" . $fetch_detail['clothing'];
			}
			$options = array();
			$colors = scandir($dir);
			foreach ($colors as $val)
			{
				if ($fetch_detail['used_by'] != $oppositeGender)
				{
					if (strpos($val, "_" . $fetch_avatar['gender'] . ".png"))
					{
						$val = str_replace("_" . $fetch_avatar['gender'] . ".png", "", $val);
						$options[] = "<option value='" . $val . "'" . ($val == $s[1] ? " selected='selected'" : "") . ">" . $val . "</option>";
					}
				}
				elseif (strpos($val, "_" . $oppositeGender . ".png"))
				{
					$val = str_replace("_" . $oppositeGender . ".png", "", $val);
					$options[] = "<option value='" . $val . "'" . ($val == $s[1] ? " selected='selected'" : "") . ">" . $val . "</option>";
				}
			}

			echo "
							<li id='o" . $s[0] . "' class='" . ($fetch_detail['rel_to_base'] != "on" ? "item" : "skin") . "'>
								<div><img src='avatars/" . $fetch_detail['position'] . "/" . $fetch_detail['clothing'] . "/" . $s[1] . "_" . $fetch_avatar['gender'] . ".png' title='" . $fetch_detail['clothing'] . "'/></div>
								<a class='close' href='preview_avi.php?delete=" . $s[0] . "'>&#10006;</a>
								<select name='i" . $s[0] . "'>" . implode($options) . "</select>";
			
			unset($price);
			if ((($fetch_detail['purchase_yes'] == "" && $fetch_detail['exoticPackage'] == 0) || $fetch_account['clearance'] >= 5) && in_array($fetch_detail['shopID'], $shops2))
			{
				$price = "";
				$comma = "";
				if ($fetch_detail['cost'] > 0)
				{
					$price .= $comma . $fetch_detail['cost'] . " Auro";
					$comma = " + ";
				}
				if ($fetch_detail['cost_credits'] > 0)
				{
					$price .= $comma . $fetch_detail['cost_credits'] . " Credits";
					$comma = " + ";
				}
				if ($price == "")
					$price = "0 Auro";
				$total += $fetch_detail['cost'];
				$total_credits += $fetch_detail['cost_credits'];
			}
			if (!owned_item($s[0], $fetch_account['account']))
			{
				$cost += $fetch_detail['cost'];
				$credits += $fetch_detail['cost_credits'];
				if (isset($price))
					echo "
								<a class='buy' onclick='return confirm(\"Are you sure you want to buy " . $fetch_detail['clothing'] . " for " . $price . "?\");' href='preview_avi.php?buy=" . $s[0] . "' title='" . $price . "'>&#10004;</a>";
			}
			else
				echo "
								<span class='owned'>&bull;</span>";
			echo "
								<a class='left' href='#'>&lt;</a>
								<a class='right' href='#'>&gt;</a>
							</li>";
		}
		else
		{
			echo "
							<li id='o0' class='base'>
								<div style='line-height:50px;'>Base</div>
								<select name='i0'>" . implode($bases) . "</select>
								<a class='left' href='#'>&lt;</a>
								<a class='right' href='#'>&gt;</a>
							</li>";
		}
	}
?>

						</ul>
						<textarea id='order' name='order' style='display:none;'></textarea>
					</form>
					<div style='clear:both;'></div>
					<br/>&#10006; click to unequip &nbsp; &#10004; click to purchase &nbsp; &bull; you own this item
					<br/><br/>
<?php
	echo "
					Total Cost: " . ($total > 0 ? $total . " Auro" : "") . ($total > 0 && $total_credits > 0 ? " + " : "") . ($total_credits > 0 ? $total_credits . " Credits" : "") . ($total == 0 && $total_credits == 0 ? " -" : "");
	if ($cost > 0 || $credits > 0)
		echo
					"<br/>Remaining Cost: <a onclick='return confirm(\"Do you really want to buy all these items? This will not repurchase items you already have.\");' href='preview_avi.php?buyall'>" . ($cost > 0 ? $cost . " Auro" : "") . ($cost > 0 && $credits > 0 ? " + " : "") . ($credits > 0 ? $credits . " Credits" : "") . "</a>";
?>

				</td>
			</tr>
		</table>
		<script src='javascript/reorder.js' type='text/javascript' charset='utf-8'></script>
	</body>
</html>