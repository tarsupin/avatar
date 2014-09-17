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
	if (!isset($_GET['itemID']))
	{
		header("Location: staff.php");
		exit;
	}
	
	require("fanctions/check_and_draw.php");
	
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
	if (!isset($_GET['recolor']))
		$_GET['recolor'] = "";

	$coord = array(0, 0, 0, 0);
	$refreshes = array();

	// Check Submission
	if (isset($_GET['submit']))
	{
		// Prepare Variable
		$_GET['item'] = trim($_GET['item']);
		$chklastname = $_GET['item']; // By default, item is normally the last name. May change in next segment.
		
		// Check if Item Name was renamed or moved (and if so, alter it in ftp)
		if (!file_exists("avatars/" . $_GET['position'] . "/" . $_GET['item'] . "/"))
		{
			$rescheck = mysql_query("SELECT `clothing`, `position` FROM `clothing_images` WHERE `clothingID`='" . ($_GET['itemID'] + 0) . "' LIMIT 1");
			if ($chkres = mysql_fetch_assoc($rescheck))
			{
				if ($chkres['clothing'] != $_GET['item'] || $chkres['position'] != $_GET['position'])
				{
					if (file_exists("avatars/" . $chkres['position'] . "/" . $chkres['clothing'] . "/"))
					{
						rename("avatars/" . $chkres['position'] . "/" . $chkres['clothing'] . "/", "avatars/" . $_GET['position'] . "/" . $_GET['item'] . "/");
						$chklastname = $chkres['clothing'];
					}
					mysql_query("UPDATE `clothing_images` SET `clothing`='" . protectSQL($_GET['item']) . "', `position`='" . protectSQL($_GET['position']) . "' WHERE `clothingID`=" . ($_GET['itemID'] + 0) . " LIMIT 1");
					renew_item_details($_GET['itemID']);
					if (isset($_GET['refresh']) && $_GET['shopID'] != "")
					{
						touch("content/shop_" . ($_GET['shopID'] + 0) . "_female_2.html", time()-86401);
						touch("content/shop_" . ($_GET['shopID'] + 0) . "_male_2.html", time()-86401);
					}
				}
				if ($chkres['position'] != $_GET['position'])
					mysql_query("UPDATE `avatar_clothing` SET `position`='" . protectSQL($_GET['position']) . "' WHERE `clothingID`=" . ($_GET['itemID'] + 0));
			}
		}
		
		// Update Item (as present in FTP); note the use of $_GET['item'] here instead of $chklastname
		if (file_exists("avatars/" . $_GET['position'] . "/" . $_GET['item'] . "/"))
		{
			// Check for Existing Data
			$result = mysql_query("SELECT `clothingID`, `clothing`, `position` FROM `clothing_images` WHERE `clothing`='" . protectSQL($chklastname) . "' AND `position`='" . protectSQL($_GET['position']) . "' LIMIT 1");
			if ($fetch_exist = mysql_fetch_assoc($result))
			{
				// Update Data
				mysql_query("UPDATE `clothing_images` SET `clothing`='" . protectSQL($_GET['item']) . "', `position`='" . protectSQL($_GET['position']) . "', `used_by`='" . protectSQL($_GET['used_by']) . "', `exoticPackage`='" . ($_GET['exoticPackage'] + 0) . "', `shopID`='" . protectSQL($_GET['shopID']) . "', `cost`='" . ($_GET['cost'] + 0) . "', `cost_credits`='" . ($_GET['cost_credits'] + 0) . "', `purchase_yes`='" . protectSQL($_GET['purchase_yes']) . "', `rel_to_base`='" . protectSQL($_GET['rel_to_base']) . "' WHERE `clothingID`='" . ($_GET['itemID'] + 0) . "' LIMIT 1");
				renew_item_details($_GET['itemID']);
				if (isset($_GET['refresh']) && $_GET['shopID'] != "")
				{
					touch("content/shop_" . ($_GET['shopID'] + 0) . "_female_2.html", time()-86401);
					touch("content/shop_" . ($_GET['shopID'] + 0) . "_male_2.html", time()-86401);
				}
			}
		}
		else
			$messages[] = "<div class='message-error'>avatars/" . $_GET['position'] . "/" . $_GET['item'] . "/ does not exist in FTP!</div>";
	}

	// Get Clothing Information
	$result = mysql_query("SELECT `clothingID`, `clothing`, `position`, `used_by`, `exoticPackage`, `shopID`, `cost`, `cost_credits`, `purchase_yes`, `rel_to_base` FROM `clothing_images` WHERE `clothingID`='" . ($_GET['itemID'] + 0) . "' LIMIT 1");
	if ($fetch_i = mysql_fetch_assoc($result))
	{
		$_GET['position'] = $fetch_i['position'];
		$_GET['item'] = $fetch_i['clothing'];
		$_GET['used_by'] = $fetch_i['used_by'];
		$_GET['shopID'] = $fetch_i['shopID'];
		$_GET['cost'] = $fetch_i['cost'];
		$_GET['cost_credits'] = $fetch_i['cost_credits'];
		$_GET['exoticPackage'] = $fetch_i['exoticPackage'];
		$_GET['purchase_yes'] = $fetch_i['purchase_yes'];
		$_GET['rel_to_base'] = $fetch_i['rel_to_base'];
	}
	else
	{
		header("Location: index.php");
		exit;
	}

	// Gather Existing Item 
	if (!isset($_GET['submit']))
	{
		// Update Coordinates
		if (isset($_GET['coord_male_x']) && isset($_GET['coord_male_y']) && isset($_GET['coord_female_x']) && isset($_GET['coord_female_y']))
		{
			file_put_contents("avatars/" . $_GET['position'] . "/" . $_GET['item'] . "/_stats.txt", $_GET['coord_male_x'] . " " . $_GET['coord_male_y'] . " " . $_GET['coord_female_x'] . " " . $_GET['coord_female_y']);
		}
	}

	// Gather Coordiantes for Item
	if (!file_exists("avatars/" . $fetch_i['position'] . "/" . $fetch_i['clothing'] . "/_stats.txt"))
	{
		file_put_contents("avatars/" . $fetch_i['position'] . "/" . $fetch_i['clothing'] . "/_stats.txt", $_GET['coord_male_x'] . " " . $_GET['coord_male_y'] . " " . $_GET['coord_female_x'] . " " . $_GET['coord_female_y']);
	}
	else
	{
		$coord = explode(" ", file_get_contents("avatars/" . $fetch_i['position'] . "/" . $fetch_i['clothing'] . "/_stats.txt"));
		if (count($coord) < 4)
		{
			$coord = array(0, 0, 0, 0);
			file_put_contents("avatars/" . $fetch_i['position'] . "/" . $fetch_i['clothing'] . "/_stats.txt", "0 0 0 0");
		}
	}

	$pagetitle = "[staff] Edit Item";
	require("incAVA/header.php");

	$layers = scandir("avatars");
	foreach ($layers as $key => $val)
		if (!in_array($val, array(".", "..", "base", "temp", ".cache")) && is_dir("avatars/" . $val))
			$layers[$key] = "<option value='" . $layers[$key] . "'>" . $layers[$key] . "</option>";
		else
			unset($layers[$key]);
	$layers = implode($layers);
	
	$position_text = "<select name='position'>" . str_replace("value='" . $_GET['position'] . "'", "value='" . $_GET['position'] . "' selected='selected'", "<option value=''>**SELECT**</option>" . $layers) . "</select>";

	// Function for Coord Links
	function drCoord($ins, $add = "")
	{
		$ins_disp = str_replace(array(" ", "0"), "", $ins);
		$ins_func = implode(", ", explode(" ", $ins));
		
		return "<a href='javascript: coordLink(" . $ins_func . ");'>" . $add . $ins_disp . "</a>";
	}
