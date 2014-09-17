<?php
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");
	require("fanctions/func_trade.php");
	require("fanctions/func_fuzzy_time.php");
	
	$pagetitle = "Trade Log";
	require("incAVA/header.php");
	
	echo "
			<div class='category-container'>
				<div class='details-header'>
					Trade Log
				</div>
				<div class='details-body'>
					<table class='alternate_without_th' style='text-align:left;'>";
	
	$q = "SELECT `id`, `account_1`, `account_2`, `approved_1`, `approved_2`, `timestamp` FROM `records_trades` WHERE `account_1`='" . protectSQL($fetch_account['account']) . "' OR `account_2`='" . protectSQL($fetch_account['account']) . "' ORDER BY `timestamp` DESC LIMIT 20";
	$res = mysql_query($q);
	while ($fetch = mysql_fetch_assoc($res))
	{
		echo "
						<tr>
							<td style='vertical-align:top;'>" . getdata_trade($fetch['id'], 1, true) . "</td>
							<td style='vertical-align:top;'>" . getdata_trade($fetch['id'], 2, true) . "</td>
							<td>
								" . ($fetch['approved_1'] == "yes" && $fetch['approved_2'] == "yes" ? "Completed" : ($fetch['approved_1'] == "no" ? "Cancelled by " . $fetch['account_1'] : "Cancelled by " . $fetch['account_2'])) . "
							</td>
							<td>
								" . fuzzy_time($fetch['timestamp']) . "
							</td>
						</tr>";
	}
	
	echo "
					</table>
				</div>
			</div>";
			
	require("incAVA/footer.php");
?>