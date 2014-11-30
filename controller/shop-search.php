<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/shop-search");
}

// Check if you have an avatar
if(!isset($avatarData['base']))		{ header("Location: /create-avatar"); exit; }

$positions = AppAvatar::positions();

if (!isset($_GET['title']))
	$_GET['title'] = "";
if (!isset($_GET['gender']))
	$_GET['gender'] = "";
if (!isset($_GET['shop']))
	$_GET['shop'] = 0;
$_GET['shop'] = (int) $_GET['shop'];
if (!isset($_GET['sortby']))
	$_GET['sortby'] = "";
if (!isset($_GET['purchasable']))
	$_GET['purchasable'] = "";
if (!isset($_GET['owned']))
	$_GET['owned'] = "";
if (!isset($_GET['cont']) || $_GET['cont'] < 0)
	$_GET['cont'] = 0;
$_GET['cont'] = (int) $_GET['cont'];
if (!isset($_GET['start']) || $_GET['start'] < 0)
	$_GET['start'] = 0;
$_GET['start'] = (int) $_GET['start'];
$result = array();

// Run Search
if(isset($_GET['submit']))
{
	// Prepare Search
	$comma = "";
	$sqlwhere = "";
	$sqlorder = "";
	$questionmarks = array();
	
	// positions
	$collect = array();
	foreach($positions as $pos)
	{
		if(isset($_GET[$pos]))
		{
			$collect[] = "'" . $pos . "'";
		}
	}
	if($collect != array())
	{
		$sqlwhere .= $comma . "position IN (" . implode(",", $collect) . ")";
		$comma = " AND ";
	}
	
	// name
	if(isset($_GET['title'][2]))
	{
		$sqlwhere .= $comma . "title LIKE ?";
		$comma = " AND ";
		$questionmarks[] = "%" . trim($_GET['title']) . "%";
	}
	
	// gender
	switch($_GET['gender'])
	{
		case "both":
			$sqlwhere .= $comma . "gender='b'";
			$comma = " AND ";
			break;
		case "female-only":
			$sqlwhere .= $comma . "gender='f'";
			$comma = " AND ";
			break;
		case "male-only":
			$sqlwhere .= $comma . "gender='m'";
			$comma = " AND ";
			break;
		case "fab":
			$sqlwhere .= $comma . "gender IN ('f', 'b')";
			$comma = " AND ";
			break;
		case "mab":
			$sqlwhere .= $comma . "gender IN ('m', 'b')";
			$comma = " AND ";
			break;
	}
	
	// shop
	if($_GET['shop'] != 0)
	{
		$sqlwhere .= $comma . "shop_id=?";
		$comma = " AND ";
		$questionmarks[] = $_GET['shop'];
	}
	
	// purchasable
	if($_GET['purchasable'] != "")
	{
		if($_GET['purchasable'] == "yes")		{ $sqlwhere .= $comma . "rarity_level='0'"; }
		else if($_GET['purchasable'] == "no")	{ $sqlwhere .= $comma . "rarity_level!='0'"; }
		$comma = " AND ";
	}
	
	// order
	if($_GET['sortby'] != "")
	{
		if(in_array($_GET['sortby'], array("title_asc", "title_desc", "gender_asc", "gender_desc", "shop_asc", "shop_desc", "cost_asc", "cost_desc", "position_asc", "position_desc", "id_asc", "id_desc")))
		{
			$_GET['sortby'] = str_replace("_", " ", $_GET['sortby']);
			if(strpos($_GET['sortby'], "cost") === false)
			{
				$_GET['sortby'] = str_replace("shop", "shop_id", $_GET['sortby']);
				$sqlorder = " ORDER BY " . $_GET['sortby'];
			}
			else
			{
				$sqlorder = " ORDER BY rarity_level" . (strpos($_GET['sortby'], "desc") > 0 ? " desc" : "") . ", " . $_GET['sortby'];
			}
			if(strpos($_GET['sortby'], "title") === false)
			{
				$sqlorder .= ", title";
			}
		}
	}
	
	// remove items from staff shops
	if(Me::$clearance < 5)
	{
		$disallow = "13,16,17,19";
	}
	else
	{
		$disallow = "";
	}
	
	// build query
	$result = Database::selectMultiple("SELECT id, title, position, gender, rarity_level, shop_id, cost FROM items INNER JOIN shop_inventory ON items.id=shop_inventory.item_id " . ($sqlwhere != "" || $disallow != "" ? " WHERE " : "") . ($sqlwhere != "" ? $sqlwhere : "") . ($disallow != "" && $sqlwhere != "" ? " AND " : "") . ($disallow != "" ? "shop_id NOT IN (" . $disallow . ")" : "") . $sqlorder . ($_GET['owned'] == "" ? " LIMIT " . ($_GET['start']*60) . ", 60" : ""), $questionmarks);
}

