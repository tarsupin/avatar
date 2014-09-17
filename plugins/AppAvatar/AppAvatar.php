<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

----------------------------------------
------ About the AppAvatar Plugin ------
----------------------------------------

This class provides handling of the dress-up avatars.

-------------------------------
------ Methods Available ------
-------------------------------

$avatarData	= AppAvatar::avatarData($uniID);
$positions	= AppAvatar::positions();
$items		= AppAvatar::getUserItems($uniID, $position, $gender);
$items		= AppAvatar::getShopItems($shopID);
$items		= AppAvatar::getEquippedItems($uniID, $gender)
$positions	= AppAvatar::getInvPositions($uniID)

$itemData	= AppAvatar::itemData($itemID, $plusShopData = false);
$colors		= AppAvatar::getItemColors($position, $title);
			= AppAvatar::itemHasColor($position, $title, $gender, $color);

AppAvatar::updateImage($uniID);

AppAvatar::createAvatar($uniID, $base, $gender);
AppAvatar::receiveItem($uniID, $itemID);
AppAvatar::dropItem($uniID, $itemID);

AppAvatar::checkOwnItem($uniID, $itemID);
AppAvatar::equip($uniID, $itemID, $color);
AppAvatar::unequip($uniID, $itemID);

AppAvatar::sort($uniID, $itemID, $toOrder);
AppAvatar::sortMove($uniID, $fromOrder, $toOrder);
AppAvatar::sortDelete($uniID, $deleteOrder);
AppAvatar::sortInsert($uniID, $insertOrder);

$title = AppAvatar::getShopTitle($shopID);

*/

abstract class AppAvatar {
	
	
/****** Get Avatar Data ******/
	public static function avatarData
	(
		$uniID			// <int> The Uni-Account to get the avatar data from.
	)					// RETURNS <str:mixed> data on the avatar, or array with a blank avatar image source.
	
	// $avatar = AppAvatar::avatarData($uniID);
	{
		if(!$uniID or !$avatar = Database::selectOne("SELECT base, gender, date_lastUpdate FROM avatars WHERE uni_id=? LIMIT 1", array($uniID)))
		{
			return array('src' => '/assets/images/blank-avatar.png');
		}
		
		// Prepare Values
		$avatar['gender_full'] = ($avatar['gender'] == "m" ? "male" : "female");
		$avatar['date_lastUpdate'] = (int) $avatar['date_lastUpdate'];
		
		$aviData = Avatar::imageData($uniID);
		
		$avatar['src'] = $aviData['path'];
		
		return $avatar;
	}
	
	
/****** Return list of valid avatar positions ******/
	public static function positions (
	)				// RETURNS <int:str> list of valid avatar positions
	
	// $positions = AppAvatar::positions();
	{
		return array(
			'ankles'
		,	'arms'
		,	'back'
		,	'background'
		,	'badge'
		,	'base'
		,	'belt'
		,	'body'
		,	'body_extras'
		,	'bodysuit'
		,	'coat'
		,	'deco'
		,	'dress'
		,	'earrings'
		,	'face'
		,	'face_extras'
		,	'foreground'
		,	'hair'
		,	'hair_extras'
		,	'handheld'
		,	'head'
		,	'legs'
		,	'misc'
		,	'neck'
		,	'overshirt'
		,	'pants'
		,	'pet'
		,	'shirt'
		,	'shoes'
		,	'shoulders'
		,	'skin'
		,	'skirt'
		,	'temp'
		,	'undershirt'
		,	'wings'
		,	'wrists'
		);
	}
	
	
/****** List a User's Items ******/
	public static function getUserItems
	(
		$uniID			// <int> The Uni-Account to list the items of.
	,	$position		// <str> The position of items to retrieve.
	,	$gender			// <str> The gender of the avatar.
	,	$group = true	// <bool> TRUE means you group similar items together.
	)					// RETURNS <int:[str:mixed]> list of items
	
	// $userItems = AppAvatar::getUserItems($uniID, $position, $gender);
	{
		if(!in_array($gender, array('male', 'female', 'm', 'f')))
		{
			return array();
		}
		
		return Database::selectMultiple("SELECT ui.item_id as id, i.title FROM user_items ui INNER JOIN items i ON i.id = ui.item_id WHERE ui.uni_id = ? AND i.position=? AND i.gender IN (?, ?)" . ($group == true ? ' GROUP BY i.title' : ''), array($uniID, $position, $gender[0], 'b'));
	}
	
	
/****** Get your Equipped Items ******/
	public static function getEquippedItems
	(
		$uniID			// <int> The ID of the avatar (Uni-Account) to retrieve items for.
	,	$gender			// <str> The gender to retrieve.
	)					// RETURNS <int:[str:mixed]> list of items
	
