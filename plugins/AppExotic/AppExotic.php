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
$success = 	AppExotic::buyPackage($packageID);		// purchase the current package
$success = 	AppExotic::stats($packageID, $packageChange, $itemID);	// log the change in EP (item) numbers

*/

abstract class AppExotic {

/****** Get Slot ******/
	public static function getSlot
	(
		$slotID				// <int> The shop slot.
	)						// RETURNS <str:mixed> on success, or FALSE if failed.
	
	//	AppExotic::getSlot($slotID);
	{
		if($slotID > 1 && $slotID < 5)
		{
			return Database::selectOne("SELECT item, stock, cost, expire FROM shop_exotic WHERE slot=? AND stock>? AND expire>=? LIMIT 1", array($slotID, 0, time()));
		}
		return Database::selectOne("SELECT item, stock, cost, expire FROM shop_exotic WHERE slot=? AND expire>=? LIMIT 1", array($slotID, (int) mktime(0, 0, 0, date("n"), 1)));
	}

/****** Choose Item ******/
	public static function chooseItem
	(
		$slotID			// <int> The shop slot.
	,	$exception = 0	// <int> The day of release if the package was late.
	)					// RETURNS <str:mixed> the item data.
	
	//	$slot = AppExotic::chooseItem($slotID);
	{
		$current_year = (int) date("Y");
		$current_month = (int) date("n");

		switch($slotID)
		{
			// current month item
			case 1:
				$age = 0;
				$stock = 0;
				// content of current EP
				$content = Database::selectMultiple("SELECT item_id FROM packages_content INNER JOIN packages ON packages_content.package_id=packages.id WHERE year=? AND month=? ORDER BY item_id", array((int) date("Y"), (int) date("n")));
				if($content == array())
				{
					return array();
				}
				// choose based on last used item
				$last = self::getSlot(1);
				if($last != array())
				{
					foreach($content as $key => $cont)
					{
						// pick next item
						if($cont['item_id'] == $last['item'] && isset($content[$key+1]))
						{
							$item = $content[$key+1]['item_id'];
							break;
						}
					}
				}
				// pick first item
				else
				{
					$item = $content[0]['item_id'];
				}
				// pick random item if cycled through all
				if(!isset($item))
				{
					//$item = $content[array_rand($content)]['item_id'];
					$item = $content[0]['item_id'];
				}
				// roughly equal time for all items
				$expire = time() + (floor((date("t") - $exception) / count($content) * 8) * 3600);
				// expire at the end of month, if not earlier
				$expire = min($expire, mktime(0, 0, 0, date("n")+1, 1)-1);
				break;
			// last month item
			case 2:
				$age = rand(1, 3);
				$stock = rand(7, 10);
				$expire = time() + (rand(36, 60) * 3600);
				break;
			// older item
			case 3:
				$age = rand(4, 10);
				$stock = rand(4, 7);
				$expire = time() + (rand(36, 60) * 3600);
				break;
			// older item
			case 4:
				$oldest = $current_year - 2009;
				$oldest *= 12;
				$oldest += $current_month - 6;
				$age = rand(11, $oldest);
				$stock = rand(1, 3);
				$expire = time() + (rand(36, 60) * 3600);
				break;
			// credit shop items
			default:
				srand(date("z") . date("Y"));
				$items = Database::selectMultiple("SELECT item, cost FROM shop_exotic_inventory", array());
				$keys = array_rand($items, 5);
				$expire = mktime(0, 0, 0, date("n"), date("j")+1);
				for($i=5; $i<10; $i++)
				{
					$key = $keys[$i-5];
					$item = AppAvatar::itemData((int) $items[$key]['item'], "id, title, position, gender");
					$result[$i] = array("itemData" => $item, "stock" => 0, "expire" => $expire, "cost" => (float) $items[$key]['cost']);
				}
				return $result;
		}
		
		$month = (int) date("n", mktime(0, 0, 0, $current_month-$age, 1, $current_year));
		$year = (int) date("Y", mktime(0, 0, 0, $current_month-$age, 1, $current_year));
		if($slotID > 1)
		{
			$content = Database::selectMultiple("SELECT item_id FROM packages_content INNER JOIN packages ON packages_content.package_id=packages.id WHERE year=? AND month=?", array($year, $month));
			$item = $content[array_rand($content)]['item_id'];
		}
		if(isset($item))
		{
			$item = AppAvatar::itemData((int) $item, "id, title, position, gender");
			$cost = 2.75 * (1 + 0.25*$age);
			// round to nearest .05
			$cost = round($cost * 2, 1) / 2;
			return array("itemData" => $item, "stock" => $stock, "expire" => $expire, "cost" => $cost, "month" => $month, "year" => $year);
		}
		return array();
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
			if(($exist['stock'] == 0 && $slotID > 1 && $slotID < 5) || $exist['expire'] < time())
			{
				return false;
			}
			// try to purchase
			$itemData = AppAvatar::itemData($itemID, "title");
			Database::startTransaction();
			$success1 = Credits::chargeInstant(Me::$id, (float) $exist['cost'], "Purchased " . $itemData['title']);
			$success2 = AppAvatar::receiveItem(Me::$id, $itemID, "Purchased from Exotic Shop");
			$success3 = true;
			if($slotID > 1 && $slotID < 5)
			{
				$success3 = Database::query("UPDATE shop_exotic SET stock=stock-? WHERE slot=? AND item=? LIMIT 1", array(1, $slotID, $itemID));
				// get package ID
				$packageID = (int) Database::selectValue("SELECT package_id FROM packages_content WHERE item_id=? LIMIT 1", array($itemID));
				self::stats($packageID, 0, $itemID);
			}
			
			Database::endTransaction($success1 && $success2 && $success3);
			return ($success1 && $success2 && $success3);
		}
		
