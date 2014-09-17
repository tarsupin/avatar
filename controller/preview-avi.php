<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you have an avatar
if(!isset($avatarData['base']))
{
	header("Location: /create-avatar"); exit;
}

// Prepare the Preview Avatar
$outfitItems = AppOutfit::get(Me::$id, "preview");

// If we're adding a color
if(isset($_GET['item']) && $_GET['color'])
{
	// Sanitize Data
	$_GET['item'] = Sanitize::variable($_GET['item'], " ");
	$_GET['color'] = Sanitize::variable($_GET['color'], " ");
	
	// Add the new item
	$outfitItems = AppOutfit::equip($outfitItems, $_GET['item'], $avatarData['gender_full'], $_GET['color']);
	
	// Save the outfit
	AppOutfit::save(Me::$id, "preview", $outfitItems);
}

else if(isset($_GET['action']))
{
	switch($_GET['action'])
	{
		// If we're unequipping something
		case "unequip":
			if(!isset($_GET['item'])) { break; }
			$outfitItems = AppOutfit::unequip($outfitItems, (int) $_GET['item']);
			
			// Save the outfit
			AppOutfit::save(Me::$id, "preview", $outfitItems);
		break;
		
		// If we're unequipping everything
		case "unequipAll":
			$outfitItems = AppOutfit::unequipAll();
			
			// Save the outfit
			AppOutfit::save(Me::$id, "preview", $outfitItems);
		break;
		
		// If we're moving something left
		case "left":
			if(!isset($_GET['item'])) { break; }
			
			foreach($outfitItems as $key => $oitem)
			{
				// Found the correct item. Now let's move it.
				if($oitem[0] == $_GET['item'])
				{
					if(isset($outfitItems[$key - 1]))
					{
						$save = $outfitItems[$key];
						$outfitItems[$key] = $outfitItems[$key - 1];
						$outfitItems[$key - 1] = $save;
						
						// Save the outfit
						AppOutfit::save(Me::$id, "preview", $outfitItems);
					}
					
					break;
				}
			}
		break;
		
		// If we're moving something right
		case "right":
			if(!isset($_GET['item'])) { break; }
			
			foreach($outfitItems as $key => $oitem)
			{
				// Found the correct item. Now let's move it.
				if($oitem[0] == $_GET['item'])
				{
					if(isset($outfitItems[$key + 1]))
					{
						$save = $outfitItems[$key];
						$outfitItems[$key] = $outfitItems[$key + 1];
						$outfitItems[$key + 1] = $save;
						
						// Save the outfit
						AppOutfit::save(Me::$id, "preview", $outfitItems);
					}
					
					break;
				}
			}
		break;
		
		// If we're changing the color of an item
		case "colorChange":
			if(!isset($_GET['item']) or !isset($_GET['recolor'])) { break; }
			
			foreach($outfitItems as $key => $oitem)
			{
				// Found the correct item. Now let's recolor it.
				if($oitem[0] == $_GET['item'])
				{
					$itemData = AppAvatar::itemData($oitem[0]);
					$_GET['recolor'] = Sanitize::variable($_GET['recolor'], " ");
					
					if(AppAvatar::itemHasColor($itemData['position'], $itemData['title'], $avatarData['gender_full'], $_GET['recolor']))
					{
						$outfitItems[$key][1] = $_GET['recolor'];
						
						// Save the outfit
						AppOutfit::save(Me::$id, "preview", $outfitItems);
					}
					
					break;
				}
			}
		break;
	}
}

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");

echo '
<body>

<div style="float:left;text-align:center;">
	<img src="' . AppOutfit::drawSrc("preview") . '" /><br />
	<a href="/preview-avi?action=unequipAll">Unequip All</a>
</div>

<div style="margin-left:220px;">
';

// Get Item List
$itemSQL = "";
$itemArray = array();

foreach($outfitItems as $item)
{
	$itemSQL .= ($itemSQL == "" ? "" : ", ") . "?";
	$itemArray[] = $item[0] + 0;
}

// Get Items
// $itemList = Database::selectMultiple("SELECT id, title FROM items WHERE id IN (" . $itemSQL . ")", $itemArray);


// Clothes currently worn
echo '
<ul id="equipped" class="dragndrop">';

// Gather your list of equipped items
foreach($outfitItems as $pos => $item)
{
	// Get Items
	$eItem = Database::selectOne("SELECT id, title, position FROM items WHERE id=?", array($item[0]));
	
	// Recognize Integers
	$eItem['id'] = (int) $eItem['id'];
	
	$eItem['color'] = $item[1];
	
	echo '
	<li id="worn_' . $eItem['id'] . '">
		<div><img id="itemImg_' . $eItem['id'] . '_eq" src="/avatar_items/' . $eItem['position'] . '/' . $eItem['title'] . '/' . $eItem['color'] . '_' . $avatarData['gender_full'] . '.png" title="' . $eItem['title'] . '"/></div>
		<a id="link_' . $eItem['id'] . '_eq" class="close" href="/preview-avi?action=unequip&item=' . $eItem['id'] . '">&#10006;</a>
		<select id="color_' . $eItem['id'] . '_eq" onchange="switch_color(' . $eItem['id'] . ', \'' . $eItem['position'] . '\', \'' . $eItem['title'] . '\', \'' . $avatarData['gender_full'] . '\', true)">';
	
	$colors = AppAvatar::getItemColors($eItem['position'], $eItem['title']);
	
	foreach($colors as $color)
	{
		echo '
		<option value="' . $color . '">' . $color . '</option>';
	}
	
	echo '
		</select>';
	
	if(isset($outfitItems[$pos - 1]))
	{
		echo '
		<a class="left" href="/preview-avi?action=left&item=' . $eItem['id'] . '">&lt;</a>';
	}
	
	if(isset($outfitItems[$pos + 1]))
	{
		echo '
		<a class="right" href="/preview-avi?action=right&item=' . $eItem['id'] . '">&gt;</a>';
	}
	
	echo '
	</li>';
}

echo '
</ul>
</div>';

?>

<script type="text/javascript">
function switch_color(itemID, position, title, gender, equipped = false)
{
	var eq = (equipped != false) ? "_eq" : "";
	
	var imgswap = document.getElementById("itemImg_" + itemID + eq);
	var colorswap = document.getElementById("color_" + itemID + eq);
	//var linkswap = document.getElementById("link_" + itemID + eq);
	
	var color = colorswap.options[colorswap.selectedIndex].value;
	
	//linkswap.href = "/preview-avi?equip=" + itemID + "&color=" + color;
	imgswap.src = "/avatar_items/" + position + "/" + title + "/" + color + "_" + gender + ".png";
	
	window.location = "/preview-avi?action=colorChange&item=" + itemID + "&recolor=" + color;
}
</script>

</body>
</html>