	// $equippedItems = AppAvatar::getEquippedItems($uniID, $gender);
	{
		$gender = ($gender[0] == "m" ? "male" : "female");
		
		return Database::selectMultiple("SELECT ae.item_id as id, ae.sort_order, ae.color, i.title, i.position, i.coord_x_" . $gender . " as coord_x, i.coord_y_" . $gender . " as coord_y FROM avatar_equipped ae INNER JOIN items i ON i.id = ae.item_id WHERE ae.uni_id=? ORDER BY ae.sort_order ASC", array($uniID));
	}
	
	
/****** Get your Inventory Positions ******/
	public static function getInvPositions
	(
		$uniID		// <int> The Uni-Account to check positions for.
	)				// RETURNS <int:str> list of items, or FALSE if failed.
	
	// $positions = AppAvatar::getInvPositions($uniID);
	{
		// Get the user's positions (if cached)
		if($posList = Cache::get("invLayers:" . $uniID))
		{
			return json_decode($posList, true);
		}
		
		// If the user's positions are stale, retrieve them normally
		$posList = array();
		$positions = Database::selectMultiple("SELECT i.position FROM user_items ui INNER JOIN items i ON i.id = ui.item_id WHERE ui.uni_id=? GROUP BY i.position", array($uniID));
		
		foreach($positions as $pos)
		{
			$posList[] = $pos['position'];
		}
		
		Cache::set("invLayers:" . $uniID, json_encode($posList), 60 * 3);
		
		return $posList;
	}
	
	
/****** Get Shop Items ******/
	public static function getShopItems
	(
		$shopID			// <int> The ID of the shop to get items from.
	,	$itemID = 0		// <int> A specific item to return from the shop, if desired.
	)					// RETURNS <array> list of items, or empty array if failed.
	
	// $items = AppAvatar::getShopItems($shopID, $itemID = 0);
	{
		if($itemID == 0)
		{
			return Database::selectMultiple("SELECT si.item_id as id, si.cost, i.title, i.position, i.gender FROM shop_inventory si INNER JOIN items i ON i.id = si.item_id WHERE si.shop_id=?", array($shopID));
		}
		
		return Database::selectOne("SELECT si.item_id as id, si.cost, i.title, i.position, i.gender, i.rarity_level FROM shop_inventory si INNER JOIN items i ON i.id = si.item_id WHERE si.shop_id=? AND si.item_id=?", array($shopID, $itemID));
	}
	
	
/****** Get Item Data ******/
	public static function itemData
	(
		$itemID			// <int> The ID of the item to get the data from.
	,	$columns = "*"	// <str> The columns to retrieve from the database.
	)					// RETURNS <str:mixed> data of the item, or FALSE if failed.
	
	// $itemData = AppAvatar::itemData($itemID);
	{
		return Database::selectOne("SELECT " . Sanitize::variable($columns, " ,`*") . " FROM items WHERE id=? LIMIT 1", array($itemID));
	}
	
	
/****** Get the list of colors that an item has ******/
	public static function getItemColors
	(
		$position		// <str> The position of the item (e.g. "hair", "chest", etc).
	,	$title			// <str> The title of the item.
	)					// RETURNS <array> a list of the item's colors, or empty array if none.
	
	// $colors = AppAvatar::getItemColors($position, $title);
	{
		// Get the cached color list (since this is much faster than a directory call
		if($colorList = Cache::get("color:" . substr(md5($position . $title), 0, 20)))
		{
			return json_decode($colorList, true);
		}
		
		// If the color cache is stale, retrieve it normally
		$colorList = array();
		$files = Dir::getFiles(APP_PATH . "/avatar_items/" . $position . "/" . $title);
		
		foreach($files as $file)
		{
			if(strpos($file, "ale.png") > -1)	// Matches for "_male.png" and "_female.png"
			{
				$colorList[] = substr($file, 0, strpos($file, "_"));
			}
		}
		
		$colorList = array_unique($colorList);
		
		// Remove keys from the list so that JSON value is minimized
		// In other words, it ends up like ['Blue','Green'] instead of {'0':'Blue','2':'Green'}
		$uniqueList = array();
		foreach($colorList as $c)
		{
			$uniqueList[] = $c;
		}
		
		Cache::set("color:" . substr(md5($position . $title), 0, 20), json_encode($uniqueList), 60 * 48);
		
		return $uniqueList;
	}
	
	
/****** Check if an item has a specific color ******/
	public static function itemHasColor
	(
		$position		// <str> The position of the item (e.g. "hair", "chest", etc).
	,	$title			// <str> The title of the item.
	,	$gender			// <str> The gender that is using this item.
	,	$color			// <str> The intended color of the item (to see if it's available).
	)					// RETURNS <bool> TRUE if the color is available, or FALSE if not.
	
