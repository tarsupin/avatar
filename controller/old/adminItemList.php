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

	// Prepare Variables
	if (!isset($_GET['position']))
		$_GET['position'] = "";
	if (!isset($_GET['name']))
		$_GET['name'] = "";
	if (!isset($_GET['used_by']))
		$_GET['used_by'] = "";
	if (!isset($_GET['shopID']))
		$_GET['shopID'] = "";
	if (!isset($_GET['whereAuro']))
		$_GET['whereAuro'] = "";
	if (!isset($_GET['whereCredits']))
		$_GET['whereCredits'] = "";
	if (!isset($_GET['auro']))
		$_GET['auro'] = "";
	if (!isset($_GET['credits']))
		$_GET['credits'] = "";
	if (!isset($_GET['exoticPackage']))
		$_GET['exoticPackage'] = "";
	if (!isset($_GET['purchase_yes']))
		$_GET['purchase_yes'] = "";
	if (!isset($_GET['rel_to_base']))
		$_GET['rel_to_base'] = "none";

	$getList = "?position=" . $_GET['position'] . "&rel=" . $_GET['rel_to_base'] . "&name=" . $_GET['name'] . "&used_by=" . $_GET['used_by'] . "&shopID=" . $_GET['shopID'] . "&whereAuro=" . $_GET['whereAuro'] . "&auro=" . $_GET['auro'] . "&whereCredits=" . $_GET['whereCredits'] . "&credits=" . $_GET['credits'] . "&exoticPackage=" . $_GET['exoticPackage'] . "&purchase_yes=" . $_GET['purchase_yes'];

	$resultList = "";

	// Delete Item
	if (isset($_GET['delete']))
	{
		mysql_query("DELETE FROM `clothing_images` WHERE `clothingID`='" . ($_GET['delete'] + 0) . "' LIMIT 1");
		delete_item_details($_GET['delete']);
		
		// Delete User's Items
		mysql_query("DELETE FROM `avatar_clothing` WHERE `clothingID`='" . ($_GET['delete'] + 0) . "'");
		$messages[] = "<div class='message-success'>You have deleted an item from the system.</div>";
	}

	// Search
	if (isset($_GET['submit']))
	{
		// Prepare Search
		$comma = "";
		$sqlwhere = "";
		$getList .= "&submit=" . $_GET['submit'];
		
		if ($_GET['position'] != "")
		{
			$sqlwhere .= $comma . "position='" . protectSQL($_GET['position']) . "'";
			$comma = " AND ";
		}
		
		if ($_GET['rel_to_base'] != "none")
		{
			$sqlwhere .= $comma . "rel_to_base='" . protectSQL($_GET['rel_to_base']) . "'";
			$comma = " AND ";
		}
		
		if ($_GET['name'] != "")
		{
			$sqlwhere .= $comma . "clothing LIKE '%" . protectSQL($_GET['name']) . "%'";
			$comma = " AND ";
		}
		
		if ($_GET['used_by'] != "")
		{
			$sqlwhere .= $comma . "used_by='" . protectSQL($_GET['used_by']) . "'";
			$comma = " AND ";
		}
		
		if ($_GET['shopID'] != "")
		{
			$sqlwhere .= $comma . "shopID='" . ($_GET['shopID'] + 0) . "'";
			$comma = " AND ";
		}
		
		if ($_GET['auro'] != "")
		{
			if ($_GET['whereAuro'] == "equal")
				$sqlwhere .= $comma . "cost=" . ($_GET['auro'] + 0);
			elseif ($_GET['whereAuro'] == "greater")
				$sqlwhere .= $comma . "cost >= " . ($_GET['auro'] + 0);
			else
				$sqlwhere .= $comma . "cost <= " . ($_GET['auro'] + 0);			
			$comma = " AND ";
		}
		
		if ($_GET['credits'] != "")
		{
			if ($_GET['whereCredits'] == "equal")
				$sqlwhere .= $comma . "cost_credits=" . ($_GET['credits'] + 0);
			elseif ($_GET['whereCredits'] == "greater")
				$sqlwhere .= $comma . "cost_credits >= " . ($_GET['credits'] + 0);
			else
				$sqlwhere .= $comma . "cost_credits <= " . ($_GET['credits'] + 0);			
			$comma = " AND ";
		}
		
		if ($_GET['exoticPackage'] != 0)
		{
			$sqlwhere .= $comma . "exoticPackage=" . ($_GET['exoticPackage'] + 0);
			$comma = " AND ";
		}
		
		if ($_GET['purchase_yes'] == "allow")
			$sqlwhere .= $comma . "purchase_yes=''";
		elseif ($_GET['purchase_yes'] == "deny")
			$sqlwhere .= $comma . "purchase_yes='deny'";
		
		if ($sqlwhere != "")
		{
			$query = "SELECT * FROM `clothing_images` WHERE " . $sqlwhere;
			$resultList = mysql_query($query);
		}
	}

	// Get shop list
	$shops = "";
	$q = mysql_query("SELECT `id`, `shopName` FROM `shop_listings` WHERE `clearance`<='" . ($fetch_account['clearance'] + 0) . "' ORDER BY `clearance`, `shopName`");
	while ($row = mysql_fetch_assoc($q))
		$shops .= "<option value='" . $row['id'] . "'>" . $row['shopName'] . "</option>";
		
	$pagetitle = "[staff] Review Item List";
	require("incAVA/header.php");
