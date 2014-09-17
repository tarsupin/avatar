<?php
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");

	// Prevent Access
	if ($fetch_account['clearance'] < 6)
	{
		header("Location: index.php");
		exit;
	}

	// Prepare Variables
	if (!isset($_GET['item']))
		$_GET['item'] = "";
	if (!isset($_GET['position']))
		$_GET['position'] = "";
	if (!isset($_GET['cost']))
		$_GET['cost'] = "";
	if (!isset($_GET['cost_credits']))
		$_GET['cost_credits'] = "";
	if (!isset($_GET['shopID']))
		$_GET['shopID'] = "91"; // test shop
	if (!isset($_GET['used_by']))
		$_GET['used_by'] = "";
	if (!isset($_GET['exoticPackage']))
		$_GET['exoticPackage'] = "";
	if (!isset($_GET['purchase_yes']))
		$_GET['purchase_yes'] = "";
	if (!isset($_GET['rel_to_base']))
		$_GET['rel_to_base'] = "";

	// Check Submission
	if (isset($_GET['submit']))
	{
		// Update Item (as present in FTP)
		if (file_exists("avatars/" . $_GET['position'] . "/" . $_GET['item'] . "/"))
		{
			// Create Data
			mysql_query("INSERT INTO `clothing_images` (`clothing`, `position`, `used_by`, `exoticPackage`, `shopID`, `cost`, `cost_credits`, `purchase_yes`, `rel_to_base`) VALUES ('" . protectSQL($_GET['item']) . "', '" . protectSQL($_GET['position']) . "', '" . protectSQL($_GET['used_by']) . "', '" . ($_GET['exoticPackage'] + 0) . "', '" . protectSQL($_GET['shopID']) . "', '" . ($_GET['cost'] + 0) . "', '" . ($_GET['cost_credits'] + 0) . "', '" . protectSQL($_GET['purchase_yes']) . "', '" . protectSQL($_GET['rel_to_base']) . "')");
			
			if (!file_exists("avatars/" . $_GET['position'] . "/" . $_GET['item'] . "/_stats.txt"))
			{
				file_put_contents("avatars/" . $_GET['position'] . "/" . $_GET['item'] . "/_stats.txt", ($_GET['coord_male_x'] + 0) . " " . ($_GET['coord_male_y'] + 0) . " " . ($_GET['coord_female_x'] + 0) . ' ' . ($_GET['coord_female_y'] + 0));
			}
			
			if (isset($_GET['refresh']) && $_GET['shopID'] != "")
			{
				touch("content/shop_" . ($_GET['shopID'] + 0) . "_female.html", time()-86401);
				touch("content/shop_" . ($_GET['shopID'] + 0) . "_male.html", time()-86401);
			}
			
			$messages[] = "<div class='message-success'>You can edit the new item <a href='adminEditItem.php?itemID=" . mysql_insert_id() . "'>here</a>.</div>";
		}
		else
			$messages[] = "<div class='message-error'>avatars/" . $_GET['position'] . "/" . $_GET['item'] . "/ does not exist in FTP!</div>";
	}

	$pagetitle = "[staff] Create Item";
	require("incAVA/header.php");

	$layers = scandir("avatars");
	foreach ($layers as $key => $val)
		if (!in_array($val, array(".", "..", "base", "temp", ".cache")) && is_dir("avatars/" . $val))
			$layers[$key] = "<option value='" . $layers[$key] . "'>" . $layers[$key] . "</option>";
		else
			unset($layers[$key]);
	$layers = implode($layers);

	$position_text = "<select name='position'>" . str_replace("value='" . $_GET['position'] . "'", "value='" . $_GET['position'] . "' selected='selected'", "<option value=''>**SELECT**</option>" . $layers) . "</select>";
