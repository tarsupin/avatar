<?php
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");

	// Leave page if you don't have an avatar active
	if (!isset($fetch_avatar['id']))
	{
		header("Location: index.php");
		exit;
	}
	
	define("EXIST", "own");
	require("fanctions/check_and_draw.php");

	// Gender Swap
	if (isset($_GET['gender']))
	{
		if (($_GET['gender'] == "female" && $fetch_avatar['gender'] == "male") || ($_GET['gender'] == "male" && $fetch_avatar['gender'] == "female"))
		{
			// Spend Auro
			$value = file_get_contents(UNIFACTION . "API_autoSpendAuro.php?account=" . $fetch_account['account'] . "&amount=1000&site=" . $siteName . "&hash=" . hash("sha256", "@uto$37@uro" . $siteName . $siteKey . $fetch_account['account']));
			
			if ($value == "SUCCESS")
			{
				// Update Gender
				$fetch_avatar['gender'] = $_GET['gender'];
				
				mysql_query("UPDATE `avatars` SET `gender`='" . protectSQL($_GET['gender']) . "' WHERE `id`='" . ($fetch_avatar['id'] + 0) . "' LIMIT 1");
				mysql_query("UPDATE `u5s_adventure`.`account_data` SET `gender`='" . protectSQL($_GET['gender']) . "' WHERE `account`='" . protectSQL($fetch_account['account']) . "' AND `active_avatar`='" . ($fetch_avatar['id'] + 0) . "' LIMIT 1");
				
				$messages[] = "<div class='message-success'>" . $fetch_avatar['avatar'] . " is now a " . $fetch_avatar['gender'] . "</div>";
				
				// Update Avatar Image
				$outfit = get_outfit($fetch_avatar['id'], $fetch_avatar['base']);
				wrapper($outfit, $fetch_avatar['gender'], $fetch_avatar['base'], $fetch_account['account'], $fetch_avatar['id']);
				// update timestamp
				$fetch_avatar['last_timestamp'] = time();
			}
			else
				$messages[] = "<div class='message-error'>You do not have enough Auro!</div>";
		}
	}
	elseif (isset($_GET['base']) && $_GET['base'] != $fetch_avatar['base'])
	{
		// Spend Auro
		$value = file_get_contents(UNIFACTION . "API_autoSpendAuro.php?account=" . $fetch_account['account'] . "&amount=30&site=" . $siteName . "&hash=" . hash("sha256", "@uto$37@uro" . $siteName . $siteKey . $fetch_account['account']));
		
		if ($value == "SUCCESS")
		{
			// Update Gender
			$fetch_avatar['base'] = $_GET['base'];
			
			mysql_query("UPDATE `avatars` SET `base`='" . protectSQL($_GET['base']) . "' WHERE `id`='" . ($fetch_avatar['id'] + 0) . "' LIMIT 1");
			
			$messages[] = "<div class='message-success'>" . $fetch_avatar['avatar'] . " now has a " . ucfirst($fetch_avatar['base']) . " Avatar Base!</div>";
			
			// Update Avatar Image
			$outfit = get_outfit($fetch_avatar['id'], $fetch_avatar['base']);
			wrapper($outfit, $fetch_avatar['gender'], $fetch_avatar['base'], $fetch_account['account'], $fetch_avatar['id']);
			// update timestamp
			$fetch_avatar['last_timestamp'] = time();
		}
		else
			$messages[] = "<div class='message-error'>You do not have enough Auro!</div>";
	}
	elseif (isset($_POST['avatar_name']))
	{
		$_POST['avatar_name'] = trim($_POST['avatar_name']);
		
		// If name is too short
		if (strlen($_POST['avatar_name']) < 3)
			$messages[] = "<div class='message-error'>That avatar name is too short!</div>";
		else
		{
			// Check if Avatar Exists
			$result = mysql_query("SELECT `avatar` FROM `avatars` WHERE `account`='" . protectSQL($fetch_account['account']) . "' AND `avatar`='" . protectSQL($_POST['avatar_name']) . "' AND `id`!=" . ($fetch_avatar['id'] + 0) . " LIMIT 1");
			
			if (mysql_num_rows($result) > 0)
			{
				// Don't rename
				$messages[] = "<div class='message-error'>You already have an avatar named " . $_POST['avatar_name'] . ".</div>";
			}
		}
		
		$result = mysql_query("SELECT `last_rename` FROM `account_info` WHERE `account`='" . protectSQL($fetch_account['account']) . "' LIMIT 1");
		if ($fetch_last = mysql_fetch_assoc($result))
		{
			// last change was too recently
			if (time() - $fetch_last['last_rename'] < 30*86400)
				$messages[] = "<div class='message-error'>You have last renamed an avatar on " . date("M j, Y g:ia", $fetch_last['last_rename']) . " UniTime. Not enough time has passed since then.</div>";
			
			if ($messages == array())
			{
				if (isset($_POST['submit_no']))
				{
					// Spend Auro
					$value = file_get_contents(UNIFACTION . "API_autoSpendAuro.php?account=" . $fetch_account['account'] . "&amount=3000&site=" . $siteName . "&hash=" . hash("sha256", "@uto$37@uro" . $siteName . $siteKey . $fetch_account['account']));
			
					if ($value == "SUCCESS")
					{
						mysql_query("UPDATE `avatars` SET `avatar`='" . protectSQL($_POST['avatar_name']) . "' WHERE `account`='" . protectSQL($fetch_account['account']) . "' AND `id`=" . ($fetch_avatar['id'] + 0) . " LIMIT 1");
						mysql_query("UPDATE `account_info` SET `last_rename`=" . (time() + 0) . " WHERE `account`='" . protectSQL($fetch_account['account']) . "' LIMIT 1");
						mysql_query("UPDATE `u5s_forum`.`s4u_user_list` SET `activeAvatarName`='" . protectSQL($_POST['avatar_name']) . "' WHERE `account`='" . protectSQL($fetch_account['account']) . "' AND `activeAvatar`=" . ($fetch_avatar['id'] + 0) . " LIMIT 1");
						
						$messages[] = "<div class='message-success'>You have successfully renamed your avatar from " . $fetch_avatar['avatar'] . " to " . $_POST['avatar_name'] . ".</div>";
						$fetch_avatar['avatar'] = $_POST['avatar_name'];
					}
					else
						$messages[] = "<div class='message-error'>You do not have enough Auro to make this change.</div>";
				}
				elseif (isset($_POST['submit_yes']))
				{
					// Spend Credits
					$value = file_get_contents(UNIFACTION . "API_autoSpendCredits.php?account=" . $fetch_account['account'] . "&amount=5&site=" . $siteName . "&hash=" . hash("sha256", "@uto$37^&*()credits003#" . $siteName . $siteKey . $fetch_account['account'] . "5"));
			
					if ($value == "SUCCESS")
					{
						mysql_query("UPDATE `avatars` SET `avatar`='" . protectSQL($_POST['avatar_name']) . "' WHERE `account`='" . protectSQL($fetch_account['account']) . "' AND `id`=" . ($fetch_avatar['id'] + 0) . " LIMIT 1");
						mysql_query("UPDATE `account_info` SET `last_rename`=" . (time() + 0) . " WHERE `account`='" . protectSQL($fetch_account['account']) . "' LIMIT 1");
						mysql_query("UPDATE `u5s_forum`.`s4u_user_list` SET `activeAvatarName`='" . protectSQL($_POST['avatar_name']) . "' WHERE `account`='" . protectSQL($fetch_account['account']) . "' AND `activeAvatar`=" . ($fetch_avatar['id'] + 0) . " LIMIT 1");
						
						for ($year=date("Y"); $year>=2011; $year--)
						{
							for ($month=12; $month>=1; $month--)
							{
								if (mktime(0, 0, 0, 3, 1, 2011) <= mktime(0, 0, 0, $month, 1, $year) && mktime(0, 0, 0, $month, 1, $year) <= mktime(0, 0, 0, date("n"), 1, date("Y")))
								{
									$postTable = "u5s_forum.s4u_post_" . $year . "_" . $month;
									if ($year != date("Y") || $month != date("n") || mysql_num_rows(mysql_query("SHOW TABLES LIKE '" . protectSQL($postTable) . "'")) > 0)
									{
										mysql_query("UPDATE " . protectSQL($postTable) . " SET `avatar_name`='" . protectSQL($_POST['avatar_name']) . "' WHERE `poster`='" . protectSQL($fetch_account['account']) . "' AND `avatar_id`=" . ($fetch_avatar['id'] + 0));
									}
								}
							}
						}
						
						$messages[] = "<div class='message-success'>You have successfully renamed your avatar from " . $fetch_avatar['avatar'] . " to " . $_POST['avatar_name'] . ". All posts made with this avatar have been updated to show the new name.</div>";
						$fetch_avatar['avatar'] = $_POST['avatar_name'];
					}
					else
						$messages[] = "<div class='message-error'>You do not have enough Credits to make this change.</div>";
				}
			}
		}
	}

	$pagetitle = "Edit " . $fetch_avatar['avatar'];
	require("incAVA/header.php");
