<?hh if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

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

*/

abstract class AppExotic {

/****** Get Slot ******/
	public static function getSlot
	(
		int $slotID				// <int> The shop slot.
	): array <str, mixed>						// RETURNS <str:mixed> on success, or FALSE if failed.
	
	//	AppExotic::getSlot($slotID);
	{
		if($slotID > 1)
		{
			return Database::selectOne("SELECT item, stock, cost, expire FROM shop_exotic WHERE slot=? AND stock>? AND expire>=? LIMIT 1", array($slotID, 0, time()));
		}
		return Database::selectOne("SELECT item, stock, cost, expire FROM shop_exotic WHERE slot=? AND expire>=? LIMIT 1", array($slotID, (int) mktime(0, 0, 0, date("n"), 1)));
	}

/****** Choose Item ******/
	public static function chooseItem
	(
		int $slotID			// <int> The shop slot.
	,	int $exception = 0	// <int> The day of release if the package was late.
	): array <str, mixed>					// RETURNS <str:mixed> the item data.
	
	//	$slot = AppExotic::chooseItem($slotID);
	{
		$current_year = (int) date("Y");
		$current_month = (int) date("n");

		switch($slotID)
		{
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
					$item = $content[array_rand($content)]['item_id'];
				}
				// roughly equal time for all items
				$expire = time() + (floor((date("t") - $exception) / count($content) * 24) * 3600);
				// expire at the end of month, if not earlier
				$expire = min($expire, mktime(0, 0, 0, date("n")+1, 1)-1);
				break;				
			case 2:
				$age = rand(1, 3);
				$stock = rand(4, 1);
				$expire = time() + (rand(36, 60) * 3600);
				break;
			case 3:
				$age = rand(4, 10);
				$stock = rand(4, 7);
				$expire = time() + (rand(36, 60) * 3600);
				break;
			case 4:
				$oldest = $current_year - 2009;
				$oldest *= 12;
				$oldest += $current_month - 6;
				$age = rand(11, $oldest);
				$stock = rand(1, 3);
				$expire = time() + (rand(36, 60) * 3600);
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
		int $slotID			// <int> The shop slot.
	,	array <str, mixed> $data			// <str:mixed> The data of the item to put into this slot.
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
		int $slotID			// <int> The shop slot.
	,	int $itemID			// <int> The ID of the item you are purchasing.
	)					// RETURNS TRUE on success, or FALSE if failed.
	
	//	AppExotic::buyItem($slotID, $itemID);
	{
		$exist = Database::selectOne("SELECT stock, cost, expire FROM shop_exotic WHERE slot=? AND item=? LIMIT 1", array($slotID, $itemID));
		if($exist != array())
		{
			// item has expired or run out of stock
			if(($exist['stock'] == 0 && $slotID > 1) || $exist['expire'] < time())
			{
				return false;
			}
			// try to purchase
			$itemData = AppAvatar::itemData($itemID, "title");
			Database::startTransaction();
			$success1 = Credits::chargeInstant(Me::$id, (float) $exist['cost'], "Purchased " . $itemData['title']);
			$success2 = AppAvatar::receiveItem(Me::$id, $itemID, "Purchased from Exotic Shop");
			$success3 = true;
			if($slotID > 1)
			{
				$success3 = Database::query("UPDATE shop_exotic SET stock=stock-? WHERE slot=? AND item=? LIMIT 1", array(1, $slotID, $itemID));
			}
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
	
	
/****** Buy Current Package ******/
	public static function buyPackage
	(
		int $packageID		// <int> The ID of the package you are purchasing.
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
			$success1 = Credits::chargeInstant(Me::$id, 3.50, "Purchased " . $exist['title']);
			$success2 = AppAvatar::receivePackage(Me::$id, $packageID, "Purchased from Exotic Shop");
			if($success1 && $success2)
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