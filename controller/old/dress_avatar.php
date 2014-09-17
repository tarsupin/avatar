<?php
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");

	if (!isset($fetch_avatar['id']))
	{
		header("Location: index.php");
		exit;
	}
	
	// parameters
	if (!isset($_GET['position']))
		$_GET['position'] = "";
	foreach ($_GET as $key => $val)
		$_GET[$key] = trim($val);
	foreach ($_POST as $key => $val)
		$_POST[$key] = trim($val);
		
	define("EXIST", "own");
	require("fanctions/check_and_draw.php");

	// get outfit code
	$fetch_outfit['outfit_serial'] = get_outfit($fetch_avatar['id'], $fetch_avatar['base']);

	if ($fetch_avatar['gender'] == "female")
		$oppositeGender = "male";
	else
		$oppositeGender = "female";
		
	if (isset($_GET['equip']) && isset($_GET['pre']))
	{
		// check if user owns the item; ownership is checked during draw, but no need to go through it all if this here fails
		if ($owned = owned_id($_GET['equip'], $fetch_account['account']))
		{
			// remove item from outfit if already part of it
			foreach ($fetch_outfit['outfit_serial'] as $key => $val)
				if ($val[0] == $owned)
				{
					unset($fetch_outfit['outfit_serial'][$key]);
					break;
				}
			// add to outfit
			$fetch_outfit['outfit_serial'][] = array($owned, $_GET['pre']);
			// run checks and draw
			$fetch_outfit['outfit_serial'] = wrapper($fetch_outfit['outfit_serial'], $fetch_avatar['gender'], $fetch_avatar['base'], $fetch_account['account'], $fetch_avatar['id']);
			// update timestamp
			$fetch_avatar['last_timestamp'] = time();
		}
	}
	elseif (isset($_GET['unequip']))
	{
		foreach ($fetch_outfit['outfit_serial'] as $key => $val)
			if ($val[0] == $_GET['unequip'])
			{
				unset($fetch_outfit['outfit_serial'][$key]);
				break;
			}
		$fetch_outfit['outfit_serial'] = wrapper($fetch_outfit['outfit_serial'], $fetch_avatar['gender'], $fetch_avatar['base'], $fetch_account['account'], $fetch_avatar['id']);
		$fetch_avatar['last_timestamp'] = time();
	}
	elseif (isset($_GET['sellback']))
	{
		if ($owned = owned_id($_GET['sellback'], $fetch_account['account']))
		{
			$q = "SELECT `clothing`, `exoticPackage`, `shopID`, `cost`, `cost_credits`, `purchase_yes` FROM `clothing_images` WHERE `clothingID`='" . ($owned + 0) . "' LIMIT 1";
			$res = mysql_query($q);
			if ($value = mysql_fetch_assoc($res))
			{
				$q = "SELECT `clearance` FROM `shop_listings` WHERE `id`='" . ($value['shopID'] + 0) . "' LIMIT 1";
				$res = mysql_query($q);
				$lim = mysql_fetch_assoc($res);
				if (isset($_GET['forceSale']) || ($value['purchase_yes'] == "" && $value['exoticPackage'] == 0 && $lim['clearance'] <= 2 && $value['cost_credits'] == 0))
				{
					$resell = round($value['cost']*0.5);
					$resell += $value['cost_credits']*2500;
					$q = "UPDATE `u5s_auth`.`s4u_account_trackers` SET `auro`=`auro`+" . ($resell + 0) . " WHERE `account`='" . protectSQL($fetch_account['account']) . "' LIMIT 1";
					mysql_query($q);
					$q = "DELETE FROM `avatar_clothing` WHERE `id`='" . ($_GET['sellback'] + 0) . "' LIMIT 1";
					mysql_query($q);
					if (!owned_item($owned, $fetch_account['account']))
					{
						$avatars = explode("|", file_get_contents("http://avatar.unifaction.com/API_avatarList.php?account=" . $fetch_account['account']));
						if ($avatars[0] != "")
						{
							foreach ($avatars as $avi)
							{
								$aviexp = explode(":", $avi);
								$q = "SELECT `outfit_serial` FROM `avatar_outfits_real` WHERE `avatar_id`='" . ($aviexp[0] + 0) . "' LIMIT 1";
								$res = mysql_query($q);
								if ($fetch = mysql_fetch_assoc($res))
								{
									$fetch['outfit_serial'] = unserialize($fetch['outfit_serial']);
									foreach ($fetch['outfit_serial'] as $key => $val)
										if ($val[0] == $owned)
										{
											unset($fetch['outfit_serial'][$key]);
											if ($aviexp[0] == $fetch_avatar['id'])
												$fetch_outfit['outfit_serial'] = wrapper($fetch['outfit_serial'], $aviexp[2], $aviexp[3], $fetch_account['account'], $aviexp[0]);
											else
												wrapper($fetch['outfit_serial'], $aviexp[2], $aviexp[3], $fetch_account['account'], $aviexp[0]);
											break;
										}
								}
							}
						}
					}
				}
				else
					$messages[] = "<div class='message-neutral'>" . $value['clothing'] . " is exotic, limited or otherwise not easily purchasable. You might not be able to obtain it again.<br/>If you are sure that you want to sell it, please confirm by clicking <a href='dress_avatar.php?position=" . $_GET['position'] . "&sellback=" . $_GET['sellback'] . "&forceSale#wrap'>here</a>.</div>";
			}
		}
	}
	elseif (isset($_GET['action']))
	{
		if ($_GET['action'] == "unequip_all")
		{
			$fetch_outfit['outfit_serial'] = array();
			$fetch_outfit['outfit_serial'][] = array(0, $fetch_avatar['base']);
		}
		elseif ($_GET['action'] == "preview")
		{
			$fetch_outfit['outfit_serial'] = array();
			$fetch_preview['outfit_serial'] = get_preview_outfit($fetch_account['account'], $fetch_avatar['base']);
			foreach ($fetch_preview['outfit_serial'] as $val)
			{
				if ($val[0] > 0)
				{
					if (owned_item($val[0], $fetch_account['account']))
						$fetch_outfit['outfit_serial'][] = $val;
				}
				else
					$fetch_outfit['outfit_serial'][] = array(0, $fetch_avatar['base']);
			}
		}
		$fetch_outfit['outfit_serial'] = wrapper($fetch_outfit['outfit_serial'], $fetch_avatar['gender'], $fetch_avatar['base'], $fetch_account['account'], $fetch_avatar['id']);
		$fetch_avatar['last_timestamp'] = time();
	}
	elseif (isset($_POST['order']))
	{
		$previous = $fetch_outfit['outfit_serial'];
		foreach ($previous as $key => $val)
			$previous[$key] = $val[0];
		$fetch_outfit['outfit_serial'] = array();
		$items = explode(",", $_POST['order']);
		foreach ($items as $item)
		{
			$id = substr($item, 1);
			if (substr($item, 0, 1) == "o" && is_numeric($id))
			{
				if (isset($_POST['i' . $id]) && $id != 0)
				{
					if (in_array($id, $previous) || owned_item($id, $fetch_account['account']))
						$fetch_outfit['outfit_serial'][] = array($id, $_POST['i' . $id]);
				}
				elseif ($id == 0)
					$fetch_outfit['outfit_serial'][] = array(0, $fetch_avatar['base']);
			}
		}
		$fetch_outfit['outfit_serial'] = wrapper($fetch_outfit['outfit_serial'], $fetch_avatar['gender'], $fetch_avatar['base'], $fetch_account['account'], $fetch_avatar['id']);
		$fetch_avatar['last_timestamp'] = time();
	}
		
	$pagetitle = $fetch_avatar['avatar'] . "'s Dressing Room" . ($_GET['position'] != "" ? ": " . $_GET['position'] : "");
	require("incAVA/header.php");