?>
			<div class='category-container'>
				<div class='details-header'>
					Create Item
				</div>
				<div class='details-body'>
					<form method='get'>
						<table class='alternate_without_th' style='text-align:left;'>
							<tr>
								<td>Renew:</td>
								<td><a href='adminCreateItem.php?position=<?php echo $_GET['position']; ?>'>New Item</a></td>
							</tr>
							<tr>
								<td>Layer:</td>
								<td><?php echo $position_text; ?></td>
							</tr>
							<tr>
								<td>Relative to Base:</td>
								<td>
									<select name='rel_to_base'>
										<option value=''<?php if ($_GET['rel_to_base'] == "") echo " selected='selected'"; ?>>free</option>
										<option value='below'<?php if ($_GET['rel_to_base'] == "below") echo " selected='selected'"; ?>>below base</option>
										<option value='on'<?php if ($_GET['rel_to_base'] == "on") echo " selected='selected'"; ?>>on base (skin)</option>
										<option value='above'<?php if ($_GET['rel_to_base'] == "above") echo " selected='selected'"; ?>>above base</option>
									</select>
								</td>
							</tr>
							<tr>
								<td>Name:</td>
								<td><input type='text' name='item' value='<?php echo $_GET['item']; ?>' maxlength='30'/></td>
							</tr>
							<tr>
								<td>Used By:</td>
								<td>
									<select name='used_by'><?php echo str_replace("value='" . $_GET['used_by'] . "'", "value='" . $_GET['used_by'] . "' selected='selected'", "
										<option value='female'>female</option>
										<option value='male'>male</option>
										<option value='both'>both</option>"); ?>
										
									</select>
								</td>
							</tr>
							<tr>
								<td>Shop:</td>
								<td>
									<select name='shopID'>
<?php
	// Gather Shop List
	$resShop = mysql_query("SELECT `id`, `shopName` FROM `shop_listings` WHERE `clearance`<=" . ($fetch_account['clearance'] + 0) . " ORDER BY `clearance`, `shopName`");

	while ($fetch_shops = mysql_fetch_assoc($resShop))
	{
		echo "
										<option value='" . ($fetch_shops['id'] + 0) . "'";
		if ($_GET['shopID'] == $fetch_shops['id'] + 0)
			echo " selected='selected'";
		echo ">" . $fetch_shops['shopName'] . "</option>";
	}
?>

									</select>
								</td>
							</tr>
							<tr>
								<td>Auro Cost:</td>
								<td><input type='text' name='cost' value='<?php echo $_GET['cost']; ?>' maxlength='8'/></td>
							</tr>
							<tr>
								<td>Credit Cost:</td>
								<td><input type='text' name='cost_credits' value='<?php echo $_GET['cost_credits']; ?>' maxlength='5'/></td>
							</tr>
							<tr>
								<td>Exotic Package:</td>
								<td>
									<select name='exoticPackage'>
										<option value=''>** NONE **</option>
<?php
	// Gather Shop List
	$resEP = mysql_query("SELECT `id`, `title`, `year`, `month` FROM `exotic_packages` ORDER BY `year` DESC, `month` DESC");
	while ($fetch_EP = mysql_fetch_assoc($resEP))
	{
		echo "
										<option value='" . ($fetch_EP['id'] + 0) . "'";		
		if ($_GET['exoticPackage'] == $fetch_EP['id'] + 0)
			echo " selected='selected'";		
		if ($fetch_EP['title'] == "")
			$fetch_EP['title'] = "EP for " . $fetch_EP['year'] . "-" . $fetch_EP['month'];
		echo ">" . $fetch_EP['title'] . "</option>";
	}
?>

									</select>
								</td>
							</tr>
							<tr>
								<td>User Purchase:</td>
								<td>
									<select name='purchase_yes'><?php echo str_replace("value='" . $_GET['purchase_yes'] . "'", "value='" . $_GET['purchase_yes'] . "' selected='selected'", "
										<option value=''>IS ALLOWED</option>
										<option value='deny'>IS DENIED</option>"); ?>
										
									</select>
								</td>
							</tr>
							<tr>
								<td>Create Item:</td>
								<td>
									<input type='checkbox' name='refresh' checked='checked'/>
									<input type='submit' name='submit' value='Submit' maxlength='20'/>
								</td>
							</tr>
						</table>
					</form>
				</div>
			</div>
<?php
	require("incAVA/footer.php");
?>