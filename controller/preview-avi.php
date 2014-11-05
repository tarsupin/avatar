<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// put out error for guests
if(!Me::$loggedIn)
{
	Alert::saveError("Guest", "You need to be logged in to use the avatar preview.");
	header("Location: /"); exit;
}

// Make sure you have an avatar
if(!isset($avatarData['base']))
{
	header("Location: /create-avatar"); exit;
}

// Prepare the avatar
$outfitArray = AppOutfit::get(Me::$id, "preview");

if(!$getLink = Link::clicked())
{
	$getLink = "";
}

// If we're adding an item
if(isset($_GET['equip']) && $_GET['color'])
{
	$_GET['equip'] = (int) $_GET['equip'];
	
	$itemData = AppAvatar::itemData($_GET['equip']);
	
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

else if(isset($_GET['buy']))
{
	$_GET['buy'] = (int) $_GET['buy'];
	AppAvatar::purchaseItem($_GET['buy']);
}

else if($getLink == "buyAll")
{
	foreach($outfitArray as $key => $oa)
	{
		if($key == 0)	{ continue; }
		
		if(!AppAvatar::checkOwnItem(Me::$id, $oa[0]))
		{
			AppAvatar::purchaseItem($oa[0]);
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

else if($getLink == "randomize")
{
	// Pick a random color for each item
	foreach($outfitArray as $key => $oa)
	{
		$item = AppAvatar::itemData($oa[0]);
		$colors = AppAvatar::getItemColors($item['position'], $item['title']);
		shuffle($colors);
		while(true && $colors != array())
		{
			$color = array_shift($colors);
			if(AppAvatar::itemHasColor($item['position'], $item['title'], $avatarData['gender'], $color))
			{
				$outfitArray[$key][1] = $color;
				break;
			}
		}
	}
	
	// Save the outfit
	AppOutfit::save(Me::$id, "preview", $outfitArray);
}

// Set page title
$config['pageTitle'] = "Preview Window";

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");

echo '
<body>
<style>
html,body { background-color:white; }
.alert-info, .alert-message, .alert-error { margin:0px 0px 5px 0px; padding:5px; }
</style>
<div id="viewport-wrap">
<div id="content" style="padding:0px;margin:0px; overflow:hidden;">
<div id="aviblock"><ul>
	<li style="height:383px;"><img src="' . AppOutfit::drawSrc("preview") . '" /></li>
	<li class="nav-slot"><a href="/preview-avi?replace&' . Link::prepare("replace") . '">Replace with Current</a></li>
	<li class="nav-slot"><a href="/preview-avi?unequipAll&' . Link::prepare("unequipAll") . '">Unequip All</a></li>
	<li class="nav-slot"><a href="/preview-avi?randomize&' . Link::prepare("randomize") . '">Randomize Colors</a></a></li>
	<li class="nav-slot"><a href="/preview-avi?buyAll&' . Link::prepare("buyAll") . '" onclick="return confirm(\'Do you really want to buy all these items? This will not repurchase items you already have.\');">Buy Missing Items</a></a></li>
</ul></div>
';


// Clothes currently worn
echo '
<form id="sortable" action="/preview-avi" method="post">
' . Alert::display() . '
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
		$eItem = Database::selectOne("SELECT id, title, position, rarity_level FROM items WHERE id=?", array($item[0]));
		
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
		
		if(AppAvatar::checkOwnItem(Me::$id, $eItem['id']))
		{
			echo '
		<span class="owned" href="">[&bull;]</span>';
		}
		elseif($eItem['rarity_level'] == 0 || Me::$clearance >= 5)
		{
			echo '
			<a class="buy" onclick="return confirm(\'Are you sure you want to buy ' . $eItem['title'] . '?\');" href="/preview-avi?buy=' . $eItem['id'] . '">&#10004;</a>';
		}

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
		<select id="color_0" disabled><option value="' . ucfirst($avatarData['base']) . '">' . ucfirst($avatarData['base']) . '</option></select>
	</li>';
	}
}

echo '
</ul>
</form>
</div>
</div>';
?>

<script src="/assets/scripts/reorder.js" type="text/javascript" charset="utf-8"></script>

</body>
</html>