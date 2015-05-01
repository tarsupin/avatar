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

// Check if you own the item
$item['id'] = (int) $item['id'];
$ownItem = AppAvatar::checkOwnItem(Me::$id, $item['id']);

$wrappers = AppAvatar::wrappers();

// Check if you purchased the item
if(Form::submitted("purchase-item"))
{
	if(AppAvatar::purchaseItem($item['id'], $shopID))
	{
		$ownItem = true;
		if(Me::$clearance >= 4)
		{
			if($packageID = (int) Database::selectValue("SELECT package_id FROM packages_content WHERE item_id=? LIMIT 1", array($item['id'])))
			{
				AppExotic::stats($packageID, 0, $item['id']);
			}
		}
	}
}

// Set page title
$config['pageTitle'] = "Purchase " . $item['title'] . (in_array($item['id'], $wrappers) ? ' (Wrapper)' : '');

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
<div class="overwrap-box">
	<div class="overwrap-line">Purchase ' . $item['title'] . (in_array($item['id'], $wrappers) ? ' (Wrapper)' : '') . '</div>
	<div class="inner-box">
	<p>Are you sure you want to purchase ' . $item['title'] . (in_array($item['id'], $wrappers) ? ' (Wrapper)' : '') . ' for ' . $item['cost'] . ' Auro? [' . $item['position'] . ', ' . ($item['gender'] == "b" ? 'both genders' : ($item['gender'] == "m" ? 'male' : 'female')) . ']</p>';
	
	// Get some of the items
	$images = Dir::getFiles(APP_PATH . "/avatar_items/" . $item['position'] . '/' . $item['title'] . '/');
	
	if($item['gender'] == $avatarData['gender'] || $item['gender'] == "b")
	{
		foreach($images as $img)
		{
			if(strpos($img, "_" . $avatarData['gender_full'] . ".png") > -1 && strpos($img, "default_") === false)
			{
				echo '
	<img src="/avatar_items/' . $item['position'] . '/' . $item['title'] . '/' . $img . '" />';
				break;
			}
		}
	}
	else
	{
		foreach($images as $img)
		{
			if(strpos($img, "_" . ($avatarData['gender'] == "m" ? "female" : "male") . ".png") > -1 && strpos($img, "default_") === false)
			{
				echo '
	<img src="/avatar_items/' . $item['position'] . '/' . $item['title'] . '/' . $img . '" />';
				break;
			}
		}
	}
	
	echo '
	<br /><br />
	<form class="uniform" method="post">' . Form::prepare("purchase-item") . '
		<input type="submit" name="submit" style="white-space:normal;" value="' . ($ownItem ? 'You already own this item. Purchase Again?' : 'Purchase') . '" />
	</form>
	</div>
</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
