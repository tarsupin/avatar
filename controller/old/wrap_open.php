<?php
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");
	
	if (isset($_POST['open']) && isset($_POST['hidden_id']))
	{
		$query = "SELECT `clothingID` FROM `avatar_clothing` WHERE `id`='" . ($_POST['hidden_id'] + 0) . "' AND `account`='" . protectSQL($fetch_account['account']) . "' LIMIT 1";
		$result = mysql_query($query);
		if ($fetch = mysql_fetch_assoc($result))
		{
			$query = "SELECT `id` FROM `wrap_items_log` WHERE `id`='" . ($_POST['hidden_id'] + 0) . "' LIMIT 1";
			$result = mysql_query($query);
			if (!$fetch_used = mysql_fetch_assoc($result))
			{
				$query = "SELECT `may_keep`, `content` FROM `wrap_items_staff` WHERE `item_id`='" . ($fetch['clothingID'] + 0) . "' LIMIT 1";
				$result = mysql_query($query);
				if ($fetchcontent = mysql_fetch_assoc($result))
				{
					$received = array();
					$items = explode(",", $fetchcontent['content']);
					foreach ($items as $item)
					{
						$query = "SELECT `clothingID`, `clothing`, `position` FROM `clothing_images` WHERE `clothingID`='" . ($item + 0) . "' LIMIT 1";
						$result = mysql_query($query);
						$fetchlayer = mysql_fetch_assoc($result);
						$query = "INSERT INTO `avatar_clothing` (`account`, `clothingID`, `position`) VALUES ('" . protectSQL($fetch_account['account']) . "', '" . ($fetchlayer['clothingID'] + 0) . "', '" . protectSQL($fetchlayer['position']) . "')";
						if (mysql_query($query))
						{
							$messages[] = "<div class='message-success'>You have received " . $fetchlayer['clothing'] . ".</div>";
							$received[] = $fetchlayer['clothingID'];
						}
					}
					$query = "INSERT INTO `wrap_items_log` (`id`, `account`, `item_id`, `timestamp`, `received`) VALUES ('" . ($_POST['hidden_id'] + 0) . "', '" . protectSQL($fetch_account['account']) . "', '" . ($fetch['clothingID'] + 0) . "', '" . time() . "', '" . protectSQL(implode(",", $received)) . "')";
					mysql_query($query);
					unset($received);
					
					if ($fetchcontent['may_keep'] == "no")
					{
						$query = "DELETE FROM `avatar_clothing` WHERE `id`='" . ($_POST['hidden_id'] + 0) . "' LIMIT 1";
						if (mysql_query($query))
							$messages[] = "<div class='message-neutral'>The wrapper has been removed from your equipment.</div>";
					}
				}
				else
					$messages[] = "<div class='message-error'>This is not a wrapper.</div>";
			}
			else
				$messages[] = "<div class='message-error'>This wrapper has already been opened.</div>";
		}
		else
			$messages[] = "<div class='message-error'>You do not own this wrapper.</div>";
	}

	$pagetitle = "Open Wrapped Set";
	require("incAVA/header.php");
?>
			<div class='category-container'>
				<div class='details-header'>
					Open Wrapped Set
				</div>
				<div class='details-body'>
<?php
	$has_content = false;
	$query = "SELECT `item_id`, `may_keep`, `content` FROM `wrap_items_staff`";
	$result = mysql_query($query);
	while ($fetch = mysql_fetch_assoc($result))
	{
		$query = "SELECT `avatar_clothing`.`id`, `clothing_images`.`clothing` FROM `avatar_clothing` INNER JOIN `clothing_images` ON `avatar_clothing`.`clothingID`=`clothing_images`.`clothingID` WHERE `avatar_clothing`.`account`='" . protectSQL($fetch_account['account']) . "' AND `avatar_clothing`.`clothingID`='" . ($fetch['item_id'] + 0) . "'";
		$res = mysql_query($query);
		while ($fetch2 = mysql_fetch_assoc($res))
		{
			if ($has_content)
				echo "<br/>";
			$has_content = true;
			$query = "SELECT `id` FROM `wrap_items_log` WHERE `id`='" . ($fetch2['id'] + 0) . "' LIMIT 1";
			$r = mysql_query($query);
			if (!$fetch_used = mysql_fetch_assoc($r))
			{
				echo "
					<form method='post'>
						<input type='hidden' name='hidden_id' value='" . $fetch2['id'] . "'/>
						<input type='submit' name='open' value='Open " . $fetch2['clothing'] . "'/> The wrapper will " . ($fetch['may_keep'] == "no" ? "<b>not</b> " : "") . "stay in your equipment after opening.<br/>
					</form>
					<ul>";
				$items = explode(",", $fetch['content']);
				foreach ($items as $item)
				{
					$q = mysql_query("SELECT `clothing`, `used_by` FROM `clothing_images` WHERE `clothingID`='" . ($item + 0) . "' LIMIT 1");
					if ($fetch3 = mysql_fetch_assoc($item))
					{
						echo "
						<li>" . $fetch3['clothing'] . " (" . ($fetch3['used_by'] == "both" ? "unisex" : $fetch3['used_by']) . ")</li>";
					}
				}
				echo "
					</ul>";
			}
			else
			{
				echo "
					<form method='post'>
						<input type='button' value='Open " . $fetch2['clothing'] . "' disabled='disabled'/> This wrapper has already been opened.
					</form>";
			}
		}
	}
	if (!$has_content)
	{
		echo "					You currently have no wrapped sets.";
	}
?>

				</div>
			</div>
<?php
	require("incAVA/footer.php");
?>