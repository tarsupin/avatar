<?php
	if (!isset($_GET['item']))
	{
		header("Location: index.php");
		exit;
	}

	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");
	define("EXIST", "own");
	require("fanctions/check_and_draw.php");

	// Check if you own item
	if (!$fetch_item['clothingID'] = owned_id($_GET['item'], $fetch_account['account']))
	{
		header("Location: index.php");
		exit;
	}
	$fetch_item['id'] = $_GET['item'] + 0;

	// Gather the item Type
	$result = mysql_query("SELECT `clothing`, `position` FROM `clothing_images` WHERE `clothingID`='" . ($fetch_item['clothingID'] + 0) . "' LIMIT 1");
	if (!$fetch_type = mysql_fetch_assoc($result))
	{
		header("Location: index.php");
		exit;
	}

	// If Page is Submitted
	if (isset($_POST['submit']) && isset($_POST['recipient']))
	{
		// Prepare Submitted Variables
		$_POST['recipient'] = trim($_POST['recipient']);
		
		// Check if recipient uses the Avatar System
		$result = mysql_query("SELECT `account` FROM `account_info` WHERE `account`='" . protectSQL($_POST['recipient']) . "' LIMIT 1");
		
		if ($fetch_recipient = mysql_fetch_assoc($result))
		{
			if ($fetch_recipient['account'] != $fetch_account['account'])
			{
				// Switch Item to New User
				mysql_query("UPDATE `avatar_clothing` SET `account`='" . protectSQL($fetch_recipient['account']) . "' WHERE `id`='" . ($_GET['item'] + 0) . "' LIMIT 1");
				
				// Update Avatar Images
				if (!owned_item($fetch_item['clothingID'], $fetch_account['account']))
				{
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
								foreach ($fetch['outfit_serial'] as $key => $val)
									if ($val[0] == $fetch_item['clothingID'])
									{
										unset($fetch['outfit_serial'][$key]);
										wrapper($fetch['outfit_serial'], $aviexp[2], $aviexp[3], $fetch_account['account'], $aviexp[0]);
										break;
									}
							}
						}
					}
				}
				
				// Send Message
				file_get_contents(UNIFACTION . "API_notifyCommon.php?account=" . urlencode($fetch_recipient['account']) . "&siteName=" . urlencode($siteName) . "&title=" . urlencode("You have been gifted an Avatar Item!") ."&message=" . urlencode("You have just received a " . $fetch_type['clothing'] . " from " . (isset($_POST['anonymous']) == true ? "an anonymous gifter!" : $fetch_account['account'] . "!") . " You can review your new item in your <a href='" . AVATAR . "dress_avatar.php?position=" . $fetch_type['position'] . "'>dressing room</a>.") . "&h=" . hash("sha256", "notify_" . $siteName . $siteKey . $fetch_recipient['account']));
				
				// add to history
				mysql_query("INSERT INTO `records_gifting` (`from_user`, `to_user`, `item_id`, `item_type`, `anonymous`, `timestamp`) VALUES ('" . protectSQL($fetch_account['account']) . "', '" . protectSQL($fetch_recipient['account']) . "', '" . ($fetch_item['id'] + 0) . "', '" . ($fetch_item['clothingID'] + 0) . "', '" . (isset($_POST['anonymous']) == true ? "1" : "0") . "', " . time() . ")");
				
				$messages[] = "<div class='message-success'>You have successfully gifted " . $fetch_type['clothing'] . " to " . $fetch_recipient['account'] . ".</div>";
				
				$fetch_type['clothing'] = "";
			}
			else
				$messages[] = "<div class='message-error'>You cannot gift an item to yourself.</div>";
		}
		else
			$messages[] = "<div class='message-error'>" . $_POST['recipient'] . " does not currently use the avatar system.</div>";
	}

	$pagetitle = "Send Item as a Gift";
	require("incAVA/header.php");
?>
			<div class='category-container'>
				<div class='details-header'>
					Send Item as a Gift
				</div>
				<div class='details-body'>
<?php
	// Display Page
	if ($fetch_type['clothing'] != "")
	{
		$item = "";
		
		// Gather File
		if ($handle = opendir("avatars/" . $fetch_type['position'] . "/" . $fetch_type['clothing']))
		{
			while (false !== ($file = readdir($handle)))
			{
				if ($file != "." && $file != "..")
				{
					if (strpos($file, ".png"))
					{
						$item = $file;
						break;
					}
				}
			}
		}
		
		// Display Form
		echo "
					<form method='post'>
						<table>
							<tr>
								<td style='width:205px;'>
									" . ($item != '' ? "<img src='avatars/" . $fetch_type['position'] . "/" . $fetch_type['clothing'] . "/" . $item . "'/>" : '&nbsp;') . "
								</td>
								<td style='text-align:left; vertical-align:top;'>
									Who would you like to send the " . $fetch_type['clothing'] . " to?<br/>
									<input type='text' name='recipient' value='' size='30' maxlength='20'/><br/>
									<input type='submit' name='submit' value='Send Gift'/><br/>
									<input type='checkbox' name='anonymous' value=''/> Send Anonymously<br/>
								</td>
							</tr>
						</table>
					</form>";
	}
	else
	{
		echo "
					Thank you for visiting the gift page!<br/><a href='dress_avatar.php'>Return To Dressing Room</a> | <a href='gift_log.php'>View Gift Log</a>";
	}
?>

				</div>
			</div>
<?php
	require("incAVA/footer.php");
?>