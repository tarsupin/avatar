<?php
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");

	if ($fetch_account['clearance'] < 6)
	{
		header("Location: index.php");
		exit;
	}
	
	function morethanone($el)
	{
		if (isset($el[1]))
			return 1;
		return 0;
	}

	function unique($arr)
	{
		$ret = array();
		foreach($arr as $sub)
		{
			sort($sub);
			if (!in_array($sub, $ret))
				$ret[] = $sub;
		}
		sort($ret);
		return $ret;
	}

	if (isset($_POST['submit']))
	{
		$_POST['items'] = explode("|", $_POST['items']);
		for ($i=0; $i<count($_POST['items']); $i++)
		{
			$_POST['items'][$i] = explode(",", $_POST['items'][$i]);
			array_walk($_POST['items'][$i], "intval");
			$_POST['items'][$i] = array_filter($_POST['items'][$i], function($t) {return $t>0;});
			$_POST['items'][$i] = implode(",", $_POST['items'][$i]);
		}
		$_POST['items'] = implode("|", $_POST['items']);
		
		if (mysql_query("UPDATE `advent_calendar_staff` SET `title`='" . protectSQL($_POST['title']) . "', `maxchoice`=" . ($_POST['maxchoice'] + 0) . ", `startyear`=" . ($_POST['startyear'] + 0) . ", `startmonth`=" . ($_POST['startmonth'] + 0) . ", `startday`=" . ($_POST['startday'] + 0) . ", `duration`=" . ($_POST['duration'] + 0) . ", `minposts`=" . ($_POST['minposts'] + 0) . ", `joinbefore`=" . ($_POST['joinbefore'] + 0) . ", `items`='" . protectSQL($_POST['items']) . "' WHERE `id`=" . ($_POST['id'] + 0) . " LIMIT 1"))
			$messages[] = "<div class='message-success'>You have successfully updated Event " . $_POST['id'] . ".</div>";
		else
			$messages[] = "<div class='message-error'>An error occurred while updating Event " . $_POST['id'] . ".</div>";
	}
	elseif (isset($_POST['create']))
	{
		$_POST['items'] = explode("|", $_POST['items']);
		for ($i=0; $i<count($_POST['items']); $i++)
		{
			$_POST['items'][$i] = explode(",", $_POST['items'][$i]);
			array_walk($_POST['items'][$i], "intval");
			$_POST['items'][$i] = array_filter($_POST['items'][$i], function($t) {return $t>0;});
			$_POST['items'][$i] = implode(",", $_POST['items'][$i]);
		}
		$_POST['items'] = implode("|", $_POST['items']);
		
		
		if (mysql_query("INSERT INTO `advent_calendar_staff` (`title`, `maxchoice`, `startyear`, `startmonth`, `startday`, `duration`, `minposts`, `joinbefore`, `items`) VALUES ('" . protectSQL($_POST['title']) . "', '" . ($_POST['maxchoice'] + 0) . "', '" . ($_POST['startyear'] + 0) . "', '" . ($_POST['startmonth'] + 0) . "', '" . ($_POST['startday'] + 0) . "', '" . ($_POST['duration'] + 0) . "', '" . ($_POST['minposts'] + 0) . "', '" . ($_POST['joinbefore'] + 0) . "', '" . protectSQL($_POST['items']) . "')"))
			$messages[] = "<div class='message-success'>You have successfully added the event.</div>";
		else
			$messages[] = "<div class='message-error'>An error occurred while adding the event.</div>";
	}

	$pagetitle = "[staff] Manage Event Calendar";
	require("incAVA/header.php");

	$result = mysql_query("SELECT * FROM `advent_calendar_staff` ORDER BY ID DESC");
	while ($calendar = mysql_fetch_assoc($result))
	{
		echo "
			<div class='category-container'>
				<div class='details-header'>
					<a href='surprise.php?id=" . $calendar['id'] . "'>Event " . $calendar['id'] . ": " . $calendar['title'] . "</a>
				</div>
				<div class='details-body'>
					<form method='post'>
						<input name='id' type='hidden' value='" . $calendar['id'] . "'/>
						<input name='title' type='text' value='" . $calendar['title'] . "'/> the event's name<br/>
						<input name='maxchoice' type='text' value='" . $calendar['maxchoice'] . "'/> maximum number of items a user may take per day<br/>
						<input name='startyear' type='text' value='" . $calendar['startyear'] . "'/> year the calendar starts (4 digits)<br/>
						<input name='startmonth' type='text' value='" . $calendar['startmonth'] . "'/> month the calendar starts (1-12)<br/>
						<input name='startday' type='text' value='" . $calendar['startday'] . "'/> day the calendar starts (1-31)<br/>
						<input name='duration' type='text' value='" . $calendar['duration'] . "'/> number of days the event runs<br/>
						<input name='minposts' type='text' value='" . $calendar['minposts'] . "'/> user must have at least this many posts<br/>
						<input name='joinbefore' type='text' value='" . $calendar['joinbefore'] . "'/> user must have joined before this (unixtime; " . $calendar['joinbefore'] . " = " . date("M j, Y g:i:sa", $calendar['joinbefore']) . ")<br/>
						<textarea name='items'>" . $calendar['items'] . "</textarea> item IDs in order of appearance in the format day1item1,day1item2|day2item1<br/><br/>
						<input name='submit' type='submit' value='Save'/>
					</form>";
			
		$starttime =  mktime(0, 0, 0, $calendar['startmonth'], $calendar['startday'], $calendar['startyear']);
		$endtime = $starttime + $calendar['duration']*86400;
			
		if (time() <= $endtime + 604800)
		{
			$ips = array();
			$participants = array();
			$res = mysql_query("SELECT account, ip, received FROM advent_calendar WHERE cal_id=" . ($calendar['id'] + 0));
			while ($list = mysql_fetch_assoc($res))
			{
				if (!isset($ips[$list['ip']]))
					$ips[$list['ip']] = array($list['account']);
				elseif (!in_array($list['account'], $ips[$list['ip']]))
					$ips[$list['ip']][] = $list['account'];
					
				if (!isset($participants[$list['account']]))
					$participants[$list['account']] = array(strval($list['received']));
				else
					$participants[$list['account']][] = $list['received'];
			}
	
			$ips = array_filter($ips, "morethanone");
			$ips = unique($ips);
			
			echo "
					<br/>
					<div class='spoiler pad'><div class='spoiler_header' onclick='$(this).next().slideToggle(\"slow\")'>Users with identical IPs (" . count($ips) . ")</div><div class='spoiler_content' style='display:none'>";
	
			foreach ($ips as $ip)
			{
				foreach ($ip as $mult)
				{
					sort($participants[$mult]);
					echo $mult . " (" . implode(", ", $participants[$mult]) . ")<br/>";
				}
				echo "<br/>";
			}
	
			echo "
					</div></div>
					<div class='spoiler pad'><div class='spoiler_header' onclick='$(this).next().slideToggle(\"slow\")'>Participants (" . count($participants) . ")</div><div class='spoiler_content' style='display:none'>";
	
			$participants = array_keys($participants);
			natcasesort($participants);
			echo implode(", ", $participants);
			
			echo "
					</div></div>";
		}
		
		echo "
				</div>
			</div>";
	}
?>
			<div class='category-container'>
				<div class='details-header'>
					New Event Calendar
				</div>
				<div class='details-body'>
					<form method='post'>
						<input name='title' type='text' value=''/> the event's name<br/>
						<input name='maxchoice' type='text' value=''/> maximum number of items a user may take per day<br/>
						<input name='startyear' type='text' value=''/> year the calendar starts (4 digits)<br/>
						<input name='startmonth' type='text' value=''/> month the calendar starts (1-12)<br/>
						<input name='startday' type='text' value=''/> day the calendar starts (1-31)<br/>
						<input name='duration' type='text' value=''/> number of days the event runs<br/>
						<input name='minposts' type='text' value=''/> user must have at least this many posts<br/>
						<input name='joinbefore' type='text' value=''/> user must have joined before this (unixtime; <?php echo time() . " = " . date("M j, Y g:i:sa"); ?>)<br/>
						<textarea name='items'></textarea> item IDs in order of appearance in the format day1item1,day1item2|day2item1<br/><br/>
						<input name='create' type='submit' value='Start Event Calendar'/>
					</form>
				</div>
			</div>
<?php
	require("incAVA/footer.php");
?>