?>
			<div class='category-container'>
				<div class='details-header'>
					<?php echo $fetch_avatar['avatar']; ?>'s Dressing Room
				</div>
				<div id='wrap' class='details-body'>
					<table style='width:100%;'>
						<tr>
							<td style='vertical-align:top; width:205px;'><?php echo "
								<img src='characters/" . $fetch_account['account'] . "/avi_" . $fetch_avatar['id'] . ".png?t=" . $fetch_avatar['last_timestamp'] . "' style='height:383px; width:205px;'/>"; ?>

								<ul style='list-style:none; margin:0px;'>
									<li><a onclick='return confirm("Are you sure you want to unequip all items?");' href='dress_avatar.php?<?php if ($_GET['position'] != "") echo "position=" . $_GET['position'] . "&"; ?>action=unequip_all#wrap'>Unequip All</a></li>
									<li><a href='dress_avatar.php?<?php if ($_GET['position'] != "") echo "position=" . $_GET['position'] . "&"; ?>action=preview#wrap'>Replace with Preview Image</a></li>
<?php
	if ($_GET['position'] != "")
		echo "
									<li><a href='shop_search.php?used_by=" . ($fetch_avatar['gender'] == "female" ? "fab" : "mab") . "&submit=Search&layer_" . $_GET['position'] . "=on'>Search " . $_GET['position'] . "</a></li>";
