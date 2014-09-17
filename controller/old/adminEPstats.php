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
	
	require("fanctions/check_and_draw.php");

	$pagetitle = "[staff] EP Stats";
	require("incAVA/header.php");
?>
			<div class='category-container'>
				<div class='details-header'>
					EP Stats
				</div>
				<div class='details-body'>
<?php			
	$q_packages = mysql_query("SELECT `id`, `title`, `image` FROM `exotic_packages` WHERE `id`>='47' ORDER BY `id` DESC");
	while ($fetch_packages = mysql_fetch_assoc($q_packages))
	{
		$q_details = mysql_query("SELECT `item_id`, `count` FROM `exotic_packages_stats` WHERE `package_id`='" . ($fetch_packages['id'] + 0) . "'");
		$count = 0;
		$eps = 0;
		$results = array();
		while ($fetch_details = mysql_fetch_assoc($q_details))
		{
			if ($fetch_details['item_id'] != 0)
			{
				$fetch_item = fetch_item_details($fetch_details['item_id']);
				$dir = "avatars/" . $fetch_item['position'] . "/" . $fetch_item['clothing'];
				if (!file_exists($dir))
				{
					$fetch_item = renew_item_details($fetch_details['item_id']);
					$dir = "avatars/" . $fetch_item['position'] . "/" . $fetch_item['clothing'];
				}
				$files = scandir($dir);
				foreach ($files as $file)
					if (substr($file,-4) == ".png")
						break;
			
				$results[$fetch_details['item_id']] = array($fetch_details['count'], $dir . "/" . $file);
				$count += $fetch_details['count'];
			}
			else
				$eps = $fetch_details['count'];
		}
		rsort($results);
		echo "
					" . $fetch_packages['title'] . " (" . $eps . ")<hr/>";
		foreach ($results as $detail)
			echo "
					<img src='" . $detail[1] . "'/ style='vertical-align:middle; max-height:75px;'> " . $detail[0] . " ";
		echo "
					<img src='images/exotic_packages/" . $fetch_packages['image'] . "'/ style='vertical-align:middle; max-height:75px;'> " . ($eps - $count);
		if ($fetch_packages['id'] > 47)
			echo "<hr/>";
	}
?>

				</div>
			</div>
<?php
	require("incAVA/footer.php");
?>