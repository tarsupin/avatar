<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// put out error for guests (no redirect)
if(!Me::$loggedIn)
{
	Alert::saveError("Guest", "You need to be logged in to use this feature.");
	Me::redirectLogin("/preview-avi");
}
// Prepare the Preview Avatar
else
{
	// Make sure you have an avatar
	if(!isset($avatarData['base']))
	{
		header("Location: /create-avatar"); exit;
	}
	$outfitArray = AppOutfit::get(Me::$id, "preview");
}

if(!$getLink = Link::clicked())
{
	$getLink = "";
}

// If we're adding an item
if(isset($_GET['equip']) && $_GET['color'])
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
	$outfitArray = AppOutfit::equip($outfitArray, $_GET['equip'], $avatarData['gender'], $_GET['color'], "preview");
	
	// Save the changes
	AppOutfit::save(Me::$id, "preview", $outfitArray);
}

// If we're unequipping something
else if(isset($_GET['unequip']))
{
	$outfitArray = AppOutfit::unequip($outfitArray, (int) $_GET['unequip']);
			
	// Save the outfit
	AppOutfit::save(Me::$id, "preview", $outfitArray);
}

// If we're unequipping everything
else if($getLink == "unequipAll")
{
	$outfitArray = AppOutfit::unequipAll();
			
	// Save the outfit
	AppOutfit::save(Me::$id, "preview", $outfitArray);
}

// If we're moving something left
else if(isset($_GET['left']))
{
	$outfitArray = AppOutfit::move($outfitArray, $_GET['left'], "left");

	// Save the outfit
	AppOutfit::save(Me::$id, "preview", $outfitArray);
}

// If we're moving something right
else if(isset($_GET['right']))
{
	$outfitArray = AppOutfit::move($outfitArray, $_GET['right'], "right");

	// Save the outfit
	AppOutfit::save(Me::$id, "preview", $outfitArray);
}

else if($getLink == "replace")
{
	$outfitArray = AppOutfit::get(Me::$id, "default");
	AppOutfit::save(Me::$id, "preview", $outfitArray);
}

else if($getLink == "buyAll")
{
	foreach($outfitArray as $key => $oa)
	{
		if($key == 0)	{ continue; }
		
		if(!AppAvatar::checkOwnItem(Me::$id, $oa[0]))
		{
			$itemData = AppAvatar::itemData($oa[0]);
			if($itemData['rarity_level'] == 0)
			{
				// check if the item is in a visible shop and get cost
				$shop = Database::selectOne("SELECT cost FROM shop_inventory INNER JOIN shop ON shop_inventory.shop_id=shop.id WHERE item_id=? AND clearance<=? LIMIT 1", array($itemData['id'], Me::$clearance));
				if($shop)
				{
					$balance = Currency::check(Me::$id);
		
					// Make sure your balance exceeds the item's cost
					if($balance < $shop['cost'])
					{
						Alert::error("Too Expensive", "You don't have enough to purchase " . $itemData['title'] . "!");
					}
					
					// Add this item to your inventory
					if(AppAvatar::receiveItem(Me::$id, $itemData['id']))
					{
						// Spend the currency to purchase this item
						Currency::subtract(Me::$id, $shop['cost'], "Purchased " . $itemData['title'], $errorStr);
						
						Alert::success("Purchased Item", "You have purchased " . $itemData['title'] . "!");
					}
				}
			}
		}
	}
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
	$outfitArray = AppOutfit::sortAll($outfitArray, $avatarData['gender'], "preview");
	
	// Save the outfit
	AppOutfit::save(Me::$id, "preview", $outfitArray);
}

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Add Javascript to header
Metadata::addHeader('
<!-- javascript -->
<script src="/assets/scripts/jquery.js" type="text/javascript" charset="utf-8"></script>
<script src="/assets/scripts/jquery-ui.js" type="text/javascript" charset="utf-8"></script>

<!-- javascript for touch devices, source: http://touchpunch.furf.com/ -->
<script src="/assets/scripts/jquery.ui.touch-punch.min.js" type="text/javascript" charset="utf-8"></script>
');

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");

echo '
<body>
<style>
.dragndrop li { background-color:white;	}
</style>

' . Alert::display() . '

<div style="float:left;text-align:center;">
	<img src="' . AppOutfit::drawSrc("preview") . '" /><br />
	<a href="/preview-avi?unequipAll&' . Link::prepare("unequipAll") . '">Unequip All</a><br/>
	<a href="/preview-avi?replace&' . Link::prepare("replace") . '">Replace with Avatar Image</a><br/>
	<a href="/preview-avi?buyAll&' . Link::prepare("buyAll") . '" onclick="return confirm(\'Do you really want to buy all these items? This will not repurchase items you already have.\');">Buy Missing Items</a>
</div>
';


// Clothes currently worn
echo '
<form id="sortable" action="/preview-avi" method="post" style="margin-left:222px;">
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
		<div><img id="itemImg_' . $eItem['id'] . '" src="/avatar_items/' . $eItem['position'] . '/' . $eItem['title'] . '/' . $eItem['color'] . '_' . $avatarData['gender_full'] . '.png" title="' . $eItem['title'] . '"/></div>
		<a id="link_' . $eItem['id'] . '" class="close" href="/preview-avi?unequip=' . $eItem['id'] . '">&#10006;</a>
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
		<a class="left" href="/preview-avi?left=' . $eItem['id'] . '">&lt;</a>';
		}
		
		if(isset($outfitArray[$pos + 1]) && $eItem['position'] != "skin")
		{
			echo '
		<a class="right" href="/preview-avi?right=' . $eItem['id'] . '">&gt;</a>';
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

?>

<script src="/assets/scripts/reorder.js" type="text/javascript" charset="utf-8"></script>

</body>
</html>