<?php
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");

	$err = array();

	if (!isset($_GET['id']) || !is_numeric($_GET['id']))
	{
		header("Location: index.php");
		exit;
	}
	
	if ($fetch_account['active_avatar'] == 0)
	{
		header("Location: index.php");
		exit;
	}
	
	$result = mysql_query("SELECT * FROM `advent_calendar_staff` WHERE `id`=" . ($_GET['id'] + 0) . " LIMIT 1");
	if (!$calendar = mysql_fetch_assoc($result))
		$err[] = "This calendar is not active.";
	else
	{
		$items = explode("|", $calendar['items']);
		for ($i=0; $i<count($items); $i++)
		{
			$items[$i] = explode(",", $items[$i]);
			array_walk($items[$i], "intval");
			$items[$i] = array_filter($items[$i], function($t) {return $t>0;});
		}

		$starttime =  mktime(0, 0, 0, $calendar['startmonth'], $calendar['startday'], $calendar['startyear']);
		$endtime = $starttime + $calendar['duration']*86400;
		$index = (mktime(0, 0, 0) - $starttime) / 86400;

		if (time() < $starttime || time() > $endtime)
			$err[] = "This calendar is not active.";
	}

	if (isset($_GET['receive']) && $err == array())
	{
		if ($calendar['id'] == 1)
			$banned = array("Birthday_Bash", "SpecialDelivery");
		elseif ($calendar['id'] == 2)
			$banned = array("Awtuu", "Kakashi", "Shenosa");
		elseif ($calendar['id'] == 3)
			$banned = array("Mister.Bojangles", "TheJoker", "SorrowNight");
		else
			$banned = array();
		if (!in_array($fetch_account['account'], $banned))
		{
			$success = false;
			
			foreach ($items[$index] as $item)
			{
				if ($item == $_GET['receive'])
				{
					$result = mysql_query("SELECT `id` FROM `advent_calendar` WHERE `account`='" . protectSQL($fetch_account['account']) . "' AND `received`=" . ($item + 0) . " LIMIT 1");
					if ($fetch_received = mysql_fetch_assoc($result))
						$messages[] = "<div class='message-error'>You have already received this item.</div>";
					else
					{
						$result = mysql_query("SELECT `id` FROM `advent_calendar` WHERE `account`='" . protectSQL($fetch_account['account']) . "' AND `cal_id`=" . ($calendar['id'] + 0) . " AND `year_day`='" . date("Y") . "_" . date("z") . "' LIMIT " . ($calendar['maxchoice'] + 0));
						if (mysql_num_rows($result) >= $calendar['maxchoice'])
							$messages[] = "<div class='message-error'>You have already received the maximum number of items for today.</div>";
					}
					
					if ($messages == array())
					{
						$result = mysql_query("SELECT `clothingID`, `clothing`, `position` FROM `clothing_images` WHERE `clothingID`=" . ($item + 0) . " LIMIT 1");
						if ($fetch_gift = mysql_fetch_assoc($result))
						{
							$result = mysql_query("SELECT `post_count`, `joinDate` FROM  `u5s_forum`.`s4u_user_list` WHERE `account`='" . protectSQL($fetch_account['account']) . "' LIMIT 1");
							if ($fetch_user = mysql_fetch_assoc($result))
							{
								if ($calendar['minposts'] > $fetch_user['post_count'])
									$messages[] = "<div class='message-error'>You do not have enough posts.</div>";
								elseif ($calendar['joinbefore'] <= $fetch_user['joinDate'])
									$messages[] = "<div class='message-error'>Not enough time has passed since you joined UniFaction.</div>";
								else
								{
									if (mysql_query("INSERT INTO `advent_calendar` (`account`, `ip`, `cal_id`, `year_day`, `received`) VALUES ('" . protectSQL($fetch_account['account']) . "', '" . protectSQL($_SERVER['REMOTE_ADDR']) . "', '" . ($calendar['id'] + 0) . "', '" . date("Y") . "_" . date("z") . "', '" . ($item + 0) . "')"))
									{
										if (mysql_query("INSERT INTO `avatar_clothing` (`account`, `clothingID`, `position`) VALUES ('" . protectSQL($fetch_account['account']) . "', '" . ($fetch_gift['clothingID'] + 0) . "', '" . protectSQL($fetch_gift['position']) . "')"))
											$messages[] = "<div class='message-success'>You have received " . $fetch_gift['clothing'] . ".</div>";
										else
											$messages[] = "<div class='message-error'>An error occurred while adding " . $fetch_gift['clothing'] . " to your inventory.</div>";
									}
									else
										$messages[] = "<div class='message-error'>An error occurred while adding " . $fetch_gift['clothing'] . " to your inventory.</div>";
								}
							}
							else
								$messages[] = "<div class='message-error'>An error occurred while checking your post count and join date.</div>";
						}
						else
							$messages[] = "<div class='message-error'>This item is not available.</div>";
					}
					$success = true;
					break;
				}
			}
			
			if (!$success)
				$messages[] = "<div class='message-error'>This item is not available.</div>";
		}
		else
			$messages[] = "<div class='message-error'>You have been banned from further participation in this event.</div>";
	}

	$pagetitle = "Event Calendar" . ($err == array() ? ": " . $calendar['title'] : "");
	require("incAVA/header.php");

	echo "
			<div class='category-container'>
				<div class='details-header'>
					Event Calendar" . ($err == array() ? ": " . $calendar['title'] : "") . ($fetch_account['clearance'] < 6 ? "" : " (<a href='surprise_staff.php'>Manage</a>)") . "
				</div>
				<div class='details-body'>";
				
	if ($err == array() && $calendar['id'] == 1)
	{
		if (!isset($_GET['day']) || $_GET['day'] != date("j"))
			$err[] = "
					<div style='text-align: center;'>
						<img usemap='#map' src='http://avatar.unifaction.com/images/events/October.png'/>
						<map name='map'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=16' coords='189,258,285,321' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=17' coords='286,258,378,321' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=18' coords='379,258,472,321' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=19' coords='473,258,569,321' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=20' coords='570,258,650,321' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=21' coords='0,322,95,387' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=22' coords='96,322,188,387' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=23' coords='189,322,285,387' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=24' coords='286,322,378,387' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=25' coords='379,322,472,387' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=26' coords='473,322,569,387' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=27' coords='570,322,650,387' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=28' coords='0,388,95,451' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=29' coords='96,388,188,451' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=30' coords='189,388,285,451' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=31' coords='286,388,378,451' shape='rect'>
						</map>
						<br/>
						If the image doesn't load or can't be clicked, use <a href='surprise.php?id=" . $calendar['id'] . "&day=" . date("j") . "'>this link</a> instead.
					</div>";
	}
	elseif ($err == array() && in_array($calendar['id'], array(2, 3)))
	{
		if (!isset($_GET['day']) || $_GET['day'] != date("j"))
			$err[] = "
					<div style='text-align: center;'>
						<img usemap='#map' src='http://avatar.unifaction.com/images/events/December.png'/>
						<map name='map'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=1' coords='539,123,622,196' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=2' coords='0,197,89,266' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=3' coords='90,197,180,266' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=4' coords='181,197,269,266' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=5' coords='270,197,360,266' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=6' coords='361,197,447,266' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=7' coords='448,197,538,266' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=8' coords='539,197,622,266' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=9' coords='0,267,89,339' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=10' coords='90,267,180,339' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=11' coords='181,267,269,339' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=12' coords='270,267,360,339' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=13' coords='361,267,447,339' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=14' coords='448,267,538,339' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=15' coords='539,267,622,339' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=16' coords='0,340,89,410' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=17' coords='90,340,180,410' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=18' coords='181,340,269,410' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=19' coords='270,340,360,410' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=20' coords='361,340,447,410' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=21' coords='448,340,538,410' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=22' coords='539,340,622,410' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=23' coords='0,411,89,480' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=24' coords='90,411,180,480' shape='rect'>
							<area href='surprise.php?id=" . $calendar['id'] . "&day=25' coords='181,411,269,480' shape='rect'>
						</map>
						<br/>
						If the image doesn't load or can't be clicked, use <a href='surprise.php?id=" . $calendar['id'] . "&day=" . date("j") . "'>this link</a> instead.
					</div>";
	}

	if ($err == array())
	{
		if (!isset($items[$index]))
			echo "
					There are no items available. They may be added later.";
		else
		{
			$max = min($calendar['maxchoice'], count($items[$index]));
		
			echo "
					<ul>
						<li>You may take " . $max . ($max > 1 ? " different items" : " item") . " from today's assortment, provided you have at least " . $calendar['minposts'] . " posts and have joined UniFaction before " . date("M j, Y g:ia", $calendar['joinbefore']) . ".</li>
						<li>You cannot receive the same item twice, but taking items on one day does not affect your ability to take items on another day.</li>";
					if ($calendar['id'] == 1)
						echo "
						<li><span style='color: red; font-weight: bold;'>Make sure to read <a href='http://forum.unifaction.com/thread.php?f=1&id=569&page=1#p1267'>THIS POST</a> before claiming any gifts! The rules are there for a reason. Breaking them will cause you to lose all event items and get you banned from further participation in the event.</span></li>";
					elseif ($calendar['id'] == 2)
						echo "
						<li><span style='color: red; font-weight: bold;'>Make sure to read <a href='http://forum.unifaction.com/thread.php?f=1&id=604&page=1#p260'>THIS POST</a> before claiming any gifts! The rules are there for a reason. Breaking them will cause you to lose all event items and get you banned from further participation in the event.</span></li>";
					elseif ($calendar['id'] == 3)
						echo "
						<li><span style='color: red; font-weight: bold;'>Make sure to read <a href='http://forum.unifaction.com/thread.php?f=1&id=765&page=1#p2'>THIS POST</a> before claiming any gifts!</span></li>";
					echo "
					</ul>
					<br/>Items for today, " . date("M jS") . ":
					<table class='items'>";
					
			if ($fetch_avatar['gender'] == "female")
				$opGender = "male";
			else
				$opGender = "female";
			
			$slotX = 0;			
			foreach ($items[$index] as $item)
			{
				$q = mysql_query("SELECT `clothingID`, `clothing`, `position`, `used_by` FROM `clothing_images` WHERE `clothingID`='" . ($item + 0) . "' LIMIT 1");
				if ($fetch_gift = mysql_fetch_assoc($q))
				{
					$list_items = array();
					$list_items2 = array();
					$files = scandir("avatars/" . $fetch_gift['position'] . "/" . $fetch_gift['clothing']);
					foreach ($files as $file)
					{
						if (strpos($file, "_" . $fetch_avatar['gender'] . ".png") > -1)
							$list_items[] = str_replace("_" . $fetch_avatar['gender'] . ".png", "", $file);
						elseif (strpos($file, "_" . $opGender . ".png") > -1)
							$list_items2[] = str_replace("_" . $opGender . ".png", "", $file);
					}
					if ($slotX % 5 == 0)
						echo "
						<tr>";
					echo "
							<td" . ($fetch_gift['used_by'] == $opGender ? " style='opacity:0.5; filter:alpha(opacity=50);'" : "") . ">";
					if ($fetch_gift['used_by'] != $opGender)
					{
						echo "
								<a href=\"javascript: review_item('" . $fetch_gift['clothingID'] . "');\"><img id='img" . $fetch_gift['clothingID'] . "' src='avatars/" . $fetch_gift['position'] . "/" . $fetch_gift['clothing'] . "/" . $list_items[0] . "_" . $fetch_avatar['gender'] . ".png' alt='" . $fetch_gift['clothing'] . "'/></a>";
					}
					else
					{
						echo "
								<img id='img" . $fetch_gift['clothingID'] . "' src='avatars/" . $fetch_gift['position'] . "/" . $fetch_gift['clothing'] . "/" . $list_items2[0] . "_" . $opGender . ".png' alt='" . $fetch_gift['clothing'] . "'/>";
					}						
					echo "
							
								
								<br/>" . $fetch_gift['clothing'];
					$q = mysql_query("SELECT `id` from `avatar_clothing` WHERE `account`='" . protectSQL($fetch_account['account']) . "' AND `clothingID`=" . ($fetch_gift['clothingID'] + 0) . " LIMIT 1");
						if ($row = mysql_fetch_assoc($q))
							echo " &bull;";
					echo "
								<br/>
								<span><a href='shop_search.php?used_by=" . ($opGender == "male" ? "fab" : "mab") . "&layer_" . $fetch_gift['position'] . "=on&submit=Search'>" . $fetch_gift['position'] . "</a>, " . $fetch_gift['used_by'] . "</span><br/>
								<select id='item" . $fetch_gift['clothingID'] . "' onchange='switch_item(\"" . $fetch_gift['clothingID'] . "\", \"" . $fetch_gift['position'] . "\", \"" . $fetch_gift['clothing'] . "\", \"" . ($fetch_gift['used_by'] != $opGender ? $fetch_avatar['gender'] : $opGender) . "\");'>";
					if ($fetch_gift['used_by'] != $opGender)
					{
						foreach ($list_items as $color)
							echo "<option name='" . $color . "'>" . $color . "</option>";
					}
					else
					{
						foreach ($list_items2 as $color)
							echo "<option name='" . $color . "'>" . $color . "</option>";
					}
					echo "</select>
								<div><a onclick='return confirm(\"Are you sure you want to receive " . $fetch_gift['clothing'] . " as a gift?\");' href='surprise.php?id=" . $calendar['id'] . (isset($_GET['day']) ? "&day=" . $_GET['day'] : "") . "&receive=" . ($fetch_gift['clothingID'] + 0) . "'>Receive this Gift</a></div>
							</td>";
					if ($slotX % 5 == 4)
						echo "
						</tr>";
					$slotX++;
				}
			}
			if ($slotX % 5 > 0)
				echo "
						</tr>";
			echo "
					</table>";
		}

		// name tomorrow's items for staff to see
		if ($fetch_account['clearance'] >= 6)
		{
			echo "<hr/>[Preview for Staff] Items for tomorrow, " . date("M jS", time()+86400) . ":<br/>";
			if (time()+86400 < $starttime || time()+86400 > $endtime)
				echo "This calendar is not active.";
			elseif (!isset($items[$index+1]))
				echo "There are no items available. They may be added later.";
			else
			{
				$list = array();
				foreach ($items[$index+1] as $item)
				{
					$q = mysql_query("SELECT `clothing` FROM `clothing_images` WHERE `clothingID`='" . ($item + 0) . "' LIMIT 1");
					if ($fetch_gift = mysql_fetch_assoc($q))
						$list[] = $fetch_gift['clothing'];
				}
				echo implode(", ", $list);
			}
		}
	}

	foreach($err as $disp)
		echo $disp;
				
	echo "
				</div>
			</div>";
?>

			<script type='text/javascript'>
				function switch_item(num, position, name, gender)
				{
					$("#img" + num).attr("src", "avatars/" + position + "/" + name + "/" + $("#item" + num).val() + "_" + gender + ".png");
				}
					
				function review_item(id)
				{
					window.open("preview_avi.php?clothingID=" + id + "&recolor=" + $("#item" + id).val(), "PreviewAvatar", "width=622,height=500,toolbar=no,location=no,directories=no,status=no,menubar=no,copyhistory=no,scrollbars=yes");
				}
			</script>
<?php
	require("incAVA/footer.php");
?>