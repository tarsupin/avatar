<?hh if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

---------------------------------------
------ About the AppTrade Plugin ------
---------------------------------------

This class provides functions to send Auro and items to another user.

-------------------------------
------ Methods Available ------
-------------------------------

AppTrade::sendAuro_doTransaction($senderID, $recipientID, $auroAmount);
AppTrade::sendItem_doTransaction($senderID, $recipientID, $itemID);

*/

abstract class AppTrade {


/****** Exchange Auro between two users ******/
	public static function sendAuro_doTransaction
	(
		int $senderID		// <int> The UniID sending the Auro.
	,	int $recipientID	// <int> The UniID receiving the Auro.
	,	int $auroAmount		// <int> The amount of Auro being sent.
	,	string $desc = "Gift or Trade"		// <str> The description for the log.
	): bool					// RETURNS <bool> TRUE if the Auro was sent, FALSE if it failed.
	
	// AppTrade::sendAuro_doTransaction($senderID, $recipientID, $auroAmount);
	{
		return Currency::exchange($senderID, $recipientID, $auroAmount, $desc);
	}
	
/****** Exchange an item between two users ******/
	public static function sendItem_doTransaction
	(
		int $senderID		// <int> The UniID sending the item.
	,	int $recipientID	// <int> The UniID receiving the item.
	,	int $itemID			// <int> The ID of the item that the sender is exchanging.
	,	string $desc = "Gift or Trade"		// <str> The description for the log.
	): bool					// RETURNS <bool> TRUE if the item was sent, FALSE if it failed.
	
	// AppTrade::sendItem_doTransaction($senderID, $recipientID, $itemID);
	{
		// Check that you own the item
		$own = AppAvatar::checkOwnItem($senderID, $itemID);
		if(!$own)	{ return false; }
		
		$success = Database::query("UPDATE user_items SET uni_id=? WHERE uni_id=? and item_id=? LIMIT 1", array($recipientID, $senderID, $itemID));
		if($success)
		{
			$success = AppAvatar::record($senderID, $recipientID, $itemID, $desc);
			
			// update cached layers
			Cache::delete("invLayers:" . $senderID);
			Cache::delete("invLayers:" . $recipientID);
			
			// update avatars if necessary
			AppOutfit::removeFromAvatar($senderID, $itemID);
		}
		return $success;
	}
	
}