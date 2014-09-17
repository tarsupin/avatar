<?php
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");

	if ($fetch_account['clearance'] < 6)
	{
		header("Location: index.php");
		exit;
	}

	if (isset($_POST['add']) || isset($_POST['update']))
	{
		$_POST['items'] = explode(",", $_POST['items']);
		$_POST['items'] = array_unique($_POST['items']);
		$items = array();
		foreach ($_POST['items'] as $item)
		{
			$item = trim($item);
			$query = "SELECT `clothingID` FROM `clothing_images` WHERE `clothingID`='" . ($item + 0) . "' LIMIT 1";
			$result = mysql_query($query);
			if ($fetch = mysql_fetch_assoc($result))
				$items[] = $fetch['clothingID'];
			else
				$messages[] = "<div class='message-error'>Invalid Content ID: " . $item . "</div>";
		}
		$_POST['items'] = implode(",", $items);
		unset($items);
	}

	if (isset($_POST['add']) && $messages == array())
	{
		$_POST['wrapper_id'] = trim($_POST['wrapper_id']);
		$query = "SELECT `clothingID` FROM `clothing_images` WHERE `clothingID`='" . ($_POST['wrapper_id'] + 0) . "' LIMIT 1";
		$result = mysql_query($query);
		if (!$fetch = mysql_fetch_assoc($result))
			$messages[] = "<div class='message-error'>Invalid Wrapper ID specified.</div>";
		else
		{
			$query = "INSERT INTO `wrap_items_staff` (`item_id`, `may_keep`, `content`) VALUES ('" . ($_POST['wrapper_id'] + 0) . "', '" . protectSQL($_POST['keepwrapper']) . "', '" . protectSQL($_POST['items']) . "')";
			if (!mysql_query($query))
				$messages[] = "<div class='message-error'>This wrapper already has content assigned. Please edit it instead of trying to add.</div>";
			else
				$messages[] = "<div class='message-success'>Wrapper has been added.</div>";
		}
	}
	elseif (isset($_POST['update']) && $messages == array())
	{
		$query = "UPDATE `wrap_items_staff` SET `may_keep`='" . protectSQL($_POST['keepwrapper']) . "', `content`='" . protectSQL($_POST['items']) . "' WHERE `item_id`='" . ($_POST['wrapper_hidden_id'] + 0) . "' LIMIT 1";
		if (!mysql_query($query))
			$messages[] = "<div class='message-error'>Update failed.</div>";
		else
			$messages[] = "<div class='message-success'>Wrapper has been updated.</div>";
	}
	elseif (isset($_POST['delete']))
	{
		$query = "DELETE FROM `wrap_items_staff` WHERE `item_id`='" . ($_POST['wrapper_hidden_id'] + 0) . "' LIMIT 1";
		if (!mysql_query($query))
			$messages[] = "<div class='message-error'>Deletion failed.</div>";
		else
			$messages[] = "<div class='message-success'>Wrapper has been deleted.</div>";
	}

	$pagetitle = "[staff] Manage Wrapped Sets";
	require("incAVA/header.php");
?>
			<div class='category-container'>
				<div class='details-header'>
					Manage Wrapped Sets
				</div>
				<div class='details-body'>
<?php
	$query = "SELECT `item_id`, `may_keep`, `content` FROM `wrap_items_staff`";
	$result = mysql_query($query);
	while ($fetch = mysql_fetch_assoc($result))
	{
		$q = mysql_query("SELECT `clothing` FROM `clothing_images` WHERE `clothingID`='" . ($fetch['item_id'] + 0) . "' LIMIT 1");
		$fetch2 = mysql_fetch_assoc($q);
		echo "
					<div class='spoiler'>
						<div class='spoiler_header' onclick='$(this).next().slideToggle(\"slow\")'><b>" . $fetch2['clothing'] . ":</b> ";
		$items = explode(",", $fetch['content']);
		$i = 0;
		foreach ($items as $item)
		{
			$q = mysql_query("SELECT `clothing` FROM `clothing_images` WHERE `clothingID`='" . ($item + 0) . "' LIMIT 1");
			$fetch2 = mysql_fetch_assoc($q);
			echo $fetch2['clothing'];
			$i++;
			if ($i < count($items))
				echo ", ";
		}
		echo "</div>
						<div class='spoiler_content'>
							<form method='post'>
								<input type='hidden' name='wrapper_hidden_id' value='" . $fetch['item_id'] . "'/>
								<select name='keepwrapper'>
									<option value='yes'" . ($fetch['may_keep'] == "yes" ? " selected='selected'" : "") . ">yes</option>
									<option value='no'" . ($fetch['may_keep'] == "no" ? " selected='selected'" : "") . ">no</option>
								</select> keep wrapper in equipment after opening<br/>
								<input type='text' name='items' maxlength='255' size='100' value='" . $fetch['content'] . "'/><br/>
								<input type='submit' name='update' value='Update'/> <input type='submit' name='delete' onclick='return confirm(\"Are you sure you want to delete this wrapper?\");' value='Delete'/>
							</form>
						</div>
					</div>";
	}
?>

				</div>
			</div>
			<div class='category-container'>
				<div class='details-header'>
					New Wrapped Set
				</div>
				<div class='details-body'>
					<form method='post'>
						<input type='text' name='wrapper_id' size='6' maxlength='6' value='<?php if (isset($_POST['wrapper_id']) && isset($_POST['add'])) echo $_POST['wrapper_id']; ?>'/> item ID of the wrapper<br/>
						<select name='keepwrapper'>
							<option value='yes'>yes</option>
							<option value='no'>no</option>
						</select> keep wrapper in equipment after opening<br/>
						<input type='text' name='items' maxlength='255' size='100' value='<?php if (isset($_POST['items']) && isset($_POST['add'])) echo $_POST['items']; ?>'/> content item IDs separated by comma<br/>
						<input type='submit' name='add' value='Add'/>
					</form>
				</div>
			</div>
<?php
	require("incAVA/footer.php");
?>