<?php
	if (!isset($_GET['package']))
	{
		header("Location: exotic_packages.php");
		exit;
	}

	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");

	// Gather Donation Packages
	$result = mysql_query("SELECT `id`, `packageID`, `in_trade` FROM `exotic_packages_owned` WHERE `id`='" . ($_GET['package'] + 0) . "' AND `account`='" . protectSQL($fetch_account['account']) . "' LIMIT 1");

	if (!$fetch_package = mysql_fetch_assoc($result))
	{
		header("Location: exotic_packages.php");
		exit;
	}
	
	if ($fetch_package['in_trade'] > 0)
	{
		header("Location: exotic_packages.php");
		exit;
	}

	$pagetitle = "Receive Exotic Item";
	require("incAVA/header.php");
?>
			<div class='category-container'>
				<div class='details-header'>
					Receive Exotic Item
				</div>
				<div class='details-body'>
<?php
		
		// Gather Donation Items
		$result = mysql_query("SELECT `clothingID`, `clothing`, `position`, `used_by` FROM `clothing_images` WHERE `exoticPackage`='" . ($fetch_package['packageID'] + 0) . "'");
		
		if (mysql_num_rows($result))
		{
			// mark owned items
			$owned = array();
			$q = mysql_query("SELECT DISTINCT `clothingID` FROM `avatar_clothing` WHERE `account`='" . protectSQL($fetch_account['account']) . "'");
			while ($row = mysql_fetch_assoc($q))
				$owned[] = $row['clothingID'];
		
			// Prepare Variables
			$slotX = 0;
			
			// Draw Table
			echo "
					<table class='items'>";
			
			while ($fetch_items = mysql_fetch_assoc($result))
			{
				// Prepare Image to Show (based on gender)
				if ($fetch_avatar['gender'] == "male" && $fetch_items['used_by'] != "female")
					$gender = "male";
				elseif ($fetch_avatar['gender'] == "female" && $fetch_items['used_by'] != "male")
					$gender = "female";
				else
					$gender = $fetch_items['used_by'];
				
				if ($slotX % 3 == 0)
					echo "
						<tr>";
				
				// Draw the Image
				$files = scandir("avatars/" . $fetch_items['position'] . "/" . $fetch_items['clothing']);
				foreach ($files as $file)
				{
					if (strpos($file, "_" . $gender . ".png"))
					{
						$color = str_replace("_" . $gender . ".png", "", $file);
						
						echo "
							<td style='vertical-align:bottom;'>
								<a href=\"javascript: review_item('" . $fetch_items['clothingID'] . "', '" . $color . "');\"><img src='avatars/" . $fetch_items['position'] . "/" . $fetch_items['clothing'] . "/" . $file . "' style='max-height:none;'/></a><br/><br/>
								<a onclick='return confirm(\"Are you sure you want to purchase " . $fetch_items['clothing'] . "?\");' id='item" . $fetch_package['id'] . "' href='exotic_packages.php?package=" . $fetch_package['id'] . "&item=" . $fetch_items['clothingID'] . "'>Get 1 " . $fetch_items['clothing'] . "</a>" . ((in_array($fetch_items['clothingID'], $owned)) ? " &bull;" : "") . "
							</td>";
						
						break;
					}
				}
				
				
				if ($slotX % 3 == 2)
					echo "
						</tr>";
				$slotX++;
			}
			
			if ($slotX % 3 > 0)
				echo "
						</tr>";
			
			echo "
					</table>";
		}
?>

					<script type='text/javascript'>
						function review_item(id, color)
						{
							window.open("preview_avi.php?clothingID=" + id + "&recolor=" + String(color), "PreviewAvatar", "width=622,height=500,toolbar=no,location=no,directories=no,status=no,menubar=no,copyhistory=no,scrollbars=yes");
						}
					</script>
				</div>
			</div>
<?php
	require("incAVA/footer.php");
?>