?>
			<div class='category-container'>
				<div class='details-header'>
					Edit Item
				</div>
				<div class='details-body'>
					<?php echo implode(", ", $refreshes); ?>
					
					<form method='get'>
						<table>
							<tr>
								<td style='vertical-align:top;'>
									<table class='alternate_without_th' style='text-align:left;'>
										<tr>
											<td colspan='2' style='text-align:center;'><a href='adminItemList.php'>Return to Item List</a></td>
										</tr>
										<tr>
											<td style='max-width:185px;'>Item ID:</td>
											<td style='width:185px;'><input type='text' name='itemID' value='<?php echo $_GET['itemID']; ?>' maxlength='8' size='8' readonly='readonly'/></td>
										</tr>
										<tr>
											<td>Layer:</td>
											<td><?php echo $position_text; ?></td>
										</tr>
										<tr>
											<td>Relative to Base:</td>
											<td>
												<select name='rel_to_base'>
													<option value='notset'<?php if ($_GET['rel_to_base'] == "notset") echo " selected='selected'"; ?>>NOT SET</option>
													<option value=''<?php if ($_GET['rel_to_base'] == "") echo " selected='selected'"; ?>>free</option>
													<option value='below'<?php if ($_GET['rel_to_base'] == "below") echo " selected='selected'"; ?>>below base</option>
													<option value='on'<?php if ($_GET['rel_to_base'] == "on") echo " selected='selected'"; ?>>on base (skin)</option>
													<option value='above'<?php if ($_GET['rel_to_base'] == "above") echo " selected='selected'"; ?>>above base</option>
												</select>
											</td>
										</tr>
										<tr>
											<td>Name:</td>
											<td><input type='text' name='item' value='<?php echo $_GET['item']; ?>' maxlength='30' size='30'/></td>
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
											<td>Edit Item:</td>
											<td>
												<input type='checkbox' name='refresh' checked='checked' title='refresh shop'/> 
												<input type='submit' name='submit' value='Update'/>
											</td>
										</tr>
										<tr>
											<td colspan='2'>&nbsp;</td>
										</tr>
										<tr>
											<td style='vertical-align:top; text-align:right;'>
