<?php
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");

	if ($fetch_account['clearance'] < 6)
	{
		header("Location: index.php");
		exit;
	}

	if ($_POST)
	{
		if (isset($_POST['refresh_male']))
			$fetch_avatar['gender']	= "male";
		elseif (isset($_POST['refresh_female']))
			$fetch_avatar['gender']	= "female";
		else
		{
			header("Location: staff.php");
			exit;
		}
		
		foreach ($_POST as $key => $val)
		{
			if (substr($key, 0, 5) == "shop_")
			{
				$result = mysql_query("SELECT `id`, `shopName`, `clearance` FROM `shop_listings` WHERE id='" . (substr($key, 5) + 0) . "' AND `clearance`<=" . ($fetch_account['clearance'] + 0) . " LIMIT 1");
				if (!$fetch_shop = mysql_fetch_assoc($result))
					continue;
				
				touch("content/shop_" . ($fetch_shop['id'] + 0) . "_" . $fetch_avatar['gender'] . "_2.html", time()-86401);
				
				$messages[] = "<div class='message-success'>" . $fetch_shop['shopName'] . " (" . $fetch_avatar['gender'] . ") will be refreshed the next time someone opens it.</div>";
				
				if ($fetch_shop['id'] == 65)
					touch("content/exotic_list.html", time()-2592001);
			}
		}
	}

	$pagetitle = "[staff] Refresh Shops";
	require("incAVA/header.php");
?>
			<div class='category-container'>
				<div class='details-header'>
					Refresh Shops
				</div>
				<div class='details-body'>
					<form method='post'>
<?php
	$q = mysql_query("SELECT `id`, `shopName` FROM `shop_listings` WHERE `clearance`<=2 ORDER BY `shopName`");
	while ($row = mysql_fetch_assoc($q))
		echo "
						<input type='checkbox' name='shop_" . $row['id'] . "'" . ($_POST ? (isset($_POST['shop_' . $row['id']]) ? " checked='checked'" : "") : " checked='checked'") . "/> " . $row['shopName'] . "<br/>";
	echo "
						<br/>";
	$q = mysql_query("SELECT `id`, `shopName` FROM `shop_listings` WHERE `clearance`>2 AND `clearance`<=" . ($fetch_account['clearance'] + 0) . " ORDER BY `shopName`");
	while ($row = mysql_fetch_assoc($q))
		echo "
						<input type='checkbox' name='shop_" . $row['id'] . "'" . ($_POST ? (isset($_POST['shop_' . $row['id']]) ? " checked='checked'" : "") : "") . "/> " . $row['shopName'] . "<br/>";
?>
						<br/>
						<input type='submit' name='refresh_female' value='Refresh Selected Female Shops'/>
						<input type='submit' name='refresh_male' value='Refresh Selected Male Shops'/>
					</form>
				</div>
			</div>
<?php
	require("incAVA/footer.php");
?>