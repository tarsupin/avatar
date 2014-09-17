<?php
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");

	// Check if have an avatar selected
	if (!isset($fetch_avatar['id']) || !isset($_GET['shop']))
	{
		header("Location: index.php");
		exit;
	}

	// Gather Shop
	$result = mysql_query("SELECT `id`, `shopName`, `clearance` FROM `shop_listings` WHERE `id`='" . ($_GET['shop'] + 0) . "' LIMIT 1");
	if (!$fetch_shop = mysql_fetch_assoc($result))
	{
		header("Location: shop_list.php");
		exit;
	}

	// Make sure you have clearance
	if ($fetch_shop['clearance'] > $fetch_account['clearance'])
	{
		header("Location: shop_list.php");
		exit;
	}
	
	$shops = array();
	$q = mysql_query("SELECT `id`, `shopName`, `clearance` FROM `shop_listings` WHERE `clearance`<='" . ($fetch_account['clearance'] + 0) . "' ORDER BY `clearance`, `shopName`");
	while ($row = mysql_fetch_assoc($q))
		$shops[] = "<option value='" . $row['id'] . "'>" . $row['shopName'] . "</option>";
	$shops = implode($shops);

	// Purchase Item
	if (isset($_GET['pur']))
	{
		$q = "SELECT `clothingID`, `clothing`, `position`, `used_by`, `exoticPackage`, `shopID`, `cost`, `cost_credits`, `purchase_yes` FROM `clothing_images` WHERE `clothingID`='" . ($_GET['pur'] + 0) . "' AND `shopID`='" . ($fetch_shop['id'] + 0) . "' LIMIT 1";
		$result = mysql_query($q);
		if ($fetch_purchase = mysql_fetch_assoc($result))
		{
			if (($fetch_purchase['purchase_yes'] == "" && $fetch_purchase['exoticPackage'] == 0) || $fetch_account['clearance'] >= 5)
			{
				$q = "SELECT `clothingID` FROM `avatar_clothing` WHERE `account`='" . protectSQL($fetch_account['account']) . "' AND `position`='" . protectSQL($fetch_purchase['position']) . "' AND `clothingID`='" . ($fetch_purchase['clothingID'] + 0) . "'";
				$result = mysql_query($q);
				if (mysql_num_rows($result) > 0 && !isset($_GET['forcePurchase']))
				{
					$messages[] = "<div class='message-neutral'>You are about to purchase an item you already own!<br/>If you are sure that this is what you want to do, please confirm by clicking <a href='shop_clothing.php?shop=" . $fetch_shop['id'] . "&pur=" . $fetch_purchase['clothingID'] . "&forcePurchase=true'>here</a>.</div>";
				}
				elseif ($fetch_purchase['used_by'] != "both" && $fetch_purchase['used_by'] != $fetch_avatar['gender'] && !isset($_GET['forcePurchase']))
				{
					$messages[] = "<div class='message-neutral'>You are about to purchase " . $fetch_purchase['clothing'] . ". This item can only be used by avatars of the opposite gender!<br/>If you are sure that this is what you want to do, please confirm by clicking <a href='shop_clothing.php?shop=" . $fetch_shop['id'] . "&pur=" . $fetch_purchase['clothingID'] . "&forcePurchase=true'>here</a>.</div>";
				}
				else
				{
					if ($fetch_purchase['cost'] == 0 && $fetch_purchase['cost_credits'] == 0)
						$value = "SUCCESS";
					else
						$value = file_get_contents("http://auth.unifaction.com/API_autoSpendBoth.php?account=" . $fetch_account['account'] . "&auro=" . ($fetch_purchase['cost'] + 0) . "&credits=" . ($fetch_purchase['cost_credits']+0) . "&site=" . $siteName . "&hash=" . hash("sha256", "@uto$37^&" . $fetch_purchase['cost'] . "*(futa)credits003#" . $siteName . $siteKey . $fetch_account['account'] . $fetch_purchase['cost_credits']));
					if ($value == "SUCCESS")
					{
						mysql_query("INSERT INTO `avatar_clothing` (`account`,`clothingID`,`position`) VALUES ('" . protectSQL($fetch_account['account']) . "', '" . ($fetch_purchase['clothingID'] + 0) . "', '" . protectSQL($fetch_purchase['position']) . "')");
						if (mysql_num_rows($result) == 0)
							$messages[] = "<div class='message-success'>You have purchased " . $fetch_purchase['clothing'] . "!</div>";
						else
							$messages[] = "<div class='message-success'>You now have " . (mysql_num_rows($result) + 1) . " sets of " . $fetch_purchase['clothing'] . ".</div>";
					}
					else
						$messages[] = "<div class='message-error'>You need more Auro and/or Credits to purchase " . $fetch_purchase['clothing'] . "!</div>";
				}
			}
			else
				$messages[] = "<div class='message-error'>" . $fetch_purchase['clothing'] . " has been set to PREVIEW ONLY. You cannot purchase it.</div>";
		}
		else
			$messages[] = "<div class='message-error'>This item does not exist or has been moved to a different shop.</div>";
	}
	elseif (isset($_GET['wish']))
	{
		// Confirm that Item is Legitimate
		$result = mysql_query("SELECT `clothingID`, `clothing` FROM `clothing_images` WHERE `clothingID`='" . ($_GET['wish'] + 0) . "' LIMIT 1");
		
		if ($fetch_purchase = mysql_fetch_assoc($result))
		{
			$result = mysql_query("SELECT `id` FROM `wish_list` WHERE `account`='" . protectSQL($fetch_account['account']) . "' AND `clothingID`=" . ($fetch_purchase['clothingID'] + 0) . " LIMIT 1");
			if (!$fetch_wish = mysql_fetch_assoc($result))
			{
				$name = substr($fetch_purchase['clothing'], 0, 3);
				
				// Add to Wishlist
				mysql_query("INSERT INTO `wish_list` (`account`, `clothingID`, `name`) VALUES ('" . protectSQL($fetch_account['account']) . "', '" . protectSQL($fetch_purchase['clothingID']) . "', '" . protectSQL($name) . "')");
				
				$messages[] = "<div class='message-success'>You have added " . $fetch_purchase['clothing'] . " to your wishlist!</div>";
			}
			else
				$messages[] = "<div class='message-error'>" . $fetch_purchase['clothing'] . " is already on your wishlist.</div>";
		}
	}
	
	if ($fetch_avatar['gender'] == "female")
		$opGender = "male";
	else
		$opGender = "female";
	
	$pagetitle = $fetch_shop['shopName'];
	require("incAVA/header.php");
