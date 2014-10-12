<?hh if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

abstract class AppOutfit {

/****** AppOutfit Class ******
* This class provides handling of the outfit storage. Doesn't save it into inventory; saves it as json.
* 
****** Examples of using this class ******



****** Methods Available ******
* $outfitItems = AppOutfit::get($uniID, $type);
* $outfitArray = AppOutfit::equip($outfitArray, $itemID, $gender, $color);
* $outfitArray = AppOutfit::unequip($outfitArray, $itemID);
* $outfitArray = AppOutfit::unequipAll();
* 			   = AppOutfit::save($uniID, $type, $outfitArray);
* 
* AppOutfit::draw($uniID, $type);
* AppOutfit::drawSrc($uniID, $type);
* 
* AppOutfit::sortDelete($uniID, $deleteOrder);
*/
	
	
/****** Get Outfit ******/
	public static function get
	(
		int $uniID			// <int> The Uni-Account to retrieve an outfit from.
	,	string $type			// <str> The type of outfit to retrieve.
	): array					// RETURNS <array> list of ID's in an outfit, or empty array if failed.
	
	// $outfitItems = AppOutfit::get($uniID, $type);
	{
		$getOutfit = Database::selectOne("SELECT outfit_json FROM user_outfits WHERE uni_id=? AND type=? LIMIT 1", array($uniID, $type));
		
		if(!$getOutfit) { return array(); }
		
		return json_decode($getOutfit['outfit_json'], true);
	}
	
	
/****** Add an item to an Outfit ******/
	public static function equip
	(
		array $outfitArray	// <array> The array of the outfit to update.
	,	int $itemID			// <int> The ID of the item to add.
	,	string $gender			// <str> The gender of the avatar wearing the item.
	,	string $color			// <str> The color of the item.
	): array					// RETURNS <array> the array of the outfit, or array() on error.
	
	// $outfitArray = AppOutfit::equip($outfitArray, $itemID, $gender, $color);
	{
		// If this outfit isn't in array form, return false
		if(!is_array($outfitArray))
		{
			return array();
		}
		
		// Prevent this outfit from having more than 30 items on it at once
		if(count($outfitArray) > 30)
		{
			return $outfitArray;
		}
		
		// Prepare the Gender
		$gender = ($gender[0] == "m" ? "male" : "female");
		
		// Make sure the item isn't already in the outfit
		$allow = true;
		
		foreach($outfitArray as $scan)
		{
			if($scan[0] == $itemID)
			{
				$allow = false; break;
			}
		}
		
		// Add the item, if allowed
		if($allow == true)
		{
			// Get the Item Data (also confirms it exists)
			// Note: $itemData['position'] refers to the item slot (e.g. "hair"), not the layer position
			$itemData = AppAvatar::itemData($itemID);
			
			if(!$itemData) { return $outfitArray; }
			
			// Make sure the item allows this color
			if(!AppAvatar::itemHasColor($itemData['position'], $itemData['title'], $gender, $color))
			{
				return $outfitArray;
			}
			
			// Determine the first allowed layer position of this item
			$count = (int) $itemData['min_order'];
			$countDir = 1;
			
			// Below the avatar base
			if($itemData['min_order'] < 0)
			{
				$count = min(-1, $itemData['max_order']);
				$countDir = -1;
			}
			
			if($itemData['min_order'] == 1)
			{
				$outfitArray[1] = array($itemID, $color);
				
				return $outfitArray;
			}
			
			// Cycle through the list until there's an available layer
			while(true)
			{
				// If this slot is available, use it
				if(!isset($outfitArray[$count]))
				{
					$outfitArray[$count] = array($itemID, $color);
					
					return $outfitArray;
				}
				
				$count += $countDir;
			}
		}
		
		return $outfitArray;
	}
	
	
/****** Unequip an item from an Outfit ******/
	public static function unequip
	(
		array $outfitArray	// <array> The array of the outfit to update.
	,	int $itemID			// <int> The ID of the item to remove.
	): array					// RETURNS <array> the array of the outfit, or array() if failed.
	
	// $outfitArray = AppOutfit::unequip($outfitArray, $itemID);
	{
		// If this outfit isn't in array form, return false
		if(!is_array($outfitArray))
		{
			return array();
		}
		
		// Scan through the array
		$deletedKey = 0;
		
		foreach($outfitArray as $key => $scan)
		{
			if($scan[0] == $itemID)
			{
				unset($outfitArray[$key]);
				
				$deletedKey = $key;
				
				break;
			}
		}
		
		return AppOutfit::sortDelete($outfitArray, $deletedKey);
	}
	
	
/****** Unequip an item from an Outfit ******/
	public static function unequipAll (
	): array			// RETURNS <array> the outfit (after unequipping everything), or FALSE if failed.
	
	// $outfitArray = AppOutfit::unequipAll();
	{
		return array();
	}
	
	
/****** Save an Avatar Outfit ******/
	public static function save
	(
		int $uniID			// <int> The Uni-Account to save an outfit to.
	,	string $type			// <str> The type of outfit to save.
	,	array $outfitArray	// <array> The list of item ID's to add to this outfit.
	): bool					// RETURNS <bool> TRUE on success, or FALSE if failed.
	