	// AppAvatar::itemHasColor($position, $title, $gender, $color);
	{
		// Prepare Gender
		$gender = ($gender[0] == "m" ? "male" : "female");
		
		// Check if color exists
		if(File::exists(APP_PATH . "/avatar_items/" . $position . "/" . $title . "/" . $color . "_" . $gender . ".png"))
		{
			return true;
		}
		
		return false;
	}
	
	
/****** Update the Avatar's Image ******/
	public static function updateImage
	(
		$uniID		// <str> The Uni-Account to update
	,	$base		// <str> The character base to use.
	,	$gender		// <str> The gender to use.
	)				// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// AppAvatar::updateImage($uniID, $base, $gender);
	{
		// Retrieve Doll Data
		$aviData = Avatar::imageData($uniID);
		
		if(!$aviData) { return false; }
		
		// Handle Gender
		$gender = ($gender[0] == "m" ? "male" : "female");
		
		$cX = "coord_x_" . $gender;
		$cY = "coord_y_" . $gender;
		
		// Create a blank image
		$image = new Image("", 204, 383, "png");
		
		// Gather equipped items below the avatar, including their coordinates and position
		$equippedItems = Database::selectMultiple("SELECT ae.sort_order, ae.color, i.title, i.position, i." . $cX . ", i." . $cY . " FROM avatar_equipped ae INNER JOIN items i ON i.id = ae.item_id WHERE ae.uni_id=? AND ae.sort_order < ? ORDER BY ae.sort_order ASC", array($uniID, 0));
		
		foreach($equippedItems as $item)
		{
			$image->paste(APP_PATH . "/avatar_items/" . $item['position'] . "/" . $item['title'] . "/" . $item['color'] . "_" . $gender . ".png", (int) $item[$cX], (int) $item[$cY]);
		}
		
		// Load your avatar's base
		$image->paste(APP_PATH . '/avatar_items/base/' . $base . '_' . $gender . '.png', 0, 0);
		
		// Gather equipped items above the avatar, including their coordinates and position
		$equippedItems = Database::selectMultiple("SELECT ae.sort_order, ae.color, i.title, i.position, i." . $cX . ", i." . $cY . " FROM avatar_equipped ae INNER JOIN items i ON i.id = ae.item_id WHERE ae.uni_id=? AND ae.sort_order > ? ORDER BY ae.sort_order ASC", array($uniID, 0));
		
		foreach($equippedItems as $item)
		{
			$image->paste(APP_PATH . "/avatar_items/" . $item['position'] . "/" . $item['title'] . "/" . $item['color'] . "_" . $gender . ".png", (int) $item[$cX], (int) $item[$cY]);
		}
		
		// Save the image
		$image->save(APP_PATH . $aviData['path']);
		
		return true;
	}
	
	
/****** Draw an Avatar from an Outfit ******/
	public static function drawAvatarOutfit
	(
		$base			// <str> The character base to use.
	,	$gender			// <str> The gender to use.
	,	$outfitArray	// <array> The outfit that you want to draw.
	)					// RETURNS <void>
	
