<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

---------------------------------------
------ About the AppTrade Plugin ------
---------------------------------------

This class provides functions to send Auro and items to another user.

-------------------------------
------ Methods Available ------
-------------------------------

AppTrade::sendAuro_doTransaction($senderID, $recipientID, $auroAmount);
AppTrade::sendItem_doTransaction($senderID, $recipientID, $itemID);
AppTrade::sendAuro_undoTransaction($senderID, $recipientID, $auroAmount);
AppTrade::sendItem_undoTransaction($senderID, $recipientID, $itemID);
AppTrade::record($senderID, $otherUser['id'], 123, "Birthday Present");

*/

abstract class AppTrade {


/****** Exchange Auro between two users ******/
	public static function sendAuro_doTransaction
	(
		$senderID		// <int> The UniID sending the Auro.
	,	$recipientID	// <int> The UniID receiving the Auro.
	,	$auroAmount		// <int> The amount of Auro being sent.
	,	$desc = "Gift or Trade"		// <str> The description for the log.
	)					// RETURNS <bool> TRUE if the Auro was sent, FALSE if it failed.
	
	// AppTrade::sendAuro_doTransaction($senderID, $recipientID, $auroAmount);
	{
		return Currency::exchange($senderID, $recipientID, $auroAmount, $desc);
	}
	
/****** Exchange an item between two users ******/
	public static function sendItem_doTransaction
	(
		$senderID		// <int> The UniID sending the item.
	,	$recipientID	// <int> The UniID receiving the item.
	,	$itemID			// <int> The ID of the item that the sender is exchanging.
	,	$desc = "Gift or Trade"		// <str> The description for the log.
	)					// RETURNS <bool> TRUE if the item was sent, FALSE if it failed.
	
	// AppTrade::sendItem_doTransaction($senderID, $recipientID, $itemID);
	{
		// Check that you own the item
		$own = AppAvatar::checkOwnItem($senderID, $itemID);
		if(!$own)	{ return false; }
		
		$success = Database::query("UPDATE user_items SET uni_id=? WHERE uni_id=? and item_id=? LIMIT 1", array($recipientID, $senderID, $itemID));
		if($success)
		{
			$success = self::record($senderID, $recipientID, $itemID, $desc);
			// update cached layers
			Cache::delete("invLayers:" . $senderID);
			Cache::delete("invLayers:" . $recipientID);
		}
		return $success;
	}
	
	
/****** Records an item transaction ******/
	public static function record
	(
		$senderID		// <int> The Uni-Account to send item.
	,	$recipientID	// <int> The Uni-Account to receive the item.
	,	$itemID			// <int> The item ID.
	,	$desc = ""		// <str> A brief description about the transaction's purpose.
	)					// RETURNS <bool> TRUE on success, or FALSE on error.
	
	// AppTrade::record($senderID, $otherUser['id'], 123, "Birthday Present");
	{
		if($senderID === false or $recipientID === false) { return false; }
		
		// Prepare Values
		$timestamp = time();
		
		// Run the record keeping
		$pass = Database::query("INSERT INTO item_records (description, uni_id, other_id, item_id, date_exchange) VALUES (?, ?, ?, ?, ?)", array(Sanitize::text($desc), $senderID, $recipientID, $itemID, $timestamp));
		
		return ($pass);
	}
	
}