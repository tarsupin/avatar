<?php
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");

	// Prepare Variables
	if (!isset($_GET['name']))
		$_GET['name'] = "";
	if (!isset($_GET['used_by']))
		$_GET['used_by'] = "";
	if (!isset($_GET['shopID']))
		$_GET['shopID'] = "";
	if (!isset($_GET['whereAuro']))
		$_GET['whereAuro'] = "";
	if (!isset($_GET['auro']))
		$_GET['auro'] = "";
	if (!isset($_GET['order_by']))
		$_GET['order_by'] = "";
	if (!isset($_GET['order_by_2']))
		$_GET['order_by_2'] = "";
	if (!isset($_GET['start']) || $_GET['start'] < 0)
		$_GET['start'] = 0;	
	if (!isset($_GET['purchasable']))
		$_GET['purchasable'] = "";
	if (!isset($_GET['owned']))
		$_GET['owned'] = "";
	if (!isset($_GET['cont']))
		$_GET['cont'] = 0;

	// Search
	if (isset($_GET['submit']))
	{
		// Prepare Search
		$comma = "";
		$sqlwhere = "";

		$c = "";
		$toadd = "";
		$numlayers = 0;
		foreach ($_GET as $key => $val)
		{
			if (substr($key, 0, 6) == "layer_")
			{
				$numlayers++;
				$toadd .= $c . "`position`='" . protectSQL(substr($key, 6)) . "'";
				$c = ' OR ';
			}
		}
		if ($numlayers > 0)
		{
			$sqlwhere .= $comma . "(" . $toadd . ")";
			$comma = " AND ";
		}
			
		if (strlen(trim($_GET['name'])) > 0)
		{
			$sqlwhere .= $comma . "`clothing` LIKE '%" . protectSQL(trim($_GET['name'])) . "%'";
			$comma = " AND ";
		}
		
		if ($_GET['used_by'] != "")
		{
			if ($_GET['used_by'] == 'both' || $_GET['used_by'] == 'female' || $_GET['used_by'] == 'male')
			{
				$sqlwhere .= $comma . "`used_by`='" . protectSQL($_GET['used_by']) . "'";
				$comma = " AND ";
			}
			if ($_GET['used_by'] == 'fab')
			{
				$sqlwhere .= $comma . "`used_by`!='male'";
				$comma = " AND ";
			}
			if ($_GET['used_by'] == 'mab')
			{
				$sqlwhere .= $comma . "`used_by`!='female'";
				$comma = " AND ";
			}
		}
		
		if ($_GET['shopID'] != "")
		{
			$sqlwhere .= $comma . "`shopID`='" . ($_GET['shopID'] + 0) . "'";
			$comma = " AND ";
		}
		
		if ($_GET['whereAuro'] != "" && $_GET['auro'] != "")
		{
			if ($_GET['whereAuro'] == "equal")
				$sqlwhere .= $comma . "`cost`=" . ($_GET['auro'] + 0);
			elseif ($_GET['whereAuro'] == "greater")
				$sqlwhere .= $comma . "`cost`>=" . ($_GET['auro'] + 0);
			else
				$sqlwhere .= $comma . "`cost`<=" . ($_GET['auro'] + 0);
			$comma = " AND ";
		}
		
		$sqlorder = "";
		if ($_GET['order_by'] != "")
		{
			if ($_GET['order_by'] == 'id')
				$sqlorder = " ORDER BY `clothing_images`.`clothingID`";
			if ($_GET['order_by'] == 'layer')
				$sqlorder = " ORDER BY `clothing_images`.`position`";
			elseif ($_GET['order_by'] == 'name')
				$sqlorder = " ORDER BY `clothing_images`.`clothing`";
			elseif ($_GET['order_by'] == 'gender')
				$sqlorder = " ORDER BY `clothing_images`.`used_by`";
			elseif ($_GET['order_by'] == 'shop')
				$sqlorder = " ORDER BY `shop_listings`.`shopName`";
			elseif ($_GET['order_by'] == 'cost')
				$sqlorder = " ORDER BY `clothing_images`.`cost`";
		}
		if ($sqlorder != "")
		{
			$sqlorder .= " " . ($_GET['order_by_2'] == 'asc' ? "ASC" : "DESC");
			if ($_GET['order_by'] != 'id' && $_GET['order_by'] != 'name')
				$sqlorder .= ", `clothing_images`.`clothing`";
		}
			
		$resultList = mysql_query("SELECT `clothing_images`.`clothing`, `clothing_images`.`position`, `clothing_images`.`used_by`, `clothing_images`.`exoticPackage`, `clothing_images`.`cost`, `clothing_images`.`cost_credits`, `clothing_images`.`purchase_yes`, `clothing_images`.`clothingID`, `clothing_images`.`shopID`, `shop_listings`.`shopName` FROM `clothing_images` INNER JOIN `shop_listings` ON `clothing_images`.`shopID` = `shop_listings`.`id`" . ($sqlwhere != "" ? " WHERE " . $sqlwhere : "") . " AND `shop_listings`.`clearance`<='" . ($fetch_account['clearance'] + 0) . "'" . $sqlorder . ($_GET['purchasable'] == "" && $_GET['owned'] == "" ? " LIMIT " . ($_GET['start']*100) . ", 100" : ""));
	}

	$pagetitle = "Shop Search";
	require("incAVA/header.php");