?>
			<div class='category-container'>
				<div class='details-header'>
					<?php echo $fetch_shop['shopName']; ?>
					
				</div>
				<div class='details-body'>
<?php
	echo "
					<select onchange='window.location=\"shop_clothing.php?shop=\" + this.options[this.selectedIndex].value;'>" . str_replace("value='" . $fetch_shop['id'] . "'", "value='" . $fetch_shop['id'] . "' selected='selected'", $shops) . "</select>";
		
	if ($fetch_account['clearance'] >= 6)
	{
		echo " &nbsp; <a href='shop_clothing.php?shop=" . $fetch_shop['id'] . "&force_update=" . $fetch_account['account'] . "'>Refresh Shop</a>";
	}
		
	echo "
					<div style='text-align:center;'>Click on an item to preview it.</div><br/>";
				
	$slotX = 0;	
	$owned = array();
	$q = "SELECT DISTINCT `avatar_clothing`.`clothingID` from `avatar_clothing` INNER JOIN `clothing_images` ON `avatar_clothing`.`clothingID`=`clothing_images`.`clothingID` WHERE `account`='" . protectSQL($fetch_account['account']) . "' AND `clothing_images`.`shopID`='" . ($fetch_shop['id'] + 0) . "'";
	$res = mysql_query($q);
	while ($row = mysql_fetch_assoc($res))
		array_push($owned, $row['clothingID']);
	
	if (filemtime("content/shop_" . ($fetch_shop['id'] + 0) . "_" . $fetch_avatar['gender'] . "_2.html") < time() - 86400 || isset($_GET['force_update']))
	{
		$contentHTML = "
					<table class='items'>";
		$q = "SELECT * FROM `clothing_images` WHERE `shopID`='" . ($fetch_shop['id'] + 0) . "' AND `used_by`!='" . protectSQL($opGender) . "' ORDER BY clothing";
		$result = mysql_query($q);
		while ($fetch_clothes = mysql_fetch_assoc($result))
		{
			if ($slotX % 5 == 0)
				$contentHTML .= "
						<tr>";
						
			$list_items = array();
			$files = scandir("avatars/" . $fetch_clothes['position'] . "/" . $fetch_clothes['clothing']);
			foreach ($files as $file)
				if (strpos($file, "_" . $fetch_avatar['gender'] . ".png"))
					$list_items[] = str_replace("_" . $fetch_avatar['gender'] . ".png", "", $file);
					
			$contentHTML .= "
							<td>
								<a href=\"javascript: review_item('" . $fetch_clothes['clothingID'] . "');\"><img id='img" . $fetch_clothes['clothingID'] . "' src='avatars/" . $fetch_clothes['position'] . "/" . $fetch_clothes['clothing'] . "/" . $list_items[0] . "_" . $fetch_avatar['gender'] . ".png'/></a><br/>
								" . $fetch_clothes['clothing'] . "<span style='color:inherit;font-size:inherit;'></span><br/>
								<span><a href='shop_search.php?used_by=" . ($opGender == "male" ? "fab" : "mab") . "&layer_" . $fetch_clothes['position'] . "=on&submit=Search'>" . $fetch_clothes['position'] . "</a>, " . $fetch_clothes['used_by'] . "</span><br/>
								<select id='item" . $fetch_clothes['clothingID'] . "' onChange=\"javascript: switch_item('" . $fetch_clothes['clothingID'] . "', '" . $fetch_clothes['position'] . "', '" . $fetch_clothes['clothing'] . "', '" . $fetch_avatar['gender'] . "');\">";	
			foreach($list_items as $color)
				$contentHTML .= "<option name='" . $color . "'>" . $color . "</option>";
			$contentHTML .= "</select>";
			if (($fetch_clothes['exoticPackage'] > 0 || $fetch_clothes['purchase_yes'] != "") && $fetch_shop['clearance'] < 5)
			{
				$contentHTML .= "
								<div>Preview Only</div>";
			}
			else
			{
				$price = "";
				$comma = "";
				if ($fetch_clothes['cost'] > 0)
				{
					$price .= $comma . $fetch_clothes['cost'] . " Auro";
					$comma = " + ";
				}
				if ($fetch_clothes['cost_credits'] > 0)
				{
					$price .= $comma . $fetch_clothes['cost_credits'] . " Credits";
					$comma = " + ";
				}
				if ($price == "")
					$price = "0 Auro";
				$contentHTML .= "
								<div><a onclick='return confirm(\"Are you sure you want to purchase " . $fetch_clothes['clothing'] . "?\");' href='shop_clothing.php?shop=" . ($fetch_shop['id'] + 0) . "&pur=" . $fetch_clothes['clothingID'] . "'>Buy for " . $price . "</a></div>";
			}
			$contentHTML .= "
								<div><a href='shop_clothing.php?shop=" . ($fetch_shop['id'] + 0) . "&wish=" . $fetch_clothes['clothingID'] . "'>Add to Wishlist</a></div>";
			if ($fetch_shop['clearance'] >= 6)
				$contentHTML .= "
					<div><a href='adminEditItem.php?itemID=" . $fetch_clothes['clothingID'] . "'>Edit Item</a></div>";
			$contentHTML .= "
							</td>";
						
			if ($slotX % 5 == 4)
				$contentHTML .= "
						</tr>";
			$slotX++;
		}
		if ($slotX % 5 > 0)
			$contentHTML .= "
						</tr>";
		$contentHTML .= "
					</table>";
		file_put_contents("content/shop_" . ($fetch_shop['id'] + 0) . "_" . $fetch_avatar['gender'] . "_2.html", $contentHTML);
	}
	include("content/shop_" . ($fetch_shop['id'] + 0) . "_" . $fetch_avatar['gender'] . "_2.html");
