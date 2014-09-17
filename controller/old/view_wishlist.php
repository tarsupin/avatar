<?php
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");
	
	if (!isset($_GET['user']) || !isset($_GET['pass']) || !isset($_GET['time']) || !isset($_GET['days']) || $_GET['time']<time()-$_GET['days']*24*3600)
	{
		header("Location: index.php");
		exit;
	}
	elseif (isset($_GET['viewer']))
	{
		if ($fetch_account['account'] != $_GET['viewer'] || sha1($_GET['user'] . $_GET['time'] . $_GET['days'] . $fetch_account['account'] . $siteKey) != $_GET['pass'])
		{
			header("Location: index.php");
			exit;
		}
	}
	elseif (sha1($_GET['user'] . $_GET['time'] .  $_GET['days'] . "wish4@ll" . $siteKey) != $_GET['pass'])
	{
		header("Location: index.php");
		exit;
	}
	
	$shops2 = array();
	$q = mysql_query("SELECT `id`, `clearance` FROM `shop_listings` WHERE `clearance`<='" . ($fetch_account['clearance'] + 0) . "'");
	while ($row = mysql_fetch_assoc($q))
		$shops2[] = $row['id'];

	$pagetitle = $_GET['user'] . "'s Wishlist";
	require("incAVA/header.php");
	
	// mark owned items
	$owned = array();
	$q = mysql_query("SELECT DISTINCT `clothingID` FROM `avatar_clothing` WHERE `account`='" . protectSQL($_GET['user']) . "'");
	while ($row = mysql_fetch_assoc($q))
		array_push($owned, $row['clothingID']);
?>
			<div class='category-container'>
				<div class='details-header'>
					<?php echo $_GET['user']; ?>'s Wishlist
				</div>
				<div class='details-body'>
					<table class='alternate_with_th'>
						<tr>
<?php
	foreach (array("name", "layer", "gender", "cost") as $col)
	{
		if (isset($_GET['sort']) && $_GET['sort'] == $col && !isset($_GET['reverse']))
			echo "
							<th>" . ucfirst($col) . " <a href='view_wishlist.php?user=" . $_GET['user'] . "&time=" . $_GET['time'] . "&days=" . $_GET['days'] . "&pass=" . $_GET['pass'] . "&sort=" . $col . "&reverse'>&#9660;</a></th>";
		elseif (isset($_GET['sort']) && $_GET['sort'] == $col)
			echo "
							<th>" . ucfirst($col) . " <a href='view_wishlist.php?user=" . $_GET['user'] . "&time=" . $_GET['time'] . "&days=" . $_GET['days'] . "&pass=" . $_GET['pass'] . "&sort=" . $col . "'>&#9650;</a></th>";
		else
			echo "
							<th>" . ucfirst($col) . " <a href='view_wishlist.php?user=" . $_GET['user'] . "&time=" . $_GET['time'] . "&days=" . $_GET['days'] . "&pass=" . $_GET['pass'] . "&sort=" . $col . "'>&#9651;</a></th>";
	}
?>

						</tr>
<?php
	// Gather List of Wishes
	if (isset($_GET['sort']))
	{
		if ($_GET['sort'] == "name")
			$qa = " ORDER BY `clothing_images`.`clothing`";
		elseif ($_GET['sort'] == "layer")
			$qa = " ORDER BY `clothing_images`.`position`";
		elseif ($_GET['sort'] == "gender")
			$qa = " ORDER BY `clothing_images`.`used_by`";
		elseif ($_GET['sort'] == "cost")
			$qa = " ORDER BY `clothing_images`.`exoticPackage`, `clothing_images`.`purchase_yes`, `clothing_images`.`cost_credits`, `clothing_images`.`cost`";
	}
	if (!isset($qa))
		$qa = " ORDER BY `wish_list`.`id`";
	if (isset($_GET['reverse']))
		$qa .= " DESC";
	$query = "SELECT `clothing_images`.`clothingID`, `clothing_images`.`clothing`, `clothing_images`.`position`, `clothing_images`.`used_by`, `clothing_images`.`exoticPackage`, `clothing_images`.`shopID`, `clothing_images`.`cost`, `clothing_images`.`cost_credits`, `clothing_images`.`purchase_yes` FROM `wish_list` INNER JOIN `clothing_images` ON `wish_list`.`clothingID`=`clothing_images`.`clothingID` WHERE `wish_list`.`account`='" . protectSQL($_GET['user']) . "'" . $qa;
	$result = mysql_query($query);
	while ($fetch_cloth = mysql_fetch_assoc($result))
	{
		echo "
						<tr>
							<td" . (in_array($fetch_cloth['clothingID'], $owned) ? " class='inactive'" : "") . ">" . $fetch_cloth['clothing'] . "</td>
							<td" . (in_array($fetch_cloth['clothingID'], $owned) ? " class='inactive'" : "") . ">" . $fetch_cloth['position'] . "</td>
							<td" . (in_array($fetch_cloth['clothingID'], $owned) ? " class='inactive'" : "") . ">" . $fetch_cloth['used_by'] . "</td>";
							
		$price = "";
		$comma = "";
		if ($fetch_cloth['cost'] > 0)
		{
			$price .= $comma . $fetch_cloth['cost'] . " Auro";
			$comma = " + ";
		}
		if ($fetch_cloth['cost_credits'] > 0)
		{
			$price .= $comma . $fetch_cloth['cost_credits'] . " Credits";
			$comma = " + ";
		}
		if (!in_array($fetch_cloth['clothingID'], $owned))
		{
			if ($fetch_cloth['purchase_yes'] == "" && $fetch_cloth['exoticPackage'] == 0)
				echo "
							<td><a onclick='return confirm(\"Are you sure you want to purchase " . $fetch_cloth['clothing'] . "?\");' target='_blank' href='shop_clothing.php?shop=" . $fetch_cloth['shopID'] . "&pur=" . $fetch_cloth['clothingID'] . "'>" . $price . "</a></td>";
			elseif ($fetch_account['clearance'] >= 5 && in_array($fetch_cloth['shopID'], $shops2))
				echo "
							<td>Preview Only (<a onclick='return confirm(\"Are you sure you want to purchase " . $fetch_cloth['clothing'] . "?\");' target='_blank' href='shop_clothing.php?shop=" . $fetch_cloth['shopID'] . "&pur=" . $fetch_cloth['clothingID'] . "'>Staff: " . $price . "</a>)</td>";
			else
				echo "
							<td>Preview Only</td>";
		}
		else
		{
			if ($fetch_cloth['purchase_yes'] == "" && $fetch_cloth['exoticPackage'] == 0)
				echo "
							<td class='inactive'>" . $price . "</td>";
			elseif ($fetch_account['clearance'] >= 5 && in_array($fetch_cloth['shopID'], $shops2))
				echo "
							<td class='inactive'>Preview Only (Staff: " . $price . ")</td>";
			else
				echo "
							<td class='inactive'>Preview Only</td>";
		}
		
		echo "
						</tr>";
	}
?>

					</table>
				</div>
			</div>	
<?php
	require("incAVA/footer.php");
?>