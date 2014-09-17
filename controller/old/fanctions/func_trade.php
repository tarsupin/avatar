<?php
	function check_and_do($partner, $auro, $credits, $message, $items, $packages, $whattodo)
	{
		global $fetch_account;
		global $siteName;
		global $siteKey;
		$messages = array();
		
		$auro = max(0, floor($auro));
		$credits = max(0, floor($credits));
		$message = str_replace("\r\n", "\n", $message);
		$message = substr($message, 0, 255);

		// check if trade partner exists
		$partner = trim($partner);
		$q = "SELECT `account` FROM `account_info` WHERE `account`='" . protectSQL($partner) . "' LIMIT 1";
		$res = mysql_query($q);
		if (!$fetch_partner = mysql_fetch_assoc($res))
			$messages[] = "<div class='message-error'>" . $partner . " does not currently use the avatar system.</div>";
		else
		{
			// check if enough Auro and Credits, spend them if okay
			if ($auro == 0 && $credits == 0)
				$value = "SUCCESS";
			else
				$value = file_get_contents("http://auth.unifaction.com/API_autoSpendBoth.php?account=" . $fetch_account['account'] . "&auro=" . ($auro + 0) . "&credits=" . ($credits+0) . "&site=" . $siteName . "&hash=" . hash("sha256", "@uto$37^&" . $auro . "*(futa)credits003#" . $siteName . $siteKey . $fetch_account['account'] . $credits));
			if ($value == "SUCCESS")
			{
				if ($whattodo == "start")
				{
					// create trade
					$q = "INSERT INTO `trading_system` (`account_1`, `account_2`, `approved_1`, `auro_1`, `credits_1`, `messagebox_1`) VALUES ('" . protectSQL($fetch_account['account']) . "', '" . protectSQL($fetch_partner['account']) . "', 'yes', '" . ($auro + 0) . "', '" . ($credits + 0) . "', '" . protectSQL($message) . "')";
					mysql_query($q);
					$insert_id = mysql_insert_id();
				}
				else
				{
					$insert_id = $whattodo;
					$q = "UPDATE `trading_system` SET `approved_2`='yes', `auro_2`='" . ($auro + 0) . "', `credits_2`='" . ($credits + 0) . "', `messagebox_2`='" . protectSQL($message) . "' WHERE `id`='" . ($insert_id + 0) . "' LIMIT 1";
					mysql_query($q);
				}
				
				// add items to trade if owned and not in trade already
				foreach ($items as $item)
					mysql_query("UPDATE `avatar_clothing` SET `in_trade`='" . ($insert_id + 0) . "' WHERE `id`='" . ($item + 0) . "' AND `account`='" . protectSQL($fetch_account['account']) . "' AND `in_trade`='0' LIMIT 1");
					
				/*// add packages to trade if owned and not in trade already
				foreach ($packages as $package)
					mysql_query("UPDATE `exotic_packages_owned` SET `in_trade`='" . ($insert_id + 0) . "' WHERE `id`='" . ($package + 0) . "' AND `account`='" . protectSQL($fetch_account['account']) . "' AND `in_trade`='0' LIMIT 1");*/

				// notify recipient
				file_get_contents(UNIFACTION . "API_notifyCommon.php?account=" . urlencode($fetch_partner['account']) . "&siteName=" . urlencode($siteName) . "&title=" . urlencode(($whattodo == "start" ? "New" : "Updated") . " Trade") ."&message=" . urlencode($fetch_account['account'] . " has " . ($whattodo == "start" ? "started" : "updated") . " a <a href='" . AVATAR . "trade_pending.php#trade" . $insert_id . "'>trade</a> with you.") . "&h=" . hash("sha256", "notify_" . $siteName . $siteKey . $fetch_partner['account']));
				
				// update avatars
				$avatars = explode("|", file_get_contents("http://avatar.unifaction.com/API_avatarList.php?account=" . $fetch_account['account']));
				if ($avatars[0] != "")
				{
					foreach ($avatars as $avi)
					{
						$aviexp = explode(":", $avi);
						$q = "SELECT `outfit_serial` FROM `avatar_outfits_real` WHERE `avatar_id`='" . ($aviexp[0] + 0) . "' LIMIT 1";
						$res = mysql_query($q);
						if ($fetch = mysql_fetch_assoc($res))
						{
							$fetch['outfit_serial'] = unserialize($fetch['outfit_serial']);
							wrapper($fetch['outfit_serial'], $aviexp[2], $aviexp[3], $fetch_account['account'], $aviexp[0]);
						}
					}
				}
				
				// go to "pending" page if started, otherwise prevent resend
				header("Location: trade_pending.php#trade" . $insert_id);
				exit;
			}
			else
				$messages[] = "<div class='message-error'>You do not have enough Auro and/or Credits to " . ($whattodo == "start" ? "start" : "update") . " this trade.</div>";
		}
		return $messages;
	}
	
	function cancel_trade($tradeid)
	{
		global $fetch_account;
		global $siteName;
		global $siteKey;
		
		// get trade info
		$q = "SELECT * FROM `trading_system` WHERE `id`='" . ($tradeid + 0) . "' AND (`account_1`='" . protectSQL($fetch_account['account']) . "' OR `account_2`='" . protectSQL($fetch_account['account']) . "') LIMIT 1";
		$res = mysql_query($q);
		if ($fetch_trade = mysql_fetch_assoc($res))
		{
			// determine who is who
			if ($fetch_account['account'] == $fetch_trade['account_1'])
			{
				$self = $fetch_trade['account_1'];
				$other = $fetch_trade['account_2'];
			}
			else
			{
				$self = $fetch_trade['account_2'];
				$other = $fetch_trade['account_1'];
			}
		
			// log trade itself
			mysql_query("INSERT INTO `records_trades` (`id`, `account_1`, `account_2`, `auro_1`, `auro_2`, `credits_1`, `credits_2`, `approved_1`, `approved_2`, `messagebox_1`, `messagebox_2`, `timestamp`) VALUES ('" . ($tradeid + 0) . "', '" . protectSQL($fetch_trade['account_1']) . "', '" . protectSQL($fetch_trade['account_2']) . "', '" . ($fetch_trade['auro_1'] + 0) . "', '" . ($fetch_trade['auro_2'] + 0) . "', '" . ($fetch_trade['credits_1'] + 0) . "', '" . ($fetch_trade['credits_2'] + 0) . "', '" . ($fetch_account['account'] == $fetch_trade['account_1'] ? "no" : "yes") . "', '" . ($fetch_account['account'] == $fetch_trade['account_1'] ? protectSQL($fetch_trade['approved_2']) : "no") . "', '" . protectSQL($fetch_trade['messagebox_1']) . "', '" . protectSQL($fetch_trade['messagebox_2']) . "', '" . time() . "')");
			
			// log items
			$q = "SELECT `id`, `clothingID` FROM `avatar_clothing` WHERE `account`='" . protectSQL($self) . "' AND `in_trade`='" . ($tradeid + 0) . "'";
			$res = mysql_query($q);
			while ($fetch_items = mysql_fetch_assoc($res))
				mysql_query("INSERT INTO `records_trading` (`trade`, `from_user`, `to_user`, `item_id`, `item_type`, `timestamp`) VALUES ('" . ($tradeid + 0) . "', '" . protectSQL($self) . "', '" . protectSQL($other) . "', '" . ($fetch_items['id'] + 0) . "', '" . ($fetch_items['clothingID'] + 0) . "', '" . time() . "')");
			$q = "SELECT `id`, `clothingID` FROM `avatar_clothing` WHERE `account`='" . protectSQL($other) . "' AND `in_trade`='" . ($tradeid + 0) . "'";
			$res = mysql_query($q);
			while ($fetch_items = mysql_fetch_assoc($res))
				mysql_query("INSERT INTO `records_trading` (`trade`, `from_user`, `to_user`, `item_id`, `item_type`, `timestamp`) VALUES ('" . ($tradeid + 0) . "', '" . protectSQL($other) . "', '" . protectSQL($self) . "', '" . ($fetch_items['id'] + 0) . "', '" . ($fetch_items['clothingID'] + 0) . "', '" . time() . "')");
			
			// log packages
			$q = "SELECT `id`, `packageID` FROM `exotic_packages_owned` WHERE `account`='" . protectSQL($self) . "' AND `in_trade`='" . ($tradeid + 0) . "'";
			$res = mysql_query($q);
			while ($fetch_packages = mysql_fetch_assoc($res))
				mysql_query("INSERT INTO `records_trading` (`trade`, `from_user`, `to_user`, `package_id`, `package_type`, `timestamp`) VALUES ('" . ($tradeid + 0) . "', '" . protectSQL($self) . "', '" . protectSQL($other) . "', '" . ($fetch_packages['id'] + 0) . "', '" . ($fetch_packages['packageID'] + 0) . "', '" . time() . "')");
			$q = "SELECT `id`, `packageID` FROM `exotic_packages_owned` WHERE `account`='" . protectSQL($other) . "' AND `in_trade`='" . ($tradeid + 0) . "'";
			$res = mysql_query($q);
			while ($fetch_packages = mysql_fetch_assoc($res))
				mysql_query("INSERT INTO `records_trading` (`trade`, `from_user`, `to_user`, `package_id`, `package_type`, `timestamp`) VALUES ('" . ($tradeid + 0) . "', '" . protectSQL($other) . "', '" . protectSQL($self) . "', '" . ($fetch_packages['id'] + 0) . "', '" . ($fetch_packages['packageID'] + 0) . "', '" . time() . "')");
			
			// refund auro and credits
			for ($i=1; $i<=2; $i++)
			{
				if ($fetch_trade['auro_' . $i] > 0)
					mysql_query("UPDATE `u5s_auth`.`s4u_account_trackers` SET `auro`=`auro`+" . ($fetch_trade['auro_' . $i] + 0) . " WHERE `account`='" . protectSQL($fetch_trade['account_' . $i]) . "' LIMIT 1");
				if ($fetch_trade['credits_' . $i] > 0)
					mysql_query("UPDATE `u5s_auth`.`s4u_account_credits` SET `credits`=`credits`+" . ($fetch_trade['credits_' . $i] + 0) . " WHERE `account`='" . protectSQL($fetch_trade['account_' . $i]) . "' LIMIT 1");
			}
			
			// remove all items and packages from trade
			mysql_query("UPDATE `avatar_clothing` SET `in_trade`='0' WHERE (`account`='" . protectSQL($self) . "' OR `account`='" . protectSQL($other) . "') AND `in_trade`='" . ($tradeid + 0) . "'");
			
			mysql_query("UPDATE `exotic_packages_owned` SET `in_trade`='0' WHERE (`account`='" . protectSQL($self) . "' OR `account`='" . protectSQL($other) . "') AND `in_trade`='" . ($tradeid + 0) . "'"); 
			
			// delete trade itself
			mysql_query("DELETE FROM `trading_system` WHERE `id`='" . ($tradeid + 0) . "' LIMIT 1");
			
			// notify trade partner
			file_get_contents(UNIFACTION . "API_notifyCommon.php?account=" . urlencode($other) . "&siteName=" . urlencode($siteName) . "&title=" . urlencode("Cancelled Trade") ."&message=" . urlencode($self . " has cancelled a <a href='" . AVATAR . "trade_log.php'>trade</a> with you.") . "&h=" . hash("sha256", "notify_" . $siteName . $siteKey . $other));
			
			header("Location: trade_log.php");
			exit;
		}
	}
	
	function accept_trade($tradeid)
	{
		global $fetch_account;
		global $siteName;
		global $siteKey;
		
		// get trade info
		$q = "SELECT * FROM `trading_system` WHERE `id`='" . ($tradeid + 0) . "' AND `account_1`='" . protectSQL($fetch_account['account']) . "' LIMIT 1";
		$res = mysql_query($q);
		if ($fetch_trade = mysql_fetch_assoc($res))
		{
			// make sure trade partner approved
			if ($fetch_trade['approved_2'] == "yes")
			{
				// log trade itself
				mysql_query("INSERT INTO `records_trades` (`id`, `account_1`, `account_2`, `auro_1`, `auro_2`, `credits_1`, `credits_2`, `approved_1`, `approved_2`, `messagebox_1`, `messagebox_2`, `timestamp`) VALUES ('" . ($tradeid + 0) . "', '" . protectSQL($fetch_trade['account_1']) . "', '" . protectSQL($fetch_trade['account_2']) . "', '" . ($fetch_trade['auro_1'] + 0) . "', '" . ($fetch_trade['auro_2'] + 0) . "', '" . ($fetch_trade['credits_1'] + 0) . "', '" . ($fetch_trade['credits_2'] + 0) . "', 'yes', 'yes', '" . protectSQL($fetch_trade['messagebox_1']) . "', '" . protectSQL($fetch_trade['messagebox_2']) . "', '" . time() . "')");
				
				// log items
				for ($i=1; $i<=2; $i++)
				{
					$q = "SELECT `id`, `clothingID` FROM `avatar_clothing` WHERE `account`='" . protectSQL($fetch_trade['account_' . $i]) . "' AND `in_trade`='" . ($tradeid + 0) . "'";
					$res = mysql_query($q);
					while ($fetch_items = mysql_fetch_assoc($res))
						mysql_query("INSERT INTO `records_trading` (`trade`, `from_user`, `to_user`, `item_id`, `item_type`, `timestamp`) VALUES ('" . ($tradeid + 0) . "', '" . protectSQL($fetch_trade['account_' . $i]) . "', '" . protectSQL($fetch_trade['account_' . ($i == 1 ? "2" : "1")]) . "', '" . ($fetch_items['id'] + 0) . "', '" . ($fetch_items['clothingID'] + 0) . "', '" . time() . "')");
				}
				
				// log packages
				for ($i=1; $i<=2; $i++)
				{
					$q = "SELECT `id`, `packageID` FROM `exotic_packages_owned` WHERE `account`='" . protectSQL($fetch_trade['account_' . $i]) . "' AND `in_trade`='" . ($tradeid + 0) . "'";
					$res = mysql_query($q);
					while ($fetch_packages = mysql_fetch_assoc($res))
						mysql_query("INSERT INTO `records_trading` (`trade`, `from_user`, `to_user`, `package_id`, `package_type`, `timestamp`) VALUES ('" . ($tradeid + 0) . "', '" . protectSQL($fetch_trade['account_' . $i]) . "', '" . protectSQL($fetch_trade['account_' . ($i == 1 ? "2" : "1")]) . "', '" . ($fetch_packages['id'] + 0) . "', '" . ($fetch_packages['packageID'] + 0) . "', '" . time() . "')");
				}
			
				// give auro and credits to both
				if ($fetch_trade['auro_1'] > 0)
					mysql_query("UPDATE `u5s_auth`.`s4u_account_trackers` SET `auro`=`auro`+" . ($fetch_trade['auro_1'] + 0) . " WHERE `account`='" . protectSQL($fetch_trade['account_2']) . "' LIMIT 1");
				if ($fetch_trade['auro_2'] > 0)
					mysql_query("UPDATE `u5s_auth`.`s4u_account_trackers` SET `auro`=`auro`+" . ($fetch_trade['auro_2'] + 0) . " WHERE `account`='" . protectSQL($fetch_trade['account_1']) . "' LIMIT 1");
				if ($fetch_trade['credits_1'] > 0)
					mysql_query("UPDATE `u5s_auth`.`s4u_account_credits` SET `credits`=`credits`+" . ($fetch_trade['credits_1'] + 0) . " WHERE `account`='" . protectSQL($fetch_trade['account_2']) . "' LIMIT 1");
				if ($fetch_trade['credits_2'] > 0)
					mysql_query("UPDATE `u5s_auth`.`s4u_account_credits` SET `credits`=`credits`+" . ($fetch_trade['credits_2'] + 0) . " WHERE `account`='" . protectSQL($fetch_trade['account_1']) . "' LIMIT 1");
				
				// move all items and packages to new owner, remove from trade
				mysql_query("UPDATE `avatar_clothing` SET `account`='" . protectSQL($fetch_trade['account_2']) . "', `in_trade`='0' WHERE `account`='" . protectSQL($fetch_trade['account_1']) . "' AND `in_trade`='" . ($tradeid + 0) . "'");
				mysql_query("UPDATE `avatar_clothing` SET `account`='" . protectSQL($fetch_trade['account_1']) . "', `in_trade`='0' WHERE `account`='" . protectSQL($fetch_trade['account_2']) . "' AND `in_trade`='" . ($tradeid + 0) . "'");
				
				mysql_query("UPDATE `exotic_packages_owned` SET `account`='" . protectSQL($fetch_trade['account_2']) . "', `in_trade`='0' WHERE `account`='" . protectSQL($fetch_trade['account_1']) . "' AND `in_trade`='" . ($tradeid + 0) . "'"); 
				mysql_query("UPDATE `exotic_packages_owned` SET `account`='" . protectSQL($fetch_trade['account_1']) . "', `in_trade`='0' WHERE `account`='" . protectSQL($fetch_trade['account_2']) . "' AND `in_trade`='" . ($tradeid + 0) . "'"); 
				
				// delete trade itself
				mysql_query("DELETE FROM `trading_system` WHERE `id`='" . ($tradeid + 0) . "' LIMIT 1");
				
				// notify trade partner
				file_get_contents(UNIFACTION . "API_notifyCommon.php?account=" . urlencode($fetch_trade['account_2']) . "&siteName=" . urlencode($siteName) . "&title=" . urlencode("Completed Trade") ."&message=" . urlencode($fetch_account['account'] . " has completed a <a href='" . AVATAR . "trade_log.php'>trade</a> with you.") . "&h=" . hash("sha256", "notify_" . $siteName . $siteKey . $fetch_trade['account_2']));
				
				header("Location: trade_log.php");
				exit;
			}
		}
	}
	
	function getdata_trade($tradeid, $accountnum, $isrecord = false)
	{
		$ret = "";
		if ($isrecord)
			$q = "SELECT `account_" . ($accountnum + 0) . "`, `auro_" . ($accountnum + 0) . "`, `credits_" . ($accountnum + 0) . "`, `messagebox_" . ($accountnum + 0) . "` FROM `records_trades` WHERE `id`='" . ($tradeid + 0) . "' LIMIT 1";
		else
			$q = "SELECT `account_" . ($accountnum + 0) . "`, `auro_" . ($accountnum + 0) . "`, `credits_" . ($accountnum + 0) . "`, `messagebox_" . ($accountnum + 0) . "` FROM `trading_system` WHERE `id`='" . ($tradeid + 0) . "' LIMIT 1";
		$res = mysql_query($q);
		if ($fetch = mysql_fetch_assoc($res))
		{
			// general data
			$ret .= "<b>" . $fetch['account_' . $accountnum] . "</b><br/><br/>";
			if ($fetch['messagebox_' . $accountnum] != "")
				$ret .= $fetch['messagebox_' . $accountnum] . "<br/><br/>";
				
			$sending = array();
			
			if ($fetch['auro_' . $accountnum] > 0)
				$sending[] = $fetch['auro_' . $accountnum] . " Auro";
			if ($fetch['credits_' . $accountnum] > 0)
				$sending[] = $fetch['credits_' . $accountnum] . " Credits";
			
			// items
			if ($isrecord)
				$q = "SELECT `clothing_images`.`clothing` FROM `records_trading` INNER JOIN `clothing_images` ON `records_trading`.`item_type`=`clothing_images`.`clothingID` WHERE `records_trading`.`trade`='" . ($tradeid + 0) . "' AND `records_trading`.`from_user`='" . protectSQL($fetch['account_' . $accountnum]) . "'";
			else
				$q = "SELECT `clothing_images`.`clothing` FROM `avatar_clothing` INNER JOIN `clothing_images` ON `avatar_clothing`.`clothingID`=`clothing_images`.`clothingID` WHERE `avatar_clothing`.`account`='" . protectSQL($fetch['account_' . $accountnum]) . "' AND `avatar_clothing`.`in_trade`='" . ($tradeid + 0) . "'";
			$res = mysql_query($q);
			while ($fetch_item = mysql_fetch_assoc($res))
				$sending[] = $fetch_item['clothing'];
				
			// packages
			if ($isrecord)
				$q = "SELECT `exotic_packages`.`title` FROM `records_trading` INNER JOIN `exotic_packages` ON `records_trading`.`package_type`=`exotic_packages`.`id` WHERE `records_trading`.`trade`='" . ($tradeid + 0) . "' AND `records_trading`.`from_user`='" . protectSQL($fetch['account_' . $accountnum]) . "'";
			else
				$q = "SELECT `exotic_packages`.`title` FROM `exotic_packages_owned` INNER JOIN `exotic_packages` ON `exotic_packages_owned`.`packageID`=`exotic_packages`.`id` WHERE `exotic_packages_owned`.`account`='" . protectSQL($fetch['account_' . $accountnum]) . "' AND `exotic_packages_owned`.`in_trade`='" . ($tradeid + 0) . "'";
			$res = mysql_query($q);
			while ($fetch_package = mysql_fetch_assoc($res))
				$sending[] = $fetch_package['title'];
				
			if ($sending != array())
			{
				$ret .= "<ul>";
				foreach ($sending as $send)
					$ret .= "<li>" . $send . "</li>";
				$ret .= "</ul>";
			}
		}
		return $ret;
	}
?>