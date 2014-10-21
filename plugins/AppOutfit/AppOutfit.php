<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

abstract class AppOutfit {

/****** AppOutfit Class ******
* This class provides handling of the outfit storage. Doesn't save it into inventory; saves it as json.
* 
****** Examples of using this class ******



****** Methods Available ******
* $outfitArray = AppOutfit::get($uniID, $type);
* $outfitArray = AppOutfit::equip($outfitArray, $itemID, $gender, $color);
* $outfitArray = AppOutfit::unequip($outfitArray, $itemID);
* $outfitArray = AppOutfit::unequipAll();
* $outfitArray = AppOutfit::move($outfitArray, $itemID, $direction);
* 			   = AppOutfit::save($uniID, $type, $outfitArray);
* 
* AppOutfit::eraseInfo($gender, $outfitArray);
*
* AppOutfit::draw($uniID, $type);
* AppOutfit::drawSrc($uniID, $type);
* 
* AppOutfit::sortDelete($uniID, $deleteOrder);
* AppOutfit::sortAll($outfitArray, $gender, $type);
*/
	
	
/****** Get Outfit ******/
	public static function get
	(
		$uniID			// <int> The Uni-Account to retrieve an outfit from.
	,	$type			// <str> The type of outfit to retrieve.
	)					// RETURNS <array> list of ID's in an outfit, or empty array if failed.
	
	// $outfitItems = AppOutfit::get($uniID, $type);
	{
		$getOutfit = Database::selectOne("SELECT outfit_json FROM user_outfits WHERE uni_id=? AND type=? LIMIT 1", array($uniID, $type));
		
		if(!$getOutfit) { return array(); }
		
		return json_decode($getOutfit['outfit_json'], true);
	}
	
	
/****** Add an item to an Outfit ******/
	public static function equip
	(
		$outfitArray		// <array> The array of the outfit to update.
	,	$itemID				// <int> The ID of the item to add.
	,	$gender				// <str> The gender of the avatar wearing the item.
	,	$color				// <str> The color of the item.
	,	$type = "preview"	// <str> The type of outfit.
	,	$forcedown = false	// <bool> Whether to force an item below the base. Needed when used through sortAll().
	)						// RETURNS <array> the array of the outfit.
	