?>
			<div class='category-container'>
				<div class='details-header'>
					Shop Search
				</div>
				<div class='details-body'>
					<form method='get'>
						<table>
							<tr>
								<th>Name</th>
								<th>Gender</th>
								<th>Shop</th>
								<th>Cost</th>
								<th>Sort By</th>
								<th>Purchasable</th>
								<th>Owned</th>
								<th>&nbsp;</th>
							</tr>
<?php
	if (!file_exists("avatars/layers_2.txt") || filemtime("avatars/layers_2.txt") < time() - 86400)
	{
		$layers = scandir("avatars");
		foreach ($layers as $key => $val)
			if (in_array($val, array(".", "..", "base", "temp", ".cache")) || !is_dir("avatars/" . $val))
				unset($layers[$key]);
		$layers = array_values($layers);
		$tosave = "
						<table><tr style='text-align:left;'><td style='vertical-align:top;'>";
		for ($i=0; $i<count($layers); $i++)
		{
			$tosave .= "<input type='checkbox' name='layer_" . $layers[$i] . "'/> " . $layers[$i];
			if (($i+1) % ceil(count($layers) / 7) == 0)
				$tosave .= "</td><td style='vertical-align:top;'>";
			else
				$tosave .= "<br/>";
		}
		$tosave .= "</td></tr></table>";
		$layers = $tosave;
		file_put_contents("avatars/layers_2.txt", $layers);
	}
	else
		$layers = file_get_contents("avatars/layers_2.txt");

	// Get shop list
	$shops = "";
	$q = mysql_query("SELECT `id`, `shopName` FROM `shop_listings` WHERE `clearance`<='" . ($fetch_account['clearance'] + 0) . "' ORDER BY `clearance`, `shopName`");
	while ($row = mysql_fetch_assoc($q))
		$shops .= "<option value='" . $row['id'] . "'>" . $row['shopName'] . "</option>";
