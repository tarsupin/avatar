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
	
	$pagetitle = "[staff] Unused Items";
	require("incAVA/header.php");
?>
			<div class='category-container'>
				<div class='details-header'>
					Unused Items
				</div>
				<div class='details-body'>
<?php
	$files = shell_exec("find avatars -type d -mindepth 2 -maxdepth 2 -print");
	$files = explode("\n", trim($files));
	$tellme = array();
	foreach ($files as $key => $val)
	{
		$split = explode("/", $val);
		$query = "SELECT `clothingID` FROM `clothing_images` WHERE `position`='" . protectSQL($split[1]) . "' AND `clothing`='" . protectSQL($split[2]) . "' LIMIT 1";
		$result = mysql_query($query);
		if (!$fetch = mysql_fetch_assoc($result))
			if ($split[1] != "temp")
				$tellme[] = array($split[1], $split[2]);
	}
	unset($files);
	echo "
					<b>in FTP, but not DB</b><br/>";
	if ($tellme != array())
	{
		sort($tellme);
		echo "
					<table class='alternate_without_th' style='text-align:left;'>";
		foreach ($tellme as $tell)
			echo "
						<tr><td>" . $tell[0] . "</td><td>" . $tell[1] . "</td></tr>";

		echo "
					</table>";
	}
	
	echo "
					<hr/>";
	
	$tellme = array();
	$query = "SELECT `clothingID`, `clothing`, `position` FROM `clothing_images`";
	$result = mysql_query($query);
	while ($fetch = mysql_fetch_assoc($result))
	{
		if (!$handle = opendir("avatars/" . $fetch['position'] . "/" . $fetch['clothing']))
			$tellme[$fetch['clothingID']] = array($fetch['position'], $fetch['clothing']);
	}
	echo "
					<b>in DB, but not FTP</b><br/>";
	if ($tellme != array())
	{
		asort($tellme);
		echo "
					<table class='alternate_without_th' style='text-align:left;'>";
		foreach ($tellme as $key => $val)
			echo "
						<tr><td>" . $val[0] . "</td><td>" . $val[1] . "</td><td><a onclick='return confirm(\"Are you sure you want to delete this item?\");' href='adminItemList.php?delete=" . $key . "'>Delete Item</a></td></tr>";

		echo "
					</table>";
	}
?>

				</div>
			</div>
<?php
	require("incAVA/footer.php");
?>