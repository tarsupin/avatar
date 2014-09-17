<?php
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");
	
	// no need to define EXIST since it'll just be the base
	require("fanctions/check_and_draw.php");

	$has_upped = 0;

	$result = mysql_query("SELECT `max` FROM `max_avatars` WHERE `account`='" . protectSQL($fetch_account['account']) . "' LIMIT 1");
	if (!$fetch_maxavis = mysql_fetch_assoc($result))
		$fetch_maxavis['max'] = 3;
	else
		$has_upped = 1;

	// If Submission is Made
	if (isset($_POST['submit']) && isset($_POST['avatar_name']) && isset($_POST['gender']) && isset($_POST['base']))
	{
		$_POST['avatar_name'] = trim($_POST['avatar_name']);
		
		// If name is too short
		if (strlen($_POST['avatar_name']) < 3)
			$messages[] = "<div class='message-error'>That avatar name is too short!</div>";
		else
		{
			// Check if Avatar Exists
			$result = mysql_query("SELECT `avatar` FROM `avatars` WHERE `account`='" . protectSQL($fetch_account['account']) . "' AND `avatar`='" . protectSQL($_POST['avatar_name']) . "' LIMIT 1");
			
			if (mysql_num_rows($result) > 0)
				$messages[] = "<div class='message-error'>You already have an avatar named " . $_POST['avatar_name'] . ".</div>";
		}
			
		// Check if the player has 3 or more avatars
		$resAdv = mysql_query("SELECT COUNT(`id`) as `val` FROM `avatars` WHERE `account`='" . protectSQL($fetch_account['account']) . "'");
			
		if ($fetch_adv = mysql_fetch_assoc($resAdv))
		{
			if ($fetch_adv['val'] >= $fetch_maxavis['max'])
				$messages[] = "<div class='message-error'>You have reached the maximum allowed avatars.</div>";
		}
		
		// Create Avatars
		if ($messages == array())
		{
			// Proof Check Input
			if ($_POST['gender'] != "male")
				$_POST['gender'] = "female";
			
			// Proof-Check Base		
			if (!in_array($_POST['base'], array("white", "tan", "light", "pacific", "dark")))
				$_POST['base'] = "white";
			
			// Create Avatar
			mysql_query("
			INSERT INTO `avatars`
			(
				`account`,
				`avatar`,
				`base`,
				`gender`,
				`is_heroic`,
				`last_timestamp`
			) VALUES (
				'" . protectSQL($fetch_account['account']) . "',
				'" . protectSQL($_POST['avatar_name']) . "',
				'" . protectSQL($_POST['base']) . "',
				'" . protectSQL($_POST['gender']) . "',
				'yes',
				'" . time() . "'
			)");
			
			$avatar_id = mysql_insert_id();
			
			// Setup Avatar Image
			$new = array(array(0, $_POST['base']));
			$q = "INSERT INTO `avatar_outfits_real` (`avatar_id`, `outfit_serial`) VALUES ('" . ($avatar_id + 0) . "', '" . mysql_real_escape_string(serialize($new)) . "')";
			mysql_query($q);
			
			// Create Directory for Your Avatars
			if (!file_exists("characters/" . $fetch_account['account'] . "/"))
				mkdir("characters/" . $fetch_account['account'] . "/");
			
			// Create Avatar Image
			wrapper($new, $_POST['gender'], $_POST['base'], $fetch_account['account'], $avatar_id);
			
			// Go to Avatar Profile
			header("Location: avatar_list.php?id=" . ($avatar_id + 0));
		}
	}
	elseif (isset($_POST['purchase_slot']))
	{
		if ($fetch_maxavis['max'] >= 9)
			$messages[] = "<div class='message-error'>You have reached the maximum allowed avatar slots.</div>";
		else
		{
			if ($fetch_maxavis['max'] == 3)
				$cost = 10;
			else
				$cost = 15;
			
			// Spend Credits
			$value = file_get_contents(UNIFACTION . "API_autoSpendCredits.php?account=" . $fetch_account['account'] . "&amount=" . $cost . "&site=" . $siteName . "&hash=" . hash("sha256", "@uto$37^&*()credits003#" . $siteName . $siteKey . $fetch_account['account'] . $cost));

			if ($value == "SUCCESS")
			{
				if ($has_upped == 1)
				{
					mysql_query("UPDATE `max_avatars` SET `max`=`max`+1 WHERE `account`='" . protectSQL($fetch_account['account']) . "' LIMIT 1");
					$fetch_maxavis['max']++;
				}
				else
				{
					mysql_query("INSERT INTO `max_avatars` (`account`, `max`) VALUES ('" . protectSQL($fetch_account['account']) . "', '4')");
					$fetch_maxavis['max'] = 4;
				}
				
				$messages[] = "<div class='message-success'>You have unlocked another avatar slot. With this you may have up to " . $fetch_maxavis['max'] . " avatars.</div>";
				
				// double bookkeeping; don't remove
				file_get_contents("http://auth.unifaction.com/API_notifyCommon.php?account=Pegasus&siteName=" . urlencode($siteName) . 
					"&title=" . urlencode("Slot Purchase") . 
					"&message=" . urlencode($fetch_account['account'] . " has unlocked avatar slot #" . ($fetch_maxavis['max'] - 3) . " for " . $cost . " Credits.") .
					"&h=" . hash("sha256", "notify_" . $siteName . $siteKey . "Pegasus"));
				
				file_put_contents("genFiles/purchased_slots.txt", date("M j, Y h:i:sa", time()) . " UniTime | " . $cost . " Credits | " . $fetch_account['account'] . "\n", FILE_APPEND);
			}
			else
				$messages[] = "<div class='message-error'>You do not have enough Credits to make this purchase.</div>";
		}
	}
				
	$pagetitle = "Create Avatar";
	require("incAVA/header.php");
?>
			<div class='category-container'>
				<div class='details-header'>
					Create Avatar
				</div>
				<div class='details-body'>
					<table style='text-align:left;'>
						<tr>
							<td><img id='aviBase' src='images/create/female_white.png'/></td>
							<td style='vertical-align:top;'>
								<form method='post'>
									<table style='text-align:left;'>
										<tr>
											<td><b>Base:</b></td>
											<td>
												<select name='base' id='id_base' onchange='change_avatar(); return false;' title='Base'>
													<option value='white' selected='selected'>White</option>
													<option value='pacific'>Pacific</option>
													<option value='light'>Light</option>
													<option value='tan'>Tan</option>
													<option value='dark'>Dark</option>
												</select>
											</td>
										</tr>
										<tr>
											<td><b>Gender:</b></td>
											<td>
												<select name='gender' id='id_gender' onchange='change_avatar(); return false;' title='Gender'>
													<option value='male'>Male</option>
													<option value='female' selected='selected'>Female</option>
												</select>
											</td>
										</tr>
										<tr>
											<td><b>Avatar Name:</b></td>
											<td><input type='text' name='avatar_name' value='' maxlength='20'/></td>
										</tr>
										<tr>
											<td><b>Generate:</b></td>
											<td><input type='submit' name='submit' value='Create Avatar'/></td>
										</tr>
									</table>
								</form>
								<br/><br/>
								<ul>
									<li>
										You can have 3 avatars for free and up to 6 additional (9 total) in exchange for Credits:
										<ul>
											<li>The first additional avatar slot costs 10 Credits.</li>
											<li>Any further additional avatar slots cost 15 Credits each.</li>
										</ul>
									</li>
									<li>You have unlocked <?php echo ($fetch_maxavis['max'] - 3); ?> additional slots, therefore you may have up to <?php echo $fetch_maxavis['max']; ?> avatars.</li>
								</ul>
								<br/><br/>
<?php
	if ($fetch_maxavis['max'] == 3)
		$cost = 10;
	else
		$cost = 15;
?>

								<form method='POST'>
									<input onclick='return confirm("Are you sure you want to unlock another avatar slot? This will cost you <?php echo $cost; ?> Credits.")' type='submit' name='purchase_slot' value='Unlock Additional Slot'/> Please be careful as we can not compensate if you click and confirm by accident. Thank you for giving to UniFaction.
								</form>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<script type='text/javascript'>
				function change_avatar()
				{
					avatarBase = document.getElementById("aviBase");
					base_val = document.getElementById("id_base");
					gender_val = document.getElementById("id_gender");
					
					avatarBase.src = "images/create/" + gender_val.value + "_" + base_val.value + ".png";
				}
			</script>
<?php
	require("incAVA/footer.php");
?>