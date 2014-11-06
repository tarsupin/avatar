<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

abstract class AppTransfer {

/****** AppTransfer Class ******
* This class allows us to transfer the old items into the new system.
* 
****** Methods Available ******
* AppTransfer::transferItems($uniID, $oldUsername);
* AppTransfer::transferPackages($uniID, $oldUsername);
*/
	
	
/****** Transfer Items to New System ******/
	public static function transferItems
	(
		$uniID			// <int> The Uni-Account to transfer items to.
	,	$oldUsername	// <str> The old username account to transfer items from.
	)					// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// AppTransfer::transferItems($uniID, $oldUsername);
	{
		// Get the transfer data
		$list = Database::selectMultiple("SELECT id, clothingID FROM _transfer_items WHERE account=?", array($oldUsername));
		
		Database::startTransaction();
		
		foreach($list as $item)
		{
			if(Database::query("INSERT INTO user_items (uni_id, item_id) VALUES (?, ?)", array($uniID, (int) $item['clothingID'])))
			{
				Database::query("DELETE FROM _transfer_items WHERE id=? AND account=? LIMIT 1", array((int) $item['id'], $oldUsername));
				AppAvatar::record(0, Me::$id, (int) $item['clothingID'], "Transfer from Uni5");
			}
		}
		
		return Database::endTransaction();
	}
	

/****** Transfer Packages to New System ******/
	public static function transferPackages
	(
		$uniID			// <int> The Uni-Account to transfer packages to.
	,	$oldUsername	// <str> The old username account to transfer packages from.
	)					// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// AppTransfer::transferPackages($uniID, $oldUsername);
	{
		// Get the transfer data
		$list = Database::selectMultiple("SELECT id, packageID FROM _transfer_packages WHERE account=?", array($oldUsername));
		
		Database::startTransaction();
		
		foreach($list as $package)
		{
			if(Database::query("INSERT INTO user_packages (uni_id, package_id) VALUES (?, ?)", array($uniID, (int) $package['packageID'])))
			{
				Database::query("DELETE FROM _transfer_packages WHERE id=? AND account=? LIMIT 1", array((int) $package['id'], $oldUsername));
			}
		}
		
		return Database::endTransaction();
	}
	
}
