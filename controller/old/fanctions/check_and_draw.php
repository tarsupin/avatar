<?php
	if (!defined("RELATIVE"))
		define("RELATIVE", "");
	if (!defined("REVERSE"))
		define("REVERSE", "");
	if (!defined("EXIST"))
		define("EXIST", "");
		
	// this data is used very often
	function fetch_item_details($clothing)
	{
		global $memc;
		if (!$fetch = $memc->get("ci_itemdata_" . ($clothing + 0)))
		{
			$res = mysql_query("SELECT * FROM `clothing_images` WHERE `clothingID`='" . ($clothing + 0) . "' LIMIT 1");
			if ($fetch = mysql_fetch_assoc($res))
			{
				$memc->set("ci_itemdata_" . ($clothing + 0), $fetch, false, 3600);
				return $fetch;
			}
		}
		elseif (!isset($fetch['cost'])) // remove later
			return renew_item_details($clothing);
		else
			return $fetch;
		return false;
	}
	
	function renew_item_details($clothing)
	{
		global $memc;
		$res = mysql_query("SELECT * FROM `clothing_images` WHERE `clothingID`='" . ($clothing + 0) . "' LIMIT 1");
		if ($fetch = mysql_fetch_assoc($res))
		{
			if (!$memc->replace("ci_itemdata_" . ($clothing + 0), $fetch, false, 3600))
				$memc->set("ci_itemdata_" . ($clothing + 0), $fetch, false, 3600);
			return $fetch;
		}
		else
			delete_item_details($clothing + 0);
		return false;
	}
	
	function delete_item_details($clothing)
	{
		global $memc;
		$memc->delete("ci_itemdata_" . ($clothing + 0));
	}
	
	function order_item_details($a, $b)
	{
		if ($a['clothing'] != $b['clothing'])
			return ($a['clothing'] < $b['clothing']) ? -1 : 1;
		return 0;
	}
		
	// get real avi outfit array
	function get_outfit($id, $base)
	{
		$q = "SELECT `outfit_serial` FROM `avatar_outfits_real` WHERE `avatar_id`='" . ($id + 0) . "' LIMIT 1";
		$res = mysql_query($q);
		if (!$fetch_outfit = mysql_fetch_assoc($res))
			return restructure_from_old($id, $base);
		else
			return unserialize($fetch_outfit['outfit_serial']);
	}
	
	// get preview avi outfit array
	function get_preview_outfit($account, $base)
	{
		$q = "SELECT `outfit_serial` FROM `avatar_outfits_preview` WHERE `account`='" . protectSQL($account) . "' LIMIT 1";
		$res = mysql_query($q);
		if (!$fetch_preview = mysql_fetch_assoc($res))
			return restructure_preview_from_old($account, $base);
		else
			return unserialize($fetch_preview['outfit_serial']);
	}

	// transfer real avi outfit from old layering system
	function restructure_from_old($avi, $base)
	{
		require(REVERSE . "layerorder.php");
		$new = array();
		$q = "SELECT `account` FROM `avatars` WHERE `id`='" . ($avi + 0) . "' LIMIT 1";
		$res = mysql_query($q);
		$fetch = mysql_fetch_assoc($res);
		$q = "SELECT * FROM `avatar_equipment` WHERE `avatarID`='" . ($avi + 0) . "' LIMIT 1";
		$res = mysql_query($q);
		if ($fetch_old = mysql_fetch_assoc($res))
		{
			foreach ($ordered_layer_list as $layer)
			{
				if (isset($fetch_old[$layer]) && isset($fetch_old[$layer . '_prefix']) && $fetch_old[$layer] > 0)
				{
					if (owned_item($fetch_old[$layer], $fetch['account']))
						$new[] = array($fetch_old[$layer], $fetch_old[$layer . '_prefix']);
				}
				elseif ($layer == "base")
					$new[] = array(0, $base);
			}
			// not using protectSQL since it removes semicolon
			$q = "INSERT INTO `avatar_outfits_real` (`avatar_id`, `outfit_serial`) VALUES ('" . ($avi + 0) . "', '" . mysql_real_escape_string(serialize($new)) . "') ON DUPLICATE KEY UPDATE `outfit_serial`='" . mysql_real_escape_string(serialize($new)) . "'";
			mysql_query($q);
			return $new;
		}
		$new[] = array(0, $base);
		$q = "INSERT INTO `avatar_outfits_real` (`avatar_id`, `outfit_serial`) VALUES ('" . ($avi + 0) . "', '" . mysql_real_escape_string(serialize($new)) . "') ON DUPLICATE KEY UPDATE `outfit_serial`='" . mysql_real_escape_string(serialize($new)) . "'";
		mysql_query($q);
		return $new;
	}
	
	// transfer preview avi outfit from old layering system
	function restructure_preview_from_old($account, $base)
	{
		require(REVERSE . "layerorder.php");
		$new = array();
		foreach ($ordered_layer_list as $layer)
		{
			$q = "SELECT `clothingID`, `prefix` FROM `preview_list` WHERE `account`='" . protectSQL($account) . "' AND `position`='" . protectSQL($layer) . "' LIMIT 1";
			$res = mysql_query($q);
			if ($fetch_old = mysql_fetch_assoc($res))
				$new[] = array($fetch_old['clothingID'], $fetch_old['prefix']);
			elseif ($layer == "base")
				$new[] = array(0, $base);
		}
		if ($new == array())
			$new[] = array(0, $base);
		// not using protectSQL since it removes semicolon
		$q = "INSERT INTO `avatar_outfits_preview` (`account`, `outfit_serial`) VALUES ('" . protectSQL($account) . "', '" . mysql_real_escape_string(serialize($new)) . "') ON DUPLICATE KEY UPDATE `outfit_serial`='" . mysql_real_escape_string(serialize($new)) . "'";
		mysql_query($q);
		return $new;
	}
	
	// get clothingID of an item if the user owns it
	function owned_id($id, $account)
	{
		$q = "SELECT `clothingID` FROM `avatar_clothing` WHERE `id`='" . ($id + 0) . "' AND `account`='" . protectSQL($account) . "' AND `in_trade`='0' LIMIT 1";
		$res = mysql_query($q);
		if ($confirm = mysql_fetch_assoc($res))
			return $confirm['clothingID'];
		return false;
	}
	
	// check if clothingID is owned
	function owned_item($id, $account)
	{
		$q = "SELECT `id` FROM `avatar_clothing` WHERE `clothingID`='" . ($id + 0) . "' AND `account`='" . protectSQL($account) . "' AND `in_trade`='0' LIMIT 1";
		$res = mysql_query($q);
		if ($confirm = mysql_fetch_assoc($res))
			return true;
		else
			return false;
	}
	
	// check if user owns or has access
	function check_owned($outfit, $account)
	{
		if (EXIST == "own")
		{
			foreach ($outfit as $key => $val)
				if ($val[0] > 0)
					if (!owned_item($val[0], $account))
						unset($outfit[$key]);
		}
		elseif (EXIST == "maysee")
		{
			$q = "SELECT `clearance` FROM `account_info` WHERE `account`='" . protectSQL($account) . "' LIMIT 1";
			$res = mysql_query($q);
			if ($fetch = mysql_fetch_assoc($res))
			{
				$shops2 = array();
				$q = mysql_query("SELECT `id` FROM `shop_listings` WHERE `clearance`<='" . ($fetch['clearance'] + 0) . "'");
				while ($row = mysql_fetch_assoc($q))
					$shops2[] = $row['id'];
				foreach ($outfit as $key => $val)
					if ($val[0] > 0)
						if (!in_array($val[5], $shops2) && !owned_item($val[0], $account))
							unset($outfit[$key]);
			}
		}
		return array_values($outfit);
	}
	
	// check if everything exists in DB and FTP, add positioning and name info
	function check_exist($outfit, $gender)
	{
		$done = array();
		foreach ($outfit as $key => $val)
		{
			if (isset($done[$val[0]]))
			{
				unset($outfit[$key]);
				if (file_exists(RELATIVE . "avatars/" . $outfit[$done[$val[0]]][4] . "/" . $outfit[$done[$val[0]]][3] . "/" . trim($val[1]) . "_" . $gender . ".png"))
					$outfit[$done[$val[0]]][1] = $val[1];
			}
			elseif ($val[0] > 0)
			{
				if ($item = fetch_item_details($val[0]))
				{
					if ($item['used_by'] == $gender || $item['used_by'] == "both")
					{
						if (file_exists(RELATIVE . "avatars/" . $item['position'] . "/" . $item['clothing'] . "/" . trim($val[1]) . "_" . $gender . ".png"))
						{
							$outfit[$key][] = $item['rel_to_base'];
							$outfit[$key][] = $item['clothing'];
							$outfit[$key][] = $item['position'];
							$outfit[$key][] = $item['shopID'];
							$done[$val[0]] = $key;
						}
						else
							unset($outfit[$key]);
					}
					else
						unset($outfit[$key]);
				}
				else
					unset($outfit[$key]);
			}
			else
				$outfit[$key][] = "base";
		}
		return array_values($outfit);
	}
	
	// fix layering order and make sure the proper base is used
	function fix_layering($outfit, $base, $id)
	{
		$outfit2 = array();
		$basepos = false;
		foreach ($outfit as $key => $val)
			if ($val[0] == 0)
			{
				if ($basepos === false)
					$basepos = $key;
				else
					unset($outfit[$key]);
			}
		if ($basepos === false)
			$basepos = -1;
		foreach ($outfit as $key => $val)
		{
			if ($key < $basepos && $val[2] != "on" && $val[2] != "above")
			{
				$outfit2[] = array($val[0], $val[1], $val[3], $val[4]);
				unset($outfit[$key]);
			}
			elseif ($key > $basepos && $val[2] == "below")
			{
				$outfit2[] = array($val[0], $val[1], $val[3], $val[4]);
				unset($outfit[$key]);
			}
		}
		if ($id > 0 || $basepos == -1)
			$outfit2[] = array(0, $base, "Base", "base");
		else
			$outfit2[] = array(0, $outfit[$basepos][1], "Base", "base");

		$has_on = false;
		foreach ($outfit as $key => $val)
		{
			if ($val[2] == "on")
			{
				if ($has_on === false)
				{
					$has_on = count($outfit2);
					$outfit2[] = array($val[0], $val[1], $val[3], $val[4]);
				}
				else
					$outfit2[$has_on] = array($val[0], $val[1], $val[3], $val[4]);
				unset($outfit[$key]);
			}
		}
		foreach ($outfit as $key => $val)
		{
			if ($key > $basepos && $val[2] != "on" && $val[2] != "below")
			{
				$outfit2[] = array($val[0], $val[1], $val[3], $val[4]);
				unset($outfit[$key]);
			}
			elseif ($key < $basepos && $val[2] == "above")
			{
				$outfit2[] = array($val[0], $val[1], $val[3], $val[4]);
				unset($outfit[$key]);
			}
		}
		return $outfit2;
	}
	
	// save real avi outfit to DB
	function save_order($outfit, $account, $id)
	{
		$outfit2 = array();
		foreach ($outfit as $o)
			$outfit2[] = array($o[0], $o[1]);
		if ($id > 0)
		{			
			$q = "UPDATE `avatar_outfits_real` SET `outfit_serial`='" . mysql_real_escape_string(serialize($outfit2)) . "' WHERE `avatar_id`='" . ($id + 0) .  "' LIMIT 1";
		}
		else
		{
			$q = "UPDATE `avatar_outfits_preview` SET `outfit_serial`='" . mysql_real_escape_string(serialize($outfit2)) . "' WHERE `account`='" . protectSQL($account) .  "' LIMIT 1";
		}
		if (mysql_query($q))
			return $outfit2;
		return false;
	}
	
	// actual image construction
	function draw_image($outfit, $gender, $save, $id)
	{
		if ($gender == "female") { $gX = 2; $gY = 3;} else { $gX = 0; $gY = 1;}
		$im = imagecreatetruecolor(205, 383);
		$background_color = imagecolorallocatealpha($im, 0, 255, 0, 127);
		imagefill($im, 0, 0, $background_color);
		imagecolortransparent($im, $background_color);
		require(REVERSE . "draw_erase.php");
		foreach ($outfit as $key => $val)
		{
			if ($val[0] == 0)
			{
				$draw = imagecreatefrompng(RELATIVE . "avatars/base/" . $val[1] . "_" . $gender . ".png");
				$coord[$gX] = 0;
				$coord[$gY] = 0;
			}
			else
			{
				$draw = imagecreatefrompng(RELATIVE . "avatars/" . $val[3] . "/" . $val[2] . "/" . $val[1] . "_" . $gender . ".png");
				$coord = explode(" ", file_get_contents(RELATIVE . "avatars/" . $val[3] . "/" . $val[2] . "/_stats.txt"));
			}
			foreach ($toerase[$gender] as $to)
			{
				if (in_array($val[3], $to[4]))
				{
					foreach ($outfit as $k => $v)
						if ($v[0] == $to[5])
						{
							if ($k > $key)
								$draw = erase($draw, $to[0], $to[1], $to[2], $to[3], $coord[$gX], $coord[$gY]);
							break;
						}
				}
			}
			if ($val[0] == 0)
				imagecopy($im, $draw, 0, 0, 0, 0, imagesx($draw), imagesy($draw));
			else
				imagecopy($im, $draw, $coord[$gX], $coord[$gY], 0, 0, imagesx($draw), imagesy($draw));
			imagedestroy($draw);
		}
		$t = time();
		if ($id > 0)
		{
			$q = "UPDATE `avatars` SET `last_timestamp`=" . ($t + 0) . " WHERE `id`='" . ($id + 0) . "' LIMIT 1";
			mysql_query($q);
			$memc = new Memcache;
			$memc->connect("localhost", 11211);
			$memc->set("lastaviupdate_" . $id, time(), false, 86400);
			$memc->close();
		}
		imagealphablending($im, true);
		imagesavealpha($im, true);
		if ($save != "")
			imagepng($im, $save);
		else
			imagepng($im);
		imagedestroy($im);
		return $t;
	}
	
	// erase part of items, mostly base and skin
	function erase($src, $x, $y, $w, $h, $movex=0, $movey=0)
	{
		$localx = $x - $movex;
		$localy = $y - $movey;
		$background_color = imagecolorallocatealpha($src, 0, 255, 0, 127);
		imagealphablending($src, false);
		imagefilledrectangle($src, $localx, $localy, $localx+$w, $localy+$h, $background_color);
		imagecolortransparent($src, $background_color);
		return $src;
	}
	
	function ensure_correctness($outfit_input, $gender, $base, $account, $id)
	{
		foreach ($outfit_input as $out)
			if ($out[0] != 0)
				renew_item_details($out[0]);
		$outfit_input = check_exist($outfit_input, $gender);
		$outfit_input = check_owned($outfit_input, $account);
		$outfit_input = fix_layering($outfit_input, $base, $id);
		return $outfit_input;
	}
	
	function wrapper($outfit, $gender, $base, $account, $id)
	{
		$outfit_input = $outfit;
		$outfit = check_exist($outfit, $gender);
		$outfit = check_owned($outfit, $account);
		$outfit = fix_layering($outfit, $base, $id);
		if (count($outfit) != count($outfit_input))
			$outfit = ensure_correctness($outfit_input, $gender, $base, $account, $id);
		unset($outfit_input);
		
		if ($outfit2 = save_order($outfit, $account, $id))
		{
			if ($id > 0)
				$save = RELATIVE . "characters/" . $account . "/avi_" . $id . ".png";
			else
				$save = RELATIVE . "characters/" . $account . "/avi_preview.png";
			draw_image($outfit, $gender, $save, $id);
			return $outfit2;
		}
		return array(array(0, $base));
	}
?>