?>

				</div>
			</div>
			<script type='text/javascript'>
<?php
	if (count($owned) == 1)
		echo "
				var owned = " . $owned[0] .";

				if (document.getElementById('img' + owned))
				{
					var el = document.getElementById('img' + owned).parentNode.parentNode.getElementsByTagName('span')[0];
					el.innerHTML += ' &bull;';
				}";
	elseif (count($owned) > 1)
	  echo "
				var owned = new Array(" . implode(", ", $owned) . ");

				for (i=0; i<owned.length; i++)
					if (document.getElementById('img' + owned[i]))
					{
						var el = document.getElementById('img' + owned[i]).parentNode.parentNode.getElementsByTagName('span')[0];
						el.innerHTML += ' &bull;';
					}";
?>

				function switch_item(num, position, name, gender)
				{
					var selbox = $("#item" + num);
					$("#img" + num).attr("src", "avatars/" + position + "/" + name + "/" + selbox.val() + "_" +gender + ".png");
				}
				
				function review_item(id)
				{
					window.open("preview_avi.php?clothingID=" + id + "&recolor=" + $("#item" + id).val(), "PreviewAvatar", "width=622,height=500,toolbar=no,location=no,directories=no,status=no,menubar=no,copyhistory=no,scrollbars=yes");
				}
			</script>
<?php
	require("incAVA/footer.php");
?>