<?php
	$files = scandir("avatars/" . $fetch_i['position'] . "/" . $fetch_i['clothing']);
	// Get Colors (male)
	foreach ($files as $file)
	{
		if (strpos($file, "_male.png") > -1)
			echo "
												<a href='adminEditItem.php?itemID=" . $_GET['itemID'] . "&recolor=" . str_replace("_male.png", "", $file) . "'>" . str_replace("_male.png", "", $file) . "</a><br/>";
	}
	echo "
											</td>
											<td style='vertical-align:top;'>";
	// Get Colors (female)
	foreach ($files as $file)
	{
		if (strpos($file, "_female.png") > -1)
			echo "
												<a href='adminEditItem.php?itemID=" . $_GET['itemID'] . "&recolor=" . str_replace("_female.png", "", $file) . "'>" . str_replace("_female.png", "", $file) . "</a><br/>";
	}
?>

											</td>
										</tr>
									</table>
								</td>
<?php
	if ($_GET['rel_to_base'] != "below")
	{
		echo "
								<td style='vertical-align:top;'>
									<img src='drawpreview.php?gender=male&position=" . $_GET['position'] . "&item=" . $_GET['item'] . "&id=" . $_GET['itemID'] . "&recolor=" . $_GET['recolor'] . "&base=tan&front=true'><br/>
									" . drCoord('-12 0 0 0') . " " . drCoord('-4 0 0 0') . " " . drCoord('-1 0 0 0') . " <input type='text' name='coord_male_x' value='" . $coord[0] . "' maxlength='3' size='3' id='maleXc' onChange=\"javascript:coordUP();\"/> " . drCoord('1 0 0 0', '+') . " " . drCoord('4 0 0 0', '+') . " " . drCoord('12 0 0 0', '+') . "<br/>
									" . drCoord('0 -12 0 0') . " " .drCoord('0 -4 0 0') . " " . drCoord('0 -1 0 0') . " <input type='text' name='coord_male_y' value='" . $coord[1] . "' maxlength='3' size='3' id='maleYc' onChange=\"javascript:coordUP();\"/> " . drCoord('0 1 0 0', '+') . " " . drCoord('0 4 0 0', '+'). " " . drCoord('0 12 0 0', '+') . "<br/>";
		if ($_GET['position'] != "background")
			echo "
									<img src='drawpreview.php?gender=male&position=" . $_GET['position'] . "&item=" . $_GET['item'] . "&id=" . $_GET['itemID'] . "&recolor=" . $_GET['recolor'] . "&front=true&bg'>";
		echo "
								</td>
								<td style='vertical-align:top;'>
									<img src='drawpreview.php?gender=female&position=" . $_GET['position'] . "&item=" . $_GET['item'] . "&id=" . $_GET['itemID'] . "&recolor=" . $_GET['recolor'] . "&base=tan&front=true'><br/>
									" . drCoord('0 0 -12 0') . " " . drCoord('0 0 -4 0') . " " . drCoord('0 0 -1 0') . " <input type='text' name='coord_female_x' value='" . $coord[2] . "' maxlength='3' size='3' id='femaleXc' onChange=\"javascript:coordUP();\"/> " . drCoord('0 0 1 0', '+') . " " . drCoord('0 0 4 0', '+') . " " . drCoord('0 0 12 0', '+') . "<br/>
									" . drCoord('0 0 0 -12') . " " . drCoord('0 0 0 -4') . " " . drCoord('0 0 0 -1') . " <input type='text' name='coord_female_y' value='" . $coord[3] . "' maxlength='3' size='3' id='femaleYc' onChange=\"javascript:coordUP();\"/> " . drCoord('0 0 0 1', '+') . " " . drCoord('0 0 0 4', '+') . " " . drCoord('0 0 0 12', '+') . "<br/>";
		if ($_GET['position'] != "background")
			echo "
									<img src='drawpreview.php?gender=female&position=" . $_GET['position'] . "&item=" . $_GET['item'] . "&id=" . $_GET['itemID'] . "&recolor=" . $_GET['recolor'] . "&front=true&bg'>";
		echo "
								</td>";
	}
	if ($_GET['rel_to_base'] != "above" && $_GET['rel_to_base'] != "on")
	{
		echo "
								
								<td style='vertical-align:top;'>
									<img src='drawpreview.php?gender=male&position=" . $_GET['position'] . "&item=" . $_GET['item'] . "&id=" . $_GET['itemID'] . "&recolor=" . $_GET['recolor'] . "&base=tan&front=false'><br/>
									" . drCoord('-12 0 0 0') . " " . drCoord('-4 0 0 0') . " " . drCoord('-1 0 0 0') . " <input type='text' name='coord_male_x' value='" . $coord[0] . "' maxlength='3' size='3' id='maleXc' onChange=\"javascript:coordUP();\"/> " . drCoord('1 0 0 0', '+') . " " . drCoord('4 0 0 0', '+') . " " . drCoord('12 0 0 0', '+') . "<br/>
									" . drCoord('0 -12 0 0') . " " .drCoord('0 -4 0 0') . " " . drCoord('0 -1 0 0') . " <input type='text' name='coord_male_y' value='" . $coord[1] . "' maxlength='3' size='3' id='maleYc' onChange=\"javascript:coordUP();\"/> " . drCoord('0 1 0 0', '+') . " " . drCoord('0 4 0 0', '+'). " " . drCoord('0 12 0 0', '+') . "<br/>";
		if ($_GET['position'] != "background")
			echo "
									<img src='drawpreview.php?gender=male&position=" . $_GET['position'] . "&item=" . $_GET['item'] . "&id=" . $_GET['itemID'] . "&recolor=" . $_GET['recolor'] . "&front=false&bg'>";
		echo "
								</td>
								<td style='vertical-align:top;'>
									<img src='drawpreview.php?gender=female&position=" . $_GET['position'] . "&item=" . $_GET['item'] . "&id=" . $_GET['itemID'] . "&recolor=" . $_GET['recolor'] . "&base=tan&front=false'><br/>
									" . drCoord('0 0 -12 0') . " " . drCoord('0 0 -4 0') . " " . drCoord('0 0 -1 0') . " <input type='text' name='coord_female_x' value='" . $coord[2] . "' maxlength='3' size='3' id='femaleXc' onChange=\"javascript:coordUP();\"/> " . drCoord('0 0 1 0', '+') . " " . drCoord('0 0 4 0', '+') . " " . drCoord('0 0 12 0', '+') . "<br/>
									" . drCoord('0 0 0 -12') . " " . drCoord('0 0 0 -4') . " " . drCoord('0 0 0 -1') . " <input type='text' name='coord_female_y' value='" . $coord[3] . "' maxlength='3' size='3' id='femaleYc' onChange=\"javascript:coordUP();\"/> " . drCoord('0 0 0 1', '+') . " " . drCoord('0 0 0 4', '+') . " " . drCoord('0 0 0 12', '+') . "<br/>";
		if ($_GET['position'] != "background")
			echo "
									<img src='drawpreview.php?gender=female&position=" . $_GET['position'] . "&item=" . $_GET['item'] . "&id=" . $_GET['itemID'] . "&recolor=" . $_GET['recolor'] . "&front=false&bg'>";
		echo "
								</td>";
	}
