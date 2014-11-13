<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

----------------------------------------
------ About the AppExotic Plugin ------
----------------------------------------

This class provides handling of the Exotic Item Shop.

-------------------------------
------ Methods Available ------
-------------------------------
$slot = AppExotic::chooseItem($slotID);		// choose an item for one of the 4 shop slots

*/

abstract class AppExotic {

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
			$cost = round(2.75 * (1 + 0.25*$age), 2);
			return array("itemData" => $item, "stock" => $stock, "expire" => $expire, "cost" => $cost, "month" => $month, "year" => $year);
		}
		return false;
	}
	
/****** Save Chosen Item ******/
public static function saveSlot
	(
		$data			// <str:mixed> The data of the item to put into this slot.
	)					// RETURNS TRUE on success, or FALSE if failed.
	
	//	$slot = AppExotic::saveSlot($slotID);
	{
		
	}
	
}