// Set page title
$config['pageTitle'] = "Shop Search";

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

require(SYS_PATH . "/controller/includes/side-panel.php");

// Display Page
echo '
<div id="panel-right"></div>
<div id="content">' .
Alert::display() . '
<div class="overwrap-box">
	<div class="overwrap-line">Shop Search</div>
	<div class="inner-box">';

// Shop Display
$shops = array(
	1 => "A Cut Above",
	2 => "All That Glitters",
	5 => "Body Shop",
	6 => "Finishing Touch",
	7 => "Haute Couture",
	3 => "Heart and Sole",
	8 => "Junk Drawer",
	9 => "Looking Glass",
	4 => "Pr&ecirc;t &agrave; Porter",
	10 => "Time Capsule",
	11 => "Under Dressed",
	12 => "Vogue Veneers",
	15 => "Avatar Museum",
	18 => "Credit Shop",
	14 => "Exotic Exhibit"
);
if(Me::$clearance >= 5)
{
	$shops[13] = "Archive";
	$shops[16] = "Staff Shop";
	$shops[17] = "Test Shop";
	$shops[19] = "Wrapper Replacements";
}

// Search Display
echo '
	<form class="uniform" action="/shop-search" method="get">
		<input type="text" name="title" maxlength="30" size="15" placeholder="Item Name" value="' . $_GET['title'] . '"/> 
		<select name="gender"><option value="">Gender:</option><option value="both"' . ($_GET['gender'] == "both" ? " selected" : "") . '>both</option><option value="female-only"' . ($_GET['gender'] == "female-only" ? " selected" : "") . '>female-only</option><option value="male-only"' . ($_GET['gender'] == "male-only" ? " selected" : "") . '>male-only</option><option value="fab"' . ($_GET['gender'] == "fab" ? " selected" : "") . '>female or both</option><option value="mab"' . ($_GET['gender'] == "mab" ? " selected" : "") . '>male or both</option></select> 
		<select name="shop"><option value="">Shop:</option><option value="1"' . ($_GET['shop'] == 1 ? " selected" : "") . '>A Cut Above</option><option value="4"' . ($_GET['shop'] == 4 ? " selected" : "") . '>Pr&ecirc;t &agrave; Porter</option><option value="7"' . ($_GET['shop'] == 7 ? " selected" : "") . '>Haute Couture</option><option value="10"' . ($_GET['shop'] == 10 ? " selected" : "") . '>Time Capsule</option><option value="2"' . ($_GET['shop'] == 2 ? " selected" : "") . '>All That Glitters</option><option value="5"' . ($_GET['shop'] == 5 ? " selected" : "") . '>Body Shop</option><option value="8"' . ($_GET['shop'] == 8 ? " selected" : "") . '>Junk Drawer</option><option value="11"' . ($_GET['shop'] == 11 ? " selected" : "") . '>Under Dressed</option><option value="3"' . ($_GET['shop'] == 3 ? " selected" : "") . '>Heart and Sole</option><option value="6"' . ($_GET['shop'] == 6 ? " selected" : "") . '>Finishing Touch</option><option value="9"' . ($_GET['shop'] == 9 ? " selected" : "") . '>Looking Glass</option><option value="12"' . ($_GET['shop'] == 12 ? " selected" : "") . '>Vogue Veneers</option><option value="14"' . ($_GET['shop'] == 14 ? " selected" : "") . '>Exotic Exhibit</option><option value="15"' . ($_GET['shop'] == 15 ? " selected" : "") . '>Avatar Museum</option><option value="18"' . ($_GET['shop'] == 18 ? " selected" : "") . '>Credit Shop</option>' . (Me::$clearance >= 5 ? '<option value="13"' . ($_GET['shop'] == 13 ? " selected" : "") . '>Archive</option><option value="16"' . ($_GET['shop'] == 16 ? " selected" : "") . '>Staff Shop</option><option value="17"' . ($_GET['shop'] == 17 ? " selected" : "") . '>Test Shop</option><option value="19"' . ($_GET['shop'] == 19 ? " selected" : "") . '>Wrapper Replacements</option>' : "") . '</select> 
		<select name="sortby"><option value="">Sort By:</option><option value="title_asc"' . ($_GET['sortby'] == "title asc" ? " selected" : "") . '>Name (asc)</option><option value="title_desc"' . ($_GET['sortby'] == "title desc" ? " selected" : "") . '>Name (desc)</option><option value="gender_asc"' . ($_GET['sortby'] == "gender asc" ? " selected" : "") . '>Gender (asc)</option><option value="gender_desc"' . ($_GET['sortby'] == "gender desc" ? " selected" : "") . '>Gender (desc)</option><option value="shop_asc"' . ($_GET['sortby'] == "shop asc" ? " selected" : "") . '>Shop (asc)</option><option value="shop_desc"' . ($_GET['sortby'] == "shop desc" ? " selected" : "") . '>Shop (desc)</option><option value="cost_asc"' . ($_GET['sortby'] == "cost asc" ? " selected" : "") . '>Cost (asc)</option><option value="cost_desc"' . ($_GET['sortby'] == "cost desc" ? " selected" : "") . '>Cost (desc)</option><option value="position_asc"' . ($_GET['sortby'] == "position asc" ? " selected" : "") . '>Position (asc)</option><option value="position_desc"' . ($_GET['sortby'] == "position desc" ? " selected" : "") . '>Position (desc)</option><option value="id_asc"' . ($_GET['sortby'] == "id asc" ? " selected" : "") . '>ID (asc)</option><option value="id_desc"' . ($_GET['sortby'] == "id desc" ? " selected" : "") . '>ID (desc)</option></select> 
		<select name="purchasable"><option value="">Purchasable:</option><option value="yes"' . ($_GET['purchasable'] == "yes" ? " selected" : "") . '>Yes</option><option value="no"' . ($_GET['purchasable'] == "no" ? " selected" : "") . '>No</option></select>
		<select name="owned"><option value="">Owned:</option><option value="yes"' . ($_GET['owned'] == "yes" ? " selected" : "") . '>Yes</option><option value="no"' . ($_GET['owned'] == "no" ? " selected" : "") . '>No</option></select>
		<br/><br/>';

