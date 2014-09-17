<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

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
			Database::query("INSERT INTO user_items (uni_id, item_id) VALUES (?, ?)", array($uniID, (int) $item['clothingID']));
			Database::query("DELETE FROM _transfer_items WHERE id=? AND account=?", array((int) $item['id'], $oldUsername));
		}
		
		return Database::endTransaction();
	}
	
}
