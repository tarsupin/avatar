<?php
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");
	require("fanctions/func_fuzzy_time.php");
	
	$pagetitle = "Gift Log";
	require("incAVA/header.php");
	
	echo "
			<div class='category-container'>
				<div class='details-header'>
					Gift Log
				</div>
				<div class='details-body'>
					<table class='alternate_with_th'>
						<tr>
							<th>From</th>
							<th>To</th>
							<th>Gift</th>
							<th>Time</th>
						</tr>";
	
	$q = "SELECT `from_user`, `to_user`, `item_type`, `package_type`, `anonymous`, `timestamp` FROM `records_gifting` WHERE `from_user`='" . protectSQL($fetch_account['account']) . "' OR `to_user`='" . protectSQL($fetch_account['account']) . "' ORDER BY `id` DESC LIMIT 20";
	$res = mysql_query($q);
	while ($fetch = mysql_fetch_assoc($res))
	{
		// only show from new system
		if ($fetch['anonymous'] != 2)
		{
			$whatisit = "";
			if ($fetch['item_type'] > 0)
			{
				$q = mysql_query("SELECT `clothing` FROM `clothing_images` WHERE `clothingID`='" . ($fetch['item_type'] + 0) . "' LIMIT 1");
				if ($fetch2 = mysql_fetch_assoc($q))
					$whatisit = $fetch2['clothing'];
			}
			elseif ($fetch['package_type'] > 0)
			{
				$q2 = "SELECT `title` FROM `exotic_packages` WHERE `id`='" . ($fetch['package_type'] + 0) . "' LIMIT 1";
				$res2 = mysql_query($q2);
				if ($fetch2 = mysql_fetch_assoc($res2))
					$whatisit = $fetch2['title'];
			}
		
			if ($whatisit != "")
				echo "
						<tr>
							<td>" . ($fetch['anonymous'] == 0 ? $fetch['from_user'] : "anonymous") . "</td>
							<td>" . $fetch['to_user'] . "</td>
							<td>" . $whatisit . "</td>
							<td>" . fuzzy_time($fetch['timestamp']) . "</td>
						</tr>";
		}
	}
	
	echo "
					</table>
				</div>
			</div>";
			
	require("incAVA/footer.php");
?>