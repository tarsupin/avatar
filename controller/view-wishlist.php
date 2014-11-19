<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Defaults if a guest is viewing the page
if(!Me::$loggedIn)
{
	Me::redirectLogin("/view-wishlist");
}

// Check if you have an avatar
if(!isset($avatarData['base']))		{ header("Location: /create-avatar"); exit; }

if(isset($url[1]))
{
	$url[1] = (int) $url[1];

	// get owner and permission setting
	$owner = Database::selectOne("SELECT uni_id FROM user_share_wishlist WHERE uni_id=? AND (other_id=? OR other_id=?) LIMIT 1", array($url[1], 0, Me::$id));
	if($owner == array() && $url[1] != Me::$id)
	{
		Alert::saveError("Not Allowed", "You do not have permission to view this wish list.");
		header("Location: /view-wishlist"); exit;
	}
	elseif($url[1] == Me::$id)
	{
		$owner['uni_id'] = Me::$id;
		$recipient = Me::$vals['handle'];
	}
	else
	{
		$recipient = User::get((int) $owner['uni_id'], "handle");
		$recipient = $recipient['handle'];
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
	
	$wished = Database::selectMultiple("SELECT item_id, title, position, gender, rarity_level FROM user_wish INNER JOIN items ON user_wish.item_id=items.id WHERE uni_id=?" . $order, array($owner['uni_id']));

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
}
else
{
	if(!isset($_GET['page']))
	{
		$_GET['page'] = 0;
	}
	if(Form::submitted("share-wishlist-all"))
	{
		if(isset($_POST['everyone']))
		{
			if(Database::query("REPLACE INTO user_share_wishlist VALUES (?, ?)", array(Me::$id, 0)))
			{
				Database::query("DELETE FROM user_share_wishlist WHERE uni_id=? AND other_id!=?", array(Me::$id, 0));
				Alert::success("Allowed", "Everyone may view your wish list now.");
			}
		}
		else
		{
			if(Database::query("DELETE FROM user_share_wishlist WHERE uni_id=? AND other_id=? LIMIT 1", array(Me::$id, 0)))
			{
				Alert::success("Not Allowed", "Your wish list may now only be viewed by the users listed below, if any.");
			}
		}
	}
	
	if(Form::submitted("share-wishlist-one"))
	{
		$allow = Database::selectOne("SELECT DISTINCT other_id FROM user_share_wishlist WHERE uni_id=? AND other_id=? LIMIT 1", array(Me::$id, 0));
		if($allow == array())
		{
			$user = Sanitize::variable($_POST['addshare']);
			$recipientID = User::getDataByHandle($user);
			if($recipientID == array())
			{
				Alert::error("Recipient Missing", $user . " does not use the avatar system.");
			}
			else
			{
				$recipientID = (int) $recipientID['uni_id'];
				if(Database::query("REPLACE INTO user_share_wishlist VALUES (?, ?)", array(Me::$id, $recipientID)))
				{
					Alert::success("Allowed", $user . " may view your wish list now.");
				}
			}
		}
		else
		{
			Alert::error("Not Possible", "You are currently allowing everyone to view your wish list. If you wish to allow only specific users, please unset the checkmark first and then add individual names.");
		}
	}
	
	if($link = Link::clicked())
	{
		if($link == "share-wishlist-not")
		{
			$recipientID = (int) $_GET['remove'];
			if(Database::query("DELETE FROM user_share_wishlist WHERE uni_id=? AND other_id=? LIMIT 1", array(Me::$id, $recipientID)))
			{
				$handle = User::get($recipientID, "handle");
				Alert::success("Not Allowed", $handle['handle'] . " may no longer view your wish list.");
			}
		}
	}
}
	
// Set page title
if(isset($url[1]))
{
	$config['pageTitle'] = "View " . (isset($recipient) ? $recipient . "'s " : "") . "Wish List";
}
else
{
	$config['pageTitle'] = "Share Wish List";
}

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
if(isset($url[1]))
{
	echo '
	<h2>View ' . (isset($recipient) ? $recipient . "'s " : "") . 'Wish List</h2>
	<table class="mod-table">
		<tr>';
	foreach(array("title", "position", "gender", "cost") as $col)
	{
		if (isset($_GET['sort']) && $_GET['sort'] == $col && !isset($_GET['reverse']))
			echo "
			<td>" . ucfirst($col) . " <a href='/view-wishlist/" . $owner['uni_id'] . "?sort=" . $col . "&reverse'>&#9650;</a></td>";
		elseif (isset($_GET['sort']) && $_GET['sort'] == $col)
			echo "
			<td>" . ucfirst($col) . " <a href='/view-wishlist/" . $owner['uni_id'] . "?sort=" . $col . "'>&#9660;</a></td>";
		else
			echo "
			<td>" . ucfirst($col) . " <a href='/view-wishlist/" . $owner['uni_id'] . "?sort=" . $col . "'>&#9651;</a></td>";
	}
	echo '
			<td>Package</td>
		</tr>';
	foreach ($wished as $itemData)
	{
		$itemData['item_id'] = (int) $itemData['item_id'];
		$own = AppAvatar::checkOwnItem((int) $owner['uni_id'], $itemData['item_id']);
		$package = Database::selectOne("SELECT title, year FROM packages_content INNER JOIN packages ON packages_content.package_id=packages.id WHERE item_id=? LIMIT 1", array($itemData['item_id']));
		echo '
		<tr' . ($own ? ' class="opaque"' : "") . '>
			<td><a href="/shop-search?title=' . $itemData['title'] . '&' . $itemData['position'] . '=on&submit=Search">' . $itemData['title'] . '</a>' . ($own ? " [&bull;]" : "") . '</td>
			<td>' . $itemData['position'] . '</td>
			<td>' . ($itemData['gender'] == "b" ? "both genders" : ($itemData['gender'] == "m" ? "male" : "female")) . '</td>
			<td>' . ($itemData['cost'] != 0 ? '<a href="/view-wishlist?buy=' . $itemData['item_id'] . '&' . Link::prepare("purchase-wish") . '" onclick="return confirm(\'Are you sure you want to buy ' . $itemData['title'] . '?\');">' . $itemData['cost'] . ' Auro</a>' : 'Preview Only') . '</td>
			<td>' . ($package ? $package['title'] . " (" . $package['year'] . ")" : "&nbsp;") . '</td>
		</tr>';
	}
	echo '
	</table>';
}
else
{
	// get permissions for own list
	$allow = Database::selectMultiple("SELECT DISTINCT other_id FROM user_share_wishlist WHERE uni_id=?", array(Me::$id));
	foreach($allow as $key => $a)
	{
		$allow[$key] = (int) $a['other_id'];
	}
	echo '
	<h2>Share Wish List</h2>
	<p><a href="/view-wishlist/' . Me::$id . '">Share this link!</a></p>
	<p>To actually be able to use the link above, user(s) must have permission to view your wish list. You can set those permissions here.</p>
	<form class="uniform" method="post">' . Form::prepare("share-wishlist-all") . '
		<p><input type="checkbox" name="everyone"' . (in_array(0, $allow) ? ' checked' : '') . '/> allow everyone <input type="submit" value="Set"></p>
	</form>
	<form class="uniform" method="post">' . Form::prepare("share-wishlist-one") . '
		<p><input type="text" name="addshare" maxlength="22" placeholder="Username"/> <input type="submit" value="Allow User"></p>
	</form>';
	foreach($allow as $a)
	{
		if($a == 0)	{ continue; }
		$handle = User::get($a, "handle");
		echo '
		<a href="/view-wishlist?remove=' . $a . '&' . Link::prepare("share-wishlist-not") . '">&#10006;</a> ' . $handle['handle'] . '<br/>';
	}
	echo '
	<div class="spacer"></div>';	
	
	// get permissions for other lists
	echo '
	<h2>Available Wish Lists</h2>
	<p>These users have made their wish list available to everyone or to you specifically. Your own wish list is not included here.</p>';
	$lists = Database::selectMultiple("SELECT DISTINCT user_share_wishlist.uni_id, handle FROM user_share_wishlist INNER JOIN users ON user_share_wishlist.uni_id=users.uni_id WHERE (other_id=? OR other_id=?) AND user_share_wishlist.uni_id!=? ORDER BY handle LIMIT " . ($_GET['page']*20 + 0) . ",20", array(0, Me::$id, Me::$id));
	echo '
	<p><ol start="' . ($_GET['page']*20 + 1) . '" style="list-style-type:decimal;margin-left:1em;">';
	foreach($lists as $list)
	{
		if($list['uni_id'] == Me::$id)	{ continue; }
		echo '
		<li><a href="/view-wishlist/' . $list['uni_id'] . '">' . $list['handle'] . '</a></li>';
	}
	echo '
	</ol></p>';
	if($_GET['page'] > 0 or isset($lists[19]))
	{
		echo '
	<br/>';
		if($_GET['page'] > 0)
		{
			echo '
	<a href="/view-wishlist?page=' . ($_GET['page']-1) . '">Previous <span class="icon-arrow-left"></span></a>';
		}
		if(isset($lists[19]))
		{
			echo '
	<a href="/view-wishlist?page=' . ($_GET['page']+1) . '"><span class="icon-arrow-right"></span> Next</a>';
		}
	}
}
echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