?>

							<tr>
								<td>
									<input type='text' name='name' value='<?php echo $_GET['name']; ?>' maxlength='30'/>
								</td>
								<td>
									<select name='used_by'><?php echo str_replace("value='" . $_GET['used_by'] . "'", "value='" . $_GET['used_by'] . "' selected='selected'", "
										<option value=''></option>
										<option value='both'>both</option>
										<option value='female'>female-only</option>
										<option value='male'>male-only</option>
										<option value='fab'>female or both</option>
										<option value='mab'>male or both</option>"); ?>
										
									</select>
								</td>
								<td>
									<select name='shopID'><?php echo str_replace("value='" . $_GET['shopID'] . "'", "value='" . $_GET['shopID'] . "' selected='selected'", "
										<option value=''></option>
										" . $shops); ?>
										
									</select>
								</td>
								<td>
									<select name='whereAuro'><?php echo str_replace("value='" . $_GET['whereAuro'] . "'", "value='" . $_GET['whereAuro'] . "' selected='selected'", "
										<option value=''></option>
										<option value='less'>&lt;=</option>
										<option value='equal'>==</option>
										<option value='greater'>&gt;=</option>"); ?>
										
									</select>
									<input type='text' name='auro' value='<?php echo $_GET['auro']; ?>' size='8' maxlength='8'/>
								</td>
								<td>
									<select name='order_by'><?php echo str_replace("value='" . $_GET['order_by'] . "'", "value='" . $_GET['order_by'] . "' selected='selected'", "
										<option value=''></option>
										<option value='name'>Name</option>
										<option value='gender'>Gender</option>
										<option value='shop'>Shop</option>
										<option value='cost'>Cost</option>
										<option value='layer'>Layer</option>
										<option value='id'>ID</option>"); ?>
										
									</select>
									<select name='order_by_2'><?php echo str_replace("value='" . $_GET['order_by_2'] . "'", "value='" . $_GET['order_by_2'] . "' selected='selected'", "
										<option value='asc'>asc</option>
										<option value='desc'>desc</option>"); ?>
										
									</select>
								</td>
								<td>
									<select name='purchasable'><?php echo str_replace("value='" . $_GET['purchasable'] . "'", "value='" . $_GET['purchasable'] . "' selected='selected'", "
										<option value=''></option>
										<option value='yes'>yes</option>
										<option value='no'>no</option>"); ?>
										
									</select>
								</td>
								<td>
									<select name='owned'><?php echo str_replace("value='" . $_GET['owned'] . "'", "value='" . $_GET['owned'] . "' selected='selected'", "
										<option value=''></option>
										<option value='yes'>yes</option>
										<option value='no'>no</option>"); ?>
										
									</select>
								</td>
								<td><input type='submit' name='submit' value='Search'/></td>
							</tr>
						</table>
<?php
	foreach ($_GET as $key => $val)
	{
		if (substr($key, 0, 6) == "layer_")
			$layers = str_replace("name='" . $key . "'", "name='" . $key . "' checked=true", $layers);
	}
	echo $layers;	
	echo "<br/><input onclick='var ins=document.getElementsByTagName(\"input\"); if (this.checked==true) for (var i=0; i<ins.length-1; i++) {if (ins[i].type==\"checkbox\") ins[i].checked=true;} else for (var i=0; i<ins.length-1; i++) {if (ins[i].type==\"checkbox\") ins[i].checked=false;}' name='checkall' type='checkbox' " . (isset($_GET['checkall']) ? "checked=true" : "") . "/> <b>Select/Deselect All</b>";
	
	echo "	
					</form>
				</div>
			</div>";

	// Owned Items
	$owned = array();
	$q = mysql_query("SELECT DISTINCT `clothingID` from `avatar_clothing` WHERE `account`='" . protectSQL($fetch_account['account']) . "'");
	while ($row = mysql_fetch_assoc($q))
		array_push($owned, $row['clothingID']);

	if ($fetch_avatar['gender'] == "female")
		$opGender = "male";
	else
		$opGender = "female";
			
	$slotX = 0;
	$contentHTML = "
					<table class='items'>";

	$found = 0;
	while ($fetch_list = mysql_fetch_assoc($resultList))
	{
		if ($fetch_list['purchase_yes'] != "" || $fetch_list['exoticPackage'] > 0)
		{
			if ($_GET['purchasable'] == "yes")
				continue;
		}
		else
		{
			if ($_GET['purchasable'] == "no")
				continue;
		}
		
		if (!in_array($fetch_list['clothingID'], $owned))
		{
			if ($_GET['owned'] == "yes")
				continue;
		}
		else
		{
			if ($_GET['owned'] == "no")
				continue;
		}
		$found++;
		if ($found <= $_GET['cont']*100)
			continue;
		
		$list_items = array();
		$colors = scandir("avatars/" . $fetch_list['position'] . "/" . $fetch_list['clothing']);
		foreach ($colors as $val)
		{
			if ($fetch_list['used_by'] != $opGender)
			{
				if (strpos($val, "_" . $fetch_avatar['gender'] . ".png"))
					$list_items[] = str_replace("_" . $fetch_avatar['gender'] . ".png", "", $val);
			}
			elseif (strpos($val, "_" . $opGender . ".png"))
				$list_items[] = str_replace("_" . $opGender . ".png", "", $val);
		}
		
		if ($slotX % 5 == 0)
			$contentHTML .= "
						<tr>";

		$contentHTML .= "
							<td>";
		if ($fetch_list['used_by'] != $opGender)
		{
			$contentHTML .= "
								<a href=\"javascript: review_item('" . $fetch_list['clothingID'] . "');\"><img id='img" . $fetch_list['clothingID'] . "' src='avatars/" . $fetch_list['position'] . "/" . $fetch_list['clothing'] . "/" . $list_items[0] . "_" . $fetch_avatar['gender'] . ".png'/></a>";
		}
		else
		{
			$contentHTML .= "
								<div class='spoiler'><div class='spoiler_header' onclick='$(this).next().slideToggle(\"slow\")'>" . $fetch_list['clothing'] . "<br/>is " . $opGender . " only.</div><div class='spoiler_content'>
								<img id='img" . $fetch_list['clothingID'] . "' src='avatars/" . $fetch_list['position'] . "/" . $fetch_list['clothing'] . "/" . $list_items[0] . "_" . $opGender . ".png'/>";
		}
		$contentHTML .= "
								<div>" . $fetch_list['clothing'];
		if (in_array($fetch_list['clothingID'], $owned))
			$contentHTML .= " &bull;";
		$contentHTML .= "</div>
								<span><a href='shop_search.php?used_by=" . ($opGender == "male" ? "fab" : "mab") . "&layer_" . $fetch_list['position'] . "=on&submit=Search'>" . $fetch_list['position'] . "</a>, <a target='_blank' href='shop_clothing.php?shop=" . $fetch_list['shopID'] . "'>" . $fetch_list['shopName'] . "</a>, " . $fetch_list['used_by'] . "</span><br/>";
		if ($fetch_list['used_by'] != $opGender)
		{
			$contentHTML .= "
								<select id='item" . $fetch_list['clothingID'] . "' onChange=\"javascript: switch_item('" . $fetch_list['clothingID'] . "', '" . $fetch_list['position'] . "', '" . $fetch_list['clothing'] . "', '" . $fetch_avatar['gender'] . "');\">";
		}
		else
		{
			$contentHTML .= "
								<select id='item" . $fetch_list['clothingID'] . "' onChange=\"javascript: switch_item('" . $fetch_list['clothingID'] . "', '" . $fetch_list['position'] . "', '" . $fetch_list['clothing'] . "', '" . $opGender . "');\">";
		}
		foreach ($list_items as $color)
			$contentHTML .= "<option name='" . $color . "'>" . $color . "</option>";
		$contentHTML .= "</select>";

		if (($fetch_list['exoticPackage'] > 0 || $fetch_list['purchase_yes'] != "") && $fetch_account['clearance'] < 5)
			$contentHTML .= "
								<div>Preview Only</div>";
		else
		{
			$price = "";
			$comma = "";
			if ($fetch_list['cost'] > 0)
			{
				$price .= $comma . $fetch_list['cost'] . " Auro";
				$comma = " + ";
			}
			if ($fetch_list['cost_credits'] > 0)
			{
				$price .= $comma . $fetch_list['cost_credits'] . " Credits";
				$comma = " + ";
			}
			if ($price == "")
				$price = "0 Auro";
			
			$contentHTML .= "<div><a onclick='return confirm(\"Are you sure you want to purchase " . $fetch_list['clothing'] . "?\");' href='shop_clothing.php?shop=" . ($fetch_list['shopID'] + 0) . "&pur=" . $fetch_list['clothingID'] . "'>Buy for " . $price . "</a></div>";
		}
		$contentHTML .=	"<div><a target='_blank' href='shop_clothing.php?shop=" . ($fetch_list['shopID'] + 0) . "&wish=" . $fetch_list['clothingID'] . "'>Add to Wishlist</a></div>";
		
		if ($fetch_list['used_by'] == $opGender)
			$contentHTML .= "</div></div>";
		
		if ($fetch_account['clearance'] >= 6) // Artists
			$contentHTML .= "<div><a href='adminEditItem.php?itemID=" . $fetch_list['clothingID'] . "'>Edit Item</a></div>";
		$contentHTML .= "
							</td>";
		
		if ($slotX % 5 == 4)
			$contentHTML .= "
						</tr>";
		$slotX++;
		
		if ($found == ($_GET['cont']+1)*100)
			break;
	}
	
	if ($slotX % 5 > 0)
			$contentHTML .= "
						</tr>";
	$contentHTML .= "
					</table>";
	
	if (isset($contentHTML))
	{
			echo "
			<div class='category-container'>
				<div class='details-header'>
					Results
				</div>
				<div class='details-body'>";
			echo $contentHTML;
			if ($_GET['purchasable'] == "" && $_GET['owned'] == "")
			{
				if ($_GET['start'] > 0 && mysql_num_rows($resultList) == 100)
					echo "
					<hr/><div style='text-align:center;'><a href='" . $_SERVER['REQUEST_URI'] . "&start=" . max(($_GET['start'] - 1), 0) . "'>< View Previous Page</a> &bull; <a href='" . $_SERVER['REQUEST_URI'] . "&start=" . ($_GET['start'] + 1) . "'>View Next Page ></a></div>";
				elseif (mysql_num_rows($resultList) == 100)
					echo "
					<hr/><div style='text-align:center;'><a href='" . $_SERVER['REQUEST_URI'] . "&start=" . ($_GET['start'] + 1) . "'>View Next Page ></a></div>";
				elseif ($_GET['start'] > 0)
					echo "
					<hr/><div style='text-align:center;'><a href='" . $_SERVER['REQUEST_URI'] . "&start=" . max(($_GET['start'] - 1), 0) . "'>< View Previous Page</a></div>";
			}
			else
			{
				if ($_GET['cont'] > 0 && $found % 100 == 0)
					echo "
					<hr/><div style='text-align:center;'><a href='" . $_SERVER['REQUEST_URI'] . "&cont=" . max(($_GET['cont'] - 1), 0) . "'>< View Previous Page</a> &bull; <a href='" . $_SERVER['REQUEST_URI'] . "&cont=" . ($_GET['cont'] + 1) . "'>View Next Page ></a></div>";
				elseif ($found == 100)
					echo "
					<hr/><div style='text-align:center;'><a href='" . $_SERVER['REQUEST_URI'] . "&cont=" . ($_GET['cont'] + 1) . "'>View Next Page ></a></div>";
				elseif ($_GET['cont'] > 0)
					echo "
					<hr/><div style='text-align:center;'><a href='" . $_SERVER['REQUEST_URI'] . "&cont=" . max(($_GET['cont'] - 1), 0) . "'>< View Previous Page</a></div>";
			}
			echo "
				</div>	
			</div>";
	}
?>

			<script type='text/javascript'>
				function switch_item(num, position, name, gender)
				{
					var selbox = $("#item" + num);
					$("#img" + num).attr("src", "avatars/" + position + "/" + name + "/" + selbox.val() + "_" +gender + ".png");
				}
				
				function review_item(id)
				{
					window.open("preview_avi.php?clothingID=" + id + "&recolor=" + $("#item" + id).val(), "PreviewAvatar", "width=622,height=500,toolbar=no,location=no,directories=no,status=no,menubar=no,copyhistory=no,scrollbars=yes");
				}
			</script>
<?php
	require("incAVA/footer.php");
?>