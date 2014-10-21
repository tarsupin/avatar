<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/home");
}

// Make sure you have an avatar
if(!isset($avatarData['base']))
{
	header("Location: /create-avatar"); exit;
}

// Check if an item was presented
if(!isset($url[1]))
{
	header("Location: /dress-avatar"); exit;
}
$url[1] = (int) $url[1];

// Check if you own the item
$ownItem = AppAvatar::checkOwnItem(Me::$id, $url[1]);
if(!$ownItem)
{
	Alert::saveError("Do Not Own Item", "You do not own this item!");
	header("Location: /dress-avatar"); exit;
}

// Check item data
$item = AppAvatar::itemData($url[1]);
if(!$item)
{
	header("Location: /dress-avatar"); exit;
}

// Get cost
if($shop = Database::selectOne("SELECT cost FROM shop_inventory INNER JOIN shop ON shop_inventory.shop_id=shop.id WHERE item_id=? AND clearance<=? LIMIT 1", array($url[1], Me::$clearance)))
{
	$item['cost'] = (float) $shop['cost'];
}

// Check if you sold the item
if(Form::submitted("sell-item"))
{
	if(FormValidate::pass())
	{
		if(AppAvatar::dropItem(Me::$id, $url[1]))
		{
			Currency::add(Me::$id, ($item['cost']/2), "Sold " . $item['title']);
			// Return to the dressing room with a success message
			Alert::saveSuccess("Item Sold", "You have sold " . $item['title'] . " for " . ($item['cost']/2) . " Auro.");
			header("Location: /dress-avatar?position=" . $item['position']); exit;
		}
	}
}

// warn about accidental sales of valuable items
if($item['rarity_level'] != 0)
{
	Alert::info("Rare Item", "This item is exotic, limited or otherwise not easily purchasable. You might not be able to obtain it again.");
}

// Set page title
$config['pageTitle'] = "Sell " . $item['title'];

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

echo '
<div id="panel-right"></div>
<div id="content">' . Alert::display() . '
	<h2>Sell ' . $item['title'] . '</h2>
	<p>Are you sure you want to sell ' . $item['title'] . ' for ' . ($item['cost']/2) . ' Auro?</p>';
	
	// Get some of the items
	$images = Dir::getFiles(APP_PATH . "/avatar_items/" . $item['position'] . '/' . $item['title'] . '/');
	
	foreach($images as $img)
	{
		if(strpos($img, "_" . ($avatarData['gender'] == "f" ? "female" : "male") . ".png") > -1 && strpos($img, "default_") === false)
		{
			echo '
	<img src="/avatar_items/' . $item['position'] . '/' . $item['title'] . '/' . $img . '" />';
			break;
		}
	}
	
	echo '
	<br /><br />
	<form class="uniform" action="/sell-item/' . $item['id'] . '" method="post">' . Form::prepare("sell-item") . '
		<input type="submit" name="submit" value="Sell" />
	</form>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
