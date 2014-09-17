<?php
	if (!isset($_GET['package']))
	{
		header("Location: index.php");
		exit;
	}

	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");

	// Check if you own package
	/*$q = "SELECT `id`, `packageID` FROM `exotic_packages_owned` WHERE `id`='" . ($_GET['package'] + 0) . "' AND `account`='" . protectSQL($fetch_account['account']) . "' LIMIT 1";
	$res = mysql_query($q);
	if (!$fetch_package = mysql_fetch_assoc($res))
	{
		header("Location: index.php");
		exit;
	}

	// Gather the package Type
	$result = mysql_query("SELECT `title`, `image` FROM `exotic_packages` WHERE `id`='" . ($fetch_package['packageID'] + 0) . "' LIMIT 1");
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
				mysql_query("UPDATE `exotic_packages_owned` SET `account`='" . protectSQL($fetch_recipient['account']) . "' WHERE `id`='" . ($fetch_package['id'] + 0) . "' LIMIT 1");
				
				// Send Message
				file_get_contents(UNIFACTION . "API_notifyCommon.php?account=" . urlencode($fetch_recipient['account']) . "&siteName=" . urlencode($siteName) . "&title=" . urlencode("You have been gifted an Exotic Package!") ."&message=" . urlencode("You have just received a " . $fetch_type['title'] . " from " . (isset($_POST['anonymous']) == true ? "an anonymous gifter!" : $fetch_account['account'] . "!") . " You can review your new package on your <a href='" . AVATAR . "exotic_packages.php'>Exotic Packages</a> page.") . "&h=" . hash("sha256", "notify_" . $siteName . $siteKey . $fetch_recipient['account']));
				
				// add to history
				mysql_query("INSERT INTO `records_gifting` (`from_user`, `to_user`, `package_id`, `package_type`, `anonymous`, `timestamp`) VALUES ('" . protectSQL($fetch_account['account']) . "', '" . protectSQL($fetch_recipient['account']) . "', '" . ($fetch_package['id'] + 0) . "', '" . ($fetch_package['packageID'] + 0) . "', '" . (isset($_POST['anonymous']) == true ? "1" : "0") . "', " . time() . ")");
				
				$messages[] = "<div class='message-success'>You have successfully gifted " . $fetch_type['title'] . " to " . $fetch_recipient['account'] . ".</div>";
				
				$fetch_type['title'] = "";
			}
			else
				$messages[] = "<div class='message-error'>You cannot gift a package to yourself.</div>";
		}
		else
			$messages[] = "<div class='message-error'>" . $_POST['recipient'] . " does not currently use the avatar system.</div>";
	}*/

	$pagetitle = "Send Package as a Gift";
	$messages[] = "<div class='message-error'>Package trading and gifting needed to be disabled. We are very sorry for any inconvenience.</div>";
	require("incAVA/header.php");
?>
			<div class='category-container'>
				<div class='details-header'>
					Send Package as a Gift
				</div>
				<div class='details-body'>
<?php
	// Display Page
	/*if ($fetch_type['title'] != "")
	{	echo "
					<form method='post'>
						<table>
							<tr>
								<td style='width:205px;'>
									<img src='images/exotic_packages/" . $fetch_type['image'] . "'/>
								</td>
								<td style='text-align:left; vertical-align:top;'>
									Who would you like to send the " . $fetch_type['title'] . " to?<br/>
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
					Thank you for visiting the gift page!<br/><a href='exotic_packages.php'>Return To Exotic Packages</a> | <a href='gift_log.php'>View Gift Log</a>";
	}*/
?>

				</div>
			</div>
<?php
	require("incAVA/footer.php");
?>