foreach($positions as $pos)
{
	echo '<div style="width:8em; display:inline-block;"><input type="checkbox" name="' . $pos . '"' . (isset($_GET[$pos]) ? " checked" : "") . '/> ' . $pos . "</div>";
}
echo '
		<br/><br/><input type="submit" name="submit" value="Search"/> <input onclick="var ins = $(\'input[type=checkbox]\'); if (this.checked) { for (var i=0; i<ins.length-1; i++) { ins[i].checked=true; } } else { for (var i=0; i<ins.length-1; i++) { ins[i].checked=false; } }" name="checkall" type="checkbox" style="margin-left:43px;"' . (isset($_GET['checkall']) ? " checked" : "") . '/ > <strong>Select/Deselect All</strong>
	</form><div class="spacer"></div>';
	
// check for (non-)owned items
$checked = array();
if($_GET['owned'] != "")
{
	$todo = array();
	foreach($result as $item)
	{
		$item['id'] = (int) $item['id'];
		if(AppAvatar::checkOwnItem(Me::$id, $item['id']))
		{
			$checked[$item['id']] = true;
			if($_GET['owned'] == "yes")	{ $todo[] = $item['id']; }
		}
		else
		{
			$checked[$item['id']] = false;
			if($_GET['owned'] == "no")	{ $todo[] = $item['id']; }
		}
	}
}

$wrappers = AppAvatar::wrappers();

