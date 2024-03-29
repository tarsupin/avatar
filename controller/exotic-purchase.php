<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/exotic-purchase");
}

// Require avatar
if(!isset($avatarData['base']))
{
	header("Location: /create-avatar"); exit;
}

$wrappers = AppAvatar::wrappers();

// purchase item
if(Form::submitted("purchase-exotic-item"))
{
	if(AppExotic::buyItem((int) $_POST['slot'], (int) $_POST['item']))
	{
		$_POST['item'] = (int) $_POST['item'];
		$itemData = AppAvatar::itemData($_POST['item'], "title");
		Alert::success("Purchased Item", "You have purchased " . $itemData['title'] . (in_array($_POST['item'], $wrappers) ? ' (Wrapper)' : '') . ". Thank you for giving to UniFaction!");
	}
	else
	{
		Alert::error("Not Available", "Sorry, this item is no longer available, or you do not have enough UniJoule to purchase it.");
	}
}

if(Form::submitted("purchase-exotic-package"))
{
	if(AppExotic::buyPackage((int) $_POST['package'], ($_POST['bulk'] ? true : false)))
	{
		$exist = Database::selectOne("SELECT title FROM packages WHERE id=? AND year=? AND month=?", array((int) $_POST['package'], (int) date("Y"), (int) date("n")));
		if($exist['title'] == '')
		{
			$exist['title'] = date("F") . ' Package';
		}
		Alert::success("Purchased Package", "You have purchased " . ($_POST['bulk'] ? "5 " : "") . $exist['title'] . ($_POST['bulk'] ? "s" : "") . ". Thank you for giving to UniFaction!");
	}
	else
	{
		Alert::error("Not Available", "Sorry, this package is no longer available, or you do not have enough UniJoule to purchase it.");
	}
}

// Set page title
$config['pageTitle'] = "Purchase Exotic Item";

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Run Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

echo '
<div id="panel-right"></div>
<div id="content">' .
Alert::display() . '
<div class="overwrap-box">
<div class="overwrap-line">Purchase Exotic Item</div>
<div class="inner-box">
<p>The items available in this shop rotate as indicated by the timer and stock below each one.<br/>You will be notified when an item on your wish list rotates in.</p>';

// current package		
$exist = Database::selectOne("SELECT id, title FROM packages WHERE year=? AND month=?", array((int) date("Y"), (int) date("n")));
if($exist != array())
{
	$content = Database::selectMultiple("SELECT item_id FROM packages_content WHERE package_id=?", array($exist['id']));
	if($content != array())
	{
		echo '
	<div class="item_block">
		' .  (File::exists('assets/exotic_packages/' . lcfirst(date("F")) . '_' . date("Y") . '.png') ? '<img src="assets/exotic_packages/' . lcfirst(date("F")) . '_' . date("Y") . '.png"/>' : '<span class="opaque">Image coming soon.</span>') . '<br/>' . $exist['title'] . '<br/><span style="font-size:0.6em;">' . date("F") . ' ' . date("Y") . '<br/>leaves ' . Time::fuzzy((int) (mktime(0, 0, 0, date("n")+1, 1)-1)) . '<br/>Stock: unlimited</span>
		<select id="bulk" onchange="document.getElementById(\'bulkprice\').innerHTML=(this.value == \'\' ? \'3.50\' : \'15.00\'); document.getElementsByName(\'bulk\')[0].value=this.value;"><option value="">1 Package</option><option value="bulk">5 Packages</option></select>
		<br/><a href="/exotic-list">View Content</a>
		<br/><span id="bulkprice">3.50</span> UniJoule';
		if(AppAvatar::checkOwnPackage(Me::$id, (int) $exist['id']))
		{
			echo ' [&bull;]';
		}
		echo '
		<br/><br/>
		<form class="uniform" method="post">' . Form::prepare("purchase-exotic-package") . '
			<input type="hidden" name="slot" value="0"/>
			<input type="hidden" name="bulk" value=""/>
			<input type="hidden" name="package" value="' . $exist['id'] . '"/>
			<input type="submit" value="Purchase" onclick="return confirm(\'Are you sure you want to purchase \' + (document.getElementById(\'bulk\').value==\'\' ? \'1\' : \'5\') + \' ' . $exist['title'] . '\' + (document.getElementById(\'bulk\').value==\'\' ? \'\' : \'s\') + \'?\');"/>
		</form>
	</div>';
	}
	else
	{
		echo '
	<div class="item_block opaque">
		EP coming soon.
	</div>';
	}
}
else
{
	echo '
	<div class="item_block opaque">
		EP coming soon.
	</div>';
}	