	// $outfitArray = AppOutfit::equip($outfitArray, $itemID, $gender, $color, $type);
	{
		// If this outfit isn't in array form, return empty array
		if(!is_array($outfitArray))
		{
			return array();
		}
		
		// Get the Item Data (also confirms it exists)
		// Note: $itemData['position'] refers to the item slot (e.g. "hair"), not the layer position
		$itemData = AppAvatar::itemData($itemID);
		
		if(!$itemData) { return $outfitArray; }
		
		// Prepare the Gender
		$gender = ($gender[0] == "m" ? "male" : "female");
		
		
		// Make sure the item allows this color
		if(!AppAvatar::itemHasColor($itemData['position'], $itemData['title'], $gender, $color))
		{
			Alert::error($itemData['title'] . " Missing Color", $itemData['title'] . " does not exist in the color " . $color . ".");
			return $outfitArray;
		}
		
		
		// Replace color if the item is already in the outfit
		foreach($outfitArray as $key => $scan)
		{
			if($scan[0] == $itemID)
			{
				$outfitArray[$key] = array($itemID, $color);
				return $outfitArray;
			}
		}
		
		// Make sure you own the item if it's equipped to an actual avatar
		if($type != "preview")
		{
			if(!AppAvatar::checkOwnItem(Me::$id, $itemID))
			{
				Alert::error($itemData['title'] . " Not Owned", "You do not own " . $itemData['title'] . ", so it cannot be equipped.");
				return $outfitArray;
			}
		}
		
		// Add the item in the highest allowed position
		$max = (int) $itemData['max_order'];
		$min = (int) $itemData['min_order'];
		
		// Force item to equip below the base if it may go there, or as low above the base as possible
		if($forcedown)
		{
			if($min < 0)		{ $max = min(-1, $max); }
			elseif($min <= 2)	{ $max = min(2, $max); }
			else				{ $max = $min; }
		}

		// Determine highest and lowest (pseudo-)existing item indices in the outfit
		if($outfitArray != array())	{ $highest = max(array_keys($outfitArray)); $lowest = min(array_keys($outfitArray)); }
		else						{ $highest = 1; $lowest = 0; }
		
		if($highest < 1)			{ $highest = 1; }
		if($lowest > 0)				{ $lowest = 0; }
		
		// Add on top if allowed and there is room
		if($max > $highest)
		{
			$outfitArray[$highest+1] = array($itemID, $color);
			ksort($outfitArray);
			return $outfitArray;
		}
		// Try pushing higher items further up to make room
		elseif($max > 1)
		{
			if($highest < 99)
			{
				for($loop=$highest; $loop>=$max; $loop--)
				{
					$outfitArray[$loop+1] = $outfitArray[$loop];
				}
				$outfitArray[$max] = array($itemID, $color);
				ksort($outfitArray);
				return $outfitArray;
			}
			else
			{
				Alert::error("Too Many Items", "You cannot equip more items above the base.");
				return $outfitArray;
			}
		}
		// Add or replace if the item is a skin
		elseif($max == 1)
		{
			$outfitArray[1] = array($itemID, $color);
			ksort($outfitArray);
			return $outfitArray;
		}
		// Item can only go below the base
		// Try pushing lower items further down to make room
		elseif($lowest > -99)
		{
			if($max >= 0)			{ $max = -1; }		// Case should not occur with proper min/max settings
			for($loop=$lowest; $loop<=$max; $loop++)
			{
				$outfitArray[$loop-1] = $outfitArray[$loop];
			}
			$outfitArray[$max] = array($itemID, $color);
			ksort($outfitArray);
			return $outfitArray;
		}
		else
		{
			Alert::error("Too Many Items", "You cannot equip more items below the base.");
			return $outfitArray;
		}
		
		return $outfitArray;
	}
	
	
/****** Unequip an item from an Outfit ******/
	public static function unequip
	(
		$outfitArray	// <array> The array of the outfit to update.
	,	$itemID			// <int> The ID of the item to remove.
	)					// RETURNS <array> the array of the outfit, or array() if failed.
	
	// $outfitArray = AppOutfit::unequip($outfitArray, $itemID);
	{
		// If this outfit isn't in array form, return false
		if(!is_array($outfitArray))
		{
			return array();
		}
		
		// Scan through the array
		foreach($outfitArray as $key => $scan)
		{
			if($scan[0] == $itemID)
			{
				unset($outfitArray[$key]);
				return self::sortDelete($outfitArray, $key);
			}
		}
		
		// Item was not in outfit
		return $outfitArray;
	}
	
	
/****** Unequip an item from an Outfit ******/
	public static function unequipAll (
	)			// RETURNS <array> the outfit (after unequipping everything).
	
	// $outfitArray = AppOutfit::unequipAll();
	{
		return array();
	}
	
	
/****** Move an item to the left or right ******/
	public static function move
	(
		$outfitArray	// <array> The array of the outfit to update.
	,	$itemID			// <int> The ID of the item to move.
	,	$direction		// <str> left or right (meaning up or down)
	)					// RETURNS <array> the array of the outfit, or array() if failed.
	
