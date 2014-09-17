<?php
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");

	// Check if user selected an avatar
	if (isset($_GET['id']))
	{
		// Check if you own the avatar
		$result = mysql_query("SELECT `id`, `avatar` FROM `avatars` WHERE `id`='" . ($_GET['id'] + 0) . "' AND `account`='" . protectSQL($fetch_account['account']) . "' LIMIT 1");

		if ($fetch_avatar = mysql_fetch_assoc($result))
		{
			// Update Avatar Setting
			mysql_query("UPDATE `account_info` SET `active_avatar`='" . ($fetch_avatar['id'] + 0) . "' WHERE `account`='" . protectSQL($fetch_account['account']) . "' LIMIT 1");
			
			$messages[] = "<div class='message-success'>You have selected " . $fetch_avatar['avatar'] . "!</div>";
		}
		else
			$messages[] = "<div class='message-error'>There was an error while attempting to select your avatar. Please try again.</div>";
	}

	// Gather Avatars (check if available)
	$avatars = explode("|", file_get_contents("http://avatar.unifaction.com/API_avatarList.php?account=" . $fetch_account['account']));
	if ($avatars[0] == "")
	{
		header("Location: index.php");
		exit;
	}

	$pagetitle = "Select Active Avatar";
	require("incAVA/header.php");
?>
			<div class='category-container'>
				<div class='details-header'>
					Select Active Avatar
				</div>
				<div class='details-body'>
					<table style='margin:0px auto;'>
<?php
	$slotX = 0;
	foreach ($avatars as $avi)
	{
		if ($slotX % 3 == 0)
			echo "
						<tr>";
		$aviexp = explode(":", $avi);
		$t = $memc->get("lastaviupdate_" . $aviexp[0]);
		echo "
							<td style='width:250px;'>
								<div style='font-weight:bold; font-size:16px;'>" . $aviexp[1] . "</div><br/>
								<a href='avatar_list.php?id=" . $aviexp[0] . "'><img src='characters/" . $fetch_account['account'] . "/avi_" . $aviexp[0] . ".png" . ($t ? "?t=" . $t : "") . "'/></a>
							</td>";
			if ($slotX % 3 == 2)
				echo "
						</tr>";
		$slotX++;
	}
	if ($slotX % 3 > 0)
		echo "
						</tr>";
?>

					</table>
					<br/>
					<div style='text-align:center;'>To set an avatar for other parts of UniFaction, use the following links: <a href='http://forum.unifaction.com/my_avatars.php'>Forum</a>, <a href='http://profile.unifaction.com/my_avatars.php?account=<?php echo $fetch_account['account']; ?>'>Profile</a>, <a href='http://inbox.unifaction.com/my_avatars.php'>Inbox</a></div>
				</div>
			</div>
<?php
	require("incAVA/footer.php");
?>