?>

								</ul>
							</td>
							<td style='vertical-align:top; font-size:12px;'>
								<form id='sortable' action='dress_avatar.php<?php if ($_GET['position'] != "") echo "?position=" . $_GET['position']; ?>#wrap' method='post'>
									<ul id='equipped' class='dragndrop'>
<?php
	$color_duplicate = array();
	$fetch_outfit['outfit_serial'] = array_reverse($fetch_outfit['outfit_serial']);
	foreach ($fetch_outfit['outfit_serial'] as $s)
	{
		if ($s[0] > 0)
		{
			$q = "SELECT `clothing`, `position`, `used_by`, `rel_to_base` FROM `clothing_images` WHERE `clothingID`='" . ($s[0] + 0) . "' LIMIT 1";
			$res = mysql_query($q);
			if ($fetch_detail = mysql_fetch_assoc($res))
			{			
				if (!isset($color_duplicate[$s[0]]))
				{
					$colors = scandir("avatars/" . $fetch_detail['position'] . "/" . $fetch_detail['clothing']);
					foreach ($colors as $val)
					{
						if ($fetch_detail['used_by'] != $oppositeGender)
						{
							if (strpos($val, "_" . $fetch_avatar['gender'] . ".png"))
								$color_duplicate[$s[0]][] = str_replace("_" . $fetch_avatar['gender'] . ".png", "", $val);
						}
						elseif (strpos($val, "_" . $oppositeGender . ".png"))
							$color_duplicate[$s[0]][] = str_replace("_" . $oppositeGender . ".png", "", $val);
					}
				}
				$colors = array();
				foreach ($color_duplicate[$s[0]] as $val)
					$colors[] = "<option value='" . $val . "'" . ($val == $s[1] ? " selected='selected'" : "") . ">" . $val . "</option>";
				echo "
										<li id='o" . $s[0] . "' class='" . ($fetch_detail['rel_to_base'] != "on" ? "item" : "skin") . "'>
											<div><img src='avatars/" . $fetch_detail['position'] . "/" . $fetch_detail['clothing'] . "/" . $s[1] . "_" . $fetch_avatar['gender'] . ".png' title='" . $fetch_detail['clothing'] . "'/></div>
											<a class='close' href='dress_avatar.php?" . ($_GET['position'] != "" ? "position=" . $_GET['position'] . "&" : "") . "unequip=" . $s[0] . "#wrap'>&#10006;</a>
											<select name='i" . $s[0] . "'>" . implode($colors) . "</select>
											<a class='left' href='#'>&lt;</a>
											<a class='right' href='#'>&gt;</a>
										</li>";
			}
		}
		else
		{
			echo "
										<li id='o0' class='base'>
											<div style='line-height:50px;'>Base</div>
											<select name='i0' disabled><option value='" . $s[1] . "'>" . ucfirst($s[1]) . "</option></select>
											<a class='left' href='#'>&lt;</a>
											<a class='right' href='#'>&gt;</a>
										</li>";
		}
	}
	
	$fetch_outfit['outfit_serial'] = array_values($fetch_outfit['outfit_serial']);