	// $outfitArray = AppOutfit::move($outfitArray, $itemID, $direction);
	{
		// If this outfit isn't in array form, return false
		if(!is_array($outfitArray))
		{
			return array();
		}
		
		$itemData = AppAvatar::itemData($itemID);
		foreach($outfitArray as $key => $oitem)
		{
			// Found the correct item. Now let's move it.
			if($oitem[0] == $itemID)
			{
				if($key == 1)									{ break; }
				
				if($direction == "left")				
				{
					if($key == -1 && $itemData['max_order'] > 1)	{ $newkey = 2; }
					elseif($itemData['max_order'] > $key)			{ $newkey = $key+1; }
					else											{ break; }
				}
				else
				{
					if($key == 2 && $itemData['min_order'] < 0)		{ $newkey = -1; }
					elseif($itemData['min_order'] < $key)			{ $newkey = $key-1; }
					else											{ break; }
				}
				
				if(isset($outfitArray[$newkey]))
				{
					// push items up/down and fix gap
					if($direction == "left" && $newkey == 2)
					{
						if(max(array_keys($outfitArray)) < 99)
						{
							for($loop=max(array_keys($outfitArray)); $loop>=$newkey; $loop--)
							{
								$outfitArray[$loop+1] = $outfitArray[$loop];
							}
							$outfitArray[$newkey] = $outfitArray[$key];
							unset($outfitArray[$key]);
							$outfitArray = self::sortDelete($outfitArray, $key);
						}
					}
					else if($direction == "right" && $newkey == -1)
					{
						if(min(array_keys($outfitArray)) > -99)
						{
							for($loop=min(array_keys($outfitArray)); $loop<=$newkey; $loop++)
							{
								$outfitArray[$loop-1] = $outfitArray[$loop];
							}
							$outfitArray[$newkey] = $outfitArray[$key];
							unset($outfitArray[$key]);
							$outfitArray = self::sortDelete($outfitArray, $key);
						}
					}
					else
					{
						$save = $outfitArray[$key];
						$outfitArray[$key] = $outfitArray[$newkey];
						$outfitArray[$newkey] = $save;
					}
				}
				else
				{
					$outfitArray[$newkey] = $outfitArray[$key];
					unset($outfitArray[$key]);
					$outfitArray = self::sortDelete($outfitArray, $key);
				}
				
				break;
			}
		}
	
		return $outfitArray;
	}
	
	
/****** Save an Avatar Outfit ******/
	public static function save
	(
		$uniID			// <int> The Uni-Account to save an outfit to.
	,	$type			// <str> The type of outfit to save.
	,	$outfitArray	// <array> The list of item ID's to add to this outfit.
	)					// RETURNS <bool> TRUE on success, or FALSE if failed.
	
	// AppOutfit::save($uniID, $type, $outfitArray);
	{
		if(!is_array($outfitArray)) { return false; }
		
		ksort($outfitArray);
		
		// Check if the file is already being used
		$check = (int) Database::selectValue("SELECT uni_id FROM user_outfits WHERE uni_id=? AND type=? LIMIT 1", array($uniID, $type));
		
		// Overwrite the file if it already exists
		if($check)
		{
			Database::query("UPDATE avatars SET date_lastUpdate=? WHERE uni_id=? LIMIT 1", array(time() -1, Me::$id));
			return Database::query("UPDATE user_outfits SET outfit_json=? WHERE uni_id=? AND type=? LIMIT 1", array(json_encode($outfitArray), $uniID, $type));
		}
		
		// If it hasn't been created yet, insert the first row
		return Database::query("INSERT INTO `user_outfits` (uni_id, type, outfit_json) VALUES (?, ?, ?)", array($uniID, $type, json_encode($outfitArray)));
	}
	
	
/****** Decide which parts of item images need to be erased ******/
	public static function eraseInfo
	(
		$gender			// <str> The gender to use.
	,	$outfitArray	// <array> The outfit that you want to draw.
	)					// RETURNS <array> of upper left coordinates and size of the rectangles to erase from the respective positions.
	
