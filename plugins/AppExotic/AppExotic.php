<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

----------------------------------------
------ About the AppExotic Plugin ------
----------------------------------------

This class provides handling of the Exotic Item Shop.

-------------------------------
------ Methods Available ------
-------------------------------
$slot =		AppExotic::getItem($slotID);			// get the slot's current content
$slot =		AppExotic::chooseItem($slotID);			// choose an item for one of the 4 shop slots
			AppExotic::saveSlot($slotID, $data); 	// save slot to database
$success =	AppExotic::buyItem($slotID, $itemID); 	// purchase an available item

*/

abstract class AppExotic {

/****** Get Slot ******/
	public static function getSlot
	(
		$slotID			// <int> The shop slot.
	)					// RETURNS <str:mixed> on success, or FALSE if failed.
	
	//	AppExotic::getSlot($slotID);
	{
		return Database::selectOne("SELECT item, stock, cost, expire FROM shop_exotic WHERE slot=? AND stock>? AND expire>=? LIMIT 1", array($slotID, 0, time()));
	}

/****** Choose Item ******/
	public static function chooseItem
	(
		$slotID			// <int> The shop slot.
	)					// RETURNS <str:mixed> the item data, or FALSE if failed.
	
	//	$slot = AppExotic::chooseItem($slotID);
	{
		$current_year = (int) date("Y");
		$current_month = (int) date("n");

		switch($slotID)
		{
			case 0:
				$age = 0;
				$stock = rand(7, 10);
				break;
			case 1:
				$age = rand(0, 3);
				$stock = rand(4, 7);
				break;
			case 2:
				$age = rand(1, 9);
				$stock = rand(4, 7);
				break;
			case 3:
				$oldest = $current_year - 2009;
				$oldest *= 12;
				$oldest += $current_month - 6;
				$age = rand(4, $oldest-1);
				$stock = rand(1, 3);
		}
		$expire = time() + (rand(36, 60) * 3600);
		
		$month = (int) date("n", mktime(0, 0, 0, $current_month-$age, 1, $current_year));
		$year = (int) date("Y", mktime(0, 0, 0, $current_month-$age, 1, $current_year));
		$content = Database::selectMultiple("SELECT item_id FROM packages_content INNER JOIN packages ON packages_content.package_id=packages.id WHERE year=? AND month=?", array($year, $month));
		if($content != array())
		{
			$item = $content[rand(0, count($content)-1)]['item_id'];
			$item = AppAvatar::itemData((int) $item, "id, title, position, gender");
			$cost = 2.75 * (1 + 0.25*$age);
			// round to nearest .05
			$cost = round($cost * 2, 1) / 2;
			return array("itemData" => $item, "stock" => $stock, "expire" => $expire, "cost" => $cost, "month" => $month, "year" => $year);
		}
		return false;
	}
	
/****** Save Chosen Item ******/
	public static function saveSlot
	(
		$slotID			// <int> The shop slot.
	,	$data			// <str:mixed> The data of the item to put into this slot.
	)					// RETURNS TRUE on success, or FALSE if failed.
	
	//	AppExotic::saveSlot($slotID, $data);
	{
		$exist = Database::selectOne("SELECT slot FROM shop_exotic WHERE slot=? LIMIT 1", array($slotID));
		if($exist != array())
		{
			$success = Database::query("UPDATE shop_exotic SET item=?, stock=?, cost=?, expire=? WHERE slot=? LIMIT 1", array((int) $data['itemData']['id'], (int) $data['stock'], (float) $data['cost'], (int) $data['expire'], $slotID));
		}
		else
		{
			$success = Database::query("INSERT INTO shop_exotic VALUES (?, ?, ?, ?, ?)", array($slotID, (int) $data['itemData']['id'], (int) $data['stock'], (float) $data['cost'], (int) $data['expire']));
		}
		return $success;
	}
	
/****** Buy Chosen Item ******/
	public static function buyItem
	(
		$slotID			// <int> The shop slot.
	,	$itemID			// <int> The ID of the item you are purchasing.
	)					// RETURNS TRUE on success, or FALSE if failed.
	
	//	AppExotic::buyItem($slotID, $itemID);
	{
		$exist = Database::selectOne("SELECT stock, cost, expire FROM shop_exotic WHERE slot=? AND item=? LIMIT 1", array($slotID, $itemID));
		if($exist != array())
		{
			// item has expired or run out of stock
			if($exist['stock'] == 0 || $exist['expire'] < time())
			{
				return false;
			}
			// try to purchase
			$itemData = AppAvatar::itemData($itemID, "title");
			Database::startTransaction();
			$success1 = Credits::chargeInstant(Me::$id, (float) $exist['cost'], "Purchased " . $itemData['title']);
			$success2 = AppAvatar::receiveItem(Me::$id, $itemID, "Purchased from Exotic Shop");
			$success3 = Database::query("UPDATE shop_exotic SET stock=stock-? WHERE slot=? AND item=? LIMIT 1", array(1, $slotID, $itemID));
			if($success1 && $success2 && $success3)
			{
				Database::endTransaction();
				return true;
			}
			else
			{
				Database::endTransaction(false);
				return false;
			}
		}
		
		return false;
	}
	
}