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
$wrappers   = AppAvatar::wrappers();
$items		= AppAvatar::getUserItems($uniID, $position, $gender);
$items		= AppAvatar::getShopItems($shopID);
$positions	= AppAvatar::getInvPositions($uniID)

$itemData	= AppAvatar::itemData($itemID, $plusShopData = false);
$itemCost	= AppAvatar::itemMinCost($itemID);
$colors		= AppAvatar::getItemColors($position, $title);
			= AppAvatar::itemHasColor($position, $title, $gender, $color);

AppAvatar::updateImage($uniID);

AppAvatar::createAvatar($uniID, $base, $gender);
AppAvatar::switchAvatar($uniID, $aviID);
AppAvatar::editAvatar($uniID, $base, $gender);

AppAvatar::purchaseItem($itemID);
AppAvatar::receiveItem($uniID, $itemID);
AppAvatar::dropItem($uniID, $itemID);
AppAvatar::record($senderID, $recipientID, 123, "Birthday Present");
AppAvatar::recordPackage($senderID, $recipientID, 5, "Birthday Present");
AppAvatar::receivePackage($uniID, $packageID);
AppAvatar::dropPackage($uniID, $packageID);

AppAvatar::checkOwnItem($uniID, $itemID);
AppAvatar::checkOwnPackage($uniID, $packageID);

$title = AppAvatar::getShopTitle($shopID);
$clearance = AppAvatar::getShopClearance($shopID);

*/

abstract class AppAvatar {
	
	
/****** Get Avatar Data ******/
	public static function avatarData
	(
		$uniID			// <int> The Uni-Account to get the avatar data from.
	,	$aviID = 1		// <int> The identification of the user's avatar. 1 is default
	)					// RETURNS <str:mixed> data on the avatar, or array with a blank avatar image source.
	