	// AppAvatar::drawAvatarOutfit($base, $gender, $outfitArray);
	{
		// Handle Gender
		$gender = ($gender[0] == "m" ? "male" : "female");
		
		$cX = "coord_x_" . $gender;
		$cY = "coord_y_" . $gender;
		
		// Create a blank image
		$image = new Image("image/png", 204, 383);
		
		// Splice the Positions (below base vs. above base)
		//$belowBase = $outfitArray;
		
		
		// Gather Items Below the Base
		foreach($outfitArray as $pos => $content)
		{
			// $content[0] = $itemID, $content[1] = $color
			$itemData = Database::selectOne("SELECT title, position, " . $cX . ", " . $cY . " FROM items WHERE id=? LIMIT 1", array($content[0]));
			
			$image->paste(APP_PATH . "/avatar_items/" . $item['position'] . "/" . $item['title'] . "/" . $item['color'] . "_" . $gender . ".png", (int) $item[$cX], (int) $item[$cY]);
		}
		
		// Gather equipped items below the avatar, including their coordinates and position
		/*
		$equippedItems = Database::selectMultiple("SELECT ae.sort_order, ae.color, i.title, i.position, i." . $cX . ", i." . $cY . " FROM avatar_equipped ae INNER JOIN items i ON i.id = ae.item_id WHERE ae.uni_id=? AND ae.sort_order < ? ORDER BY ae.sort_order ASC", array($uniID, 0));
		
		foreach($equippedItems as $item)
		{
			$image->paste(APP_PATH . "/avatar_items/" . $item['position'] . "/" . $item['title'] . "/" . $item['color'] . "_" . $gender . ".png", (int) $item[$cX], (int) $item[$cY]);
		}
		*/
		
		// Load your avatar's base
		$image->paste(APP_PATH . '/avatar_items/base/' . $base . '_' . $gender . '.png', 0, 0);
		
		// Gather equipped items above the avatar, including their coordinates and position
		$equippedItems = Database::selectMultiple("SELECT ae.sort_order, ae.color, i.title, i.position, i." . $cX . ", i." . $cY . " FROM avatar_equipped ae INNER JOIN items i ON i.id = ae.item_id WHERE ae.uni_id=? AND ae.sort_order > ? ORDER BY ae.sort_order ASC", array($uniID, 0));
		
		foreach($equippedItems as $item)
		{
			$image->paste(APP_PATH . "/avatar_items/" . $item['position'] . "/" . $item['title'] . "/" . $item['color'] . "_" . $gender . ".png", (int) $item[$cX], (int) $item[$cY]);
		}
		
		// Save the image
		$image->save(APP_PATH . $aviData['path']);
	}
	
	
/****** Create an Avatar ******/
	public static function createAvatar
	(
		$uniID			// <int> The Uni-Account to create an avatar for.
	,	$base			// <str> The avatar base (race) to use.
	,	$gender			// <str> The gender of the avatar.
	)					// RETURNS <bool> TRUE on success, or FALSE if failed.
	
	// AppAvatar::createAvatar($uniID, $base, $gender);
	{
		$gender = ($gender == "male" ? "male" : "female");
		
		$aviData = Avatar::imageData($uniID);
		$imgDir = '/' . $aviData['image_directory'] . '/' . $aviData['main_directory'] . '/' . $aviData['second_directory'];
		
		// Make sure the directory exists
		Dir::create(APP_PATH . $imgDir);
		
		// Create the Avatar Image
		$image = new Image(APP_PATH . "/assets/create-avatar/" . $gender . '_' . $base . ".png");
		
		if($image->save(APP_PATH . $imgDir . '/' . $aviData['filename']))
		{
			// If the avatar image was created successfully, add the avatar
			return Database::query("INSERT IGNORE INTO `avatars` (uni_id, base, gender, date_lastUpdate) VALUES (?, ?, ?, ?)", array($uniID, $base, $gender[0], time()));
		}
		
		return false;
	}
	
	
/****** Add Item to User ******/
	public static function receiveItem
	(
		$uniID			// <int> The Uni-Account to receive an item.
	,	$itemID			// <int> The item to provide (based on ID).
	)					// RETURNS <bool> TRUE on success, or FALSE if failed.
	
	// AppAvatar::receiveItem($uniID, $itemID);
	{
		return Database::query("INSERT INTO `user_items` (uni_id, item_id) VALUES (?, ?)", array($uniID, $itemID));
	}
	
	
/****** Drop an Item from User ******/
	public static function dropItem
	(
		$uniID			// <int> The Uni-Account to drop the item from.
	,	$itemID			// <int> The item to drop (based on ID).
	)					// RETURNS <bool> TRUE on success, or FALSE if failed.
	
	// AppAvatar::dropItem($uniID, $itemID);
	{
		return Database::query("DELETE FROM `user_items` WHERE uni_id=? AND item_id=? LIMIT 1", array($uniID, $itemID));
	}
	
	
/****** Check if you own this Item ******/
	public static function checkOwnItem
	(
		$uniID			// <int> The Uni-Account to check the item for.
	,	$itemID			// <int> The item to check if you own.
	)					// RETURNS <bool> TRUE on success, or FALSE if failed.
	
