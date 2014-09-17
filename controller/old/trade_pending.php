<?php
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");
	define("EXIST", "own");
	require("fanctions/check_and_draw.php");
	require("fanctions/func_trade.php");
	
	// accept trade
	if (isset($_POST['accept']) && isset($_POST['tradeid']) && is_numeric($_POST['tradeid']))
	{
		accept_trade($_POST['tradeid']);
	}
	
	// cancel trade
	elseif (isset($_POST['cancel']) && isset($_POST['tradeid']) && is_numeric($_POST['tradeid']))
	{
		cancel_trade($_POST['tradeid']);
	}

	// update trade
	elseif (isset($_POST['tradeid']) && is_numeric($_POST['tradeid']) && isset($_POST['auro']) && isset($_POST['credits']) && is_numeric($_POST['auro'] + 0) && is_numeric($_POST['credits'] + 0))
	{
		if (!isset($_POST['items']))
			$_POST['items'] = array();
		if (!isset($_POST['packages']))
			$_POST['packages'] = array();
			
		// check if trade exists
		$q = "SELECT `account_1` FROM `trading_system` WHERE `id`='" . ($_POST['tradeid'] + 0) . "' AND `account_2`='" . protectSQL($fetch_account['account']) . "' LIMIT 1";
		$res = mysql_query($q);
		if ($fetch_trade = mysql_fetch_assoc($res))
			$messages = array_merge($messages, check_and_do($fetch_trade['account_1'], $_POST['auro'], $_POST['credits'], $_POST['message'], $_POST['items'], $_POST['packages'], $_POST['tradeid']));
	}
	
	$pagetitle = "Pending Trades";
	require("incAVA/header.php");
	
	$trades = array("byself_require" => array(), "byother_require" => array(), "byself_wait" => array(), "byother_wait" => array());
	
	// sort trades into groups
	$q = "SELECT `id`, `account_1`, `account_2`, `approved_2` FROM `trading_system` WHERE `account_1`='" . protectSQL($fetch_account['account']) . "' OR `account_2`='" . protectSQL($fetch_account['account']) . "'";
	$res = mysql_query($q);
	$num_rows = mysql_num_rows($res);
	while ($fetch_pending = mysql_fetch_assoc($res))
	{
		// initiated by you
		if ($fetch_pending['account_1'] == $fetch_account['account'])
		{
			// partner has updated
			if ($fetch_pending['approved_2'] == "yes")
				$trades['byself_require'][] = $fetch_pending;
			// awaiting partner's update
			else
				$trades['byself_wait'][] = $fetch_pending;
		}
		// initiated by someone else
		else
		{
			// you have updated
			if ($fetch_pending['approved_2'] != "yes")
				$trades['byother_require'][] = $fetch_pending;
			// awaiting completion by trade starter
			else
				$trades['byother_wait'][] = $fetch_pending;
		}
	}
	
	foreach ($trades['byother_require'] as $trade)
	{
		echo "
			<div class='category-container'>
				<div class='details-header'>
					Update Trade with " . $trade['account_1'] . "
				</div>
				<div class='details-body'>
					<form id='trade" . $trade['id'] . "' method='post'>
						<input type='hidden' name='tradeid' value='" . $trade['id'] . "'/>
						<table class='tradetable' style='text-align:left;'>
							<tr>
								<th colspan='3'>" . getdata_trade($trade['id'], 1) . "<br/></th>
							</tr>
							<tr>
								<td><br/><b>Recipient:</b></td>
								<td><br/>" . $trade['account_1'] . "</td>
								<td rowspan='4'><br/>";
								
		$bylayer = array();
		$q = "SELECT `id`, `clothingID` FROM `avatar_clothing` WHERE `avatar_clothing`.`account`='" . protectSQL($fetch_account['account']) . "' AND `avatar_clothing`.`in_trade`='0'";
		$res = mysql_query($q);
		$fetch_items = array();
		while ($fetch = mysql_fetch_assoc($res))
			$fetch_items[$fetch['id']] = fetch_item_details($fetch['clothingID']);
		uasort($fetch_items, "order_item_details");
		foreach ($fetch_items as $key => $fetch)
			$bylayer[$fetch['position']][] = "<input type='checkbox' onclick='fixvisibility();' name='items[]' value='" . $key . "'" . (isset($_POST['items']) && in_array($key, $_POST['items']) && $trade['id'] == $_POST['tradeid'] ? " checked" : "") . "/> " . $fetch['clothing'] . " (<a href='shop_search.php?name=" . $fetch['clothing'] . "&layer_" . $fetch['position'] . "=on&owned=yes&submit=Search' target='_blank'>?</a>)";
		echo "
										";
		$keys = array_keys($bylayer);
		sort($keys);
		foreach ($keys as $key)
			echo "<input type='button' onclick='showme(\"#layer_" . $key . $trade['id'] . "\");' value='" . $key . "'/>";
		
		/*$q = "SELECT `exotic_packages_owned`.`id`, `exotic_packages`.`title` FROM `exotic_packages_owned` INNER JOIN `exotic_packages` ON `exotic_packages_owned`.`packageID`=`exotic_packages`.`id` WHERE `exotic_packages_owned`.`account`='" . protectSQL($fetch_account['account']) . "' AND `exotic_packages_owned`.`in_trade`='0' ORDER BY `exotic_packages_owned`.`packageID` DESC";
		$res = mysql_query($q);
		if (mysql_num_rows($res) > 0)
			echo "<input type='button' onclick='showme(\"#packages" . $trade['id'] . "\");' value='Exotic Packages'/>";*/
			
		echo "
								</td>
							</tr>
							<tr>
								<td><b>Auro:</b></td>
								<td><input type='text' name='auro' maxlength='6' value='" . (isset($_POST['auro']) && $trade['id'] == $_POST['tradeid'] ? $_POST['auro'] : "0") . "'/></td>
							</tr>
							<tr>
								<td><b>Credits:</b></td>
								<td><input type='text' name='credits' maxlength='3' value='" . (isset($_POST['credits']) && $trade['id'] == $_POST['tradeid'] ? $_POST['credits'] : "0") . "'/></td>
							</tr>
							<tr>
								<td><b>Message:</b><br/>(optional)<span id='counter" . $trade['id'] . "'></span></td>
								<td><textarea name='message' rows='5' onkeyup='check_length(\"message\", \"counter" . $trade['id'] . "\", \"255\", \"" . $trade['id'] . "\");'>" . (isset($_POST['message']) && $trade['id'] == $_POST['tradeid'] ? $_POST['message'] : "") . "</textarea></td>
							</tr>
						</table>
						<br/>";
						
		foreach ($keys as $key)
		{
			echo "
							<div id='layer_" . $key . $trade['id'] . "' class='tradebox'><b>" . $key . ":</b><a class='close' href='#' onclick='showme(\"#layer_" . $key . "\"); return false;'>&#10006;</a>";
			foreach ($bylayer[$key] as $val)
				echo "<br/>" . $val;
			echo "</div>";
		}
		
		/*if (mysql_num_rows($res) > 0)
		{
			echo "
							<div id='packages" . $trade['id'] . "' class='tradebox'><b>Exotic Packages:</b><a class='close' href='#' onclick='showme(\"#packages\"); return false;'>&#10006;</a>";
			while ($fetch = mysql_fetch_assoc($res))
			{
				echo "<br/><input type='checkbox' name='packages[]' onclick='fixvisibility();' value='" . $fetch['id'] . "'" . (isset($_POST['packages']) && in_array($fetch['id'], $_POST['packages']) && $trade['id'] == $_POST['tradeid'] ? " checked" : "") . "/> " . $fetch['title'];
			}
			echo "</div>";
		}*/
		
		echo "
						<div style='clear:both;'></div><br/>
						<input type='button' value='Update Trade' onclick='fixvisibility(); confirmlist(" . $trade['id'] . ");'/> OR <input type='submit' name='cancel' value='Cancel Trade' onclick='return confirm(\"Are you sure you want to cancel this trade?\");'/>
					</form>
				</div>
			</div>";
	}
	
	if ($trades['byself_require'] != array() || $trades['byother_wait'] != array() || $trades['byself_wait'] != array())
	{
		echo "
			<div class='category-container'>
				<div class='details-header'>
					Complete / Cancel Trade
				</div>
				<div class='details-body'>
					<table class='alternate_without_th' style='text-align:left;'>";
		foreach ($trades['byself_require'] as $trade)
			echo "
						<tr>
							<td style='vertical-align:top;'>" . getdata_trade($trade['id'], 1) . "</td>
							<td style='vertical-align:top;'>" . getdata_trade($trade['id'], 2) . "</td>
							<td>&nbsp;</td>
							<td>
								<form id='trade" . $trade['id'] . "' method='post'>
									<input type='hidden' name='tradeid' value='" . $trade['id'] . "'/>
									<input type='submit' name='cancel' value='Cancel Trade' onclick='return confirm(\"Are you sure you want to cancel this trade?\");'/>
									OR
									<input type='submit' name='accept' value='Accept Trade' onclick='return confirm(\"Are you sure you want to accept this trade?\");'/>
								</form>
							</td>
						</tr>";
		foreach ($trades['byother_wait'] as $trade)
			echo "
						<tr>
							<td style='vertical-align:top;'>" . getdata_trade($trade['id'], 1) . "</td>
							<td style='vertical-align:top;'>" . getdata_trade($trade['id'], 2) . "</td>
							<td>" . $trade['account_1'] . " has not accepted the trade yet.</td>
							<td>
								<form id='trade" . $trade['id'] . "' method='post'>
									<input type='hidden' name='tradeid' value='" . $trade['id'] . "'/>
									<input type='submit' name='cancel' value='Cancel Trade' onclick='return confirm(\"Are you sure you want to cancel this trade?\");'/>
								</form>
							</td>
						</tr>";
		foreach ($trades['byself_wait'] as $trade)
			echo "
						<tr>
							<td style='vertical-align:top;'>" . getdata_trade($trade['id'], 1) . "</td>
							<td style='vertical-align:top;'><b>" . $trade['account_2'] . "</b></td>
							<td>" . $trade['account_2'] . " has not updated the trade yet.</td>
							<td>
								<form id='trade" . $trade['id'] . "' method='post'>
									<input type='hidden' name='tradeid' value='" . $trade['id'] . "'/>
									<input type='submit' name='cancel' value='Cancel Trade' onclick='return confirm(\"Are you sure you want to cancel this trade?\");'/>
								</form>
							</td>
						</tr>";
		echo "
					</table>
				</div>
			</div>";
	}
	
	// no pending trades of any sort
	if ($num_rows == 0)
	{
		echo "
			<div class='category-container'>
				<div class='details-header'>
					Pending Trades
				</div>
				<div class='details-body'>
					You currently have no pending trades.
				</div>
			</div>";
	}