?>

			<div class='category-container'>
				<div class='details-header'>
					Review Item List
				</div>
				<div class='details-body'>
<?php
	$layers = scandir("avatars");
	foreach ($layers as $key => $val)
		if (!in_array($val, array(".", "..", "base", "temp", ".cache")) && is_dir("avatars/" . $val))
			$layers[$key] = "<option value='" . $layers[$key] . "'>" . $layers[$key] . "</option>";
		else
			unset($layers[$key]);
	$layers = implode($layers);
?>

					<form method='get'>
						<table class='alternate_with_th'>
							<tr>
								<th>ID</th>
								<th>Position</th>
								<th>Relative to Base</th>
								<th>Name</th>
								<th>Used By</th>
								<th>Shop</th>
								<th>Auro</th>
								<th>Credits</th>
								<th>EP</th>
								<th>Purchase</th>
								<th>&nbsp;</th>
							</tr>
							<tr>
								<td>&nbsp;</td>
								<td>
									<select name='position'><?php echo str_replace("value='" . $_GET['position'] . "'", "value='" . $_GET['position'] . "' selected='selected'", "<option value=''>**SELECT**</option>" . $layers); ?></select>
								</td>
								<td>
									<select name='rel_to_base'>
										<option value='none'>**SELECT**</option>
										<option value='notset'<?php if ($_GET['rel_to_base'] == "notset") echo " selected='selected'"; ?>>NOT SET</option>
										<option value=''<?php if ($_GET['rel_to_base'] == "") echo " selected='selected'"; ?>>free</option>
										<option value='below'<?php if ($_GET['rel_to_base'] == "below") echo " selected='selected'"; ?>>below base</option>
										<option value='on'<?php if ($_GET['rel_to_base'] == "on") echo " selected='selected'"; ?>>on base (skin)</option>
										<option value='above'<?php if ($_GET['rel_to_base'] == "above") echo " selected='selected'"; ?>>above base</option>
									</select>
								</td>
								<td>
									<input type='text' name='name' value='<?php echo $_GET['name']; ?>' size='16' maxlength='30'/>
								</td>
								<td>
									<select name='used_by'><?php echo str_replace("value='" . $_GET['used_by'] . "'", "value='" . $_GET['used_by'] . "' selected='selected'", "
										<option value=''>**SELECT**</option>
										<option value='both'>Both</option>
										<option value='male'>Male</option>
										<option value='female'>Female</option>"); ?>
										
									</select>
								</td>
								<td>
									<select name='shopID' style='width: 80px;'><?php echo str_replace("value='" . $_GET['shopID'] . "'", "value='" . $_GET['shopID'] . "' selected='selected'", "
										<option value=''>**SELECT**</option>
										" . $shops); ?>
										
									</select>
								</td>
								<td>
									<select name='whereAuro'><?php echo str_replace("value='" . $_GET['whereAuro'] . "'", "value='" . $_GET['whereAuro'] . "' selected='selected'", "
										<option value='less'>&lt;=</option>
										<option value='equal'>=</option>
										<option value='greater'>&gt;=</option>"); ?>
										
									</select>
									<input type='text' name='auro' value='<?php echo $_GET['auro']; ?>' size='8' maxlength='8'/>
								</td>
								<td>
									<select name='whereCredits'><?php echo str_replace("value='" . $_GET['whereCredits'] . "'", "value='" . $_GET['whereCredits'] . "' selected='selected'", "
										<option value='less'>&lt;=</option>
										<option value='equal'>=</option>
										<option value='greater'>&gt;=</option>"); ?>
										
									</select>
									<input type='text' name='credits' value='<?php echo $_GET['credits']; ?>' size='5' maxlength='5'/>
								</td>
								<td>
									<select name='exoticPackage' style='width: 80px;'>
										<option value='0'>**SELECT**</option>
<?php
	// Gather Exotic Packages
	$result = mysql_query("SELECT `id`, `title` FROM `exotic_packages` ORDER BY `year` DESC, `month` DESC");
	
	while ($fetch_ep = mysql_fetch_assoc($result))
	{
		echo "
										<option value='" . ($fetch_ep['id'] + 0) . "'" . ($fetch_ep['id'] == $_GET['exoticPackage'] ? " selected='selected'" : "") . ">" . $fetch_ep['title'] . "</option>";
	}
?>

									</select>
								</td>
								<td>
									<select name='purchase_yes'><?php echo str_replace("value='" . $_GET['purchase_yes'] . "'", "value='" . $_GET['purchase_yes'] . "' selected='selected'", "
										<option value=''>**SELECT**</option>
										<option value='allow'>Allowed</option>
										<option value='deny'>Denied</option>"); ?>
										
									</select>
								</td>
								<td><input type='submit' name='submit' value='Search'/></td>
							</tr>
<?php
	// List of Results
	if ($resultList != "")
	{
		// Prepare List (Exotic Packages)
		$epList = array(0 => "");
		$result = mysql_query("SELECT `id`, `title` FROM `exotic_packages`");
		
		while ($chkEP = mysql_fetch_assoc($result))
			$epList[$chkEP['id']] = $chkEP['title'];
			
		// Show List
		while ($fetch_list = mysql_fetch_assoc($resultList))
		{
			// Prepare Allowed / Denied
			if ($fetch_list['purchase_yes'] == "") { $purYes = "Allowed"; } else { $purYes = "Denied"; }

			// Display Row
			echo "
							<tr>
								<td>" . $fetch_list['clothingID'] . "</td>
								<td>" . $fetch_list['position'] . "</td>
								<td>" . ($fetch_list['rel_to_base'] == "notset" ? "NOT SET" : ($fetch_list['rel_to_base'] == "" ? "free" : $fetch_list['rel_to_base'])) . "</td>
								<td><a href='adminEditItem.php?itemID=" . $fetch_list['clothingID'] . "'>" . $fetch_list['clothing'] . "</a></td>
								<td>" . $fetch_list['used_by'] . "</td>
								<td>" . $fetch_list['shopID'] . "</td>
								<td>" . $fetch_list['cost'] . "</td>
								<td>" . $fetch_list['cost_credits'] . "</td>
								<td>" . $epList[$fetch_list['exoticPackage']] . "</td>
								<td>" . $purYes . "</td>
								<td align='center'><a href='adminItemList.php" . $getList . "&delete=" . $fetch_list['clothingID'] . "' onclick=\"return confirm('Do you really want to delete the " . $fetch_list['clothing'] . "?')\">&#10006;</a></td>
							</tr>";
		}
	}
?>

						</table>
					</form>
				</div>
			</div>
<?php
	require("incAVA/footer.php");
?>