// determine items for slots and display
for($i=1; $i<5; $i++)
{
	$slot = AppExotic::getSlot($i);

	if($slot == array() || $slot['expire'] < time())
	{
		$slot = AppExotic::chooseItem($i);
		if($slot != array())
		{
			// save new item
			AppExotic::saveSlot($i, $slot);
			
			// notify people who have it on their wishlist
			$wish = Database::selectMultiple("SELECT uni_id FROM user_wish WHERE item_id=?", array($slot['itemData']['id']));
			$uniIDList = array();
			foreach($wish as $w)
			{
				$uniIDList[] = (int) $w['uni_id'];
			}
			Notifications::createMultiple($uniIDList, SITE_URL . "/exotic-purchase", $slot['itemData']['title'] . (in_array($slot['itemData']['id'], $wrappers) ? ' (Wrapper)' : '') . " has rotated into the Exotic Shop.");
		}
	}
	else
	{
		$slot['itemData'] = AppAvatar::itemData((int) $slot['item'], "id, title, position, gender");
		$date = Database::selectOne("SELECT year, month FROM packages INNER JOIN packages_content ON packages.id=packages_content.package_id WHERE item_id=? LIMIT 1", array((int) $slot['item']));
		$slot['year'] = (int) $date['year'];
		$slot['month'] = (int) $date['month'];
	}

	if($slot !== false && $slot != array())
	{
		// adjust gender if item not available for the gender
		$gender = $avatarData['gender_full'];
		if(!in_array($slot['itemData']['gender'], array("b", $avatarData['gender']))) { $gender = ($avatarData['gender_full'] == "male" ? "female" : "male"); }
		
		// Get list of colors
		$colors = AppAvatar::getItemColors($slot['itemData']['position'], $slot['itemData']['title'], (in_array($slot['itemData']['gender'], array($avatarData['gender'], "b")) ? $avatarData['gender'] : ($avatarData['gender'] == "m" ? "f" : "m")));
		if(!$colors) { continue; }
		
		// Display the Item					
		echo '
	<div class="item_block' . ($avatarData['gender_full'] != $gender ? " opaque" : "") . '">
		' . ($avatarData['gender_full'] == $gender ? '<a href="javascript:review_item(\'' . $slot['itemData']['id'] . '\');">' : '') . '<img id="img_' . $slot['itemData']['id'] . '" src="/avatar_items/' . $slot['itemData']['position'] . '/' . $slot['itemData']['title'] . '/default_' . $gender . '.png" />' . ($avatarData['gender_full'] == $gender ? '</a>' : '') . '<br />' . $slot['itemData']['title'] . (in_array($slot['itemData']['id'], $wrappers) ? ' (Wrapper)' : '') . '<br/><span style="font-size:0.6em;">' . date("F", mktime(0, 0, 0, $slot['month'])) . ' ' . $slot['year'] . '<br/>leaves ' . Time::fuzzy((int) $slot['expire']) . '<br/>Stock: ' . ($slot['stock'] == 0 ? 'unlimited' : $slot['stock']) . '</span>
		<select id="item_' . $slot['itemData']['id'] . '" onChange="switch_item(\'' . $slot['itemData']['id'] . '\', \'' . $slot['itemData']['position'] . '\', \'' . $slot['itemData']['title'] . '\', \'' . $gender . '\');">';
			
		foreach($colors as $color)
		{
			echo '
			<option name="' . $color . '">' . $color . '</option>';
		}
			
		echo '
		</select>
		<br/><a href="' . SITE_URL . '/wish-list?add=' . $slot['itemData']['id'] . '"/>Add to Wish List</a>
		<br/>' . number_format($slot['cost'], 2) . ' UniJoule';
		if(AppAvatar::checkOwnItem(Me::$id, (int) $slot['itemData']['id']))
		{
			echo ' [&bull;]';
		}
		echo '
		<br/><br/>
		<form class="uniform" method="post">' . Form::prepare("purchase-exotic-item") . '
			<input type="hidden" name="slot" value="' . $i . '"/>
			<input type="hidden" name="item" value="' . $slot['itemData']['id'] . '"/>
			<input type="submit" value="Purchase" onclick="return confirm(\'Are you sure you want to purchase ' . $slot['itemData']['title'] . (in_array($slot['itemData']['id'], $wrappers) ? ' (Wrapper)' : '') . '?\');"/>
		</form>
	</div>';
	}
	else
	{
		echo '
	<div class="item_block opaque">
		Item coming soon.
	</div>';
	}
}

