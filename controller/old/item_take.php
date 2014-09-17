<?php
	require("config.php");
	require("./incAVA/dbAVAconnect.php");
	require("./incAVA/global.php");
	
	$permissible_items = array('3382', '3384', '3383', '3385');
	
	// check validity
	$message = "";
	if (isset($_GET['item']) && isset($_GET['check']) && in_array($_GET['item'], $permissible_items) && $_GET['check'] == strrev(substr(sha1("needakey" . $_GET['item']), 22, 10)))
	{
		$query = "SELECT `timestamp` FROM `giving_log` WHERE `account`='" . protectSQL($fetch_account['account']) . "' AND `item_id`='" . ($_GET['item'] + 0) . "' LIMIT 1";
		$res = mysql_query($query);
		if (!$fetch = mysql_fetch_assoc($res))
		{
			$query = "SELECT `clothingID`, `clothing`, `position`, `used_by` FROM `clothing_images` WHERE `clothingID`='" . protectSQL($_GET['item']) . "' LIMIT 1";
			$res = mysql_query($query);
			if ($piece = mysql_fetch_assoc($res))
			{
				$query = "INSERT INTO `giving_log` (`account`, `item_id`, `timestamp`, `ip`) VALUES ('" . protectSQL($fetch_account['account']) . "', '" . ($_GET['item'] + 0) . "', '" . time() . "', '" . protectSQL($_SERVER['REMOTE_ADDR']) . "')";
				if (mysql_query($query))
				{
					$query = "INSERT INTO `avatar_clothing` (`account`, `clothingID`, `position`) VALUES ('" . protectSQL($fetch_account['account']) . "', '" . ($piece['clothingID'] + 0) . "', '" . protectSQL($piece['position']) . "')";
					if (mysql_query($query))
					{
						$message = "You have taken " . $piece['clothing'] . ". Go <a href='dress_avatar.php?position=" . $piece['position'] . "#body-wrap'>here</a> to view it. This item can be used by " . ($piece['used_by'] != "both" ? $piece['used_by'] . " avatars." : "both genders.");
					}
					else
						$message = "An error has occurred. The item could not be given to you.";
				}
				else
					$message = "An error has occurred. The item could not be given to you.";
			}
			else
				$message = "This item does not exist.";
		}
		else
			$message = "You have already taken this item.";
	}
	elseif (isset($_GET['item']) && isset($_GET['check']))
		$message = "Something seems to be wrong with your link, or this item is not available.";
	
	require("./incAVA/header.php");
	echo "
	<div class='category-container'>
		<div class='details-header'>
			<p>Item Giving</p>
		</div>
		<div class='details-body'>";
	if ($message != "")
		echo "
			<p>" . $message . "</p>";
	if ($fetch_account['clearance'] > 5)
	{
		foreach ($permissible_items as $item)
		{
			$query = "SELECT `clothing` FROM `clothing_images` WHERE `clothingID`='" . protectSQL($item) . "' LIMIT 1";
			$res = mysql_query($query);
			if ($piece = mysql_fetch_assoc($res))
				echo "
			<p><b>" . $piece['clothing'] . ":</b> http://avatar.unifaction.com/item_take.php?item=" . $item . "&check=" . strrev(substr(sha1("needakey" . $item), 22, 10)) . "</p>";
		}
	}
			
	echo "
		</div>
	</div>";
	require("./incAVA/footer.php");
?>