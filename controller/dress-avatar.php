<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Return home if you don't have an avatar
if(!isset($avatarData['base']))
{
	header("Location: /create-avatar"); exit;
}

$outfitArray = AppOutfit::get(Me::$id, "default");

if(!$getLink = Link::clicked())
{
	$getLink = "";
}

// Equip an item
if(isset($_GET['equip']))
{
	$_GET['equip'] = (int) $_GET['equip'];
	
	$itemData = AppAvatar::itemData($_GET['equip']);

	// If a color was not provided (or is invalid), choose the first one
	$colors = AppAvatar::getItemColors($itemData['position'], $itemData['title']);

	if(!isset($_GET['color']) or !in_array($_GET['color'], $colors))
	{
		$_GET['color'] = $colors[0];
	}
		
	// Equip your item
	$outfitArray = AppOutfit::equip($outfitArray, $_GET['equip'], $avatarData['gender'], $_GET['color'], "default");
	
	// Update your avatar's image
	$aviData = Avatar::imageData(Me::$id);
	AppOutfit::draw($avatarData['base'], $avatarData['gender'], $outfitArray, APP_PATH . '/' . $aviData['image_directory'] . '/' . $aviData['main_directory'] . '/' . $aviData['second_directory'] . '/' . $aviData['filename']);
	
	// Save the changes
	AppOutfit::save(Me::$id, "default", $outfitArray);
}

// Unequip an Item
else if(isset($_GET['unequip']))
{
	$outfitArray = AppOutfit::unequip($outfitArray, (int) $_GET['unequip']);
	
	// Update your avatar's image
	$aviData = Avatar::imageData(Me::$id);
	AppOutfit::draw($avatarData['base'], $avatarData['gender'], $outfitArray, APP_PATH . '/' . $aviData['image_directory'] . '/' . $aviData['main_directory'] . '/' . $aviData['second_directory'] . '/' . $aviData['filename']);
	
	// Save the outfit
	AppOutfit::save(Me::$id, "default", $outfitArray);
}

// If we're unequipping everything
else if($getLink == "unequipAll")
{
	$outfitArray = AppOutfit::unequipAll();
	
	// Update your avatar's image
	$aviData = Avatar::imageData(Me::$id);
	AppOutfit::draw($avatarData['base'], $avatarData['gender'], $outfitArray, APP_PATH . '/' . $aviData['image_directory'] . '/' . $aviData['main_directory'] . '/' . $aviData['second_directory'] . '/' . $aviData['filename']);
			
	// Save the outfit
	AppOutfit::save(Me::$id, "default", $outfitArray);
}

// If we're moving something left
else if(isset($_GET['left']))
{
	$outfitArray = AppOutfit::move($outfitArray, $_GET['left'], "left");
	
	// Update your avatar's image
	$aviData = Avatar::imageData(Me::$id);
	AppOutfit::draw($avatarData['base'], $avatarData['gender'], $outfitArray, APP_PATH . '/' . $aviData['image_directory'] . '/' . $aviData['main_directory'] . '/' . $aviData['second_directory'] . '/' . $aviData['filename']);

	// Save the outfit
	AppOutfit::save(Me::$id, "default", $outfitArray);
}

// If we're moving something right
else if(isset($_GET['right']))
{
	$outfitArray = AppOutfit::move($outfitArray, $_GET['right'], "right");
	
	// Update your avatar's image
	$aviData = Avatar::imageData(Me::$id);
	AppOutfit::draw($avatarData['base'], $avatarData['gender'], $outfitArray, APP_PATH . '/' . $aviData['image_directory'] . '/' . $aviData['main_directory'] . '/' . $aviData['second_directory'] . '/' . $aviData['filename']);

	// Save the outfit
	AppOutfit::save(Me::$id, "default", $outfitArray);
}

else if($getLink == "replace")
{
	$outfitArray2 = AppOutfit::get(Me::$id, "preview");
	
	$outfitArray = AppOutfit::unequipAll();
	foreach($outfitArray2 as $key => $oa)
	{
		if($key < 0)
		{
			$outfitArray = AppOutfit::equip($outfitArray, $oa[0], $avatarData['gender'], $oa[1], "default", true);
		}
		else
		{
			$outfitArray = AppOutfit::equip($outfitArray, $oa[0], $avatarData['gender'], $oa[1], "default");
		}
	}
	
	// Update your avatar's image
	$aviData = Avatar::imageData(Me::$id);
	AppOutfit::draw($avatarData['base'], $avatarData['gender'], $outfitArray, APP_PATH . '/' . $aviData['image_directory'] . '/' . $aviData['main_directory'] . '/' . $aviData['second_directory'] . '/' . $aviData['filename']);
	
	AppOutfit::save(Me::$id, "default", $outfitArray);
}

