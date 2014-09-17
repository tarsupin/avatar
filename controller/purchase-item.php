<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you have an avatar
if(!isset($avatarData['base']))
{
	header("Location: /create-avatar"); exit;
}

// Check if an item was presented
if(!isset($url[1]) or (!isset($_GET['shopID'])))
{
	header("Location: /shop-list"); exit;
}

// Get the item and ensure it is available at the shop
if(!$item = AppAvatar::getShopItems((int) $_GET['shopID'], $url[1]))
{
	Alert::saveError("Item Missing", "That item has been discontinued in that shop.");
	header("Location: /shop-list"); exit;
}

// Make sure you're allowed to purchase the item
if($item['rarity_level'] != 0) { header("Location: /shop-list"); exit; }

// Check if you purchased the item
if(Form::submitted("purchase-item"))
{
	$balance = Currency::check(Me::$id);
	
	// Make sure your balance exceeds the item's cost
	if($balance < $item['cost'])
	{
		Alert::error("Too Expensive", "You don't have enough to purchase this item!");
	}
	
	if(FormValidate::pass())
	{
		// Add this item to your inventory
		if(AppAvatar::receiveItem(Me::$id, $item['id']))
		{
			// Spend the currency to purchase this item
			Currency::subtract(Me::$id, $item['cost'], "Purchased the " . $item['title'], $errorStr);
			
			Alert::saveMessage("Purchased Item", "You have purchased the " . $item['title'] . "!");
			
			// Return to the shop with a success message
			header("Location: /shop/" . $_GET['shopID'] . "?purchased=" . $item['id']); exit;
		}
	}
}

// Check if you own the item
$ownItem = AppAvatar::checkOwnItem(Me::$id, $item['id']);

// If you own the item, announce it here
if($ownItem)
{
	Alert::info("Own Item", "Note: You already own this item!");
}

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

echo '
<style>
.shop-block { display:inline-block; padding:15px; text-align:center; }
.shop-block img { max-height:130px; }
</style>';

echo '
<div id="panel-right"></div>
<div id="content">' . Alert::display() . '
	<div class="category-container">
		<h2>Purchase ' . $item['title'] . '</h2>
		<p>Are you sure you want to purchase the ' . $item['title'] . ' for ' . $item['cost'] . ' coins?</p>';
		
		// Get some of the items
		$images = Dir::getFiles(APP_PATH . "/avatar_items/" . $item['position'] . '/' . $item['title'] . '/');
		
		foreach($images as $img)
		{
			if(strpos($img, ".png") > -1)
			{
				echo '
				<img src="/avatar_items/' . $item['position'] . '/' . $item['title'] . '/' . $img . '" />';
				break;
			}
		}
		
		echo '
			<br /><br />
			<form class="uniform" action="/purchase-item/' . $item['id'] . '?shopID=' . $_GET['shopID'] . '" method="post">' . Form::prepare("purchase-item") . '
				<input type="submit" name="submit" value="Purchase" />
			</form>
		</div>
	</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