// output results
$found = 0;
foreach($result as $item)
{
	$item['id'] = (int) $item['id'];
	if($_GET['owned'] != "" && !in_array($item['id'], $todo))	{ continue; }	
	$found++;
	if($found <= $_GET['cont']*60) { continue; }

	// adjust gender if item not available for the gender
	$gender = $avatarData['gender_full'];
	if(!in_array($item['gender'], array("b", $avatarData['gender']))) { $gender = ($avatarData['gender_full'] == "male" ? "female" : "male"); }

	// Get list of colors
	$colors = AppAvatar::getItemColors($item['position'], $item['title'], (in_array($item['gender'], array($avatarData['gender'], "b")) ? $avatarData['gender'] : ($avatarData['gender'] == "m" ? "f" : "m")));
	if(!$colors) { continue; }
	
	// Display the Item					
	echo '
	<div class="item_block' . ($avatarData['gender_full'] != $gender ? " opaque" : "") . '">
		' . ($avatarData['gender_full'] == $gender ? '<a href="javascript:review_item(\'' . $item['id'] . '\');">' : '') . '<img id="img_' . $item['id'] . '" src="/avatar_items/' . $item['position'] . '/' . $item['title'] . '/default_' . $gender . '.png" />' . ($avatarData['gender_full'] == $gender ? '</a>' : '') . '<br />
		' . $item['title'] . (in_array($item['id'], $wrappers) ? ' (Wrapper)' : '') . '<br /><span style="font-size:0.6em;"><a href="/shop-search?submit=Search&' . $item['position'] . '=on&gender=' . $avatarData['gender'] . 'ab">' . $item['position'] . '</a>, ' . ($item['gender'] == "b" ? 'both genders' : ($item['gender'] == "m" ? 'male' : 'female')) . '<br/><a href="/shop/' . $item['shop_id'] . '">' . $shops[$item['shop_id']] . '</a></span><br />
		<select id="item_' . $item['id'] . '" onChange="switch_item(\'' . $item['id'] . '\', \'' . $item['position'] . '\', \'' . $item['title'] . '\', \'' . $gender . '\');">';
		
	foreach($colors as $color)
	{
		echo '
			<option name="' . $color . '">' . $color . '</option>';
	}
		
	echo '
		</select>
		<br/><a href="/wish-list?add=' . $item['id'] . '">Add to Wish List</a><br/>';
	if($item['rarity_level'] == 0 || Me::$clearance >= 5)
	{
		echo '
		<a href="/purchase-item/' . $item['id'] . '?shopID=' . $item['shop_id'] . '">Buy for ' . (int) $item['cost'] . '</a>';
	}
	else
	{
		echo '
		Preview Only';
	}
	if((isset($checked[$item['id']]) && $checked[$item['id']] == true) || AppAvatar::checkOwnItem(Me::$id, $item['id']))
	{
		echo ' [&bull;]';
	}
	if(Me::$clearance >= 5)
	{
		echo '
		<br/><a href="/staff/item-edit/' . $item['id'] . '">Edit Item</a>';
	}
	echo '
	</div>';
	
	if($found == ($_GET['cont']+1)*60) { break; }
}

// pages
if(strpos($_SERVER['REQUEST_URI'], "start=") > 0)
{
	$url = substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], "&"));
}
else
{
	$url = $_SERVER['REQUEST_URI'];
}

if ($_GET['owned'] == "")
{
	if($_GET['start'] > 0 || count($result) == 60)
	{
		echo '<div class="spacer"></div>';
		if($_GET['start'] > 0)
		{
			echo '
	<a href="' . $url . "&start=" . max(($_GET['start'] - 1), 0) . '">Previous <span class="icon-arrow-left"></span></a>';
		}
		if(count($result) == 60)
		{
			echo '
	<a href="' . $url . "&start=" . ($_GET['start'] + 1) . '"><span class="icon-arrow-right"> Next</span></a>';
		}
	}
}
else
{
	if($_GET['cont'] > 0 || $found % 60 == 0)
	{
		echo '<div class="spacer"></div>';
		if($_GET['cont'] > 0)
		{
			echo '
		<a href="' . $url . "&cont=" . max(($_GET['cont'] - 1), 0) . '">Previous <span class="icon-arrow-left"></span></a>';			
		}
		if($found % 60 == 0)
		{
			echo '
		<a href="' . $url . "&cont=" . ($_GET['cont'] + 1) . '"><span class="icon-arrow-right"> Next</span></a>';
		}
	}
}

echo '
	</div>
</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
