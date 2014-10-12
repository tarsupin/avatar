<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Return home if you don't have an avatar
if(!isset($avatarData['base']))
{
	header("Location: /create-avatar"); exit;
}

// Equip an item
if(isset($_GET['equip']))
{
	$_GET['equip'] = (int) $_GET['equip'];
	
	$itemData = AppAvatar::itemData($_GET['equip']);
	
	// Check if you own the item
	if(AppAvatar::checkOwnItem(Me::$id, $_GET['equip']))
	{
		// If a color was not provided (or is invalid), choose the first one
		$colors = AppAvatar::getItemColors($itemData['position'], $itemData['title']);
		
		if(!isset($_GET['color']) or !in_array($_GET['color'], $colors))
		{
			$_GET['color'] = $colors[0];
		}
		
		// Equip your item
		AppAvatar::equip(Me::$id, $_GET['equip'], $_GET['color'], $itemData['min_order'], $itemData['max_order']);
		
		// Update your avatar's image
		AppAvatar::updateImage(Me::$id, $avatarData['base'], $avatarData['gender']);
	}
}

// Unequip an Item
else if(isset($_GET['unequip']))
{
	AppAvatar::unequip(Me::$id, (int) $_GET['unequip']);
	
	// Update your avatar's image
	AppAvatar::updateImage(Me::$id, $avatarData['base'], $avatarData['gender']);
}

// Reposition an item
else if(isset($_GET['moveItem']) && isset($_GET['to']))
{
	AppAvatar::sort(Me::$id, (int) $_GET['moveItem'], (int) $_GET['to']);
}

// List of categories to pick from
$_GET['position'] = (!isset($_GET['position']) ? "body" : $_GET['position']);

// Get the layers you can search between
$positions = AppAvatar::getInvPositions(Me::$id);

// Add Javascript to header
/*
Metadata::addHeader('
<!-- javascript -->
<script src="/assets/scripts/jquery.js" type="text/javascript" charset="utf-8"></script>
<script src="/assets/scripts/jquery-ui.js" type="text/javascript" charset="utf-8"></script>

<!-- javascript for touch devices, source: http://touchpunch.furf.com/ -->
<script src="/assets/scripts/jquery.ui.touch-punch.min.js" type="text/javascript" charset="utf-8"></script>');
*/

// Set page title
$config['pageTitle'] = "Dressing Room";

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

echo '
<style>
.item_block { display:inline-block; padding:15px; text-align:center; width:110px; }
.item_block select { width:110px; }
.item_block img { max-height:100px; max-width:80px; }
</style>';

echo '
<div id="panel-right"></div>
<div id="content">
	<h3>Dressing Room</h3>';
	
	// Clothes currently worn
	echo '
	<ul id="equipped" class="dragndrop">';
	
	// Gather your list of equipped items
	$count = 0;
	$equippedItems = AppAvatar::getEquippedItems(Me::$id, $avatarData['gender']);
	
	foreach($equippedItems as $eItem)
	{
		echo '
		<li id="worn_' . $eItem['id'] . '">
			<div><img id="itemImg_' . $eItem['id'] . '_eq" src="/avatar_items/' . $eItem['position'] . '/' . $eItem['title'] . '/' . $eItem['color'] . '_' . $avatarData['gender_full'] . '.png" title="' . $eItem['title'] . '"/></div>
			<a id="link_' . $eItem['id'] . '_eq" class="close" href="/dress-avatar?position=' . $eItem['position'] . '&unequip=' . $eItem['id'] . '">&#10006;</a>
			<select id="color_' . $eItem['id'] . '_eq" onchange="switch_color(' . $eItem['id'] . ', \'' . $eItem['position'] . '\', \'' . $eItem['title'] . '\', \'' . $avatarData['gender_full'] . '\', true)">';
		
		$colors = AppAvatar::getItemColors($eItem['position'], $eItem['title']);
		
		foreach($colors as $color)
		{
			echo '
			<option value="' . $color . '">' . $color . '</option>';
		}
		
		echo '
			</select>';
		
		if(isset($equippedItems[$count - 1]))
		{
			echo '
			<a class="left" href="/dress-avatar?position=' . $_GET['position'] . '&moveItem=' . $eItem['id'] . '&to=' . $equippedItems[$count - 1]['sort_order'] . '">&lt;</a>';
		}
		
		if(isset($equippedItems[$count + 1]))
		{
			echo '
			<a class="right" href="/dress-avatar?position=' . $_GET['position'] . '&moveItem=' . $eItem['id'] . '&to=' . $equippedItems[$count + 1]['sort_order'] . '">&gt;</a>';
		}
		
		echo '
		</li>';
		
		$count++;
	}
	
	echo '
	</ul>';
	
	// Show the layers you have access to
	echo '
	<style>
	.redlinks>a { background-color:#eeeeee; border-radius:8px; padding:5px; line-height:2.2em; }
	</style>
	<div class="redlinks">';
	
	foreach($positions as $pos)
	{
		echo '
		<a href="/dress-avatar?position=' . $pos . '">' . $pos . '</a>';
	}
	
	echo '
	</div>';
	
	// Show the items within the category selected
	$userItems = AppAvatar::getUserItems(Me::$id, $_GET['position'], $avatarData['gender_full']);
	
	// If you have no items, say so
	if(count($userItems) == 0)
	{
		echo "<p>You have no items.</p>";
	}
	
	foreach($userItems as $item)
	{
		$colors = AppAvatar::getItemColors($_GET['position'], $item['title']);
		
		// Display the item block
		echo '
		<div class="item_block">
			<a id="link_' . $item['id'] . '" href="/dress-avatar?position=' . $_GET['position'] . '&equip=' . $item['id'] . '"><img id="itemImg_' . $item['id'] . '" src="/avatar_items/' . $_GET['position'] . '/' . $item['title'] . '/default.png" /></a>
			<br />' . $item['title'] . '
			<select id="color_' . $item['id'] . '" name="color" onchange="switch_color(' . $item['id'] . ', \'' . $_GET['position'] . '\', \'' . $item['title'] . '\', \'' . $avatarData['gender_full'] . '\')">';
		
		foreach($colors as $color)
		{
			echo '
				<option value="' . $color . '">' . $color . '</option>';
		}
		
		echo '
			</select>
		</div>';
	}
	
	echo '
</div>';

?>

<script type="text/javascript">
function switch_color(itemID, position, title, gender, equipped = false)
{
	var eq = (equipped != false) ? "_eq" : "";
	
	var imgswap = document.getElementById("itemImg_" + itemID + eq);
	var colorswap = document.getElementById("color_" + itemID + eq);
	var linkswap = document.getElementById("link_" + itemID + eq);
	
	var color = colorswap.options[colorswap.selectedIndex].value;
	
	linkswap.href = "/dress-avatar?equip=" + itemID + "&color=" + color;
	imgswap.src = "/avatar_items/" + position + "/" + title + "/" + color + "_" + gender + ".png";
}
</script>

<?php

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
