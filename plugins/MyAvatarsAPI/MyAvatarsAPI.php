<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

----------------------------
------ About this API ------
----------------------------

This API allows other sites to identify how many avatars the user has created (on the avatar site) and what their names are. The forums, for example, need to know if the user has avatars, and which ones can be accessed.

If the user has created two avatars, then the avatar numbers would be 1 and 2, respectively. Their names can be provided in an array like this:

	// The value that the API returns
	array(1 => "Avatar Name", 2 => "Avatar Name");
	
	
------------------------------
------ Calling this API ------
------------------------------
	
	// Prepare a list of plugins and their current versions
	$packet = array(
		"uni_id"			=> $uniID			// The UniID to check avatars for
	);
	
	$avatarList = Connect::to("avatar", "MyAvatarsAPI", $packet);
	
	
[ Possible Responses ]
	
	$avatarList = array(
		1	=> "Name of Avatar #1"
	,	2	=> "Name of Avatar #2"
	);

*/

class MyAvatarsAPI extends API {
	
	
/****** API Variables ******/
	public $isPrivate = true;			// <bool> TRUE if this API is private (requires an API Key), FALSE if not.
	public $encryptType = "";			// <str> The encryption algorithm to use for response, or "" for no encryption.
	public $allowedSites = array();		// <int:str> the sites to allow the API to connect with. Default is all sites.
	public $microCredits = 50;			// <int> The cost in microcredits (1/10000 of a credit) to access this API.
	public $minClearance = 6;			// <int> The clearance level required to use this API.
	
	
/****** Run the API ******/
	public function runAPI (
	)					// RETURNS <int:str> the data of the avatars available, array() if no avatars.
	
	// $this->runAPI()
	{
		// Make sure the appropriate information was sent to the API
		if(!isset($this->data['uni_id']))
		{
			return array();
		}
		
		// Prepare Values
		$avatarList = array();
		$uniID = (int) $this->data['uni_id'];
		
		// Select the avatars that the user has
		if(!$avatars = Database::selectMultiple("SELECT avatar_number, avatar_name FROM users_avatars WHERE uni_id=?", array($uniID)))
		{
			return array();
		}
		
		// Cycle through the list of avatars and return it in a proper format
		foreach($avatars as $avi)
		{
			$avatarList[(int) $avi['avatar_number']] = $avi['avatar_name'];
		}
		
		return $avatarList;
	}
	
}