	// AppAvatar::checkOwnItem($uniID, $itemID);
	{
		return (Database::selectValue("SELECT item_id FROM user_items WHERE uni_id=? AND item_id=? LIMIT 1", array($uniID, $itemID))) ? true : false;
	}
	
	
/****** Equip Item to Avatar ******/
	public static function equip
	(
		$uniID			// <int> The avatar (Uni-Account) to equip an item to.
	,	$itemID			// <int> The item to equip (based on ID).
	,	$color			// <str> The color of the item that you're equipping.
	,	$minOrder = -99	// <int> The minimum order position for the item.
	,	$maxOrder = 99	// <int> The maximum order position for the item.
	)					// RETURNS <bool> TRUE on success, or FALSE if failed.
	
	// AppAvatar::equip($uniID, $itemID, $color, $minOrder, $maxOrder);
	{
		// Check if you already have the item equipped
		if(Database::selectOne("SELECT item_id FROM avatar_equipped WHERE uni_id=? AND item_id=? LIMIT 1", array($uniID, $itemID)))
		{
			return false;
		}
		
		// Sort your item into the last allowed position
		$sortInto = (int) Database::selectValue("SELECT (MAX(sort_order) + 1) as sortInto FROM avatar_equipped WHERE uni_id=? AND sort_order >= ? AND sort_order <= ?", array($uniID, $minOrder, $maxOrder));
		
		if(!$sortInto)
		{
			if($maxOrder < 0) { $sortInto = -1; }
			else if($minOrder > 1) { $sortInto = 2; }
		}
		else if($sortInto == 1) { $sortInto = 2; }
		
		// Equip the avatar
		if(Database::query("INSERT INTO `avatar_equipped` (uni_id, sort_order, item_id, color) VALUES (?, ?, ?, ?)", array($uniID, $sortInto, $itemID, $color)))
		{
			Database::query("UPDATE avatars SET date_lastUpdate=? WHERE uni_id=? LIMIT 1", array(time(), $uniID));
			return true;
		}
		
		return false;
	}
	
	
/****** Unequip Item from Avatar ******/
	public static function unequip
	(
		$uniID			// <int> The avatar (Uni-Account) to unequip an item from.
	,	$itemID			// <int> The item to unequip (based on ID).
	)					// RETURNS <bool> TRUE on success, or FALSE if failed.
	
	// AppAvatar::unequip($uniID, $itemID);
	{
		$sortOrder = (int) Database::selectValue("SELECT sort_order FROM avatar_equipped WHERE uni_id=? AND item_id=? LIMIT 1", array($uniID, $itemID));
		
		if(Database::query("DELETE FROM `avatar_equipped` WHERE uni_id=? AND sort_order=? AND item_id=? LIMIT 1", array($uniID, $sortOrder, $itemID)))
		{
			// Resort the list (since that item was removed)
			self::sortDelete($uniID, $sortOrder);
			
			Database::query("UPDATE avatars SET date_lastUpdate=? WHERE uni_id=? LIMIT 1", array(time(), $uniID));
			return true;
		}
		
		return false;
	}
	
	
/****** Sort the Item Positions on an Avatar ******/
	public static function sort
	(
		$uniID			// <int> The avatar (Uni-Account) to sort the positions for.
	,	$itemID			// <int> The item that you're sorting.
	,	$toOrder		// <int> The sort position that you're moving TO.
	)					// RETURNS <bool> TRUE on success, or FALSE if failed.
	
	// AppAvatar::sort($uniID, $itemID, $toOrder);
	{
		// Get the item details
		$itemData = self::itemData($itemID, "min_order, max_order");
		
		// Make sure the item allows this resort
		if($toOrder < $itemData['min_order'] or $toOrder > $itemData['max_order'])
		{
			return false;
		}
		
		// Check the sort order of the item
		$sortValue = (int) Database::selectValue("SELECT sort_order FROM avatar_equipped WHERE uni_id=? AND item_id=? LIMIT 1", array($uniID, $itemID));
		
		if(!$sortValue) { return false; }
		
		if(self::sortMove($uniID, $sortValue, $toOrder))
		{
			return Database::query("UPDATE avatar_equipped SET sort_order=? WHERE uni_id=? AND sort_order=? AND item_id=? LIMIT 1", array($toOrder, $uniID, $sortValue, $itemID));
		}
		
		return false;
	}
	
	
/****** Sort the Item Positions on an Avatar ******/
	public static function sortMove
	(
		$uniID			// <int> The avatar (Uni-Account) to sort the positions for.
	,	$fromOrder		// <int> The order that you're moving FROM (such as from position 8).
	,	$toOrder		// <int> The order that you're moving TO (such as to position 4).
	)					// RETURNS <bool> TRUE on success, or FALSE if failed.
	
