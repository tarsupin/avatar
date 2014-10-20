<?hh if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

abstract class AppTransfer {

/****** AppTransfer Class ******
* This class allows us to transfer the old items into the new system.
* 
****** Methods Available ******
* AppTransfer::transferItems($uniID, $oldUsername);
*/
	
	
/****** Transfer Items to New System ******/
	public static function transferItems
	(
		int $uniID			// <int> The Uni-Account to transfer items to.
	,	string $oldUsername	// <str> The old username account to transfer items from.
	): bool					// RETURNS <bool> TRUE on success, FALSE on failure.
	
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
			}
		}
		
		return Database::endTransaction();
	}
	

/****** Transfer Packages to New System ******/
	public static function transferPackages
	(
		int $uniID			// <int> The Uni-Account to transfer packages to.
	,	string $oldUsername	// <str> The old username account to transfer packages from.
	): bool					// RETURNS <bool> TRUE on success, FALSE on failure.
	
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