	// AppOutfit::eraseInfo($gender, $outfitArray);
	{
		$toerase['female'] = array("base" => array(), "skin" => array(), "shoes" => array());
		$toerase['male'] = array("base" => array(), "skin" => array(), "shoes" => array());
		foreach($outfitArray as $oa)
		{
			// the item ID itself must be saved so that the item doesn't erase parts of itself
			switch($oa[0])
			{
				case 2489:	// Mermaid Tail Blue
				case 2490:	// Mermaid Tail Green
				case 2491:	// Mermaid Tail Pink
				case 2516:	// Octopus
				case 2517:	// Shark Tail
				case 2949:	// Ebony Centaur Legs
				case 2950:	// Chestnut Centaur Legs
				case 2951:	// Ivory Centaur Legs
					$toerase['female']['base'][] = array(30, 220, 160, 163, $oa[0]);
					$toerase['female']['skin'][] = array(30, 220, 160, 163, $oa[0]);
					$toerase['male']['base'][] = array(30, 230, 150, 153, $oa[0]);
					$toerase['male']['skin'][] = array(30, 230, 150, 153, $oa[0]);
					break;
				case 3169:	// Peg
					$toerase['female']['base'][] = array(118, 263, 87, 65, $oa[0]);
					$toerase['female']['skin'][] = array(118, 263, 87, 65, $oa[0]);
					$toerase['female']['base'][] = array(136, 328, 69, 55, $oa[0]);
					$toerase['female']['skin'][] = array(136, 328, 69, 55, $oa[0]);
					$toerase['male']['base'][] = array(0, 260, 100, 123, $oa[0]);
					$toerase['male']['skin'][] = array(0, 260, 100, 123, $oa[0]);
					$toerase['female']['shoes'][] = array(116, 263, 89, 48, $oa[0]);
					$toerase['female']['shoes'][] = array(127, 311, 78, 19, $oa[0]);
					$toerase['female']['shoes'][] = array(135, 330, 69, 53, $oa[0]);
					$toerase['male']['shoes'][] = array(0, 262, 98, 121, $oa[0]);
					break;
				case 3171:	// Hook
					$toerase['female']['base'][] = array(143, 174, 62, 43, $oa[0]);
					$toerase['female']['skin'][] = array(143, 174, 62, 43, $oa[0]);
					$toerase['male']['base'][] = array(0, 171, 74, 53, $oa[0]);
					$toerase['male']['skin'][] = array(0, 171, 74, 53, $oa[0]);
					break;
				case 1362:	// Booby Base
				case 3002:	// Heel Feet
				case 3248:	// Velvet Skin Heels
				case 3303:	// Ballerina Base
				case 3455:  // Velvet Ballerina
					$toerase['female']['base'][] = array(0, 0, 205, 383, $oa[0]);
					break;
				case 3302:	// Dancer Base
				case 3454:  // Velvet Dancer
					$toerase['female']['base'][] = array(0, 0, 205, 383, $oa[0]);
					$toerase['male']['base'][] = array(0, 0, 205, 383, $oa[0]);
					break;
			}
		}
		
		// Remove unneeded entries
		foreach($toerase[$gender] as $position => $te)
		{
			foreach($te as $key => $val)
			{
				foreach($te as $key2 => $val2)
				{
					if($key == $key2)	{ continue; }
					// Rectangle described by $val2 is completely contained in $val
					if($val[0] <= $val2[0] && $val[1] <= $val2[1] && $val[0]+$val[2] >= $val2[0]+$val2[2] && $val[1]+$val[3] >= $val2[1]+$val2[3])
					{
						unset($toerase[$gender][$position][$key2]);
						unset($te[$key2]);
					}
				}
			}
		}
		
		return $toerase[$gender];
	}
	

/****** Draw an Avatar from an Outfit ******/
	public static function draw
	(
		$base			// <str> The character base to use.
	,	$gender			// <str> The gender to use.
	,	$outfitArray	// <array> The outfit that you want to draw.
	,	$location = ""	// <str> The location to save the image in.
	)					// RETURNS <void>
	
