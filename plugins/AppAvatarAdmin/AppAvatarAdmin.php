<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

abstract class AppAvatarAdmin {

/****** AppAvatarAdmin Class ******
* This class allows you to create the foundation for the avatar site.
* 
****** Examples of using this class ******



****** Methods Available ******
* AppAvatarAdmin::createItem($title, $position, $gender, $rarityLevel, $coordXMale, $coordYMale, $coordXFemale, $coordYFemale);
* AppAvatarAdmin::editItemCoordinates($itemID, $coordXMale, $coordYMale, $coordXFemale, $coordYFemale);
* AppAvatarAdmin::editItemPosition($itemID, $position);
* AppAvatarAdmin::deleteItem($itemID);
* 
* AppAvatarAdmin::createShop($title, $clearance);
* AppAvatarAdmin::renameShop($shopID, $title);
* 
* AppAvatarAdmin::addShopItem($shopID, $itemID, $cost);
* AppAvatarAdmin::deleteShopItem($shopID, $itemID);
*
*/
	
	
/****** Create a new Item ******/
	public static function createItem
	(
		$title			// <str> The name of the item.
	,	$position		// <str> The position (or body slot) of the item on the avatar.
	,	$gender			// <str> This can be: male, female, or both.
	,	$rarityLevel	// <int> 0 is normal, 1 or higher is exotic rarity. 8 is legendary, 9 is staff-only.
	,	$coordXMale		// <int> The Male's X coordinate of the item on the avatar.
	,	$coordYMale		// <int> The Male's Y coordinate of the item on the avatar.
	,	$coordXFemale	// <int> The Female's X coordinate of the item on the avatar.
	,	$coordYFemale	// <int> The Female's Y coordinate of the item on the avatar.
	)					// RETURNS <bool> TRUE on success, or FALSE if failed.
	
	// AppAvatarAdmin::createItem($title, $position, $gender, $rarityLevel, $coordXMale, $coordYMale, $coordXFemale, $coordYFemale);
	{
		// Title - Test Legitimacy
		if(!isSanitized::variable($title, " ")) { return false; }
		
		// Position - Test Legitimacy
		$positionsAllowed = AppAvatar::positions();
		
		if(!in_array($position, $positionsAllowed)) { return false; }
		
		// Gender - Test Legitimacy
		if(!in_array($gender, array('male', 'female', 'both', 'm', 'f', 'b')))
		{
			return false;
		}
		
		$gender = $gender[0];
		
		// Rarity Level - Test Legitimacy
		if(!is_numeric($rarityLevel) or $rarityLevel < 0 or $rarityLevel > 9)
		{
			return false;
		}
		
		// Coordinates - Test Legitimacy
		if(!is_numeric($coordXMale) or !is_numeric($coordYMale) or $coordXMale < 0 or $coordYMale < 0 or !is_numeric($coordXFemale) or !is_numeric($coordYFemale) or $coordXFemale < 0 or $coordYFemale < 0)
		{
			return false;
		}
		
		// Insert the item into the database
		return Database::query("INSERT INTO `items` (title, position, gender, coord_x_male, coord_y_male, coord_x_female, coord_y_female, rarity_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", array($title, $position, $gender, $coordXMale, $coordYMale, $coordXFemale, $coordYFemale, $rarityLevel));
	}
	
	
/****** Edit an existing Item's Coordinates ******/
	public static function editItemCoordinates
	(
		$itemID			// <int> The ID of the item to edit.
	,	$coordXMale		// <int> The Male's X coordinate of the item on the avatar.
	,	$coordYMale		// <int> The Male's Y coordinate of the item on the avatar.
	,	$coordXFemale	// <int> The Female's X coordinate of the item on the avatar.
	,	$coordYFemale	// <int> The Female's Y coordinate of the item on the avatar.
	)					// RETURNS <bool> TRUE on success, or FALSE if failed.
	