		return false;
	}
	
	
/****** Buy Current Package ******/
	public static function buyPackage
	(
		$packageID		// <int> The ID of the package you are purchasing.
	,	$bulk = false	// <bool> Whether the user purchases a single package (false) or 5 packages for a discount (true).
	)					// RETURNS TRUE on success, or FALSE if failed.
	
	//	AppExotic::buyPackage($packageID);
	{
		$exist = Database::selectOne("SELECT title FROM packages WHERE id=? AND year=? AND month=?", array($packageID, (int) date("Y"), (int) date("n")));
		if($exist != array())
		{
			if($exist['title'] == '')
			{
				$exist['title'] = date("F", mktime(0, 0, 0, $package['month'], 1)) . ' Package';
			}
		
			// try to purchase
			Database::startTransaction();
			if(!$bulk)
			{
				$success1 = Credits::chargeInstant(Me::$id, 3.50, "Purchased " . $exist['title']);
				$success2 = AppAvatar::receivePackage(Me::$id, $packageID, "Purchased from Exotic Shop");
				self::stats($packageID, 1);
			}
			else
			{
				$success1 = Credits::chargeInstant(Me::$id, 15.00, "Purchased 5 " . $exist['title'] . "s");
				$count = 0;
				do
				{
					$count++;
					$success2 = AppAvatar::receivePackage(Me::$id, $packageID, "Purchased from Exotic Shop");
				} while($success2 && $count < 5);
				self::stats($packageID, 5);
			}
			
			Database::endTransaction($success1 && $success2);
			return ($success1 && $success2);
		}
		
		return false;
	}
	
/****** Log for the sale statistics ******/
	public static function stats
	(
		$packageID		// <int> The ID of the package being bought or opened.
	,	$packageChange	// <int> What to do with the package count. -1 for reduce (opening), 0 for nothing (buying item), 1 for increase (purchasing)
	,	$itemID = 0		// <int> The ID of the item being bought or chosen, if applicable.
	,	$itemChange = 1	// <int> Whether to increase or reduce the item count.
	)					// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// AppExotic::stats($packageID, $packageChange, $itemID);
	{
		if(!$packageID)
		{
			return false;
		}
		
		$pass = true;
		Database::startTransaction();
		if(!Database::selectOne("SELECT existing FROM packages_stats WHERE package_id=? LIMIT 1", array($packageID)))
		{
			$count = (int) Database::selectValue("SELECT COUNT(*) FROM user_packages WHERE package_id=?", array($packageID));
			$pass = Database::query("INSERT INTO packages_stats VALUES (?, ?)", array($packageID, $count));
		}
		if($pass && $packageChange != 0)
		{
			$pass = Database::query("UPDATE packages_stats SET existing=existing+? WHERE package_id=? LIMIT 1", array($packageChange, $packageID));
		}

		if($itemID && $pass)
		{
			if(!Database::selectValue("SELECT existing FROM packages_content WHERE item_id=? AND package_id=? LIMIT 1", array($itemID, $packageID)))
			{
				$count = (int) Database::selectValue("SELECT COUNT(*) FROM user_items WHERE item_id=?", array($itemID));
				$pass = Database::query("REPLACE INTO packages_content VALUES (?, ?, ?)", array($itemID, $packageID, $count));
			}
			else
			{
				$pass = Database::query("UPDATE packages_content SET existing=existing+? WHERE item_id=? AND package_id=? LIMIT 1", array($itemChange, $itemID, $packageID));
			}
		}
		Database::endTransaction($pass);
		
		return $pass;
	}
	
}