else if(isset($_POST['order']))
{
	// reformat code
	$order = explode(",", $_POST['order']);
	$outfitArray = array();
	foreach ($order as $o)
	{
		$outfitArray[] = explode("#", $o);
	}

	// resort it all
	$outfitArray = AppOutfit::sortAll($outfitArray, $avatarData['gender'], "default");
	
	// Update your avatar's image
	$aviData = Avatar::imageData(Me::$id);
	AppOutfit::draw($avatarData['base'], $avatarData['gender'], $outfitArray, APP_PATH . '/' . $aviData['image_directory'] . '/' . $aviData['main_directory'] . '/' . $aviData['second_directory'] . '/' . $aviData['filename']);
	
	// Save the outfit
	AppOutfit::save(Me::$id, "default", $outfitArray);
}

// Add links to nav panel
WidgetLoader::add("SidePanel", 40, '
	<div class="panel-links" style="text-align:center;">
		<a href="javascript:review_item(0);">Open Preview Window</a><br/>
		<a href="/dress-avatar?replace&' . Link::prepare("replace") . '">Replace with Preview Image</a><br/>
		<a href="/dress-avatar?unequipAll&' . Link::prepare("unequipAll") . '">Unequip All</a>' . (isset($_GET['position']) ? '<br/>
		<a href="/shop-search?submit=Search&' . $_GET['position'] . '=on&gender=' . $avatarData['gender'] . 'ab">Shop Search</a>' : "") . '
	</div>');

// Get the layers you can search between
$positions = AppAvatar::getInvPositions(Me::$id);

// Set page title
$config['pageTitle'] = "Dressing Room";
if(isset($_GET['position']))
{
	$config['pageTitle'] .= " > " . $_GET['position'];
}

// List of categories to pick from
$_GET['position'] = (!isset($_GET['position']) ? "" : $_GET['position']);

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Add Javascript to header
Metadata::addHeader('
<!-- javascript -->
<script src="/assets/scripts/jquery.js" type="text/javascript" charset="utf-8"></script>
<script src="/assets/scripts/jquery-ui.js" type="text/javascript" charset="utf-8"></script>
<script src="/assets/scripts/review-switch.js" type="text/javascript" charset="utf-8"></script>

<!-- javascript for touch devices, source: http://touchpunch.furf.com/ -->
<script src="/assets/scripts/jquery.ui.touch-punch.min.js" type="text/javascript" charset="utf-8"></script>
');

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

echo '
<div id="panel-right"></div>
<div id="content">' . Alert::display() . '
	<h2>Dressing Room</h2>';

	// Clothes currently worn
	echo '
	<form id="sortable" action="/dress-avatar' . ($_GET['position'] != "" ? "?position=" . $_GET['position'] : "") . '" method="post">
	<textarea id="order" name="order" style="display:none;"></textarea>
	<ul id="equipped" class="dragndrop">';

	$outfitArray[0] = array(0, $avatarData['base']);
	ksort($outfitArray);

	$outfitArray = array_reverse($outfitArray);

	// Gather your list of equipped items
	foreach($outfitArray as $pos => $item)
	{
		// Get Items
		if($item[0] != 0)
		{
			$eItem = Database::selectOne("SELECT id, title, position FROM items WHERE id=?", array($item[0]));
			
			// Recognize Integers
			$eItem['id'] = (int) $eItem['id'];
			
			$eItem['color'] = $item[1];
			
			echo '
		<li id="worn_' . $eItem['id'] . '">
			<div><img id="img_' . $eItem['id'] . '" src="/avatar_items/' . $eItem['position'] . '/' . $eItem['title'] . '/' . $eItem['color'] . '_' . $avatarData['gender_full'] . '.png" title="' . $eItem['title'] . '"/></div>
			<a class="close" href="/dress-avatar?position=' . $_GET['position'] . '&unequip=' . $eItem['id'] . '">&#10006;</a>
			<select id="color_' . $eItem['id'] . '">';
			
			$colors = AppAvatar::getItemColors($eItem['position'], $eItem['title']);
			
			foreach($colors as $color)
			{
				echo '
				<option value="' . $color . '"' . ($color == $item[1] ? " selected" : "") . '>' . $color . '</option>';
			}
			
			echo '
			</select>';

			if(isset($outfitArray[$pos - 1]) && $eItem['position'] != "skin")
			{
				echo '
			<a class="left" href="/dress-avatar?position=' . $_GET['position'] . '&left=' . $eItem['id'] . '">&lt;</a>';
			}
			
			if(isset($outfitArray[$pos + 1]) && $eItem['position'] != "skin")
			{
				echo '
			<a class="right" href="/dress-avatar?position=' . $_GET['position'] . '&right=' . $eItem['id'] . '">&gt;</a>';
			}
			
				echo '
		</li>';
		}
		else
		{
			echo '
		<li id="worn_0">
			<div style="line-height:50px;">Base</div>
			<select id="color_0" disabled="disabled"><option value="' . ucfirst($avatarData['base']) . '">' . ucfirst($avatarData['base']) . '</option></select>
		</li>';
		}
	}

	echo '
	</ul>
	</form>';
	
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
	
	if($_GET['position'] != "")
	{
		$itemlist = array();
		
		// Show the items within the category selected
		$userItems = AppAvatar::getUserItems(Me::$id, $_GET['position'], $avatarData['gender_full']);
		
		// If you have no items, say so
		if(count($userItems) == 0)
		{
			echo "<p>You have no items.</p>";
		}
		
		foreach($userItems as $item)
		{
			$itemlist[] = $item['id'];
			
			$colors = AppAvatar::getItemColors($_GET['position'], $item['title']);
			
			// Display the item block
			echo '
			<div class="item_block">
				<a id="link_' . $item['id'] . '" href="/dress-avatar?position=' . $_GET['position'] . '&equip=' . $item['id'] . '"><img id="pic_' . $item['id'] . '" src="/avatar_items/' . $_GET['position'] . '/' . $item['title'] . '/default_' . $avatarData['gender_full'] . '.png" /></a>
				<br />' . $item['title'] . ($item['count'] > 1 ? ' <span style="color:#fb7c7c;">(' . $item['count'] . ')</span>' : "") . '
				<select id="item_' . $item['id'] . '" onChange="switch_item_inventory(\'' . $item['id'] . '\', \'' . $_GET['position'] . '\', \'' . $item['title'] . '\', \'' . $avatarData['gender_full'] . '\');">';
			
			foreach($colors as $color)
			{
				echo '
					<option value="' . $color . '">' . $color . '</option>';
			}
			
			echo '
				</select>
				<br /><a href="javascript:review_item(' . $item['id'] . ');">Preview</a> | <a href="/sell-item/' . $item['id'] . '">Sell</a>
			</div>';
		}
		
		// Show the items of the other gender within the category selected
		$userItemsOther = AppAvatar::getUserItems(Me::$id, $_GET['position'], ($avatarData['gender_full'] == "male" ? "female" : "male"));
		foreach($userItemsOther as $key => $item)
		{
			// avoid duplicates
			if(in_array($item['id'], $itemlist))
			{
				unset($userItemsOther[$key]);
			}
		}
		
		foreach($userItemsOther as $item)
		{
			$itemlist[] = $item['id'];
			
			$colors = AppAvatar::getItemColors($_GET['position'], $item['title']);
			
			// Display the item block
			echo '
			<div class="item_block opaque">
				<a id="link_' . $item['id'] . '" href="/dress-avatar?position=' . $_GET['position'] . '&equip=' . $item['id'] . '"><img id="pic_' . $item['id'] . '" src="/avatar_items/' . $_GET['position'] . '/' . $item['title'] . '/default_' . ($avatarData['gender_full'] == "male" ? "female" : "male") . '.png" /></a>
				<br />' . $item['title'] . ($item['count'] > 1 ? ' <span style="color:#fb7c7c;">(' . $item['count'] . ')</span>' : "") . '
				<select id="item_' . $item['id'] . '" onChange="switch_item_inventory(\'' . $item['id'] . '\', \'' . $_GET['position'] . '\', \'' . $item['title'] . '\', \'' . ($avatarData['gender_full'] == "male" ? "female" : "male") . '\');">';
			
			foreach($colors as $color)
			{
				echo '
					<option value="' . $color . '">' . $color . '</option>';
			}
			
			echo '
				</select>
				<br /><a href="javascript:review_item(' . $item['id'] . ');">Preview</a> | <a href="/sell-item/' . $item['id'] . '">Sell</a>
			</div>';
		}
	}
	
	echo '
</div>';

?>

<script src="/assets/scripts/reorder.js" type="text/javascript" charset="utf-8"></script>

<script>
function switch_item_inventory(id, layer, name, gender)
{
	$("#pic_" + id).attr("src", "/avatar_items/" + layer + "/" + name + "/" + $("#item_" + id).val() + "_" + gender + ".png");
	$("#link_" + id).attr("href", "/dress-avatar?<?php echo ($_GET['position'] != "" ? 'position=' . $_GET['position'] . '&' : ''); ?>equip=" + id + "&color=" + $("#item_" + id).val());
}
</script>

<?php

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
