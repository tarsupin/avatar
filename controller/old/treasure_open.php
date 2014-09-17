<?php
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");

	if ($fetch_account['clearance'] < 6)
	{
		header("Location: index.php");
		exit;
	}
	
	require("fanctions/check_and_draw.php");
	
	// $_GET['replacement']							replacement item ID
	// $testshop[$_GET['replacement']][0]			new name
	// $testshop[$_GET['replacement']][1]			new gender
	// $testshop[$_GET['replacement']][2]			new layer
	// $_GET['color']								new color

	// $_GET['id']									old item ID
	// $items[$_GET['id']][0]						old name
	// $items[$_GET['id']][1]						old gender
	// $items[$_GET['id']][2]						old layer
	// $oldcolor									old color

	// get list of treasure pieces
	$items = array();
	$result = mysql_query("SELECT `year_day`, `items` FROM `treasure_search_staff`");
	while ($piece = mysql_fetch_assoc($result))
	{
		$whatisit = explode(",", $piece['items']);
		foreach ($whatisit as $whatis)
		{
			if (!isset($items[$whatis]))
			{
				$q_what = mysql_query("SELECT `clothingID`, `clothing`, `position`, `used_by` FROM `clothing_images` WHERE `clothingID`='" . ($whatis + 0) . "' LIMIT 1");
				if ($what = mysql_fetch_assoc($q_what))
				{
					$files = scandir("avatars/" . $what['position'] . "/" . $what['clothing']);
					$count_f = 0;
					$count_m = 0;
					foreach ($files as $file)
					{
						if (strpos($file, "_female.png") > -1)
						{
							$count_f++;
							$color = explode("_", $file);
							$color = $color[0];
						}
						elseif (strpos($file, "_male.png") > -1)
						{
							$count_m++;
							$color = explode("_", $file);
							$color = $color[0];
						}							
						if ($count_f > 1 || $count_m > 1)
							break;
					}
					if ($count_f <= 1 && $count_m <= 1)
						$items[$what['clothingID']] = array($what['clothing'], $what['used_by'], $what['position'], $color);
				}
			}
		}
	}

	// get list of available replacements
	$testshop = array();
	$result = mysql_query("SELECT `clothingID`, `clothing`, `position`, `used_by` FROM `clothing_images` WHERE `shopID`='91'");
	while ($test = mysql_fetch_assoc($result))
	{
		if (!isset($items[$test['clothingID']]))
			$testshop[$test['clothingID']] = array($test['clothing'], $test['used_by'], $test['position']);
	}
	asort($items);
	asort($testshop);

	// check
	if (isset($_GET['id']) && isset($_GET['replacement']) && isset($_GET['color']))
	{
		$_GET['id'] += 0;
		$_GET['replacement'] += 0;
		$_GET['color'] = trim($_GET['color']);
		if (isset($items[$_GET['id']]) && isset($testshop[$_GET['replacement']]))
		{
			if ($items[$_GET['id']][1] == $testshop[$_GET['replacement']][1])
			{
				if ($_GET['id'] != $_GET['replacement'])
				{
					if ($items[$_GET['id']][1] == "both" || $items[$_GET['id']][1] == "female")
						$target = "avatars/" . $testshop[$_GET['replacement']][2] . "/" . $testshop[$_GET['replacement']][0] . "/" . $_GET['color'] . "_female.png";
					else
						$target = "avatars/" . $testshop[$_GET['replacement']][2] . "/" . $testshop[$_GET['replacement']][0] . "/" . $_GET['color'] . "_male.png";
					if (file_exists($target))
					{
						$oldcolor = $items[$_GET['id']][3];

						// action!
						if (isset($_GET['start']))
						{
							// layer change
							if ($testshop[$_GET['replacement']][2] != $items[$_GET['id']][2])
							{
								// edit layer in avatar_clothing
								mysql_query("UPDATE `avatar_clothing` SET `position`='" . protectSQL($testshop[$_GET['replacement']][2]) . "' WHERE `clothingID`='" . protectSQL($_GET['id']) . "'");
								$messages[] = "<div class='message-success'>Layer in user inventories has been changed.</div>";
								
								// name and layer in clothing_images
								mysql_query("UPDATE `clothing_images` SET `clothing`='" . protectSQL($testshop[$_GET['replacement']][0]) . "', `position`='" . protectSQL($testshop[$_GET['replacement']][2]) . "' WHERE `clothingID`='" . protectSQL($_GET['id']) . "' LIMIT 1");
								renew_item_details($_GET['id']);
								$messages[] = "<div class='message-success'>Name and layer in the shops have been changed.</div>";
							}
							// no layer change
							else
							{								
								// name in clothing_images
								mysql_query("UPDATE `clothing_images` SET `clothing`='" . protectSQL($testshop[$_GET['replacement']][0]) . "' WHERE `clothingID`='" . protectSQL($_GET['id']) . "' LIMIT 1");
								renew_item_details($_GET['id']);
								$messages[] = "<div class='message-success'>Name in the shops has been changed.</div>";
							}
							
							// remove source item
							mysql_query("UPDATE `avatar_clothing` SET `clothingID`='" . protectSQL($_GET['id']) . "' WHERE `clothingID`='" . protectSQL($_GET['replacement']) . "'");
							$messages[] = "<div class='message-success'>Users owning the source item have been given the new item instead.</div>";
							mysql_query("DELETE FROM `clothing_images` WHERE `clothingID`='" . protectSQL($_GET['replacement']) . "' LIMIT 1");
							$messages[] = "<div class='message-success'>Source item has been removed from the database to avoid duplicates.</div>";
						}
						else
						{
							if ($items[$_GET['id']][1] == "both" || $items[$_GET['id']][1] == "female")
								$messages[] = "<div class='message-neutral'>Are you sure you want to replace <img src='avatars/" . $items[$_GET['id']][2] . "/" . $items[$_GET['id']][0] . "/" . $oldcolor . "_female.png'/> with <img src='avatars/" . $testshop[$_GET['replacement']][2] . "/" . $testshop[$_GET['replacement']][0] . "/" . $_GET['color'] . "_female.png'/>?<br/><br/><a href='treasure_open.php?id=" . $_GET['id'] . "&replacement=" . $_GET['replacement'] . "&color=" . $_GET['color'] . "&start'>Yes!</a> | <a href='treasure_open.php'>No!</a></div>";
							else
								$messages[] = "<div class='message-neutral'>Are you sure you want to replace <img src='avatars/" . $items[$_GET['id']][2] . "/" . $items[$_GET['id']][0] . "/" . $oldcolor . "_male.png'/> with <img src='avatars/" . $testshop[$_GET['replacement']][2] . "/" . $testshop[$_GET['replacement']][0] . "/" . $_GET['color'] . "_male.png'/>?<br/><br/><a href='treasure_open.php?id=" . $_GET['id'] . "&replacement=" . $_GET['replacement'] . "&color=" . $_GET['color'] . "&start'>Yes!</a> | <a href='treasure_open.php'>No!</a></div>";
						}
					}
					else
						$messages[] = "<div class='message-error'>The replacement does not exist in '" . $_GET['color'] . "'.</div>";
				}
				else
					$messages[] = "<div class='message-error'>The items must be different.</div>";
			}
			else
				$messages[] = "<div class='message-error'>The gender of both items must be the same.</div>";
		}
		else
			$messages[] = "<div class='message-error'>One or both of these items are not available for this action.</div>";
	}

	$pagetitle = "[staff] Reveal Treasure";
	require("incAVA/header.php");
?>
			<div class='category-container'>
				<div class='details-header'>
					Reveal Treasure
				</div>
				<div class='details-body'>
<?php
	foreach ($items as $key => $val)
	{
		echo "
					<form method='get'>
						<input type='hidden' name='id' value='" . $key . "'/>
						Replace
						<input type='text' value='" . $val[0] . "' size='30' disabled='disabled'/>
						in
						<input type='text' value='" . $val[3] . "' size='22' disabled='disabled'/>
						with
						<select name='replacement' style='width:150px;'>
							<option value=''></option>";
		foreach ($testshop as $keyt => $valt)
		{
			if ($valt[1] == $val[1] && $keyt != $key)
				echo "
							<option value='" . $keyt . "'>" . $valt[0] . "</option>";
		}
					
		echo "
						</select>
						in color
						<input type='text' name='color' maxlength='22' size='22'/>
						<input type='submit' name='submit' value='!'/>
					</form>";
	}
?>

				</div>
			</div>
<?php
	require("incAVA/footer.php");
?>