	// AppOutfit::save($uniID, $type, $outfitArray);
	{
		if(!is_array($outfitArray)) { return false; }
		
		// Check if the file is already being used
		$check = (int) Database::selectValue("SELECT uni_id FROM user_outfits WHERE uni_id=? AND type=? LIMIT 1", array($uniID, $type));
		
		// Overwrite the file if it already exists
		if($check)
		{
			return Database::query("UPDATE user_outfits SET outfit_json=? WHERE uni_id=? AND type=? LIMIT 1", array(json_encode($outfitArray), $uniID, $type));
		}
		
		// If it hasn't been created yet, insert the first row
		return Database::query("INSERT INTO `user_outfits` (uni_id, type, outfit_json) VALUES (?, ?, ?)", array($uniID, $type, json_encode($outfitArray)));
	}
	
	
/****** Draw an Avatar from an Outfit ******/
	public static function draw
	(
		string $base			// <str> The character base to use.
	,	string $gender			// <str> The gender to use.
	,	array $outfitArray	// <array> The outfit that you want to draw.
	): void					// RETURNS <void>
	
	// AppOutfit::draw($base, $gender, $outfitArray);
	{
		// Handle Gender
		$gender = ($gender[0] == "m" ? "male" : "female");
		
		$cX = "coord_x_" . $gender;
		$cY = "coord_y_" . $gender;
		
		// Create a blank image
		$image = new Image("image/png", 204, 383);
		
		// Draw everything below the avatar base
		$start = 1;
		
		if($outfitArray != array())
		{
			foreach($outfitArray as $key => $oa)
			{
				$start = min($start, $key);
			}
		}
		
		while($start < 0)
		{
			// Check if the layer is available
			if(!isset($outfitArray[$start])) { break; }
			
			$content = $outfitArray[$start];
			
			// Get the item data
			$itemData = Database::selectOne("SELECT title, position, " . $cX . ", " . $cY . " FROM items WHERE id=? LIMIT 1", array($content[0]));
			
			// Copy this onto the avatar image
			$image->paste(APP_PATH . "/avatar_items/" . $itemData['position'] . "/" . $itemData['title'] . "/" . $content[1] . "_" . $gender . ".png", (int) $itemData[$cX], (int) $itemData[$cY]);
			
			$start++;
		}
		
		// Draw the avatar base
		$image->paste(APP_PATH . '/avatar_items/base/' . $base . '_' . $gender . '.png', 0, 0);
		
		// Draw everything above the avatar base
		$start = 2;
		
		while(true)
		{
			// Check if the layer is available
			if(!isset($outfitArray[$start])) { break; }
			
			$content = $outfitArray[$start];
			
			// Get the item data
			$itemData = Database::selectOne("SELECT title, position, " . $cX . ", " . $cY . " FROM items WHERE id=? LIMIT 1", array($content[0]));
			
			// Copy this onto the avatar image
			$image->paste(APP_PATH . "/avatar_items/" . $itemData['position'] . "/" . $itemData['title'] . "/" . $content[1] . "_" . $gender . ".png", (int) $itemData[$cX], (int) $itemData[$cY]);
			
			$start++;
		}
		
		// Save the image
		$image->display();
	}
	
	
/****** Return the URL that draws this avatar ******/
	public static function drawSrc
	(
		string $type			// <str> The type of outfit to return (e.g. "preview").
	): string					// RETURNS <str> the URL that would load the appropriate avatar outfit image.
	
	// AppOutfit::draw($type);
	{
		return SITE_URL . "/draw-avatar?type=" . $type;
	}
	
	
/****** Resort layer positions after deleting an item ******/
	public static function sortDelete
	(
		array $outfitArray	// <array> The outfit data.
	,	int $deletedKey		// <int> The key (sort order) that you deleted.
	): array					// RETURNS <array> the outfit data (after deletion).
	
	// $outfitArray = AppOutfit::sortDelete($outfitArray, $deletedKey);
	{
		// Resort the layers after the delete
		if($deletedKey != 0 && $deletedKey != 1)
		{
			// If DELETE KEY is > 1
			if($deletedKey > 1)
			{
				$start = $deletedKey + 1;
				
				while(true)
				{
					// If we don't find another entry, end here
					if(!isset($outfitArray[$start]))
					{
						break;
					}
					
					$outfitArray[$start - 1] = $outfitArray[$start];
					
					unset($outfitArray[$start]);
					$start++;
				}
			}
			
			// If DELETE KEY is < 0
			else
			{
				$start = $deletedKey - 1;
				
				while(true)
				{
					// If we don't find another entry, end here
					if(!isset($outfitArray[$start]))
					{
						break;
					}
					
					$outfitArray[$start + 1] = $outfitArray[$start];
					
					unset($outfitArray[$start]);
					$start--;
				}
			}
		}
		
		return $outfitArray;
	}
}