?>
			<div class='category-container'>
				<div class='details-header'>
					Edit <?php echo $fetch_avatar['avatar']; ?>

				</div>
				<div class='details-body'>
<?php
	// Change Gender
	if ($fetch_avatar['gender'] == "female") { $opGen = "male"; } else { $opGen = "female"; }

echo "
					<ul>
						<li>Change your gender from " . ucfirst($fetch_avatar['gender']) . " to <a href='edit_avatar.php?gender=" . $opGen . "' onclick='return confirm(\"Are you sure you want to switch " . $fetch_avatar['avatar'] . " to a " . $opGen . " for 1000 Auro?\");'>" . ucfirst($opGen) . "</a>. This costs 1000 Auro.<br/><br/></li>
						<li>Click on an avatar below to change your base. Changing your Base costs 30 Auro.<br/>
							<table style='width:100%;'>
								<tr>";
				
				// Draw the base images, and provide links
				$baseTypes = array("dark", "light", "pacific", "tan", "white");
				
				foreach($baseTypes as $newbase)
				{
					if ($fetch_avatar['base'] != $newbase)
					{
						echo "
									<td>
										<a href='edit_avatar.php?base=" . $newbase . "' onclick='return confirm(\"Are you sure you want to change your base to " . $newbase . "? This will cost you 30 Auro.\");'><img src='images/create/" . $fetch_avatar['gender'] . "_" . $newbase . ".png' width='150'/><br/></a>" . ucfirst($newbase) . "
									</td>";
					}
					else
					{
						echo "
									<td>
										<img src='images/create/" . $fetch_avatar['gender'] . "_" . $newbase . ".png' width='150'/><br/>" . ucfirst($newbase) . "
									</td>";
					}
				}
?>

								</tr>
							</table><br/>
						</li>
						<li>Change your name.
							<ul>
								<li>This applies to all future posts made with this avatar.</li>
								<li>When you have changed the name, 30 days must pass before you may again change the name of any of your avatars.</li>
								<li>If you click "Basic Change", posts made with this avatar before the change will still show the old name unless you edit them manually. This costs 3000 Auro.</li>
								<li>If you click "Extensive Change" instead, all posts made with this avatar before the change will also show the new name. This costs 5 Credits.</li>
							</ul>
							<form method='post'>
								<input type='text' name='avatar_name' maxlength='20'/>
								<input onclick='return confirm("Are you sure you want to rename your avatar? This will cost you 3000 Auro.");' type='submit' name='submit_no' value='Basic Change'/>
								<input onclick='return confirm("Are you sure you want to rename your avatar and update the name in previous posts? This will cost you 5 Credits.");' type='submit' name='submit_yes' value='Extensive Change'/>
							</form>
						</li>
					</ul>
				</div>
			</div>
<?php
	require("incAVA/footer.php");
?>