	// AppAvatar::sortMove($uniID, $fromOrder, $toOrder);
	{
		// Make sure this isn't a redundant sort
		if($fromOrder == $toOrder)
		{
			return false;
		}
		
		// Make sure we're not moving to or from the untouchables
		if($fromOrder == 0 or $fromOrder == 1) { return false; }
		if($toOrder == 0 or $toOrder == 1) { return false; }
		
		// If we're moving from front to back (or vice versa) do a delete and insert sort
		if(($fromOrder < 0 && $toOrder > 1) or ($fromOrder > 1 && $toOrder < 0))
		{
			self::sortDelete($uniID, $fromOrder);
			self::sortInsert($uniID, $toOrder);
			return true;
		}
		
		// Determine the Sorting Rule to Run
		
		// If FROM is > 1 and greater than TO
		// If FROM is < 0 and greater than TO
		if(($fromOrder > 1 && $toOrder < $fromOrder) or ($fromOrder < 0 && $toOrder < $fromOrder))
		{
			return Database::query("UPDATE avatar_equipped SET sort_order=sort_order+1 WHERE uni_id=? AND sort_order < ? AND sort_order >= ?", array($uniID, $fromOrder, $toOrder));
		}
		
		// If FROM is > 1 and less than TO
		// If FROM is < 0 and less than TO
		return Database::query("UPDATE avatar_equipped SET sort_order=sort_order-1 WHERE uni_id=? AND sort_order > ? AND sort_order <= ?", array($uniID, $fromOrder, $toOrder));
	}
	
	
/****** Sort the Item Positions on an Avatar ******/
	public static function sortDelete
	(
		$uniID			// <int> The avatar (Uni-Account) to sort the positions for.
	,	$deleteOrder	// <int> The sort order that you're deleting.
	)					// RETURNS <bool> TRUE on success, or FALSE if failed.
	
	// AppAvatar::sortDelete($uniID, $deleteOrder);
	{
		// Make sure we're not moving to or from the untouchables
		if($deleteOrder == 0 or $deleteOrder == 1) { return false; }
		
		// If DELETE ORDER is > 1
		if($deleteOrder > 1)
		{
			return Database::query("UPDATE avatar_equipped SET sort_order=sort_order-1 WHERE uni_id=? AND sort_order > ?", array($uniID, $deleteOrder));
		}
		
		// If DELETE ORDER is < 0
		return Database::query("UPDATE avatar_equipped SET sort_order=sort_order+1 WHERE uni_id=? AND sort_order < ?", array($uniID, $deleteOrder));
	}
	
	
/****** Sort the Item Positions on an Avatar ******/
	public static function sortInsert
	(
		$uniID			// <int> The avatar (Uni-Account) to sort the positions for.
	,	$insertOrder	// <int> The sort order that you're deleting.
	)					// RETURNS <bool> TRUE on success, or FALSE if failed.
	
	// AppAvatar::sortInsert($uniID, $insertOrder);
	{
		// Make sure we're not moving to or from the untouchables
		if($insertOrder == 0 or $insertOrder == 1) { return false; }
		
		// If INSERT ORDER is > 1
		if($insertOrder > 1)
		{
			return Database::query("UPDATE avatar_equipped SET sort_order=sort_order+1 WHERE uni_id=? AND sort_order >= ?", array($uniID, $insertOrder));
		}
		
		// If INSERT ORDER is < 0
		return Database::query("UPDATE avatar_equipped SET sort_order=sort_order-1 WHERE uni_id=? AND sort_order <= ?", array($uniID, $insertOrder));
	}
	
	
/****** Get Shop Title ******/
	public static function getShopTitle
	(
		$shopID			// <str> The ID of the shop.
	)					// RETURNS <str> title of the shop, or "" if failed.
	
	// $title = AppAvatar::getShopTitle($shopID);
	{
		return (string) Database::selectValue("SELECT title FROM shop WHERE id=? LIMIT 1", array($shopID));
	}
}

