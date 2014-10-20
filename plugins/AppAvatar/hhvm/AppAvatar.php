<?hh if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

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
$positions	= AppAvatar::getInvPositions($uniID)

$itemData	= AppAvatar::itemData($itemID, $plusShopData = false);
$colors		= AppAvatar::getItemColors($position, $title);
			= AppAvatar::itemHasColor($position, $title, $gender, $color);

AppAvatar::updateImage($uniID);

AppAvatar::createAvatar($uniID, $base, $gender);
AppAvatar::receiveItem($uniID, $itemID);
AppAvatar::dropItem($uniID, $itemID);

AppAvatar::checkOwnItem($uniID, $itemID);

$title = AppAvatar::getShopTitle($shopID);
$clearance = AppAvatar::getShopClearance($shopID);

*/

abstract class AppAvatar {
	
	
/****** Get Avatar Data ******/
	public static function avatarData
	(
		int $uniID			// <int> The Uni-Account to get the avatar data from.
	): array <str, mixed>					// RETURNS <str:mixed> data on the avatar, or array with a blank avatar image source.
	
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
	): array <int, str>				// RETURNS <int:str> list of valid avatar positions
	
	// $positions = AppAvatar::positions();
	{
		return array(
			'ankles'
		,	'arms'
		,	'back'
		,	'background'
		,	'badge'
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
		,	'undershirt'
		,	'wings'
		,	'wrists'
		);
	}
	
	
/****** List a User's Items ******/
	public static function getUserItems
	(
		int $uniID			// <int> The Uni-Account to list the items of.
	,	string $position		// <str> The position of items to retrieve.
	,	string $gender			// <str> The gender of the avatar.
	,	bool $group = true	// <bool> TRUE means you group similar items together.
	): array <int, array<str, mixed>>					// RETURNS <int:[str:mixed]> list of items
	
	// $userItems = AppAvatar::getUserItems($uniID, $position, $gender);
	{
		if(!in_array($gender, array('male', 'female', 'm', 'f')))
		{
			return array();
		}
		
		return Database::selectMultiple("SELECT ui.item_id as id, i.title FROM user_items ui INNER JOIN items i ON i.id = ui.item_id WHERE ui.uni_id = ? AND i.position=? AND i.gender IN (?, ?)" . ($group == true ? ' GROUP BY i.title' : ''), array($uniID, $position, $gender[0], 'b'));
	}
	
	
/****** Get your Inventory Positions ******/
	public static function getInvPositions
	(
		int $uniID		// <int> The Uni-Account to check positions for.
	): array <int, str>				// RETURNS <int:str> list of items, or FALSE if failed.
	
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
		int $shopID			// <int> The ID of the shop to get items from.
	,	int $itemID = 0		// <int> A specific item to return from the shop, if desired.
	): array					// RETURNS <array> list of items, or empty array if failed.
	
	// $items = AppAvatar::getShopItems($shopID, $itemID = 0);
	{
		if($itemID == 0)
		{
			return Database::selectMultiple("SELECT si.item_id as id, si.cost, i.title, i.position, i.gender, i.rarity_level FROM shop_inventory si INNER JOIN items i ON i.id = si.item_id WHERE si.shop_id=?", array($shopID));
		}
		
		return Database::selectOne("SELECT si.item_id as id, si.cost, i.title, i.position, i.gender, i.rarity_level FROM shop_inventory si INNER JOIN items i ON i.id = si.item_id WHERE si.shop_id=? AND si.item_id=?", array($shopID, $itemID));
	}
	
	
/****** Get Item Data ******/
	public static function itemData
	(
		int $itemID			// <int> The ID of the item to get the data from.
	,	string $columns = "*"	// <str> The columns to retrieve from the database.
	): array <str, mixed>					// RETURNS <str:mixed> data of the item, or FALSE if failed.
	
	// $itemData = AppAvatar::itemData($itemID);
	{
		return Database::selectOne("SELECT " . Sanitize::variable($columns, " ,`*") . " FROM items WHERE id=? LIMIT 1", array($itemID));
	}
	
	
/****** Get the list of colors that an item has ******/
	public static function getItemColors
	(
		string $position		// <str> The position of the item (e.g. "hair", "chest", etc).
	,	string $title			// <str> The title of the item.
	): array					// RETURNS <array> a list of the item's colors, or empty array if none.
	
	// $colors = AppAvatar::getItemColors($position, $title);
	{
		// Get the cached color list (since this is much faster than a directory call)
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
				if(strpos($file, "default") === false)	// Skip the default image
				{
					$colorList[] = substr($file, 0, strpos($file, "_"));
				}
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
		string $position		// <str> The position of the item (e.g. "hair", "chest", etc).
	,	string $title			// <str> The title of the item.
	,	string $gender			// <str> The gender that is using this item.
	,	string $color			// <str> The intended color of the item (to see if it's available).
	): bool					// RETURNS <bool> TRUE if the color is available, or FALSE if not.
	
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
	
	
/****** Create an Avatar ******/
	public static function createAvatar
	(
		int $uniID			// <int> The Uni-Account to create an avatar for.
	,	string $base			// <str> The avatar base (race) to use.
	,	string $gender			// <str> The gender of the avatar.
	): bool					// RETURNS <bool> TRUE on success, or FALSE if failed.
	
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
		int $uniID			// <int> The Uni-Account to receive an item.
	,	int $itemID			// <int> The item to provide (based on ID).
	): bool					// RETURNS <bool> TRUE on success, or FALSE if failed.
	
	// AppAvatar::receiveItem($uniID, $itemID);
	{
		return Database::query("INSERT INTO `user_items` (uni_id, item_id) VALUES (?, ?)", array($uniID, $itemID));
	}
	
	
/****** Drop an Item from User ******/
	public static function dropItem
	(
		int $uniID			// <int> The Uni-Account to drop the item from.
	,	int $itemID			// <int> The item to drop (based on ID).
	): bool					// RETURNS <bool> TRUE on success, or FALSE if failed.
	
	// AppAvatar::dropItem($uniID, $itemID);
	{
		return Database::query("DELETE FROM `user_items` WHERE uni_id=? AND item_id=? LIMIT 1", array($uniID, $itemID));
	}
	
	
/****** Check if you own this Item ******/
	public static function checkOwnItem
	(
		int $uniID			// <int> The Uni-Account to check the item for.
	,	int $itemID			// <int> The item to check if you own.
	): bool					// RETURNS <bool> TRUE on success, or FALSE if failed.
	
	// AppAvatar::checkOwnItem($uniID, $itemID);
	{
		return (Database::selectValue("SELECT item_id FROM user_items WHERE uni_id=? AND item_id=? LIMIT 1", array($uniID, $itemID))) ? true : false;
	}
	
	
/****** Get Shop Title ******/
	public static function getShopTitle
	(
		int $shopID			// <int> The ID of the shop.
	): string					// RETURNS <str> title of the shop, or "" if failed.
	
	// $title = AppAvatar::getShopTitle($shopID);
	{
		return (string) Database::selectValue("SELECT title FROM shop WHERE id=? LIMIT 1", array($shopID));
	}


/****** Get Shop Clearance ******/
	public static function getShopClearance
	(
		int $shopID			// <int> The ID of the shop.
	): int					// RETURNS <int> clearance of the shop, or "" if failed.
	
	// $title = AppAvatar::getShopClearance($shopID);
	{
		return (int) Database::selectValue("SELECT clearance FROM shop WHERE id=? LIMIT 1", array($shopID));
	}
}