?>

							</tr>
						</table>
						<input type='hidden' name='recolor' value='<?php echo $_GET['recolor']; ?>'/>
					</form>
				</div>
			</div>
			<script type='text/javascript'>
				function coordLink(mX, mY, fX, fY)
				{
					var maleX = <?php echo $coord[0]; ?>;
					var maleY = <?php echo $coord[1]; ?>;
					var femaleX = <?php echo $coord[2]; ?>;
					var femaleY = <?php echo $coord[3]; ?>;
					
					window.location = "adminEditItem.php?itemID=<?php echo $_GET['itemID']; ?>&recolor=<?php echo $_GET['recolor']; ?>&coord_male_x="+(maleX+mX)+"&coord_male_y="+(maleY+mY)+"&coord_female_x="+(femaleX+fX)+"&coord_female_y="+(femaleY+fY);
				}

				function coordUP()
				{
					var maleX = $("#maleXc").val();
					var maleY = $("#maleYc").val();
					var femaleX = $("#femaleXc").val();
					var femaleY = $("#femaleYc").val();
					
					window.location = "adminEditItem.php?itemID=<?php echo $_GET['itemID']; ?>&recolor=<?php echo $_GET['recolor']; ?>&coord_male_x="+(maleX)+"&coord_male_y="+(maleY)+"&coord_female_x="+(femaleX)+"&coord_female_y="+(femaleY);
				}
			</script>
<?php
	require("incAVA/footer.php");
?>