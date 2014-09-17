<?php
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");

	// Check if you have an avatar selected
	if (!isset($fetch_avatar['id']))
	{
		header("Location: index.php");
		exit;
	}

	$pagetitle = "List of Shops";
	require("incAVA/header.php");
?>
			<div class='category-container'>
				<div class='details-header'>
					List of Shops
				</div>
				<div class='details-body'>
<?php
	// Gather Shop Listings
	$result = mysql_query("SELECT `id`, `shopName`, `shopSign`, `shopHover`, `type`, `clearance`, `orderID` FROM `shop_listings` WHERE `clearance`<='" . ($fetch_account['clearance'] + 0) . "' ORDER BY `orderID`");

	$regularshops = array();						
	$staffshops = array();
	$previewshops = array();
	$specialshops = array();

	while ($fetch_shops = mysql_fetch_assoc($result))
	{
		if ($fetch_shops['clearance'] <= 2)
		{
			if ($fetch_shops['type'] == "regular")
				$regularshops[] = $fetch_shops;
			elseif ($fetch_shops['type'] == "preview")
				$previewshops[] = $fetch_shops;
			elseif ($fetch_shops['type'] == "special")
				$specialshops[] = $fetch_shops;
		}
		elseif ($fetch_shops['type'] == "staff")
			$staffshops[] = $fetch_shops;
	}

	$quarter = floor(count($regularshops)/4);
?>
					<table style='width:100%;'>
						<tr>
							<td>
<?php
	for ($i=0; $i<count($regularshops); $i++)
	{
		$s = $regularshops[$i];
		echo "
								<a class='button' href='shop_clothing.php?shop=" . $s['id'] . "' title='" . str_replace("'", "&#039;", $s['shopHover']) . "'><span style='padding:20px 0px 21px 0px;'>" . ($s['shopSign'] != "" ? "<img src='images/shops/" . $s['shopSign'] . "' alt='" . str_replace("'", "&#039;", $s['shopName']) . "'/>" : $s['shopName']) . "</span></a>";
		if ($i == $quarter-1 || $i == 2*$quarter-1 || $i == 3*$quarter-1)
			echo "
							</td>
							<td>";
	}				
	echo "					
							</td>
							<td>";
	foreach ($previewshops as $s)
		echo "
								<a class='button' href='shop_clothing.php?shop=" . $s['id'] . "' title='" . str_replace("'", "&#039;", $s['shopHover']) . "'><span style='padding:5px 0px;'>" . ($s['shopSign'] != "" ? "<img src='images/shops/" . $s['shopSign'] . "' alt='" . str_replace("'", "&#039;", $s['shopName']) . "'/>" : $s['shopName']) . "</span></a>";
	foreach ($specialshops as $s)
		echo "
								<a class='button' href='shop_clothing.php?shop=" . $s['id'] . "' title='" . str_replace("'", "&#039;", $s['shopHover']) . "'><span style='padding:5px 0px;'>" . ($s['shopSign'] != "" ? "<img src='images/shops/" . $s['shopSign'] . "' alt='" . str_replace("'", "&#039;", $s['shopName']) . "'/>" : $s['shopName']) . "	</span></a>";			
	echo "
							</td>
						</tr>
					</table>";
	if ($fetch_account['clearance'] > 2 && $staffshops != array())
	{
		$c = count($staffshops);
		echo "
					<table style='width:100%; table-layout:fixed;'>
						<tr>";
		for ($i=0; $i<$c; $i++)
		{
			$s = $staffshops[$i];
			echo "
							<td><a class='button' href='shop_clothing.php?shop=" . $s['id'] . "' title='" . str_replace("'", "&#039;", $s['shopHover']) . "'><span>" . $s['shopName'] . "</span></a></td>";
		}
		echo "
						</tr>
					</table>";
	}
?>

				</div>
			</div>
<?php
	require("incAVA/footer.php");
?>