<?php
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");
	
	if (!isset($_GET['item']) || !isset($_GET['left']) || !isset($_GET['top']) || !isset($_GET['where']) || !isset($_GET['check']))
	{
		header("Location: index.php");
		exit;
	}
	
	// check validity
	$check = strrev(substr(sha1($_GET['item'] . "_rePNQ_" . $_GET['left'] . "_" . $_GET['top'] . "_" . $fetch_account['account'] . "_" . $_GET['where']), 22, 10));
	$message = "";
	if ($check == $_GET['check'])
	{
		$year_day = date("Y") . "_" . date("z");
		$last_grab = $memc->get("treasure_" . $fetch_account['account'] . "_grab_" . $year_day . "_rePNQ");
		if (!$last_grab)
		{
			$last_grab = 0;
			$cooldown = 3600;
			$q_available = mysql_query("SELECT `items` FROM `treasure_search_staff` WHERE `year_day`='" . protectSQL($year_day) . "' LIMIT 1");
			if ($available = mysql_fetch_assoc($q_available))
			{
				$items = explode(",", $available['items']);
				foreach ($items as $key => $val)
				{
					$q_has = mysql_query("SELECT `timestamp` FROM `treasure_search` WHERE `account`='" . protectSQL($fetch_account['account']) . "' AND `received`='" . protectSQL($val) . "' LIMIT 1");
					if ($has = mysql_fetch_assoc($q_has))
					{
						unset($items[$key]);
						if ($has['timestamp'] > $last_grab)
							$last_grab = $has['timestamp'];
					}
				}
				
				if ($items != array())
				{
					// in case memcache failed
					if (time() - $last_grab > $cooldown)
					{
						// item is available for this day and hasn't been taken
						if (in_array($_GET['item'], $items))
						{
							$q_piece = mysql_query("SELECT `clothingID`, `clothing`, `position`, `used_by` FROM `clothing_images` WHERE `clothingID`='" . protectSQL($_GET['item']) . "' LIMIT 1");
							if ($piece = mysql_fetch_assoc($q_piece))
							{
								if (mysql_query("INSERT INTO `treasure_search` (`account`, `ip`, `year_day`, `received`, `origin`, `timestamp`) VALUES ('" . protectSQL($fetch_account['account']) . "', '" . protectSQL($_SERVER['REMOTE_ADDR']) . "', '" . protectSQL($year_day) . "', '" . protectSQL($piece['clothingID']) . "', '" . protectSQL($_GET['where']) . "', '" . time() . "')"))
								{
									if (mysql_query("INSERT INTO `avatar_clothing` (`account`, `clothingID`, `position`) VALUES ('" . protectSQL($fetch_account['account']) . "', '" . protectSQL($piece['clothingID']) . "', '" . protectSQL($piece['position']) . "')"))
									{
										$message = "You have taken " . $piece['clothing'] . ". Go <a href='dress_avatar.php?position=" . $piece['position'] . "#wrap'>here</a> to view it. This item can be used by " . ($piece['used_by'] != "both" ? $piece['used_by'] . " avatars." : "both genders.");
									
										// save
										$memc->set("treasure_" . $fetch_account['account'] . "_grab_" . $year_day . "_rePNQ", time(), false, $cooldown);
									}
								}
							}
						}
						else
							$message = "This item is not available today, or you have already taken it.";
					}
					else
						$message = "You have last taken an item " . floor((time() - $last_grab) / 60) . " minutes ago. The cooldown is still active.";
				}
				// all items for the day have been grabbed, avoid unnecessary queries
				else
				{
					$message = "You have already taken all of today's items.";
					$memc->set("treasure_" . $fetch_account['account'] . "_show_" . $year_day . "_rePNQ", time(), false, 86400);
				}
			}
			else
				$message = "There are no items available for today.";
		}
		else
			$message = "You have last taken an item " . floor((time() - $last_grab) / 60) . " minutes ago. The cooldown is still active.";
	}
	else
		$message = "Something seems to be wrong with your link.";
	
	$pagetitle = "Treasure Hunt";
	require("incAVA/header.php");
?>
			<div class='category-container'>
				<div class='details-header'>
					Treasure Hunt
				</div>
				<div class='details-body'>
					<?php echo $message; ?>
				</div>
			</div>
<?php
	require("incAVA/footer.php");
?>