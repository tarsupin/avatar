<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Return home if you don't have an avatar
if(!isset($avatarData['base']))
{
	header("Location: /create-avatar"); exit;
}

$outfitArray = AppOutfit::get(Me::$id, $avatarData['identification']);

if(!$getLink = Link::clicked())
{
	$getLink = "";
}

// Equip an item
if(isset($_GET['equip']))
{
	$_GET['equip'] = (int) $_GET['equip'];
	if(AppAvatar::checkOwnItem(Me::$id, $_GET['equip']))
	{
		$itemData = AppAvatar::itemData($_GET['equip']);

		// If a color was not provided (or is invalid), choose the first one
		$colors = AppAvatar::getItemColors($itemData['position'], $itemData['title'], $avatarData['gender']);

		if($colors != array())
		{
			if(!isset($_GET['color']) or !in_array($_GET['color'], $colors))
			{
				$_GET['color'] = $colors[0];
			}
				
			// Equip your item
			$outfitArray = AppOutfit::equip($outfitArray, $_GET['equip'], $avatarData['gender'], $_GET['color']);
			
			// Update your avatar's image
			$aviData = Avatar::imageData(Me::$id, $activeAvatar);
			AppOutfit::draw($avatarData['base'], $avatarData['gender'], $outfitArray, APP_PATH . '/' . $aviData['image_directory'] . '/' . $aviData['main_directory'] . '/' . $aviData['second_directory'] . '/' . $aviData['filename']);
			
			// Save the changes
			AppOutfit::save(Me::$id, $avatarData['identification'], $outfitArray);
		}
		else
		{
			Alert::error("No Color", "This item does not seem to exist for " . $avatarData['gender_full'] . " avatars.");
		}
	}
	else
	{
		$itemData = AppAvatar::itemData($_GET['equip'], "title");
		Alert::error($itemData['title'] . " Not Owned", "You do not own " . $itemData['title'] . ", so it cannot be equipped.");
	}
}

// Unequip an Item
else if(isset($_GET['unequip']))
{
	$outfitArray = AppOutfit::unequip($outfitArray, (int) $_GET['unequip']);
	
	// Update your avatar's image
	$aviData = Avatar::imageData(Me::$id, $activeAvatar);
	AppOutfit::draw($avatarData['base'], $avatarData['gender'], $outfitArray, APP_PATH . '/' . $aviData['image_directory'] . '/' . $aviData['main_directory'] . '/' . $aviData['second_directory'] . '/' . $aviData['filename']);
	
	// Save the outfit
	AppOutfit::save(Me::$id, $avatarData['identification'], $outfitArray);
}

// If we're unequipping everything
else if($getLink == "unequipAll")
{
	$outfitArray = AppOutfit::unequipAll();
	
	// Update your avatar's image
	$aviData = Avatar::imageData(Me::$id, $activeAvatar);
	AppOutfit::draw($avatarData['base'], $avatarData['gender'], $outfitArray, APP_PATH . '/' . $aviData['image_directory'] . '/' . $aviData['main_directory'] . '/' . $aviData['second_directory'] . '/' . $aviData['filename']);
			
	// Save the outfit
	AppOutfit::save(Me::$id, $avatarData['identification'], $outfitArray);
}

// If we're moving something left
else if(isset($_GET['left']))
{
	$outfitArray = AppOutfit::move($outfitArray, (int) $_GET['left'], "left");
	
	// Update your avatar's image
	$aviData = Avatar::imageData(Me::$id, $activeAvatar);
	AppOutfit::draw($avatarData['base'], $avatarData['gender'], $outfitArray, APP_PATH . '/' . $aviData['image_directory'] . '/' . $aviData['main_directory'] . '/' . $aviData['second_directory'] . '/' . $aviData['filename']);

	// Save the outfit
	AppOutfit::save(Me::$id, $avatarData['identification'], $outfitArray);
}

// If we're moving something right
else if(isset($_GET['right']))
{
	$outfitArray = AppOutfit::move($outfitArray, (int) $_GET['right'], "right");
	
	// Update your avatar's image
	$aviData = Avatar::imageData(Me::$id, $activeAvatar);
	AppOutfit::draw($avatarData['base'], $avatarData['gender'], $outfitArray, APP_PATH . '/' . $aviData['image_directory'] . '/' . $aviData['main_directory'] . '/' . $aviData['second_directory'] . '/' . $aviData['filename']);

	// Save the outfit
	AppOutfit::save(Me::$id, $avatarData['identification'], $outfitArray);
}

