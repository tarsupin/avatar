<?php
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");

	// Delete Item from Wishlist
	if (isset($_GET['del']))
	{
		if (mysql_query("DELETE FROM `wish_list` WHERE id='" . ($_GET['del'] + 0) . "' AND `account`='" . protectSQL($fetch_account['account']) . "' LIMIT 1"))
			$messages[] = "<div class='message-success'>The item has been removed from your wishlist.</div>";
		else
			$messages[] = "<div class='message-error'>The item could not be removed from your wishlist.</div>";		
	}
	
	$shops2 = array();
	$q = mysql_query("SELECT `id`, `clearance` FROM `shop_listings` WHERE `clearance`<='" . ($fetch_account['clearance'] + 0) . "'");
	while ($row = mysql_fetch_assoc($q))
		$shops2[] = $row['id'];
	
	// Share Wishlist
	$t = time();
	if (isset($_POST['duration']) && (!is_numeric($_POST['duration']) || $_POST['duration'] < 1 || $_POST['duration'] > 30))
		$messages[] = "<div class='message-error'>Invalid duration entered.</div>";
	elseif (isset($_POST['duration']))
	{
		$_POST['duration'] = floor($_POST['duration']);
		if (isset($_POST['sharewith']) && isset($_POST['personal']))
		{
			$result = mysql_query("SELECT `account` FROM `account_info` WHERE account='" . protectSQL($_POST['sharewith']) . "' LIMIT 1");
			if ($fetch_viewer = mysql_fetch_assoc($result))
				$messages[] = "<div class='message-neutral'>Give the following link to " . $fetch_viewer['account'] . ". They, and only they, will be able to use it to see your wishlist.<br/><a href='view_wishlist.php?user=" . $fetch_account['account'] . "&viewer=" . $fetch_viewer['account'] . "&time=" . $t . "&days=" . $_POST['duration'] . "&pass=" . sha1($fetch_account['account'] . $t . $_POST['duration'] . $fetch_viewer['account'] . $siteKey) . "'>http://avatar.unifaction.com/view_wishlist.php?user=" . $fetch_account['account'] . "&viewer=" . $fetch_viewer['account'] . "&time=" . $t . "&days=" . $_POST['duration'] . "&pass=" . sha1($fetch_account['account'] . $t . $_POST['duration'] . $fetch_viewer['account'] . $siteKey) . "</a></div>";
			else
				$messages[] = "<div class='message-error'>User \"" . $_POST['sharewith'] . "\" not found.</div>";
		}
		elseif (isset($_POST['everyone']))
		{
			$messages[] = "<div class='message-neutral'>Share the following link. Everyone who has it will be able to see your wishlist.<br/><a href='view_wishlist.php?user=" . $fetch_account['account'] . "&time=" . $t . "&days=" . $_POST['duration'] . "&pass=" . sha1($fetch_account['account'] . $t . $_POST['duration'] . "wish4@ll" . $siteKey) . "'>http://avatar.unifaction.com/view_wishlist.php?user=" . $fetch_account['account'] . "&time=" . $t . "&days=" . $_POST['duration'] . "&pass=" . sha1($fetch_account['account'] . $t . $_POST['duration'] . "wish4@ll" . $siteKey) . "</a></div>";
		}
	}

	$pagetitle = "Wishlist";
	require("incAVA/header.php");

	// mark owned items
	$owned = array();
	$q = mysql_query("SELECT DISTINCT `clothingID` FROM `avatar_clothing` WHERE `account`='" . protectSQL($fetch_account['account']) . "'");
	while ($row = mysql_fetch_assoc($q))
		array_push($owned, $row['clothingID']);
?>
			<div class='category-container'>
				<div class='details-header'>
					Wishlist
				</div>
				<div class='details-body'>
					<table class='alternate_with_th'>
						<tr>
							<th>&nbsp;</th>
<?php
	foreach (array("name", "layer", "gender", "cost") as $col)
	{
		if (isset($_GET['sort']) && $_GET['sort'] == $col && !isset($_GET['reverse']))
			echo "
							<th>" . ucfirst($col) . " <a href='wishlist.php?sort=" . $col . "&reverse'>&#9660;</a></th>";
		elseif (isset($_GET['sort']) && $_GET['sort'] == $col)
			echo "
							<th>" . ucfirst($col) . " <a href='wishlist.php?sort=" . $col . "'>&#9650;</a></th>";
		else
			echo "
							<th>" . ucfirst($col) . " <a href='wishlist.php?sort=" . $col . "'>&#9651;</a></th>";
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
	$query = "SELECT `wish_list`.`id`, `clothing_images`.`clothingID`, `clothing_images`.`clothing`, `clothing_images`.`position`, `clothing_images`.`used_by`, `clothing_images`.`exoticPackage`, `clothing_images`.`shopID`, `clothing_images`.`cost`, `clothing_images`.`cost_credits`, `clothing_images`.`purchase_yes` FROM `wish_list` INNER JOIN `clothing_images` ON `wish_list`.`clothingID`=`clothing_images`.`clothingID` WHERE `wish_list`.`account`='" . protectSQL($fetch_account['account']) . "'" . $qa;
	$result = mysql_query($query);
	while ($fetch_cloth = mysql_fetch_assoc($result))
	{
		echo "
						<tr>
							<td><a href='wishlist.php?del=" . $fetch_cloth['id'] . "'>&#10006;</a></td>
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
			<div class='category-container'>
				<div class='details-header'>
					Share
				</div>
				<div class='details-body'>
					<ul>
						<li>In order to let others view your wishlist, you need to generate a link and share it with the person or people whom you want to show.</li>
						<li>The list they'll see will always be up to date, meaning it will reflect all changes you make.</li>
						<li>Any generated link will work for the set number of days, so once you share it, you can't take the permission back during that time.</li>
					</ul>
					<br/>
					<form method='post'>
						I want to allow <input type='text' name='sharewith'/> to see my wishlist for a duration of <input type='text' name='duration' value='30' size='2'/> (1-30) days.
						<input type='submit' name='personal' value='Generate Link'/>
					</form>
					<form method='post'>
						I want to allow everyone to see my wishlist for a duration of <input type='text' name='duration' value='30' size='2'/> (1-30) days.
						<input type='submit' name='everyone' value='Generate Link'/>
					</form>
				</div>
			</div>
<?php
	require("incAVA/footer.php");
?>