echo '
	<div class="spacer"></div>
	<hr />';

// determine items from credit shop for slots and display
for($i=5; $i<10; $i++)
{
	$slot = AppExotic::getSlot($i);

	if($slot == array() || $slot['expire'] < time())
	{
		// returns 5 items, so this IF block runs at most once
		$slots = AppExotic::chooseItem($i);
		$slot = $slots[$i];
		
		// save new items
		for($j=5; $j<10; $j++)
		{
			AppExotic::saveSlot($j, $slots[$j]);
			
			// notify people who have it on their wishlist
			$wish = Database::selectMultiple("SELECT uni_id FROM user_wish WHERE item_id=?", array($slots[$j]['itemData']['id']));
			$uniIDList = array();
			foreach($wish as $w)
			{
				$uniIDList[] = (int) $w['uni_id'];
			}
			Notifications::createMultiple($uniIDList, SITE_URL . "/exotic-purchase", $slots[$j]['itemData']['title'] . (in_array($slots[$j]['itemData']['id'], $wrappers) ? ' (Wrapper)' : '') . " has rotated into the Exotic Shop.");
		}
	}
	else
	{
		$slot['itemData'] = AppAvatar::itemData((int) $slot['item'], "id, title, position, gender");
	}

	if($slot !== false && $slot != array())
	{
		// adjust gender if item not available for the gender
		$gender = $avatarData['gender_full'];
		if(!in_array($slot['itemData']['gender'], array("b", $avatarData['gender']))) { $gender = ($avatarData['gender_full'] == "male" ? "female" : "male"); }
		
		// Get list of colors
		$colors = AppAvatar::getItemColors($slot['itemData']['position'], $slot['itemData']['title'], (in_array($slot['itemData']['gender'], array($avatarData['gender'], "b")) ? $avatarData['gender'] : ($avatarData['gender'] == "m" ? "f" : "m")));
		if(!$colors) { continue; }
		
		// Display the Item					
		echo '
	<div class="item_block' . ($avatarData['gender_full'] != $gender ? " opaque" : "") . '">
		' . ($avatarData['gender_full'] == $gender ? '<a href="javascript:review_item(\'' . $slot['itemData']['id'] . '\');">' : '') . '<img id="img_' . $slot['itemData']['id'] . '" src="/avatar_items/' . $slot['itemData']['position'] . '/' . $slot['itemData']['title'] . '/default_' . $gender . '.png" />' . ($avatarData['gender_full'] == $gender ? '</a>' : '') . '<br />' . $slot['itemData']['title'] . (in_array($slot['itemData']['id'], $wrappers) ? ' (Wrapper)' : '') . '<br/><span style="font-size:0.6em;">leaves ' . Time::fuzzy((int) $slot['expire']) . '<br/>Stock: ' . ($slot['stock'] == 0 ? 'unlimited' : $slot['stock']) . '</span>
		<select id="item_' . $slot['itemData']['id'] . '" onChange="switch_item(\'' . $slot['itemData']['id'] . '\', \'' . $slot['itemData']['position'] . '\', \'' . $slot['itemData']['title'] . '\', \'' . $gender . '\');">';
			
		foreach($colors as $color)
		{
			echo '
			<option name="' . $color . '">' . $color . '</option>';
		}
			
		echo '
		</select>
		<br/><a href="' . SITE_URL . '/wish-list?add=' . $slot['itemData']['id'] . '"/>Add to Wish List</a>
		<br/>' . number_format($slot['cost'], 2) . ' UniJoule';
		if(AppAvatar::checkOwnItem(Me::$id, (int) $slot['itemData']['id']))
		{
			echo ' [&bull;]';
		}
		echo '
		<br/><br/>
		<form class="uniform" method="post">' . Form::prepare("purchase-exotic-item") . '
			<input type="hidden" name="slot" value="' . $i . '"/>
			<input type="hidden" name="item" value="' . $slot['itemData']['id'] . '"/>
			<input type="submit" value="Purchase" onclick="return confirm(\'Are you sure you want to purchase ' . $slot['itemData']['title'] . (in_array($slot['itemData']['id'], $wrappers) ? ' (Wrapper)' : '') . '?\');"/>
		</form>
	</div>';
	}
}

echo '
</div>
</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