?>

									</ul>
									<textarea id='order' name='order' style='display:none;'></textarea>
								</form>

<?php
	$q = "SELECT DISTINCT `position` FROM `avatar_clothing` WHERE `account`='" . protectSQL($fetch_account['account']) . "' AND `in_trade`='0'";
	$res = mysql_query($q);
	$layers = array();
	while ($fetch_positions = mysql_fetch_assoc($res))
		$layers[] = "<a href='dress_avatar.php?position=" . $fetch_positions['position'] . "#wrap'>" . $fetch_positions['position'] . "</a>";
	echo "
								<div style='text-align:left; clear:both;'>" . implode(" &bull; ", $layers) . "</div>";
	unset($layers);
	
	if ($_GET['position'] != "")
	{	
		$slotX = 0;
		$results = array();	
		$op = array();
		echo "
								<br/>
								<table class='items'>";
		$q = "SELECT `id`, `clothingID` FROM `avatar_clothing` WHERE `account`='" . protectSQL($fetch_account['account']) . "' AND `in_trade`='0' AND `position`='" . protectSQL($_GET['position']) . "'";
		$res = mysql_query($q);
		while ($fetch_cloth = mysql_fetch_assoc($res))
		{
			$fetch_clothes = fetch_item_details($fetch_cloth['clothingID']);
			$dir = "avatars/" . $fetch_clothes['position'] . "/" . $fetch_clothes['clothing'];
			if (!file_exists($dir))
			{
				$fetch_clothes = renew_item_details($fetch_cloth['clothingID']);
				$dir = "avatars/" . $fetch_clothes['position'] . "/" . $fetch_clothes['clothing'];
			}
			$fetch_clothes['id'] = $fetch_cloth['id'];
			$fetch_clothes['clothingID'] = $fetch_cloth['clothingID'];
			unset($fetch_cloth);
			if (!isset($color_duplicate[$fetch_clothes['clothingID']]))
			{
				$colors = scandir($dir);
				foreach ($colors as $val)
				{
					if ($fetch_clothes['used_by'] != $oppositeGender)
					{
						if (strpos($val, "_" . $fetch_avatar['gender'] . ".png"))
							$color_duplicate[$fetch_clothes['clothingID']][] = str_replace("_" . $fetch_avatar['gender'] . ".png", "", $val);
					}
					elseif (strpos($val, "_" . $oppositeGender . ".png"))
						$color_duplicate[$fetch_clothes['clothingID']][] = str_replace("_" . $oppositeGender . ".png", "", $val);
				}
			}

			$fetch_clothes['resell'] = round($fetch_clothes['cost']*0.5) + $fetch_clothes['cost_credits']*2500;
		
			if ($fetch_clothes['used_by'] != $oppositeGender)
				$results[] = $fetch_clothes;
			else
				$op[] = $fetch_clothes;
		}
		
		uasort($results, "order_item_details");
		uasort($op, "order_item_details");
		
		foreach ($results as $res)
		{
			$options = array();
			foreach ($color_duplicate[$res['clothingID']] as $color)
				$options[] = "<option name='" . $color . "'>" . $color . "</option>";
		
			if ($slotX % 5 == 0)
				echo "
									<tr>";
			echo "
										<td>
											<a href=\"javascript: review_item('" . $res['id'] . "', '" . $res['clothingID'] . "');\"><img id='img" . $res['id'] . "' src='avatars/" . $res['position'] . "/" . $res['clothing'] . "/" . $color_duplicate[$res['clothingID']][0] . "_" . $fetch_avatar['gender'] . ".png' alt='" . $res['clothing'] . "'/></a><br/>
											" . $res['clothing'] . "<br/>
											<select id='item" . $res['id'] . "' onchange='switch_item(\"" . $res['id'] . "\", \"" . $res['position'] . "\", \"" . $res['clothing'] . "\", \"" . $fetch_avatar['gender'] . "\");'>" . implode($options) . "</select>
											<div>
												<a id='dresslink_" . $res['id'] . "' href='dress_avatar.php?position=" . $_GET['position'] . "&equip=" . $res['id'] . "&pre=" . $color_duplicate[$res['clothingID']][0] . "#wrap'>Equip</a>
											</div>
											<div>
												<a onclick='return confirm(\"Are you sure you want to sell " . $res['clothing'] . " for 50% of its Auro value (" . $res['resell'] . " Auro)?\");' href='dress_avatar.php?position=" . $_GET['position'] . "&sellback=" . $res['id'] . "#wrap'>Sell</a> | <a href='trade_start.php?add=" . $res['id'] . "'>Trade</a> | <a href='send_gift.php?item=" . $res['id'] . "'>Gift</a>
											</div>
										</td>";
			if ($slotX % 5 == 4)
				echo "
									</tr>";
			$slotX++;
		}
		unset($results);
		
		foreach ($op as $res)
		{
			$options = array();
			foreach ($color_duplicate[$res['clothingID']] as $color)
				$options[] = "<option name='" . $color . "'>" . $color . "</option>";
		
			if ($slotX % 5 == 0)
				echo "
									<tr>";
			echo "
										<td style='opacity:0.5; filter:alpha(opacity=50);'>
											<img id='img" . $res['id'] . "' src='avatars/" . $res['position'] . "/" . $res['clothing'] . "/" . $color_duplicate[$res['clothingID']][0] . "_" . $oppositeGender . ".png' alt='" . $res['clothing'] . "'/><br/>
											" . $res['clothing'] . "<br/>
											<select disabled>" . implode($options) . "</select>
											<div>
												" . $oppositeGender . " only
											</div>
											<div>
												<a onclick='return confirm(\"Are you sure you want to sell " . $res['clothing'] . " for 50% of its Auro value (" . $res['resell'] . " Auro)?\");' href='dress_avatar.php?position=" . $_GET['position'] . "&sellback=" . $res['id'] . "#wrap'>Sell</a> | <a href='trade_start.php?add=" . $res['id'] . "'>Trade</a> | <a href='send_gift.php?item=" . $res['id'] . "'>Gift</a>
											</div>
										</td>";
			if ($slotX % 5 == 4)
				echo "
									</tr>";
			$slotX++;
		}
		unset($op);
		
		if ($slotX > 5 && $slotX % 5 > 0)
		{
			for ($i=0; $i<5-($slotX%5); $i++)
				echo "
										<td>&nbsp;</td>";
		}
		if ($slotX % 5 > 0)
			echo "
									</tr>";
		echo "
								</table>";
	}
?>

							</td>
						</tr>
					</table>
				</div>
			</div>
			
			<script type='text/javascript'>
				function switch_item(num, position, name, gender)
				{
					var selbox = $("#item" + num);
					var selbox2 = $("#pos" + num);
					$("#img" + num).attr("src", "avatars/" + position + "/" + name + "/" + selbox.val() + "_" +gender + ".png");
					$("#dresslink_" + num).attr("href", "dress_avatar.php?position=" + position + "&equip=" + num + "&pre=" + selbox.val() + "#wrap");
				}
				
				function review_item(itemid, id)
				{
					window.open("preview_avi.php?clothingID=" + id + "&recolor=" + $("#item" + itemid).val(), "PreviewAvatar", "width=622,height=500,toolbar=no,location=no,directories=no,status=no,menubar=no,copyhistory=no,scrollbars=yes");
				}
			</script>
			<script src='javascript/reorder.js' type='text/javascript' charset='utf-8'></script>
<?php
	require("incAVA/footer.php");
?>