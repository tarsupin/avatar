<?hh if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

----------------------------
------ About this API ------
----------------------------

This API verifies whether or not a particular user has an avatar.

------------------------------
------ Calling this API ------
------------------------------
	
	// Prepare the packet to send
	$packet = array(
		"uni_id"	=> $uniID
	,	"avi_id"	=> $avatarID		// The ID of the avatar to test for
	);
	
	Connect::to("avatar", "AvatarExists", $packet);
	
	
[ Possible Responses ]
	TRUE if the user has an avatar.
	FALSE if the user doesn't have an avatar.

*/

class AvatarExists extends API {
	
	
/****** API Variables ******/
	public bool $isPrivate = true;			// <bool> TRUE if this API is private (requires an API Key), FALSE if not.
	public string $encryptType = "";			// <str> The encryption algorithm to use for response, or "" for no encryption.
	public array <int, str> $allowedSites = array();		// <int:str> the sites to allow the API to connect with. Default is all sites.
	public int $microCredits = 10;			// <int> The cost in microcredits (1/10000 of a credit) to access this API.
	public int $minClearance = 6;			// <int> The minimum clearance level required to use this API.
	
	
/****** Run the API ******/
	public function runAPI (
	): bool					// RETURNS <bool> TRUE if the user has an avatar, FALSE if not
	
	// $this->runAPI()
	{
		// Make sure the necessary data was sent
		if(!isset($this->data['uni_id']) or !isset($this->data['avi_id']))
		{
			return false;
		}
		
		return (bool) Database::selectValue("SELECT uni_id FROM avatars WHERE uni_id=? AND avatar_id=? LIMIT 1", array($this->data['uni_id'], $this->data['avatar_id']));
	}
	
}