	// $avatar = AppAvatar::avatarData($uniID);
	{
		if(!$uniID or !$avatar = Database::selectOne("SELECT avatar_id, base, gender, name, date_lastUpdate FROM avatars WHERE uni_id=? AND avatar_id=? LIMIT 1", array($uniID, $aviID)))
		{
			return array('src' => '/assets/images/blank-avatar.png');
		}
		
		// Prepare Values
		$avatar['identification'] = ($aviID == 1 ? "real" : "real" . $aviID);
		$avatar['gender_full'] = ($avatar['gender'] == "m" ? "male" : "female");
		$avatar['date_lastUpdate'] = (int) $avatar['date_lastUpdate'];
		
		$aviData = Avatar::imageData($uniID, $aviID);
		
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
	
	
/****** Return list of valid avatar positions ******/
	public static function wrappers (
	)				// RETURNS <int:int> list of wrapper IDs
	
	// $wrappers = AppAvatar::wrappers();
	{
		$wrap = Database::selectMultiple("SELECT id FROM wrappers", array());
		$wrappers = array();
		foreach($wrap as $w)
		{
			$wrappers[] = (int) $w['id'];
		}
		return $wrappers;
	}
	
	
/****** List a User's Items ******/
	public static function getUserItems
	(
		$uniID			// <int> The Uni-Account to list the items of.
	,	$position		// <str> The position of items to retrieve.
	,	$gender	= ""	// <str> The gender of the avatar or empty string to return items for all genders.
	,	$group = true	// <bool> TRUE means you group similar items together.
	)					// RETURNS <int:[str:mixed]> list of items
	
	// $userItems = AppAvatar::getUserItems($uniID, $position, $gender);
	{
		if(!in_array($gender, array('male', 'female', 'm', 'f', '')))
		{
			return array();
		}
		
		if($gender != "")
		{
			return Database::selectMultiple("SELECT ui.item_id as id, i.title, " . ($group == true ? 'COUNT(id) as count, ' : '') . "i.gender FROM user_items ui INNER JOIN items i ON i.id = ui.item_id WHERE ui.uni_id = ? AND i.position=? AND i.gender IN (?, ?)" . ($group == true ? ' GROUP BY i.title' : ' ORDER BY i.title'), array($uniID, $position, $gender[0], 'b'));
		}
		else
		{
			return Database::selectMultiple("SELECT ui.item_id as id, i.title, " . ($group == true ? 'COUNT(id) as count, ' : '') . "i.gender FROM user_items ui INNER JOIN items i ON i.id = ui.item_id WHERE ui.uni_id = ? AND i.position=?" . ($group == true ? ' GROUP BY i.title' : ' ORDER BY i.title'), array($uniID, $position));
		}
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
			return Database::selectMultiple("SELECT si.item_id as id, si.cost, i.title, i.position, i.gender, i.rarity_level FROM shop_inventory si INNER JOIN items i ON i.id = si.item_id WHERE si.shop_id=?", array($shopID));
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
	
	
/****** Get Item Cost ******/
	public static function itemMinCost
	(
		$itemID				// <int> The ID of the item to get the data from.
	,	$allShops = false	// <bool> Whether the function should include shops inaccessible to the user.
	)						// RETURNS <int> minimum cost of the item, or 0 if failed.
	
	// $itemData = AppAvatar::itemMinCost($itemID);
	{
		if(Me::$clearance < 5)
		{
			$rarity = self::itemData($itemID, "rarity_level");
			if($rarity['rarity_level'] != 0)
			{
				return 0;
			}
		}
	
		$shop = Database::selectOne("SELECT MIN(cost) AS m FROM shop_inventory INNER JOIN shop ON shop_inventory.shop_id=shop.id WHERE item_id=? AND clearance<=?", array($itemID, Me::$clearance));
		if(!$shop['m'] && $allShops)
		{
			$shop = Database::selectOne("SELECT MIN(cost) AS m FROM shop_inventory WHERE item_id=?", array($itemID));
		}
	
		if($shop['m'])
		{
			return (int) $shop['m'];
		}
		return 0;
	}
	
	
/****** Get the list of colors that an item has ******/
	public static function getItemColors
	(
		$position		// <str> The position of the item (e.g. "hair", "chest", etc).
	,	$title			// <str> The title of the item.
	,	$gender = "b"	// <str> The gender to get a list of colors for. Items may have different colors for male and female.
	)					// RETURNS <array> a list of the item's colors, or empty array if none.
	
	// $colors = AppAvatar::getItemColors($position, $title);
	{
		$gender = $gender[0];
		// Get the cached color list (since this is much faster than a directory call)
		if($colorList = Cache::get("color:" . substr(md5($position . $title), 0, 20)))
		{
			$colorList = json_decode($colorList, true);
			$uniqueList = $colorList["b"];
			if($gender != "f")
			{
				$uniqueList = array_merge($uniqueList, $colorList["m"]);
			}
			if($gender != "m")
			{
				$uniqueList = array_merge($uniqueList, $colorList["f"]);
			}
			sort($uniqueList);
			
			return $uniqueList;
		}
		
		// If the color cache is stale, retrieve it normally
		$colorList = array("b" => array(), "m" => array(), "f" => array());
		$files = Dir::getFiles(APP_PATH . "/avatar_items/" . $position . "/" . $title);

		// gather colors for male and female
		foreach($files as $file)
		{
			if(strpos($file, "default") !== false) { continue; }	// Skip the default image
			
			if(strpos($file, "_male.png") > -1)
			{
				$colorList["m"][] = substr($file, 0, strpos($file, "_"));
			}
			elseif(strpos($file, "_female.png") > -1)
			{
				$colorList["f"][] = substr($file, 0, strpos($file, "_"));
			}
		}
		$colorList["m"] = array_unique($colorList["m"]);
		$colorList["f"] = array_unique($colorList["f"]);
		
		// move duplicates to "b"
		$colorList["b"] = array_intersect($colorList["m"], $colorList["f"]);
		$colorList["m"] = array_diff($colorList["m"], $colorList["b"]);
		$colorList["f"] = array_diff($colorList["f"], $colorList["b"]);
		
		// Remove keys from the list so that JSON value is minimized
		// In other words, it ends up like ['Blue','Green'] instead of {'0':'Blue','2':'Green'}
		$colorList["b"] = array_values($colorList["b"]);
		$colorList["m"] = array_values($colorList["m"]);
		$colorList["f"] = array_values($colorList["f"]);
		
		Cache::set("color:" . substr(md5($position . $title), 0, 20), json_encode($colorList), 60 * 48);
		
		// combine lists for both and the wanted gender(s)
		$uniqueList = $colorList["b"];
		if($gender != "f")
		{
			$uniqueList = array_merge($uniqueList, $colorList["m"]);
		}
		if($gender != "m")
		{
			$uniqueList = array_merge($uniqueList, $colorList["f"]);
		}
		sort($uniqueList);
		
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
	
	
/****** Create an Avatar ******/
	public static function createAvatar
	(
		$uniID			// <int> The Uni-Account to create an avatar for.
	,	$base			// <str> The avatar base (race) to use.
	,	$gender			// <str> The gender of the avatar.
	)					// RETURNS <bool> TRUE on success, or FALSE if failed.
	
	// AppAvatar::createAvatar($uniID, $base, $gender);
	{
		// Determine avatar number_format
		$number = Database::selectOne("SELECT MAX(avatar_id) AS max FROM avatars WHERE uni_id=?", array($uniID));
		if($number !== false)
		{
			$number = $number['max'] + 1;
		}
		else
		{
			$number = 1;
		}
		
		$gender = ($gender == "male" ? "male" : "female");
		
		$aviData = Avatar::imageData($uniID, $number);
		$imgDir = '/' . $aviData['image_directory'] . '/' . $aviData['main_directory'] . '/' . $aviData['second_directory'];

		// Make sure the directory exists
		Dir::create(APP_PATH . $imgDir);
		
		// Create the Avatar Image
		$image = new Image(APP_PATH . "/assets/create-avatar/" . $gender . '_' . $base . ".png");
		
		if($image->save(APP_PATH . $imgDir . '/' . $aviData['filename']))
		{
			// If the avatar image was created successfully, add the avatar
			$success = Database::query("INSERT IGNORE INTO `avatars` (uni_id, avatar_id, base, gender, date_lastUpdate) VALUES (?, ?, ?, ?, ?)", array($uniID, $number, $base, $gender[0], time()));
			if($success)
			{
				self::switchAvatar($uniID, $number);
				return true;
			}
		}
		
		return false;
	}
	
	
/****** Switch to different Avatar ******/
	public static function switchAvatar
	(
		$uniID			// <int> The Uni-Account to create an avatar for.
	,	$aviID			// <int> The avatar to use.
	)					// RETURNS <bool> TRUE on success, or FALSE if failed.
	
	// AppAvatar::switchAvatar($uniID, $aviID);
	{
		// Check if you have an avatar with this identification
		$has = Database::selectOne("SELECT avatar_id FROM avatars WHERE uni_id=? AND avatar_id=?", array($uniID, $aviID));
		if($has !== false)
		{
			// Switch to the chosen avatar
			if(Database::query("UPDATE users SET avatar_opt=? WHERE uni_id=? LIMIT 1", array($has['avatar_id'], $uniID)))
			{
				return true;
			}
		}
		
		return false;
	}
	
	
/****** Create an Avatar ******/
	public static function editAvatar
	(
		$uniID			// <int> The Uni-Account to edit an avatar for.
	,	$base			// <str> The avatar base (race) to use.
	,	$gender			// <str> The gender of the avatar.
	,	$aviID = 1		// <int> The ID of the specific avatar. 1 is default.
	)					// RETURNS <bool> TRUE on success, or FALSE if failed.
	
	// AppAvatar::editAvatar($uniID, $base, $gender);
	{
		global $config;
	
		$gender = ($gender == "male" ? "male" : "female");
		
		// Compare with current data to determine cost
		$avatarData = self::avatarData(Me::$id, $aviID);
		$cost = 0;
		if($gender != $avatarData['gender_full'])	{ $cost += 1000; }
		if($base != $avatarData['base'])			{ $cost += 30; }
		
		if($cost == 0)
		{
			return true;
		}
		
		// Update the avatar data
		if(Database::query("UPDATE avatars SET base=?, gender=? WHERE uni_id=? AND avatar_id=? LIMIT 1", array($base, $gender[0], $uniID, $aviID)))
		{
			// Pay cost
			if(Auro::spend(Me::$id, (int) $cost, "Changed Base", $config['site-name']))
			{
				// Update the Avatar Image
				$outfitArray = AppOutfit::get($uniID, ($aviID == 1 ? "real" : "real" . $aviID));
				$outfitArray[0] = array(0, $base);
				$outfitArray = AppOutfit::sortAll($outfitArray, $gender, ($aviID == 1 ? "real" : "real" . $aviID));
				$aviData = Avatar::imageData(Me::$id, $aviID);
				AppOutfit::draw($base, $gender[0], $outfitArray, APP_PATH . '/' . $aviData['image_directory'] . '/' . $aviData['main_directory'] . '/' . $aviData['second_directory'] . '/' . $aviData['filename']);
				AppOutfit::save($uniID, ($aviID == 1 ? "real" : "real" . $aviID), $outfitArray);
				return true;
			}
		}
		
		return false;
	}
	

/****** Purchase an Item ******/
	public static function purchaseItem
	(
		$itemID			// <int> The item to provide (based on ID).
	,	$shopID = 0		// <int> The ID of the shop this item is from.
	)					// RETURNS <bool> TRUE on success, or FALSE if failed.
	
	// AppAvatar::purchaseItem($itemID);
	{
		global $config;
	
		// Make sure the item exists
		if(!$itemData = self::itemData($itemID))
		{
			Alert::saveError($itemData['title'] . "  Does Not Exist", $itemData['title'] . " does not exist.");
			return false;
		}
		
		// staff may purchase rare items
		$itemData['rarity_level'] = (int) $itemData['rarity_level'];
		if($itemData['rarity_level'] > 0 && Me::$clearance < 5)
		{
			Alert::saveError($itemData['title'] . "  Not Allowed", "Purchase of " . $itemData['title'] . " is not allowed.");
			return false;
		}
		
		// Get cost and check if it's in an available shop
		if($shopID == 0)
		{
			if(!$shop['cost'] = self::itemMinCost($itemID))
			{
				Alert::saveError($itemData['title'] . "  Not Allowed", "Purchase of " . $itemData['title'] . " is not allowed.");
				return false;
			}
		}
		// Shop was provided
		else
		{
			if(!$item = self::getShopItems($shopID, $itemID))
			{
				Alert::saveError($itemData['title'] . "  Not Available", $itemData['title'] . " is not available in this shop.");
				return false;
			}
			$shop['cost'] = $item['cost'];
		}
		
		// Spend the currency to purchase this item
		if(Auro::spend(Me::$id, (int) $shop['cost'], "Purchased " . $itemData['title'], $config['site-name']))
		{
			// Add this item to your inventory
			self::receiveItem(Me::$id, $itemID, "Purchased from Shop");
			
			Alert::saveSuccess($itemData['title'] . " Purchased Item", 'You have purchased ' . $itemData['title'] . '!' . ($shopID != 0 ? ' <a href="javascript:window.history.go(-2);">Would you like to go back to the previous page?</a>' : ''));
			Cache::delete("invLayers:" . Me::$id);
			return true;
		}

		return false;
	}
	
	
/****** Add Item to User ******/
	public static function receiveItem
	(
		$uniID			// <int> The Uni-Account to receive an item.
	,	$itemID			// <int> The item to provide (based on ID).
	,	$desc = ""		// <str> The message to log with.
	)					// RETURNS <bool> TRUE on success, or FALSE if failed.
	
	// AppAvatar::receiveItem($uniID, $itemID);
	{
		$result = Database::query("INSERT INTO `user_items` (uni_id, item_id) VALUES (?, ?)", array($uniID, $itemID));
		if($result)
		{
			self::record(0, $uniID, $itemID, Sanitize::safeword($desc));
			Cache::delete("invLayers:" . $uniID);
		}
		return $result;
	}
	
	
/****** Drop an Item from User ******/
	public static function dropItem
	(
		$uniID			// <int> The Uni-Account to drop the item from.
	,	$itemID			// <int> The item to drop (based on ID).
	,	$desc = ""		// <str> The message to log with.
	)					// RETURNS <bool> TRUE on success, or FALSE if failed.
	
	// AppAvatar::dropItem($uniID, $itemID);
	{
		// remove item
		$result = Database::query("DELETE FROM `user_items` WHERE uni_id=? AND item_id=? LIMIT 1", array($uniID, $itemID));
		if($result)
		{
			self::record($uniID, 0, $itemID, $desc);
			// remove from outfits
			AppOutfit::removeFromAvatar($uniID, $itemID);
			Cache::delete("invLayers:" . $uniID);
		}
		
		return $result;
	}
	
/****** Records an item transaction ******/
	public static function record
	(
		$senderID		// <int> The Uni-Account to send item. 0 if given by the system.
	,	$recipientID	// <int> The Uni-Account to receive the item. 0 if removed from the system.
	,	$itemID			// <int> The item ID.
	,	$desc = ""		// <str> A brief description about the transaction's purpose.
	)					// RETURNS <bool> TRUE on success, or FALSE on error.
	
	// AppAvatar::record($senderID, $recipientID, 123, "Birthday Present");
	{
		if($senderID === false or $recipientID === false) { return false; }
		
		// Prepare Values
		$timestamp = time();
		
		// Run the record keeping
		$pass = Database::query("INSERT INTO item_records (description, uni_id, other_id, item_id, date_exchange) VALUES (?, ?, ?, ?, ?)", array(Sanitize::text($desc), $senderID, $recipientID, $itemID, $timestamp));
		
		return ($pass);
	}
	
	
/****** Add Package to User ******/
	public static function receivePackage
	(
		$uniID			// <int> The Uni-Account to receive a package.
	,	$packageID		// <int> The package to provide (based on ID).
	,	$desc = ""		// <str> A brief description about the transaction's purpose.
	)					// RETURNS <bool> TRUE on success, or FALSE if failed.
	
	// AppAvatar::receivePackage($uniID, $packageID);
	{
		$success = Database::query("INSERT INTO `user_packages` (uni_id, package_id) VALUES (?, ?)", array($uniID, $packageID));
		if($success)
		{
			self::recordPackage(0, $uniID, $packageID, Sanitize::safeword($desc));
		}
		return $success;
	}
	
	
/****** Drop an Item from User ******/
	public static function dropPackage
	(
		$uniID			// <int> The Uni-Account to drop the package from.
	,	$packageID		// <int> The package to drop (based on ID).
	,	$desc = ""		// <str> A brief description about the transaction's purpose.
	)					// RETURNS <bool> TRUE on success, or FALSE if failed.
	
	// AppAvatar::dropPackage($uniID, $itemID);
	{
		$success = Database::query("DELETE FROM `user_packages` WHERE uni_id=? AND package_id=? LIMIT 1", array($uniID, $packageID));
		if($success)
		{
			self::recordPackage($uniID, 0, $packageID, Sanitize::safeword($desc));
		}
		return $success;
	}
	
	
/****** Records an item transaction ******/
	public static function recordPackage
	(
		$senderID		// <int> The Uni-Account to send item. 0 if given by the system.
	,	$recipientID	// <int> The Uni-Account to receive the item. 0 if removed from the system.
	,	$packageID		// <int> The package ID.
	,	$desc = ""		// <str> A brief description about the transaction's purpose.
	)					// RETURNS <bool> TRUE on success, or FALSE on error.
	
	// AppAvatar::recordPackage($senderID, $recipientID, 5, "Birthday Present");
	{
		if($senderID === false or $recipientID === false) { return false; }
		
		// Prepare Values
		$timestamp = time();
		
		// Run the record keeping
		$pass = Database::query("INSERT INTO package_records (description, uni_id, other_id, package_id, date_exchange) VALUES (?, ?, ?, ?, ?)", array(Sanitize::text($desc), $senderID, $recipientID, $packageID, $timestamp));
		
		return ($pass);
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
	

/****** Check if you own this Package ******/
	public static function checkOwnPackage
	(
		$uniID			// <int> The Uni-Account to check the package for.
	,	$packageID			// <int> The package to check if you own.
	)					// RETURNS <bool> TRUE on success, or FALSE if failed.
	
	// AppAvatar::checkOwnPackage($uniID, $packageID);
	{
		return (Database::selectValue("SELECT package_id FROM user_packages WHERE uni_id=? AND package_id=? LIMIT 1", array($uniID, $packageID))) ? true : false;
	}
	
	
/****** Get Shop Title ******/
	public static function getShopTitle
	(
		$shopID			// <int> The ID of the shop.
	)					// RETURNS <str> title of the shop, or "" if failed.
	
	// $title = AppAvatar::getShopTitle($shopID);
	{
		return (string) Database::selectValue("SELECT title FROM shop WHERE id=? LIMIT 1", array($shopID));
	}


/****** Get Shop Clearance ******/
	public static function getShopClearance
	(
		$shopID			// <int> The ID of the shop.
	)					// RETURNS <int> clearance of the shop, or "" if failed.
	
	// $title = AppAvatar::getShopClearance($shopID);
	{
		return (int) Database::selectValue("SELECT clearance FROM shop WHERE id=? LIMIT 1", array($shopID));
	}
	
}