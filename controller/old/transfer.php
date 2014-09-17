<?php
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");
	
	// Gather User Info
	if (isset($_POST['username']) && isset($_POST['password']))
	{
		// Connect to Uni4 Database
		mysql_select_db("ufforum_old");
		$result = mysql_query("SELECT `id`, `username` FROM `users` WHERE `username`='" . protectSQL($_POST['username']) . "' AND `password`='" . sha1($_POST['password']) . "' LIMIT 1");
		
		if ($fetch_user = mysql_fetch_assoc($result))
		{
			// Transfer Special Items
			if (isset($_POST['transfer_special_items']))
			{
				mysql_select_db("ufforum_old");
				
				$getAllItems = array();
				$result = mysql_query("SELECT `id`, `item_id` FROM `equipment2_0` WHERE `user_id`='" . ($fetch_user['id'] + 0) . "' AND `uni5_USED`='0'");
				while ($fetch_items = mysql_fetch_assoc($result))
				{
					// Gather Type
					$resItems = mysql_query("SELECT `name`, `uni5_ID` FROM `shop_equipment2_0` WHERE `id`='" . ($fetch_items['item_id'] + 0) . "' AND `uni5_ID`!='0' LIMIT 1");
					
					if ($fet_swap = mysql_fetch_assoc($resItems))
					{
						// Update Item to Uni5 Used
						mysql_query("UPDATE `equipment2_0` SET `uni5_USED`='1' WHERE `id`='" . ($fetch_items['id'] + 0) . "' LIMIT 1");
						$getAllItems[] = $fet_swap['uni5_ID'];
					}
				}
				
				mysql_select_db("u5s_avatar");
				
				foreach($getAllItems as $key => $val)
				{
					// Gather Item Info
					$resItemInfo = mysql_query("SELECT `clothingID`, `clothing`, `position` FROM `clothing_images` WHERE `clothingID`='" . ($val + 0) . "' LIMIT 1");
					
					if ($getItemInfo = mysql_fetch_assoc($resItemInfo))
					{
						// Add Item to Inventory
						mysql_query("INSERT INTO `avatar_clothing` (`account`, `clothingID`, `position`) VALUES ('" . protectSQL($fetch_account['account']) . "', '" . ($getItemInfo['clothingID'] + 0) . "', '" . protectSQL($getItemInfo['position']) . "')");
						$messages[] = "<div class='message-success'>" . $getItemInfo['clothing'] . " has been transferred to this account.</div>";
					}
				}
				
				if ($getAllItems == array())
					$messages[] = "<div class='message-neutral'>All Limited and Donation Items have been transferred from this user.</div>";
			}
			
			// Gather Donation Packages
			elseif (isset($_POST['gather_donation_packages']))
			{
				// Prepare Variables
				$dp = array();
				
				// Run Query
				$query = "SELECT `PackageUserID`, `PackageID` FROM `Packages_Users` WHERE `UserID`='" . ($fetch_user['id'] + 0) . "' AND `ShopEquipmentID` is NULL AND `Traded_PackageUserID` is NULL AND `uni5_USED`='0'";
				
				$result = mysql_query($query);
				
				while ($chk_don = mysql_fetch_assoc($result))
				{
					$res = mysql_query("SELECT `uni5_EP_ID`, `PackageName` FROM `Packages` WHERE `PackageID`='" . ($chk_don['PackageID'] + 0) . "' LIMIT 1");
					
					mysql_query("UPDATE `Packages_Users` SET `uni5_USED`='1' WHERE `PackageUserID`='" . ($chk_don['PackageUserID'] + 0) . "' LIMIT 1");
					
					if ($chk_dp_name = mysql_fetch_assoc($res))
					{
						array_push($dp, $chk_dp_name['uni5_EP_ID']);
						$messages[] = "<div class='message-success'>You have transferred a " . $chk_dp_name['PackageName'] . " (Donation Package).</div>";
					}
				}
				
				mysql_select_db("u5s_avatar");
				
				if ($dp == array())
					$messages[] = "<div class='message-neutral'>All Donation Packages have been transferred from this user.</div>";
				else
				{
					foreach($dp as $key => $val)
					{
						// Insert Items
						mysql_query("INSERT INTO `exotic_packages_owned` (`account`, `packageID`) VALUES ('" . protectSQL($fetch_account['account']) . "', '" . ($val + 0) . "')");
					}
				}
			}
		}
		else
			$messages[] = "<div class='message-error'>Your Uni4 username / password was incorrect.</div>";
	}

	$pagetitle = "Avatar Transfer";
	require("incAVA/header.php");
?>
			<div class='category-container'>
				<div class='details-header'>
					Avatar Transfer
				</div>
				<div class='details-body'>
					Don't forget to go to <a href='http://auth.unifaction.com/transfer.php'>Auth Transfer</a> and <a href='http://forum.unifaction.com/transfer.php'>Forum Transfer</a>!
					<br/><br/>
					<form method='post'>
						Username: <input type='text' name='username' value=''/>
						Password: <input type='password' name='password' value=''/>
						<input type='submit' name='transfer_special_items' value='Transfer Limited and Donation Items'/>
						<input type='submit' name='gather_donation_packages' value='Gather Donation Packages'/>
					</form>
				</div>
			</div>
<?php
	require("incAVA/footer.php");
?>