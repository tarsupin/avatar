<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Defaults if a guest is viewing the page
if(!Me::$loggedIn)
{
	Me::redirectLogin("/utilities/wish-list");
}

// Check if you have an avatar
if(!isset($avatarData['base']))		{ header("Location: /create-avatar"); exit; }

// Add to List
if(isset($url[2]))
{
	$url[2] = (int) $url[2];
	// check item data
	if($itemData = AppAvatar::itemData($url[2], "title"))
	{
		if(Database::query("REPLACE INTO user_wish VALUES (?, ?)", array(Me::$id, $url[2])))
		{
			Alert::success("Item Added", $itemData['title'] . " has been added to your wish list.");
		}
	}
}

// Remove from List
if(isset($_GET['remove']))
{
	$_GET['remove'] = (int) $_GET['remove'];
	// check item data
	if($itemData = AppAvatar::itemData($_GET['remove'], "title"))
	{
		if(Database::query("DELETE FROM user_wish WHERE uni_id=? AND item_id=? LIMIT 1", array(Me::$id, $_GET['remove'])))
		{
			Alert::success("Item Removed", $itemData['title'] . " has been removed from your wish list.");
		}
	}
}

// Set page title
$config['pageTitle'] = "Utilities > Wish List";

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
WidgetLoader::add("SidePanel", 40, '
	<div class="panel-links" style="text-align:center;">
		<a href="/shop-search">Shop Search</a>
	</div>
	<br/>');

echo '
<style>
table tr td { text-align:center; }
</style>';

require(SYS_PATH . "/controller/includes/side-panel.php");

// Display Page
echo '
<div id="panel-right"></div>
<div id="content">' .
Alert::display();

// Page Display
$wished = Database::selectMultiple("SELECT item_id FROM user_wish WHERE uni_id=?", array(Me::$id));
echo '
	<h2>Wish List</h2>
	<table class="mod-table">
		<tr>
			<td>Remove</td>
			<td>Item</td>
			<td>Position</td>
			<td>Gender</td>
			<td>Owned</td>
		</tr>';
foreach ($wished as $wish)
{
	$own = AppAvatar::checkOwnItem(Me::$id, $wish['item_id']);
	$itemData = AppAvatar::itemData($wish['item_id'], "title,position,gender");
	echo '
		<tr' . ($own ? ' class="opaque"' : "") . '>
			<td><a href="/utilities/wish-list?remove=' . $wish['item_id'] . '">&#10006;</a></td>
			<td>' . $itemData['title'] . '</td>
			<td>' . $itemData['position'] . '</td>
			<td>' . ($itemData['gender'] == "b" ? "both genders" : ($itemData['gender'] == "m" ? "male" : "female")) . '</td>
			<td>' . ($own ? "yes" : "no") . '</td>
		</tr>';
}
echo '
	</table>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