	// AppOutfit::draw($base, $gender, $outfitArray);
	{
		// Handle Gender
		$gender = ($gender[0] == "m" ? "male" : "female");
		
		$cX = "coord_x_" . $gender;
		$cY = "coord_y_" . $gender;
		
		// Create a blank image
		$image = new Image("", 205, 383, "png");

		// Get data about what to erase
		$toerase = self::eraseInfo($gender, $outfitArray);
		
		// Draw everything
		
		// Setting this avoids using the same code for below and above parts
		// $outfitArray is not returned, so this can be done
		// base isn't drawn if a skin is drawn to avoid lots of erasing
		if(!isset($outfitArray[1]))
		{
			$outfitArray[0] = array(0, $base);
		}
		
		// make sure it starts with the lowest keys
		ksort($outfitArray);

		foreach($outfitArray as $start => $content)
		{
			// Get the source path and item data
			if($start != 0)
			{
				$itemData = AppAvatar::itemData($content[0]);
				$path = APP_PATH . "/avatar_items/" . $itemData['position'] . "/" . $itemData['title'] . "/" . $content[1] . "_" . $gender . ".png";
				$itemData = Database::selectOne("SELECT title, position, " . $cX . ", " . $cY . " FROM items WHERE id=? LIMIT 1", array($content[0]));
				$itemData[$cX] = (int) $itemData[$cX];
				$itemData[$cY] = (int) $itemData[$cY];
			}
			else
			{
				$path = APP_PATH . "/avatar_items/base/" . $base . "_" . $gender . ".png";
				$itemData = array("title" => "Base", "position" => "base", $cX => 0, $cY => 0);
			}

			// Erase parts of the to-be-added image if needed
			if(isset($toerase[$itemData['position']]) && $toerase[$itemData['position']] != array())
			{
				$draw = new Image($path);
				$background_color = imagecolorallocatealpha($draw->resource, 0, 255, 0, 127);
				imagecolortransparent($draw->resource, $background_color);
				imagealphablending($draw->resource, false);
				foreach($toerase[$itemData['position']] as $te)
				{
					// don't erase from item itself
					if($te[4] != $content[0])
					{
						$localx = $te[0] - $itemData[$cX];
						$localy = $te[1] - $itemData[$cY];
						imagefilledrectangle($draw->resource, $localx, $localy, $localx+$te[2], $localy+$te[3], $background_color);
					}
				}
				imagecopy($image->resource, $draw->resource, $itemData[$cX], $itemData[$cY], 0, 0, $draw->width, $draw->height);
				imagedestroy($draw->resource);
			}
			// Copy this onto the avatar image
			else
			{
				$image->paste($path, $itemData[$cX], $itemData[$cY]);
			}
			
			$start++;
		}
		
		// Save the image, otherwise display it
		if($location != "")
		{
			$image->save($location, 100);
		}
		else
		{		
			$image->display();
		}
	}
	
	
/****** Return the URL that draws this avatar ******/
	public static function drawSrc
	(
		$type			// <str> The type of outfit to return (e.g. "preview").
	)					// RETURNS <str> the URL that would load the appropriate avatar outfit image.
	
	// AppOutfit::draw($type);
	{
		return SITE_URL . "/draw-avatar?type=" . $type;
	}
	
	
/****** Resort layer positions after deleting an item ******/
	public static function sortDelete
	(
		$outfitArray	// <array> The outfit data.
	,	$deletedKey		// <int> The key (sort order) that you deleted.
	)					// RETURNS <array> the outfit data (after deletion).
	
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
		
		ksort($outfitArray);
		
		return $outfitArray;
	}
	
/****** Reorder and check outfit code ******/
	public static function sortAll
	(
		$outfitArray		// <array> The outfit data.
	,	$gender				// <str> The gender of the avatar.
	,	$type = "preview"	// <str> The type of outfit.
	)						// RETURNS <array> the sorted and checked outfit data.
	
	// $outfitArray = AppOutfit::sortAll($outfitArray, $gender, $type);
	// Outfits are often constructed by entering a code or submitting various changes at once, so it needs to be built and checked from scratch.
	// Outfits that this function is called on always include the base.
	{
		if(!is_array($outfitArray) || $outfitArray == array())
		{
			return array();
		}
		
		// make sure it starts with the lowest keys
		ksort($outfitArray);
	
		// Identify items below base and unset base
		$below = array();
		foreach($outfitArray as $key => $oa)
		{
			if ($oa[0] == 0) { unset($outfitArray[$key]); break; }
			$below[] = $oa[0];
		}
		
		// Start with empty outfit and add items in order
		$outfitArray2 = array();
		foreach($outfitArray as $key => $oa)
		{
			if(in_array($oa[0], $below))
			{
				$outfitArray2 = self::equip($outfitArray2, (int) $oa[0], $gender, $oa[1], $type, true);
			}
			else
			{
				$outfitArray2 = self::equip($outfitArray2, (int) $oa[0], $gender, $oa[1], $type);
			}
		}	
	
		return $outfitArray2;
	}
	
}