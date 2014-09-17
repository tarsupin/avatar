<?php
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");

	// Gather Current Donation Package
	$result = mysql_query("SELECT `id`, `title`, `image` FROM `exotic_packages` WHERE `year`='" . date("Y", time()) . "' AND `month`='" . date("m", time()) . "' LIMIT 1");

	if ($fetch_package = mysql_fetch_assoc($result))
	{
		// Prepare Default Title & Image
		if ($fetch_package['title'] == "")
			$fetch_package['title'] = date("m", time()) . "'s Exotic Package";
		if ($fetch_package['image'] == "")
			$fetch_package['image'] = "_default.png";

		// Submission for Purchasing Exotic Packages
		if (isset($_POST['submitExotic']))
		{
			if ($_POST['getExotic'] == 1 || $_POST['getExotic'] == 3 || $_POST['getExotic'] == 6)
			{
				// Prepare Cost
				if ($_POST['getExotic'] == 1)
					$creditCost = 5;
				elseif ($_POST['getExotic'] == 3)
					$creditCost = 10;
				else
					$creditCost = 15;
				
				// Check if user has enough credits
				$hsh = hash("sha256", "@uto$37^&*()credits003#" . $siteName . $siteKey . $fetch_account['account'] . ($creditCost + 0));
				
				$val = file_get_contents(UNIFACTION . "API_autoSpendCredits.php?account=" . $fetch_account['account'] . "&amount=" . ($creditCost + 0) . "&site=" . $siteName . "&hash=" . $hsh);
				
				if ($val == "SUCCESS")
				{
					for ($i=0; $i<$_POST['getExotic']+0; $i++)
					{
						// Insert New Table
						mysql_query("INSERT INTO `exotic_packages_owned` (`account`, `packageID`) VALUES ('" . protectSQL($fetch_account['account']) . "', '" . ($fetch_package['id'] + 0) . "')");
					}
					
					// stats
					mysql_query("UPDATE `exotic_packages_stats` SET `count`=`count`+" . ($_POST['getExotic'] + 0) . " WHERE `package_id`='" . ($fetch_package['id'] + 0) . "' AND `item_id`='0' LIMIT 1");
					if (mysql_affected_rows() == 0)
						mysql_query("INSERT INTO `exotic_packages_stats` (`package_id`, `item_id`, `count`) VALUES ('" . ($fetch_package['id'] + 0) . "', '0', '" . ($_POST['getExotic'] + 0) . "')");
					
					// Provide Success Message
					$messages[] = "<div class='message-success'>You have acquired " . ($_POST['getExotic'] + 0) . " " . $fetch_package['title'] . "(s)!</div>";
				}
				else
					$messages[] = "<div class='message-error'>You don't have enough Credits to purchase " . ($_POST['getExotic'] + 0) . " " . $fetch_package['title'] . "(s).</div>";
			}
		}
	}

	// If User selected an Exotic Package
	if (isset($_GET['item']) && isset($_GET['package']))
	{
		// Gather Donation Packages
		$result = mysql_query("SELECT `id`, `packageID`, `in_trade` FROM `exotic_packages_owned` WHERE `id`='" . ($_GET['package'] + 0) . "' AND `account`='" . protectSQL($fetch_account['account']) . "' LIMIT 1");
		
		if ($conf_package = mysql_fetch_assoc($result))
		{
			if ($conf_package['in_trade'] == 0)
			{
				// Confirm that Item is in the Package
				$result = mysql_query("SELECT `clothingID`, `position`, `clothing` FROM `clothing_images` WHERE `clothingID`='" . ($_GET['item'] + 0) . "' AND `exoticPackage`='" . ($conf_package['packageID'] + 0) . "' LIMIT 1");
				
				if ($chk_item = mysql_fetch_assoc($result))
				{
					// Add to Inventory
					mysql_query("INSERT INTO `avatar_clothing` (`account`, `clothingID`, `position`) VALUES ('" . protectSQL($fetch_account['account']) . "', '" . protectSQL($chk_item['clothingID']) . "', '" . protectSQL($chk_item['position']) . "')");
					
					// Reduce Package Count
					mysql_query("DELETE FROM `exotic_packages_owned` WHERE `id`='" . ($conf_package['id'] + 0) . "' LIMIT 1");
					
					// stats
					if ($conf_package['packageID'] >= 47)
					{
						mysql_query("UPDATE `exotic_packages_stats` SET `count`=`count`+1 WHERE `package_id`='" . ($conf_package['packageID'] + 0) . "' AND `item_id`='" . ($chk_item['clothingID'] + 0) . "' LIMIT 1");
						if (mysql_affected_rows() == 0)
							mysql_query("INSERT INTO `exotic_packages_stats` (`package_id`, `item_id`, `count`) VALUES ('" . ($conf_package['packageID'] + 0) . "', '" . ($chk_item['clothingID'] + 0) . "', '1')");
					}
					
					$messages[] = "<div class='message-success'>You have just acquired the " . $chk_item['clothing'] . "!</div>";
				}
			}
			else
				$messages[] = "<div class='message-error'>This package is currently in a trade. You cannot open it.</div>";
		}
	}

	$pagetitle = "Exotic Packages";
	require("incAVA/header.php");
