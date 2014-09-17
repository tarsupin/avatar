<?php
	if ($treasureorigin != "avatar")
		mysql_select_db("u5s_avatar");
	$last_show = $memc->get("treasure_" . $treasureparticipant . "_show_" . $year_day . "_rePNQ");
	$last_grab = $memc->get("treasure_" . $treasureparticipant . "_grab_" . $year_day . "_rePNQ");
	
	// no item has been shown or grabbed within the set limits
	if (!$last_show && !$last_grab)
	{
		$last_show = 0;
		$last_grab = 0;
		$interval = rand(300, 600);
		$cooldown = 3600;
		$chance = 5;
		if (rand(1, 100) <= $chance)
		{
			$q_available = mysql_query("SELECT `items` FROM `treasure_search_staff` WHERE `year_day`='" . protectSQL($year_day) . "' LIMIT 1");
			if ($available = mysql_fetch_assoc($q_available))
			{
				// get gender of active avatar; if user has made no avatars, no participation
				$q_active = mysql_query("SELECT `active_avatar` FROM `account_info` WHERE `account`='" . protectSQL($treasureparticipant) . "' LIMIT 1"); 
				if ($active = mysql_fetch_assoc($q_active))
				{
					if ($active['active_avatar'] > 0)
					{
						// decides if male or female item version is displayed
						$q_gender = mysql_query("SELECT `gender` FROM `avatars` WHERE `id`='" . protectSQL($active['active_avatar']) . "' LIMIT 1");
						if ($gender = mysql_fetch_assoc($q_gender))
						{
							$items = explode(",", $available['items']);
							foreach ($items as $key => $val)
							{
								$q_canwear = mysql_query("SELECT `used_by` FROM `clothing_images` WHERE `clothingID`='" . protectSQL($val) . "' LIMIT 1");
								if ($canwear = mysql_fetch_assoc($q_canwear))
								{
									if ($canwear['used_by'] != "both" && $canwear['used_by'] != $gender['gender'])
										unset($items[$key]);
									
									$q_has = mysql_query("SELECT `timestamp` FROM `treasure_search` WHERE `account`='" . protectSQL($treasureparticipant) . "' AND `received`='" . protectSQL($val) . "' LIMIT 1");
									if ($has = mysql_fetch_assoc($q_has))
									{
										unset($items[$key]);
										if ($has['timestamp'] > $last_grab)
											$last_grab = $has['timestamp'];
									}
								}
							}
								
							if ($items != array())
							{
								// in case memcache failed
								if (time() - $last_grab > $cooldown)
								{
									// choose an item to display
									$treasurepiece = array_rand($items);
									$treasurepiece = $items[$treasurepiece];
									$q_piece = mysql_query("SELECT `clothingID`, `clothing`, `position` FROM `clothing_images` WHERE `clothingID`='" . protectSQL($treasurepiece) . "' LIMIT 1");
									if ($piece = mysql_fetch_assoc($q_piece))
									{
										$colors = array();
										if ($treasureorigin == "avatar")
											$dir = "avatars/" . $piece['position'] . "/" . $piece['clothing'];
										else
											$dir = "../../avatar/public_html/avatars/" . $piece['position'] . "/" . $piece['clothing'];
										$files = scandir($dir);
										foreach ($files as $file)
										{
											if (substr($file,-5-strlen($gender['gender'])) == "_" . $gender['gender'] . ".png")
												$colors[] = $file;
										}
										if ($colors != array())
										{
											$color = array_rand($colors);
											$color = $colors[$color];
											$left = rand(0, 95);
											$top = rand(0, 95);
											$turn = rand(0, 360);
											$check = strrev(substr(sha1($treasurepiece . "_rePNQ_" . $left . "_" . $top . "_" . $treasureparticipant . "_" . $treasureorigin), 22, 10));
											echo "<a href='http://avatar.unifaction.com/treasure_take.php?item=" . $treasurepiece . "&left=" . $left . "&top=" . $top . "&where=" . $treasureorigin . "&check=" . $check . "'><img src='http://avatar.unifaction.com/avatars/" . $piece['position'] . "/" . $piece['clothing'] . "/" . $color . "' style='z-index:1000; position:fixed; left:" . $left . "%; top:" . $top . "%; transform:rotate(" . $turn . "deg); -ms-transform: rotate(" . $turn . "deg); -webkit-transform: rotate(" . $turn . "deg);' title='You have found a treasure! Take it?'/></a>";
											
											// item has been shown, so activate interval
											$memc->set("treasure_" . $treasureparticipant . "_show_" . $year_day . "_rePNQ", time(), false, $interval);
										}
									}
									
									// save or overwrite
									if (time() - $last_grab < $cooldown)
										$memc->set("treasure_" . $treasureparticipant . "_grab_" . $year_day . "_rePNQ", $last_grab, false, $cooldown - (time() - $last_grab));
								}
							}
						}
					}
				}
			}
		}
	}
?>