	// AppAvatarAdmin::editItemCoordinates($itemID, $coordXMale, $coordYMale, $coordXFemale, $coordYFemale);
	{
		// Test Legitimacy of Male Coordinates
		if(!is_numeric($coordXMale) or !is_numeric($coordYMale) or $coordXMale < 0 or $coordYMale < 0)
		{
			return false;
		}
		
		// Test Legitimacy of Female Coordinates
		if(!is_numeric($coordXFemale) or !is_numeric($coordYFemale) or $coordXFemale < 0 or $coordYFemale < 0)
		{
			return false;
		}
		
		return Database::query("UPDATE `items` SET coord_x_male=?, coord_y_male=?, coord_x_female=?, coord_y_female=? WHERE id=? LIMIT 1", array($coordXMale, $coordYMale, $coordXFemale, $coordYFemale, $itemID));
	}
	
	
/****** Edit an Item's Position ******/
	public static function editItemPosition
	(
		$itemID			// <int> The ID of the item to edit.
	,	$position		// <str> The updated position for the item.
	)					// RETURNS <bool> TRUE on success, or FALSE if failed.
	
	// AppAvatarAdmin::editItemPosition($itemID, $position);
	{
		// Position - Test Legitimacy
		$positionsAllowed = self::positions();
		
		if(!in_array($position, $positionsAllowed)) { return false; }
		
		$itemData = AppAvatar::itemData($itemID);
		if($position != $itemData['position'])
		{
			if(Dir::move(APP_PATH . "/avatar_items/" . $itemData['position'] . "/" . $itemData['title'], APP_PATH . "/avatar_items/" . $position . "/" . $itemData['title']))
			{
				return Database::query("UPDATE `items` SET position=? WHERE itemID=? LIMIT 1", array($position, $itemID));
			}
			return false;
		}
		return true;
	}
	
	
/****** Delete an Item ******/
	public static function deleteItem
	(
		$itemID			// <int> The ID of the item to delete.
	)					// RETURNS <bool> TRUE on success, or FALSE if failed.
	
	// AppAvatarAdmin::deleteItem($itemID);
	{
		return Database::query("DELETE FROM `items` WHERE id=? LIMIT 1", array($itemID));
	}
	
	
/****** Create a Shop ******/
	public static function createShop
	(
		$title			// <str> The title of the shop.
	,	$clearance = 1	// <int> The required clearance to view or buy from the shop.
	)					// RETURNS <bool> TRUE on success, or FALSE if failed.
	
	// AppAvatarAdmin::createShop($title, $clearance);
	{
		return Database::query("INSERT INTO `shop` (title, clearance) VALUES (?, ?)", array($title, $clearance));
	}
	
	
/****** Rename a Shop ******/
	public static function renameShop
	(
		$shopID			// <int> The ID of the shop to rename.
	,	$title			// <str> The title of the shop.
	)					// RETURNS <bool> TRUE on success, or FALSE if failed.
	
	// AppAvatarAdmin::renameShop($shopID, $title);
	{
		return Database::query("UPDATE `shop` SET title=? WHERE id=? LIMIT 1", array($title, $shopID));
	}
	
	
/****** Add Item to Shop Inventory ******/
	public static function addShopItem
	(
		$shopID			// <int> The ID of the shop to add an item to.
	,	$itemID			// <int> The ID of the item to add to the shop.
	,	$cost			// <int> The cost (in standard currency) to purchase the item.
	)					// RETURNS <bool> TRUE on success, or FALSE if failed.
	
	// AppAvatarAdmin::addShopItem($shopID, $itemID, $cost);
	{
		return Database::query("INSERT INTO `shop_inventory` (shop_id, item_id, cost) VALUES (?, ?, ?)", array($shopID, $itemID, $cost));
	}
	
	
/****** Delete an Item from a Shop ******/
	public static function deleteShopItem
	(
		$shopID			// <int> The ID of the shop to remove an item from.
	,	$itemID			// <int> The ID of the item to remove from the shop.
	)					// RETURNS <bool> TRUE on success, or FALSE if failed.
	
	// AppAvatarAdmin::deleteShopItem($shopID, $itemID);
	{
		return Database::query("DELETE FROM `shop_inventory` WHERE shop_id=? AND item_id=? LIMIT 1", array($shopID, $itemID));
	}
	
}