?>

			<script type='text/javascript'>
				function check_length(el, display_in, out_of, tradeid)
				{
					var l = $("#trade" + tradeid + " textarea[name=" + el + "]").val().length;
					$("#" + display_in).html("<br/><br/>" + l + "/" + out_of);
				}					
				function showme(block)
				{
					if ($(block).css("display") == "none")
						$(block).css("display", "block");
					else
					{
						$(block).css("display", "none");
						$("input:checked").each(
						function()
						{
							$(this).parent().css("display", "block");
						});
					}
				}
				function fixvisibility()
				{
					$("input:checked").each(
						function()
						{
							$(this).parent().css("display", "block");
						});
				}
				function confirmlist(tradeid)
				{
					var inc = "";
					var auro = parseInt($("#trade" + tradeid + " input[name=auro]").val());
					if (!isNaN(auro) && auro > 0)
						inc += "\n" + auro + " Auro";
					var credits = parseInt($("#trade" + tradeid + " input[name=credits]").val());
					if (!isNaN(credits) && credits > 0)
						inc += "\n" + credits + " Credits";
					/*var packages = $("#packages" + tradeid + " input:checked").length;
					if (($("#trade" + tradeid + " input:checked").length - packages) > 0)
						inc += "\n" + ($("#trade" + tradeid + " input:checked").length - packages) + " Items";
					if (packages > 0)
						inc += "\n" + packages + " Exotic Packages";*/
					var answer = confirm("Are you sure you want to update this trade? You will send:\n" + inc);
					if (answer)
						$("#trade" + tradeid).submit();
				}
				fixvisibility();
			</script>
<?php
	require("incAVA/footer.php");
?>