else if($getLink == "replace")
{
	$outfitArray2 = AppOutfit::get(Me::$id, "preview");
	
	$outfitArray = AppOutfit::unequipAll();
	foreach($outfitArray2 as $key => $oa)
	{
		if(AppAvatar::checkOwnItem(Me::$id, $oa[0]))
		{
			if($key < 0)
			{
				$outfitArray = AppOutfit::equip($outfitArray, $oa[0], $avatarData['gender'], $oa[1], true);
			}
			else
			{
				$outfitArray = AppOutfit::equip($outfitArray, $oa[0], $avatarData['gender'], $oa[1]);
			}
		}
		else
		{
			$itemData = AppAvatar::itemData($oa[0], "title");
			Alert::error($itemData['title'] . " Not Owned", "You do not own " . $itemData['title'] . ", so it cannot be equipped.");
		}
	}
	
	// Update your avatar's image
	$aviData = Avatar::imageData(Me::$id, $activeAvatar);
	AppOutfit::draw($avatarData['base'], $avatarData['gender'], $outfitArray, APP_PATH . '/' . $aviData['image_directory'] . '/' . $aviData['main_directory'] . '/' . $aviData['second_directory'] . '/' . $aviData['filename']);
	
	AppOutfit::save(Me::$id, $avatarData['identification'], $outfitArray);
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

	// check ownership
	foreach($outfitArray as $key => $oa)
	{
		if($oa[0] == 0)	{ continue; }
		if(!AppAvatar::checkOwnItem(Me::$id, (int) $oa[0]))
		{
			$itemData = AppAvatar::itemData($oa[0], "title");
			Alert::error($itemData['title'] . " Not Owned", "You do not own " . $itemData['title'] . ", so it cannot be equipped.");
			unset($outfitArray[$key]);
		}
	}
			
	// resort it all
	$outfitArray = AppOutfit::sortAll($outfitArray, $avatarData['gender'], $avatarData['identification']);
	
	// Update your avatar's image
	$aviData = Avatar::imageData(Me::$id, $activeAvatar);
	AppOutfit::draw($avatarData['base'], $avatarData['gender'], $outfitArray, APP_PATH . '/' . $aviData['image_directory'] . '/' . $aviData['main_directory'] . '/' . $aviData['second_directory'] . '/' . $aviData['filename']);
	
	// Save the outfit
	AppOutfit::save(Me::$id, $avatarData['identification'], $outfitArray);
}

// Get the layers you can search between
$positions = AppAvatar::getInvPositions(Me::$id);

// Provide link to transfer if the user has no items yet
if($positions == array())
{
	Alert::info("No Items", 'Looks like you have no items yet! If you were a member of Uni5, perhaps you haven\'t <a href="/transfer">transferred your belongings</a> yet?');
}

// Set page title
$config['pageTitle'] = ($avatarData['name'] != '' ? $avatarData['name'] . '\'s ' : '') . "Dressing Room";
if(isset($_GET['position']))
{
	$config['pageTitle'] .= " > " . $_GET['position'];
}

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

echo '
<div id="panel-right"></div>
<div id="content" style="overflow:hidden;">' . Alert::display() . '';

	// Clothes currently worn
	echo '
	<div id="aviblock"><ul>
	<li style="height:383px;"><img src="' . $avatarData['src'] . (isset($avatarData['date_lastUpdate']) ? '?' . $avatarData['date_lastUpdate'] : "") . '"/></li>
	<li class="nav-slot"><a href="/dress-avatar?replace&' . Link::prepare("replace") . '">Replace with Preview</a></li>
	<li class="nav-slot"><a href="/dress-avatar?unequipAll&' . Link::prepare("unequipAll") . '">Unequip All</a></li>
	' . (isset($_GET['position']) ? '<li class="nav-slot"><a href="/shop-search?submit=Search&' . $_GET['position'] . '=on&gender=' . $avatarData['gender'] . 'ab">Search ' . $_GET['position'] . '</a></li>' : "") . '
