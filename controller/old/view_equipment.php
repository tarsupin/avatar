<?php
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");
	
	if (!isset($_GET['user']) || !isset($_GET['pass']) || !isset($_GET['time']) || !isset($_GET['days']) || $_GET['time']<time()-$_GET['days']*24*3600)
	{
		header("Location: index.php");
		exit();
	}
	elseif (isset($_GET['viewer']))
	{
		if ($fetch_account['account'] != $_GET['viewer'] || sha1($_GET['user'] . $_GET['time'] . $_GET['days'] . $fetch_account['account'] . $siteKey) != $_GET['pass'])
		{
			header("Location: index.php");
			exit();
		}
	}
	else
	{
		if (sha1($_GET['user'] . $_GET['time'] . $_GET['days'] . "equip4@ll" . $siteKey) != $_GET['pass'])
		{
			header("Location: index.php");
			exit();
		}
	}
	require("fanctions/check_and_draw.php");
	
	if ($fetch_avatar['gender'] == "female")
		$oppositeGender = "male";
	else
		$oppositeGender = "female";

	$pagetitle = "View " . $_GET['user'] . "'s Equipment";
	require("incAVA/header.php");
?>
			<div class='category-container'>
				<div class='details-header'>
					View <?php echo $_GET['user']; ?>'s Equipment
				</div>
				<div class='details-body'>
					<table style='width:100%;'>
						<tr>
							<td colspan='2' style='font-size:12px;'>
<?php
	$comma = "";
	echo "
								";
	// Gather Distinct Positions List
	$resPos = mysql_query("SELECT DISTINCT `position` FROM avatar_clothing WHERE account='" . protectSQL($_GET['user']) . "' AND in_trade='0'");
	while($fetch_positions = mysql_fetch_assoc($resPos))
	{
		echo $comma . "<a href='view_equipment.php?user=" . $_GET['user'] . (isset($_GET['viewer'])? "&viewer=" . $_GET['viewer'] : "") . "&time=" . $_GET['time'] . "&days=" . $_GET['days'] . "&pass=" . $_GET['pass'] . "&position=" . $fetch_positions['position'] . "'>" . $fetch_positions['position'] . "</a>";
		$comma = " &bull; ";
	}
?>

							</td>
						</tr>
						<tr>
							<td style='width:205px;'>
								<table class='alternate_without_th'>
<?php
		$result = mysql_query("SELECT `clothing_images`.`clothing`, `clothing_images`.`position`, `clothing_images`.`used_by` FROM `avatar_clothing` INNER JOIN `clothing_images` ON `avatar_clothing`.`clothingID`=`clothing_images`.`clothingID` WHERE `avatar_clothing`.`account`='" . protectSQL($_GET['user']) . "' AND `avatar_clothing`.`in_trade`='0' ORDER BY `clothing_images`.`position`, `clothing_images`.`clothing`");
		while ($fetch_cloth = mysql_fetch_assoc($result))
			echo "
									<tr>
										<td>" . $fetch_cloth['position'] . "</td>
										<td>" . $fetch_cloth['clothing'] . "</td>
										<td>" . $fetch_cloth['used_by'] . "</td>
									</tr>";
		echo "
								</table>";
?>

							</td>
							<td style='vertical-align:top;'>
<?php					
	if (isset($_GET['position']))
	{
		$shops2 = array();
		$q = mysql_query("SELECT `id`, `clearance` FROM `shop_listings` WHERE `clearance`<='" . ($fetch_account['clearance'] + 0) . "'");
		while ($row = mysql_fetch_assoc($q))
			$shops2[] = $row['id'];
	
		$slotX = 0;
		$op = array();
		echo "
								<table class='items'>";
		// Gather Clothing List
		$q = "SELECT `id`, `clothingID` FROM `avatar_clothing` WHERE `account`='" . protectSQL($_GET['user']) . "' AND `in_trade`='0' AND `position`='" . protectSQL($_GET['position']) . "'";
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
	else
		echo "
								&nbsp;";
?>

							</td>
						</tr>
					</table>
					<script type='text/javascript'>
						function switch_item(num, position, name, gender)
						{
							var selbox = $("#item" + num);
							$("#img" + num).attr("src", "avatars/" + position + "/" + name + "/" + selbox.val() + "_" +gender + ".png");
						}
						
						function review_item(itemid, id)
						{
							window.open("preview_avi.php?clothingID=" + id + "&recolor=" + $("#item" + itemid).val(), "PreviewAvatar", "width=622,height=500,toolbar=no,location=no,directories=no,status=no,menubar=no,copyhistory=no,scrollbars=yes");
						}
					</script>
				</div>
			</div>
<?php
	require("incAVA/footer.php");
?>