?>
			<div class='category-container'>
				<div class='details-header'>
					Current Exotic Package
				</div>
				<div class='details-body'>
<?php
	if (isset($fetch_package['id']))
	{
		// Display Package
		echo "
					<form method='post'>
						<table>
							<tr>
								<td valign='bottom' style='width:220px;'>
									<img src='images/exotic_packages/" . $fetch_package['image'] . "'/><br/>" . $fetch_package['title'] . "<br/>
									<select name='getExotic' id='getExotic'>
										<option value='0'>Please Choose:</option>
										<option value='1'>1 " . $fetch_package['title'] . " (5 Credits)</option>
										<option value='3'>3 " . $fetch_package['title'] . " (10 Credits)</option>
										<option value='6'>6 " . $fetch_package['title'] . " (15 Credits)</option>
									</select><br/>
									<input onclick='return confirmexotic();' type='submit' name='submitExotic' value='Get Exotic Packages'/>
								</td>
								<td style='text-align:left;'>
									<ul>
										<li>Exotic Packages are special, limited-time packages that hold a selection of rare items.</li>
										<li>Each Exotic Package lets you choose 1 of the items inside.</li>
										<li>Each Exotic Package is valued individually at 5 Credits, but you can acquire multiple packages when gathering them in bulk. 10 Credits will get you 3 Exotic Packages, and 15 Credits will get you 6 Exotic Packages!</li>
										<li><a href='http://forum.unifaction.com/thread.php?f=1&id=37'>Awesome link to past EPs, by awesome Kittaly</a></li>
									</ul>
									<br/>
									Please be careful when you purchase your boxes as we can no longer compensate if you accidentally purchase the wrong quantity. Thank you for giving to UniFaction!";
	$result = mysql_query("SELECT credits FROM u5s_auth.s4u_account_credits WHERE account='" . protectSQL($fetch_account['account']) . "' LIMIT 1");
	if ($fetch_credits = mysql_fetch_assoc($result))
		echo "
									<br/><br/>You currently have " . $fetch_credits['credits'] . " Credits available. You can purchase more <a href='http://auth.unifaction.com/get_credits.php'>here</a>.";
	echo "
								</td>
							</tr>
						</table>
					</form>";
	}
	else
		echo "
					Sorry, there are no Exotic Packages available for purchase right now. Please return at a later time.";
	echo "
				</div>
			</div>";
	
	// Gather the Packages You Own
	$result = mysql_query("SELECT `exotic_packages_owned`.`id`, `exotic_packages_owned`.`packageID`, `exotic_packages`.`title`, `exotic_packages`.`image` FROM `exotic_packages_owned` INNER JOIN `exotic_packages` ON `exotic_packages_owned`.`packageID`=`exotic_packages`.`id` WHERE `exotic_packages_owned`.`account`='" . protectSQL($fetch_account['account']) . "' AND `exotic_packages_owned`.`in_trade`='0' ORDER BY `exotic_packages_owned`.`packageID` DESC");
	
	if (mysql_num_rows($result) > 0)
	{
		// Display Your Packages
		echo "
			<div class='category-container'>
				<div class='details-header'>
					My Exotic Packages
				</div>
				<div class='details-body'>
					<table style='width:100%;'>";
		
		// Prepare Variables
		$slotX = 0;
		
		while ($fetch_my_packages = mysql_fetch_assoc($result))
		{
			// Run Display
			if ($slotX % 6 == 0)
				echo "
						<tr>";
			
			echo "
							<td><a href='exotic_receive.php?package=" . $fetch_my_packages['id'] . "'><img src='images/exotic_packages/" . $fetch_my_packages['image'] . "'/></a><br/>" . $fetch_my_packages['title'] . /*"<br/><br/><a href='trade_start.php?add_package=" . $fetch_my_packages['id'] . "'>Trade</a> | <a href='send_package.php?package=" . $fetch_my_packages['id'] . "'>Gift</a>*/ "</td>";
			
			if ($slotX % 6 == 5)
				echo "
						</tr>";
			$slotX++;
		}
		
		if ($slotX % 6 > 0)
			echo "
						</tr>";
		
		echo "
					</table>";
	}
?>

				</div>
			</div>

			<script type='text/javascript'>
				function confirmexotic()
				{
					var e = document.getElementById("getExotic");
					if (e.options[e.selectedIndex].value > 0)
						return confirm("Do you really want to purchase " + e.options[e.selectedIndex].text + "?");
					else
						return false;
				}
			</script>
<?php
	require("incAVA/footer.php");
?>