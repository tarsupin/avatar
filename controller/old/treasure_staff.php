<?php
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");

	if ($fetch_account['clearance'] < 6)
	{
		header("Location: index.php");
		exit;
	}

	// add treasure piece
	if (isset($_POST['add']))
	{
		$_POST['items'] = explode(",", $_POST['items']);
		for ($i=0; $i<count($_POST['items']); $i++)
			$_POST['items'][$i] += 0;
		$_POST['items'] = array_filter($_POST['items'], function($t) {return $t>0;});
		$_POST['items'] = implode(",", $_POST['items']);
		
		$date = mktime(0, 0, 0, $_POST['month'], $_POST['day'], $_POST['year']);
		$today = mktime(0, 0, 0);
		if ($date > $today)
		{
			if (mysql_query("INSERT INTO `treasure_search_staff` (`year_day`, `items`) VALUES ('" . protectSQL(date("Y_z", $date)) . "', '" . protectSQL($_POST['items']) . "')"))
				$messages[] = "<div class='message-success'>You have added items for " . date("M j, Y", $date) . ".</div>";
			else
				$messages[] = "<div class='message-error'>You cannot use this form to add items to a day that already has items set.</div>";
		}
		else
			$messages[] = "<div class='message-error'>Sorry, you can only add items for future days.</div>";
	}

	// save changes
	if (isset($_POST['save']))
	{
		$date = date_create_from_format("Y_z", $_POST['year_day']);
		$date = mktime(0, 0, 0, $date->format("n"), $date->format("j"), $date->format("Y"));
		$today = mktime(0, 0, 0);
		if ($date > $today)
		{
			$_POST['items'] = explode(",", $_POST['items']);
			for ($i=0; $i<count($_POST['items']); $i++)
				$_POST['items'][$i] += 0;
			$_POST['items'] = array_filter($_POST['items'], function($t) {return $t>0;});
			$_POST['items'] = implode(",", $_POST['items']);
			
			mysql_query("UPDATE `treasure_search_staff` SET `items`='" . protectSQL($_POST['items']) . "' WHERE `year_day`='" . protectSQL($_POST['year_day']) . "' LIMIT 1");
			$date = date_create_from_format("Y_z", $_POST['year_day']);
			$messages[] = "<div class='message-success'>You have updated the items for " . $date->format("M j, Y")  . ".</div>";
		}
		else
			$messages[] = "<div class='message-error'>Sorry, you can only edit items for future days.</div>";
	}

	// remove event from a certain day
	if (isset($_POST['delete']))
	{
		if (mysql_query("DELETE FROM `treasure_search_staff` WHERE `year_day`='" . protectSQL($_POST['year_day']) . "' LIMIT 1"))
		{
			$date = date_create_from_format("Y_z", $_POST['year_day']);
			$messages[] = "<div class='message-success'>You have deleted the entry for " . $date->format("M j, Y")  . ".</div>";
		}
		else
			$messages[] = "<div class='message-error'>Entry for " . $date->format("M j, Y")  . " could not be deleted.</div>";
	}

	$messages[] = "<div class='message-neutral'>The code for this event is currently deactivated. See footer files in each subdomain to activate.</div>";

	$pagetitle = "[staff] Manage Treasure Hunt";
	require("incAVA/header.php");
?>
			<div class='category-container'>
				<div class='details-header'>
					Manage Treasure Hunt
				</div>
				<div class='details-body'>
<?php
	$result = mysql_query("SELECT `year_day`, `items` FROM `treasure_search_staff`");
	while ($piece = mysql_fetch_assoc($result))
	{
		$date = date_create_from_format("Y_z", $piece['year_day']);
		$date2 = mktime(0, 0, 0, $date->format("n"), $date->format("j"), $date->format("Y"));
		$today = mktime(0, 0, 0);
		if ($date2 > $today)
		{
			echo "
					<form method='post'>
						" . $date->format("M j, Y") . "
						<input type='text' name='items' maxlength='50' size='50' value='" . $piece['items'] . "'/>
						<input type='hidden' name='year_day' value='" . $piece['year_day'] . "'/>
						<input type='submit' name='save' value='Save'/>
						<input type='submit' name='delete' value='Delete' onclick='return confirm(\"Are you sure you want to delete this?\");'/>";
			$whatisit = explode(",", $piece['items']);
			$thatisit = array();
			foreach ($whatisit as $whatis)
			{
				$q = mysql_query("SELECT `clothing`, `used_by` FROM `clothing_images` WHERE `clothingID`='" . ($whatis + 0) . "' LIMIT 1");
				if ($what = mysql_fetch_assoc($q))
					$thatisit[] = $what['clothing'] . " [" . $what['used_by'] . "]";
			}
			echo " <span class='inline_spoiler_notify' onclick='if ($(this).next().is(\":visible\")){ $(this).next().fadeOut(\"fast\"); } else { $(this).next().fadeIn(\"fast\"); }'>Content</span><span class='inline_spoiler'> " . implode(", ", $thatisit) . "</span>";
			echo "
					</form>";
		}
		else
		{
			echo "
					<form method='post'>
						" . $date->format("M j, Y") . "
						<input type='text' name='items' maxlength='50' size='50' value='" . $piece['items'] . "' disabled='disabled'/>
						<input type='hidden' name='year_day' value='" . $piece['year_day'] . "'/>
						<input type='submit' name='save' value='Save' disabled='disabled'/>
						<input type='submit' name='delete' value='Delete' onclick='return confirm(\"Are you sure you want to delete this?\");'/>";
			$whatisit = explode(",", $piece['items']);
			$thatisit = array();
			foreach ($whatisit as $whatis)
			{
				$q = mysql_query("SELECT `clothing`, `used_by` FROM `clothing_images` WHERE `clothingID`='" . ($whatis + 0) . "' LIMIT 1");
				if ($what = mysql_fetch_assoc($q))
					$thatisit[] = $what['clothing'] . " [" . $what['used_by'] . "]";
			}
			echo " <span class='inline_spoiler_notify' onclick='if ($(this).next().is(\":visible\")){ $(this).next().fadeOut(\"fast\"); } else { $(this).next().fadeIn(\"fast\"); }'>Content</span><span class='inline_spoiler'> " . implode(", ", $thatisit) . "</span>";
			echo "
					</form>";
		}
	}
?>

				</div>
			</div>
			<div class='category-container'>
				<div class='details-header'>
					New Treasure
				</div>
				<div class='details-body'>
					<form method='post'>
						Tomorrow's date is pre-filled. You cannot add or edit items for the current day.<br/>
						<input type='text' name='year' value='<?php echo date("Y", time()+86400); ?>' maxlength='4' size='4'/> year (4 digits)<br/>
						<input type='text' name='month' value='<?php echo date("n", time()+86400); ?>' maxlength='2' size='4'/> month (1-12)<br/>
						<input type='text' name='day' value='<?php echo date("j", time()+86400); ?>' maxlength='2' size='4'/> day (1-31)<br/>
						<input type='text' name='items' maxlength='50' size='50'/> item IDs separated by comma<br/>
						<input type='submit' name='add' value='Add'/>
					</form>
				</div>
			</div>
<?php
	require("incAVA/footer.php");
?>