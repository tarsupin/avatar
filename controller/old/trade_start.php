<?php
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");
	define("EXIST", "own");
	require("fanctions/check_and_draw.php");
	require("fanctions/func_trade.php");

	// start trade
	if (isset($_POST['tradepartner']) && $_POST['tradepartner'] != "" && $_POST['tradepartner'] != $fetch_account['account'] && isset($_POST['auro']) && isset($_POST['credits']) && is_numeric($_POST['auro'] + 0) && is_numeric($_POST['credits'] + 0))
	{
		if (!isset($_POST['items']))
			$_POST['items'] = array();
		if (!isset($_POST['packages']))
			$_POST['packages'] = array();
		$messages = array_merge($messages, check_and_do($_POST['tradepartner'], $_POST['auro'], $_POST['credits'], $_POST['message'], $_POST['items'], $_POST['packages'], "start"));
	}
	
	$pagetitle = "Start Trade";
	require("incAVA/header.php");
?>
			<div class='category-container'>
				<div class='details-header'>
					Start Trade
				</div>
				<div class='details-body'>
					<form id='trade' method='post'>
						<table class='tradetable' style='text-align:left;'>
							<tr>
								<td><b>Recipient:</b></td>
								<td><input type='text' name='tradepartner' maxlength='20'<?php if (isset($_POST['tradepartner'])) echo " value='" . $_POST['tradepartner'] . "'"; ?>/></td>
								<td rowspan='4'>
<?php
	$bylayer = array();
	$q = "SELECT `id`, `clothingID` FROM `avatar_clothing` WHERE `avatar_clothing`.`account`='" . protectSQL($fetch_account['account']) . "' AND `avatar_clothing`.`in_trade`='0'";
	$res = mysql_query($q);
	$fetch_items = array();
	while ($fetch = mysql_fetch_assoc($res))
		$fetch_items[$fetch['id']] = fetch_item_details($fetch['clothingID']);
	uasort($fetch_items, "order_item_details");
	foreach ($fetch_items as $key => $fetch)
		$bylayer[$fetch['position']][] = "<input type='checkbox' onclick='fixvisibility();' name='items[]' value='" . $key . "'" . ((isset($_GET['add']) && $_GET['add'] == $key) || (isset($_POST['items']) && in_array($key, $_POST['items'])) ? " checked" : "") . "/> " . $fetch['clothing'] . " (<a href='shop_search.php?name=" . $fetch['clothing'] . "&layer_" . $fetch['position'] . "=on&owned=yes&submit=Search' target='_blank'>?</a>)";
	echo "
									";
	$keys = array_keys($bylayer);
	sort($keys);
	foreach ($keys as $key)
		echo "<input type='button' onclick='showme(\"#layer_" . $key . "\");' value='" . $key . "'/>";
	
	/*$q = "SELECT `exotic_packages_owned`.`id`, `exotic_packages`.`title` FROM `exotic_packages_owned` INNER JOIN `exotic_packages` ON `exotic_packages_owned`.`packageID`=`exotic_packages`.`id` WHERE `exotic_packages_owned`.`account`='" . protectSQL($fetch_account['account']) . "' AND `exotic_packages_owned`.`in_trade`='0' ORDER BY `exotic_packages_owned`.`packageID` DESC";
	$res = mysql_query($q);
	if (mysql_num_rows($res) > 0)
		echo "<input type='button' onclick='showme(\"#packages\");' value='Exotic Packages'/>";*/
?>								

								</td>
							</tr>
							<tr>
								<td><b>Auro:</b></td>
								<td><input type='text' name='auro' maxlength='6' value='<?php echo (isset($_POST['auro']) ? $_POST['auro'] : "0"); ?>'/></td>
							</tr>
							<tr>
								<td><b>Credits:</b></td>
								<td><input type='text' name='credits' maxlength='3' value='<?php echo (isset($_POST['credits']) ? $_POST['credits'] : "0"); ?>'/></td>
							</tr>
							<tr>
								<td><b>Message:</b><br/>(optional)<span id='counter'></span></td>
								<td><textarea name='message' rows='5' onkeyup='check_length("message", "counter", "255");'><?php if (isset($_POST['message'])) echo $_POST['message']; ?></textarea></td>
							</tr>
						</table>
						<br/>
<?php
	foreach ($keys as $key)
	{
		echo "
						<div id='layer_" . $key . "' class='tradebox'><b>" . $key . ":</b><a class='close' href='#' onclick='showme(\"#layer_" . $key . "\"); return false;'>&#10006;</a>";
		foreach ($bylayer[$key] as $val)
			echo "<br/>" . $val;
		echo "</div>";
	}
	
	/*if (mysql_num_rows($res) > 0)
	{
		echo "
						<div id='packages' class='tradebox'><b>Exotic Packages:</b><a class='close' href='#' onclick='showme(\"#packages\"); return false;'>&#10006;</a>";
		while ($fetch = mysql_fetch_assoc($res))
		{
			echo "<br/><input type='checkbox' name='packages[]' onclick='fixvisibility();' value='" . $fetch['id'] . "'" . ((isset($_GET['add_package']) && $_GET['add_package'] == $fetch['id']) || (isset($_POST['packages']) && in_array($fetch['id'], $_POST['packages'])) ? " checked" : "") . "/> " . $fetch['title'];
		}
		echo "</div>";
	}*/
?>

						<div style='clear:both;'></div><br/>
						<input type='button' value='Start Trade' onclick='fixvisibility(); confirmlist();'/>
					</form>	
					<script type='text/javascript'>
						function check_length(el, display_in, out_of)
						{
							var l = $("textarea[name=" + el + "]").val().length;
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
						function confirmlist()
						{
							var inc = "";
							var auro = parseInt($("input[name=auro]").val());
							if (!isNaN(auro) && auro > 0)
								inc += "\n" + auro + " Auro";
							var credits = parseInt($("input[name=credits]").val());
							if (!isNaN(credits) && credits > 0)
								inc += "\n" + credits + " Credits";
							/*var packages = $("#packages input:checked").length;
							if (($("input:checked").length - packages) > 0)
								inc += "\n" + ($("input:checked").length - packages) + " Items";
							if (packages > 0)
								inc += "\n" + packages + " Exotic Packages";*/
							var answer = confirm("Are you sure you want to start this trade with " + $("input[name=tradepartner]").val() + "? It includes:\n" + inc);
							if (answer)
								$("#trade").submit();
						}
						fixvisibility();
					</script>
				</div>
			</div>
<?php
	require("incAVA/footer.php");
?>