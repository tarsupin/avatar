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
if(!isset($url[1]) or (!isset($_GET['shopID'])))
{
	header("Location: /shop-list"); exit;
}

$shopID = (int) $_GET['shopID'];
$url[1] = (int) $url[1];

// Get the item and ensure it is available at the shop
if(!$item = AppAvatar::getShopItems($shopID, $url[1]))
{
	Alert::saveError("Item Missing", "That item has been discontinued in that shop.");
	header("Location: /shop-list"); exit;
}

// Check if you purchased the item
if(Form::submitted("purchase-item"))
{
	if(FormValidate::pass())
	{
		AppAvatar::purchaseItem($item['id'], $shopID, true);
		// Return to the shop with a success or error message
		header("Location: /shop/" . $shopID . "?purchased=" . $item['id']); exit;
	}
}

// Check if you own the item
$ownItem = AppAvatar::checkOwnItem(Me::$id, $item['id']);

// If you own the item, announce it here
if($ownItem)
{
	Alert::info("Own Item", "Note: You already own this item!");
}

// Set page title
$config['pageTitle'] = "Purchase " . $item['title'];

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
	<h2>Purchase ' . $item['title'] . '</h2>
	<p>Are you sure you want to purchase ' . $item['title'] . ' for ' . $item['cost'] . ' Auro? [' . $item['position'] . ', ' . ($item['gender'] == "b" ? 'both genders' : ($item['gender'] == "m" ? 'male' : 'female')) . ']</p>';
	
	// Get some of the items
	$images = Dir::getFiles(APP_PATH . "/avatar_items/" . $item['position'] . '/' . $item['title'] . '/');
	
	foreach($images as $img)
	{
		if(strpos($img, "_" . ($avatarData['gender'] == "m" ? "male" : "female") . ".png") > -1 && strpos($img, "default_") === false)
		{
			echo '
	<img src="/avatar_items/' . $item['position'] . '/' . $item['title'] . '/' . $img . '" />';
			break;
		}
	}
	
	echo '
	<br /><br />
	<form class="uniform" action="/purchase-item/' . $item['id'] . '?shopID=' . $shopID . '" method="post">' . Form::prepare("purchase-item") . '
		<input type="submit" name="submit" value="Purchase" />
	</form>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
