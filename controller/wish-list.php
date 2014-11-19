<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Defaults if a guest is viewing the page
if(!Me::$loggedIn)
{
	Me::redirectLogin("/wish-list");
}

// Check if you have an avatar
if(!isset($avatarData['base']))		{ header("Location: /create-avatar"); exit; }

// Add to List
if(isset($_GET['add']))
{
	$_GET['add'] = (int) $_GET['add'];
	// check item data
	if($itemData = AppAvatar::itemData($_GET['add'], "title"))
	{
		if(Database::query("REPLACE INTO user_wish VALUES (?, ?)", array(Me::$id, $_GET['add'])))
		{
			Alert::success("Item Added", $itemData['title'] . ' has been added to your wish list. <a href="javascript:window.history.back();">Would you like to go back to the previous page?</a>');
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

// Buy an item
if($link = Link::clicked())
{
	if($link == "purchase-wish" && isset($_GET['buy']))
	{
		$_GET['buy'] = (int) $_GET['buy'];
		AppAvatar::purchaseItem($_GET['buy']);
	}
}

// Sort order
$order = "";
if(isset($_GET['sort']) && in_array($_GET['sort'], array("title", "position", "gender")))
{
	$order = " ORDER BY " . $_GET['sort'];
	if(isset($_GET['reverse']))
	{
		$order .= " DESC";
	}
}

// Set page title
$config['pageTitle'] = "My Wish List";

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

echo '
<style>
table tr:first-child td { text-align:center; }
</style>';

require(SYS_PATH . "/controller/includes/side-panel.php");

// Display Page
echo '
<div id="panel-right"></div>
<div id="content">' .
Alert::display();

// Page Display
$wished = Database::selectMultiple("SELECT item_id, title, position, gender, rarity_level FROM user_wish INNER JOIN items ON user_wish.item_id=items.id WHERE uni_id=?" . $order, array(Me::$id));

foreach($wished as $key => $wish)
{
	$wished[$key]['cost'] = AppAvatar::itemMinCost((int) $wish['item_id']);
}

if(isset($_GET['sort']) && $_GET['sort'] == "cost")
{
	if(isset($_GET['reverse']))
	{
		function cmp($a, $b)
		{
			if($a['cost'] == $b['cost'])	{ return 0; }
			return ($a['cost'] > $b['cost'] ? -1 : 1);
		}
	}
	else
	{
		function cmp($a, $b)
		{
			if($a['cost'] == $b['cost'])	{ return 0; }
			return ($a['cost'] < $b['cost'] ? -1 : 1);
		}
	}
	usort($wished, "cmp");
}

echo '
	<h2>My Wish List</h2>
	<table class="mod-table">
		<tr>
			<td>&nbsp;</td>';
foreach(array("title", "position", "gender", "cost") as $col)
{
	if (isset($_GET['sort']) && $_GET['sort'] == $col && !isset($_GET['reverse']))
		echo "
			<td>" . ucfirst($col) . " <a href='/wish-list?sort=" . $col . "&reverse'>&#9650;</a></td>";
	elseif (isset($_GET['sort']) && $_GET['sort'] == $col)
		echo "
			<td>" . ucfirst($col) . " <a href='/wish-list?sort=" . $col . "'>&#9660;</a></td>";
	else
		echo "
			<td>" . ucfirst($col) . " <a href='/wish-list?sort=" . $col . "'>&#9651;</a></td>";
}
echo '
			<td>Package</td>
		</tr>';
foreach ($wished as $itemData)
{
	$itemData['item_id'] = (int) $itemData['item_id'];
	$own = AppAvatar::checkOwnItem(Me::$id, $itemData['item_id']);
	$package = Database::selectOne("SELECT title, year FROM packages_content INNER JOIN packages ON packages_content.package_id=packages.id WHERE item_id=? LIMIT 1", array($itemData['item_id']));
	echo '
		<tr' . ($own ? ' class="opaque"' : "") . '>
			<td><a href="/wish-list?remove=' . $itemData['item_id'] . '">&#10006;</a></td>
			<td><a href="/shop-search?title=' . $itemData['title'] . '&' . $itemData['position'] . '=on&submit=Search">' . $itemData['title'] . '</a>' . ($own ? " [&bull;]" : "") . '</td>
			<td>' . $itemData['position'] . '</td>
			<td>' . ($itemData['gender'] == "b" ? "both genders" : ($itemData['gender'] == "m" ? "male" : "female")) . '</td>
			<td>' . ($itemData['cost'] != 0 ? '<a href="/wish-list?buy=' . $itemData['item_id'] . '&' . Link::prepare("purchase-wish") . '" onclick="return confirm(\'Are you sure you want to buy ' . $itemData['title'] . '?\');">' . $itemData['cost'] . ' Auro</a>' : 'Preview Only') . '</td>
			<td>' . ($package ? $package['title'] . " (" . $package['year'] . ")" : "&nbsp;") . '</td>
		</tr>';
}
echo '
	</table>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