</ul></div>
	
	<form id="sortable" action="/dress-avatar' . (isset($_GET['position']) ? "?position=" . $_GET['position'] : "") . '" method="post">
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
			<a class="close" href="/dress-avatar?' . (isset($_GET['position']) ? 'position=' . $_GET['position'] . '&' : '') . 'unequip=' . $eItem['id'] . '">&#10006;</a>
			<select id="color_' . $eItem['id'] . '">';
			
			$colors = AppAvatar::getItemColors($eItem['position'], $eItem['title'], $avatarData['gender']);
			
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
			<a class="left" href="/dress-avatar?' . (isset($_GET['position']) ? 'position=' . $_GET['position'] . '&' : '') . 'left=' . $eItem['id'] . '">&lt;</a>';
			}
			
			if(isset($outfitArray[$pos + 1]) && $eItem['position'] != "skin")
			{
				echo '
			<a class="right" href="/dress-avatar?' . (isset($_GET['position']) ? 'position=' . $_GET['position'] . '&' : '') . 'right=' . $eItem['id'] . '">&gt;</a>';
			}
			
				echo '
		</li>';
		}
		else
		{
			echo '
		<li id="worn_0">
			<div style="line-height:50px;">Base</div>
			<select id="color_0" disabled><option value="' . ucfirst($avatarData['base']) . '">' . ucfirst($avatarData['base']) . '</option></select>
		</li>';
		}
	}

	echo '
	</ul>
	</form>';
	
	// Show the layers you have access to
	echo '
	<div class="redlinks">';
	
	foreach($positions as $pos)
	{
		echo '
		' . (isset($_GET['position']) && $_GET['position'] == $pos ? '<span class="nav-active">' : '') . '<a href="/dress-avatar?position=' . $pos . '">' . $pos . '</a>' . (isset($_GET['position']) && $_GET['position'] == $pos ? '</span>' : '');
	}
	
	echo '
	</div>';
	
	if(isset($_GET['position']))
	{		
		// Show the items within the category selected
		$userItems = AppAvatar::getUserItems(Me::$id, $_GET['position']);
		$userItemsOther = array();
		
		// If you have no items, say so
		if(count($userItems) == 0)
		{
			echo "<p>You have no items in " . $_GET['position'] . ".</p>";
		}
		
		foreach($userItems as $key => $item)
		{
			if(!in_array($item['gender'], array($avatarData['gender'], "b")))
			{
				unset($userItems[$key]);
				$userItemsOther[] = $item;
				continue;
			}
			
			$colors = AppAvatar::getItemColors($_GET['position'], $item['title'], $avatarData['gender']);
			
			// Display the item block
			echo '
			<div class="item_block">
				<a href="javascript:review_item(' . $item['id'] . ');"><img id="pic_' . $item['id'] . '" src="/avatar_items/' . $_GET['position'] . '/' . $item['title'] . '/default_' . $avatarData['gender_full'] . '.png" /></a>
				<br />' . $item['title'] . ($item['count'] > 1 ? ' (' . $item['count'] . ')' : "") . '
				<select id="item_' . $item['id'] . '" onChange="switch_item_inventory(\'' . $item['id'] . '\', \'' . $_GET['position'] . '\', \'' . $item['title'] . '\', \'' . $avatarData['gender_full'] . '\');">';
			
			foreach($colors as $color)
			{
				echo '
					<option value="' . $color . '">' . $color . '</option>';
			}
			
			echo '
				</select>
				<br /><a id="link_' . $item['id'] . '" href="/dress-avatar?position=' . $_GET['position'] . '&equip=' . $item['id'] . '">Equip</a> | <a href="/sell-item/' . $item['id'] . '">Sell</a>
			</div>';
		}

		foreach($userItemsOther as $item)
		{			
			$colors = AppAvatar::getItemColors($_GET['position'], $item['title'], ($avatarData['gender'] == "m" ? "f" : "m"));
			
			// Display the item block
			echo '
			<div class="item_block opaque">
				<a href="javascript:review_item(' . $item['id'] . ');"><img id="pic_' . $item['id'] . '" src="/avatar_items/' . $_GET['position'] . '/' . $item['title'] . '/default_' . ($avatarData['gender_full'] == "male" ? "female" : "male") . '.png" /></a>
				<br />' . $item['title'] . ($item['count'] > 1 ? ' (' . $item['count'] . ')' : "") . '
				<select id="item_' . $item['id'] . '" onChange="switch_item_inventory(\'' . $item['id'] . '\', \'' . $_GET['position'] . '\', \'' . $item['title'] . '\', \'' . ($avatarData['gender_full'] == "male" ? "female" : "male") . '\');">';
			
			foreach($colors as $color)
			{
				echo '
					<option value="' . $color . '">' . $color . '</option>';
			}
			
			echo '
				</select>
				<br /><a href="/sell-item/' . $item['id'] . '">Sell</a>
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
	if($("#link_" + id))
	{
		$("#link_" + id).attr("href", "/dress-avatar?<?php echo (isset($_GET['position']) ? 'position=' . $_GET['position'] . '&' : ''); ?>equip=" + id + "&color=" + $("#item_" + id).val());
	}
}
</script>

<?php

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
