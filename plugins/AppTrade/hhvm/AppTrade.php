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
AppTrade::sendPackage_doTransaction($senderID, $recipientID, $itemID);

*/

abstract class AppTrade {


/****** Exchange Auro between two users ******/
	public static function sendAuro_doTransaction
	(
		int $senderID		// <int> The UniID sending the Auro.
	,	int $recipientID	// <int> The UniID receiving the Auro.
	,	int $auroAmount		// <int> The amount of Auro being sent.
	,	string $desc = ""		// <str> The description for the log.
	,	bool $anon = false	// <bool> Whether to show it as anonymous or not.
	): bool					// RETURNS <bool> TRUE if the Auro was sent, FALSE if it failed.
	
	// AppTrade::sendAuro_doTransaction($senderID, $recipientID, $auroAmount);
	{
		global $config;
		
		$auroAmount = round($auroAmount, 2);
		if(!$anon)
		{
			$return = Auro::exchange($senderID, $recipientID, $auroAmount, ($desc != "" ? $desc . ": " : "") . "Gift or Trade", $config['site-name']);
		}
		else
		{
			$recipient = User::get($recipientID, "handle");
			$pass1 = Auro::grant($recipientID, $auroAmount, ($desc != "" ? $desc . ": " : "") . "Anonymous Gift", $config['site-name']);
			$pass2 = Auro::spend($senderID, $auroAmount, ($desc != "" ? $desc . ": " : "") . "Anonymous Gift to " . $recipient['handle'], $config['site-name']);
			$return = ($pass1 !== false && $pass2 !== false ? true : false);
		}
		if($return === false)
		{
			return false;
		}
		return true;
	}
	
/****** Exchange an item between two users ******/
	public static function sendItem_doTransaction
	(
		int $senderID		// <int> The UniID sending the item.
	,	int $recipientID	// <int> The UniID receiving the item.
	,	int $itemID			// <int> The ID of the item that the sender is exchanging.
	,	string $desc = ""		// <str> The description for the log.
	,	bool $anon = false	// <bool> Whether to show it as anonymous or not.
	): bool					// RETURNS <bool> TRUE if the item was sent, FALSE if it failed.
	
	// AppTrade::sendItem_doTransaction($senderID, $recipientID, $itemID);
	{
		// Check that you own the item
		$own = AppAvatar::checkOwnItem($senderID, $itemID);
		if(!$own)	{ return false; }
		
		if(!$anon)
		{
			$success = Database::query("UPDATE user_items SET uni_id=? WHERE uni_id=? and item_id=? LIMIT 1", array($recipientID, $senderID, $itemID));
			if($success)
			{
				AppAvatar::record($senderID, $recipientID, $itemID, ($desc != "" ? $desc . ": " : "") . "Gift or Trade");
			}
		}
		else
		{
			$recipient = User::get($recipientID, "handle");
			$pass1 = AppAvatar::receiveItem($recipientID, $itemID, ($desc != "" ? $desc . ": " : "") . "Anonymous Gift");
			$pass2 = AppAvatar::dropItem($senderID, $itemID, ($desc != "" ? $desc . ": " : "") . "Anonymous Gift to " . $recipient['handle']);
			$success = ($pass1 !== false && $pass2 !== false ? true : false);
		}
		
		// update cached layers
		Cache::delete("invLayers:" . $senderID);
		Cache::delete("invLayers:" . $recipientID);
		
		// update avatars if necessary
		AppOutfit::removeFromAvatar($senderID, $itemID);

		return $success;
	}
	
/****** Exchange a package between two users ******/
	public static function sendPackage_doTransaction
	(
		int $senderID		// <int> The UniID sending the item.
	,	int $recipientID	// <int> The UniID receiving the item.
	,	int $packageID		// <int> The ID of the item that the sender is exchanging.
	,	string $desc = ""		// <str> The description for the log.
	,	bool $anon = false	// <bool> Whether to show it as anonymous or not.
	): bool					// RETURNS <bool> TRUE if the item was sent, FALSE if it failed.
	
	// AppTrade::sendPackage_doTransaction($senderID, $recipientID, $itemID);
	{
		// Check that you own the item
		$own = AppAvatar::checkOwnPackage($senderID, $packageID);
		if(!$own)	{ return false; }

		if(!$anon)
		{
			$success = Database::query("UPDATE user_packages SET uni_id=? WHERE uni_id=? and package_id=? LIMIT 1", array($recipientID, $senderID, $packageID));
			if($success)
			{
				AppAvatar::recordPackage($senderID, $recipientID, $packageID, ($desc != "" ? $desc . ": " : "") . "Gift or Trade");
			}
		}
		else
		{
			$recipient = User::get($recipientID, "handle");
			$pass1 = AppAvatar::receivePackage($recipientID, $packageID, ($desc != "" ? $desc . ": " : "") . "Anonymous Gift");
			$pass2 = AppAvatar::dropPackage($senderID, $packageID, ($desc != "" ? $desc . ": " : "") . "Anonymous Gift to " . $recipient['handle']);
			$success = ($pass1 !== false && $pass2 !== false ? true : false);
		}

		return $success;
	}
	
}