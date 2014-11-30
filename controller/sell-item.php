<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/");
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

// Check item data
$item = AppAvatar::itemData($url[1]);
if(!$item)
{
	header("Location: /dress-avatar"); exit;
}

// Get cost
$item['cost'] = AppAvatar::itemMinCost($url[1], true);
if($item['cost'] != 0)
{
	$item['cost'] = (int) $item['cost'];
}
else
{
	Alert::error("No Cost", "The item's value could not be determined.");
}

$wrappers = AppAvatar::wrappers();

// Check if you sold the item
if(Form::submitted("sell-item") && !Alert::hasErrors())
{		
	if(Auro::grant(Me::$id, (int) round($item['cost']/2), "Sold " . $item['title'] . (in_array($item['id'], $wrappers) ? ' (Wrapper)' : ''), $config['site-name']))
	{
		AppAvatar::dropItem(Me::$id, $url[1], "Sold to Shop");
		Alert::saveSuccess("Item Sold", 'You have sold ' . $item['title'] . ' for ' . round($item['cost']/2) . ' Auro.');
		header("Location: /dress-avatar?position=" . $item['position']); exit;	
	}
}

// Check if you own the item
$ownItem = AppAvatar::checkOwnItem(Me::$id, $url[1]);
if(!$ownItem)
{
	Alert::saveError("Do Not Own Item", "You do not own this item!");
	header("Location: /dress-avatar"); exit;
}

// warn about accidental sales of valuable items
if($item['rarity_level'] != 0)
{
	Alert::info("Rare Item", "This item is exotic, limited or otherwise not easily purchasable. You might not be able to obtain it again.");
}

// Set page title
$config['pageTitle'] = "Sell " . $item['title'] . (in_array($item['id'], $wrappers) ? ' (Wrapper)' : '');

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
	<div class="overwrap-line">Sell ' . $item['title'] . (in_array($item['id'], $wrappers) ? ' (Wrapper)' : '') . '</div>
	<div class="inner-box">';
if(!Alert::hasErrors())
{	
	echo '
	<p>Are you sure you want to sell ' . $item['title'] . (in_array($item['id'], $wrappers) ? ' (Wrapper)' : '') . ' for ' . round($item['cost']/2) . ' Auro?</p>';
	
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
	<form class="uniform" action="/sell-item/' . $item['id'] . '" method="post">' . Form::prepare("sell-item") . '
		<input type="submit" name="submit" value="Sell" />
	</form>';
}
echo '
	</div>
</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
