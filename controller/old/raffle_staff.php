<?php
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");
	
	if ($fetch_account['clearance'] < 6)
	{
		header("Location: index.php");
		exit;
	}
	
	if (isset($_POST['savechanges']) || isset($_POST['startraffle']))
	{
		if (is_numeric($_POST['duration']) && is_numeric($_POST['winners']) && is_numeric($_POST['ticketprice']) && is_numeric($_POST['maxtotal']) && is_numeric($_POST['maxdaily']))
		{
			$_POST['duration'] = floor($_POST['duration']);
			$_POST['winners'] = floor($_POST['winners']);
			$_POST['ticketprice'] = floor($_POST['ticketprice']);
			$_POST['maxtotal'] = floor($_POST['maxtotal']);
			$_POST['maxdaily'] = floor($_POST['maxdaily']);
			if ($_POST['maxdaily'] > 0 && $_POST['duration']%24 != 0)
				$messages[] = "<div class='message-error'>You cannot have a daily max if the duration is not divisible by 24.</div>";
			else
			{
				if (isset($_POST['savechanges']) && isset($_POST['raffleid']))
				{
					if (!$time = DateTime::createFromFormat("M j, Y g:ia", $_POST['starttime']))
						$messages[] = "<div class='message-error'>Invalid start time entered.</div>";
					else
					{
						$time = date_format($time, "U");
						if (mysql_query("UPDATE `raffle_staff` SET 
							`title`='" . protectSQL($_POST['title']) . "',
							`start`=" . $time . ",
							`end`=" . ($time + ($_POST['duration']*3600)) . ", 
							`winners`=" . ($_POST['winners'] + 0) . ", 
							`ticketprice`=" . ($_POST['ticketprice'] + 0) . ", 
							`maxtotal`=" . ($_POST['maxtotal'] + 0) . ",  
							`maxdaily`=" . ($_POST['maxdaily'] + 0) . ", 
							`prize`='" . protectSQL($_POST['prize']) . "' WHERE `id`=" . ($_POST['raffleid'] + 0) . " LIMIT 1"))
							$messages[] = "<div class='message-success'>Raffle " . $_POST['raffleid'] . " has been updated.</div>";
					}
				}
				elseif (isset($_POST['startraffle']))
				{
					if (!$time = DateTime::createFromFormat("M j, Y g:ia", $_POST['starttime']))
						$messages[] = "<div class='message-error'>Invalid start time entered.</div>";
					else
					{
						$time = date_format($time, 'U');
						if (mysql_query("INSERT INTO `raffle_staff` (`title`, `start`, `end`, `winners`, `ticketprice`, `maxtotal`, `maxdaily`, `prize`, `selected`, `confirmed`) VALUES (
							'" . protectSQL($_POST['title']) . "',
							'" . $time . "', 
							'" . ($time + ($_POST['duration']*3600)) . "', 
							'" . ($_POST['winners'] + 0) . "', 
							'" . ($_POST['ticketprice'] + 0) . "', 
							'" . ($_POST['maxtotal'] + 0) . "',  
							'" . ($_POST['maxdaily'] + 0) . "', 
							'" . protectSQL($_POST['prize']) . "',
							'?',
							'no')"))
							$messages[] = "<div class='message-success'>Raffle has been initiated.</div>";
					}
				}
			}
		}
		else
			$messages[] = "<div class='message-error'>Invalid or incomplete input.</div>";
	}
	elseif (isset($_POST['drawwinners']) && isset($_POST['raffleid']))
	{
		$was_running = mysql_query("SELECT `winners` FROM `raffle_staff` WHERE `id`=" . ($_POST['raffleid'] + 0) . " LIMIT 1");
		if ($raffle = mysql_fetch_assoc($was_running))
		{
			$chances = array();
			$tickets = mysql_query("SELECT `participant` FROM `raffle_tickets` WHERE `raffle_id`=" . ($_POST['raffleid'] + 0));
			while ($ticket = mysql_fetch_assoc($tickets))
				$chances[] = $ticket['participant'];
			$raffle['winners'] = min($raffle['winners'], count(array_unique($chances)));
			do
			{
				$winners = array();
				shuffle($chances);
				if ($raffle['winners'] > 1)
				{
					$winnersi = array_rand($chances, $raffle['winners']);
					for ($i=0; $i<count($winnersi); $i++)
						$winners[] = $chances[$winnersi[$i]];
				}
				elseif ($raffle['winners'] == 1)
				{
					$r = rand(0, count($chances)-1);
					$winners[] = $chances[$r];
				}
				shuffle($winners);
			}
			while (count($winners) != count(array_unique($winners)));
			if (mysql_query("UPDATE `raffle_staff` SET `selected`='" . protectSQL(implode(",", $winners)) . "' WHERE `id`=" . ($_POST['raffleid'] + 0) . " LIMIT 1"))
				$messages[] = "<div class='message-success'>Winners have been saved.</div>";
			else
				$messages[] = "<div class='message-error'>Winners could not be saved.</div>";
		}
	}
	elseif (isset($_POST['notify']))
	{
		$was_running = mysql_query("SELECT `id`, `selected` FROM `raffle_staff` WHERE `id`=" . ($_POST['raffleid'] + 0) . " LIMIT 1");
		if ($raffle = mysql_fetch_assoc($was_running))
		{
			$winners = explode(",", $raffle['selected']);
			foreach ($winners as $winner)
				file_get_contents("http://auth.unifaction.com/API_notifyCommon.php?account=" . urlencode($winner) . 
				"&siteName=" . urlencode($siteName) . 
				"&title=" . urlencode("Raffle Win") . 
				"&message=" . urlencode("Congratulations! You have won in a <a href='http://avatar.unifaction.com/raffle.php?id=" . $raffle['id'] . "'>staff-run raffle</a>. You will be contacted about your prize.") .
				"&h=" . hash("sha256", "notify_" . $siteName . $siteKey . $winner));
			$messages[] = "<div class='message-success'>Winners have been notified.</div>";
			if (mysql_query("UPDATE `raffle_staff` SET confirmed='yes' WHERE id=" . ($_POST['raffleid'] + 0) . " LIMIT 1"))
				$messages[] = "<div class='message-success'>You have successfully confirmed the result.</div>";
		}
	}
	elseif (isset($_POST['erase']) && isset($_POST['raffleid']))
	{
		$get_staff = mysql_query("SELECT * FROM `raffle_staff` WHERE id=" . ($_POST['raffleid'] + 0) . " LIMIT 1");
		if ($gets = mysql_fetch_assoc($get_staff))
		{
			$to_put = "Title:\n" . $gets['title'] . "\n\nStart:\n" . date("M j, Y g:ia", $gets['start']) . "\n\nEnd:\n" . date("M j, Y g:ia", $gets['end']) . "\n\nWinners:\n" . $gets['winners'] . "\n\nTicket price:\n" . $gets['ticketprice'] . "\n\nMax total:\n" . $gets['maxtotal'] . "\n\nMax daily:\n" . $gets['maxdaily'] . "\n\nPrize:\n" . $gets['prize'] . "\n\nSelected:\n" . implode(", ", explode(",", $gets['selected'])) . "\n\nTickets:\n";
			file_put_contents("genFiles/raffles/" . $gets['start'] . "_" . $_POST['raffleid'] . ".txt", $to_put);
		}
		$to_put = "";
		$counter = 0;
		$get_tickets = mysql_query("SELECT * FROM `raffle_tickets` WHERE `raffle_id`=" . ($_POST['raffleid'] + 0));
		while ($gett = mysql_fetch_assoc($get_tickets))
		{
			$counter++;
			$to_put .= $counter . ".\t" . $gett['participant'] . "\n";
			if ($counter % 1000 == 0)
			{
				file_put_contents("genFiles/raffles/" . $gets['start'] . "_" . $_POST['raffleid'] . ".txt", $to_put, FILE_APPEND);
				$to_put = "";
			}
		}
		file_put_contents("genFiles/raffles/" . $gets['start'] . "_" . $_POST['raffleid'] . ".txt", $to_put, FILE_APPEND);
		if (mysql_query("DELETE FROM `raffle_staff` WHERE `id`=" . ($_POST['raffleid'] + 0) . " LIMIT 1"))
			$messages[] = "<div class='message-success'>Raffle has been removed.</div>";
		if (mysql_query("DELETE FROM `raffle_tickets` WHERE `raffle_id`=" . ($_POST['raffleid'] + 0)))
			$messages[] = "<div class='message-success'>Ticket list has been removed.</div>";
	}
	elseif (isset($_GET['disqualify']) && isset($_GET['raffleid']))
	{
		mysql_query("DELETE FROM `raffle_tickets` WHERE `raffle_id`=" . ($_GET['raffleid'] + 0) . " AND `participant`='" . protectSQL($_GET['disqualify']) . "'");
		if (mysql_affected_rows() > 0)
			$messages[] = "<div class='message-success'>" . $_GET['disqualify'] . " has been disqualified.</div>";
		else
			$messages[] = "<div class='message-error'>" . $_GET['disqualify'] . " could not be disqualified.</div>";
	}
	elseif (isset($_GET['delete']) && isset($_GET['raffleid']))
	{
		unlink("genFiles/raffles/" . $_GET['delete']);
	}
	
	$pagetitle = "[staff] Manage Raffle";
	require("incAVA/header.php");
			
	$running = mysql_query("SELECT * FROM `raffle_staff` ORDER BY `id` DESC");
	while ($raffle = mysql_fetch_assoc($running))
	{
		if ($raffle['end'] > time())
		{
			echo "
			<div class='category-container'>
				<div class='details-header'>
					<a href='raffle.php?id=" . $raffle['id'] . "'>Raffle " . $raffle['id'] . ": " . $raffle['title'] . "</a>
				</div>
				<div class='details-body'>
					<form method='post'>
						<input type='hidden' name='raffleid' value='" . $raffle['id'] . "'/>
						<input type='text' name='title' value='" . $raffle['title'] . "'/> the event's name<br/>
						<input type='text' name='starttime' value='" . date("M j, Y g:ia", $raffle['start']) . "'/> start time<br/>
						<input type='text' name='duration' value='" . (($raffle['end']-$raffle['start'])/3600) . "'/> duration (hours)<br/>
						<input type='text' name='winners' value='" . $raffle['winners'] . "'/> number of winners<br/>
						<input type='text' name='ticketprice' value='" . $raffle['ticketprice'] . "'/> ticket price (Auro)<br/>
						<input type='text' name='maxtotal' value='" . $raffle['maxtotal'] . "'/> maximum amount of tickets per participant total (set to 0 if not limited)<br/>
						<input type='text' name='maxdaily' value='" . $raffle['maxdaily'] . "'/> maximum amount of tickets per participant per day (set to 0 if not limited)<br/>
						<textarea name='prize' cols='70' rows='3'>" . $raffle['prize'] . "</textarea> prize (BB code)<br/><br/>
						<input type='submit' name='savechanges' value='Save Changes'/>
					</form>
					<div class='spoiler'><div class='spoiler_header' onclick='$(this).next().slideToggle(\"slow\")'>Ticket List</div><div class='spoiler_content'>";
						$tickets = mysql_query("SELECT `participant`, COUNT(`participant`) AS `count` FROM `raffle_tickets` WHERE `raffle_id`=" . ($raffle['id'] + 0) . " GROUP BY `participant`");
						while ($ticket = mysql_fetch_assoc($tickets))
							echo $ticket['participant'] . " (" . $ticket['count'] . ")<br/>";
			echo "
					</div></div>
				</div>
			</div>";
		}
		else
		{
			echo "
			<div class='category-container'>
				<div class='details-header'>
					<a href='raffle.php?id=" . $raffle['id'] . "'>Raffle " . $raffle['id'] . ": " . $raffle['title'] . "</a>
				</div>
				<div class='details-body'>
					Winners: " . ($raffle['selected'] != "?" ? str_replace(",", ", ", $raffle['selected']) : "?") . "<br/>
					<div class='spoiler'><div class='spoiler_header' onclick='$(this).next().slideToggle(\"slow\")'>Ticket List</div><div class='spoiler_content'>";
					
					$hasparticipants = false;
					$tickets = mysql_query("SELECT `participant`, COUNT(`participant`) AS `count` FROM `raffle_tickets` WHERE `raffle_id`=" . ($raffle['id'] + 0) . " GROUP BY `participant`");
					while ($ticket = mysql_fetch_assoc($tickets))
					{
						$hasparticipants = true;
						if ($raffle['confirmed'] == 'yes')
							echo $ticket['participant'] . " (" . $ticket['count'] . ")<br/>";
						else
							echo "<a onclick='return confirm(\"Are you sure you want to disqualify " . $ticket['participant']. "?\");' href='raffle_staff.php?raffleid=" . $raffle['id'] . "&disqualify=" . $ticket['participant'] . "'><img src='css/icons/cross.png'/></a> " . $ticket['participant'] . " (" . $ticket['count'] . ")<br/>";
					}
							
			echo"
					</div></div>
					<form method='post'>
						<input type='hidden' name='raffleid' value='" . $raffle['id'] . "'/>";
			if ($raffle['confirmed'] != 'yes')
			{
				echo "
						<input type='submit' name='drawwinners' value='Draw Winners'/>";
				if ($raffle['selected'] != "?" || !$hasparticipants)
					echo "
						<input type='submit' name='notify' value='Confirm Choice & Notify Winners'/>";
			}
			else
				echo "
						<input onclick='return confirm(\"Are you sure you want to remove all data about this raffle?\");' type='submit' name='erase' value='Remove Records'/>";
			echo "
					</form>
				</div>
			</div>";
		}
	}
?>

			<div class='category-container'>
				<div class='details-header'>
					Start Raffle
				</div>
				<div class='details-body'>
					<form method='post'>
						<input type='text' name='title' value='<?php echo $raffle['title']; ?>'/> the event's name<br/>
						<input type='text' name='starttime' value='<?php echo date("M j, Y g:ia", time()); ?>'/> start time<br/>
						<input type='text' name='duration'/> duration (hours)<br/>
						<input type='text' name='winners'/> number of winners<br/>
						<input type='text' name='ticketprice'/> ticket price (Auro)<br/>
						<input type='text' name='maxtotal'/> maximum amount of tickets per participant total (set to 0 if not limited)<br/>
						<input type='text' name='maxdaily'/> maximum amount of tickets per participant per day (set to 0 if not limited)<br/>
						<textarea name='prize' cols='70' rows='3'></textarea> prize (BB code)<br/><br/>
						<input type='submit' name='startraffle' value='Start Raffle'/>
					</form>
				</div>
			</div>

			<div class='category-container'>
				<div class='details-header'>
					Previous Raffles
				</div>
				<div class='details-body'>
<?php
	$files = scandir("genFiles/raffles");
	foreach ($files as $file)
	{
		if ($file != "." && $file != "..")
		{
			$file2 = explode("_", substr($file, 0, -4));
			echo "
				<a onclick='return confirm(\"Are you sure you want to delete this file?\");' href='raffle_staff.php?raffleid=" . $raffle['id'] . "&delete=" . $file . "'><img src='css/icons/cross.png'/></a> <a href='genFiles/raffles/" . $file . "'>Raffle " . $file2[1] . ": " . date("M j, Y g:ia", intval($file2[0])) . "</a><br/>";
		}
	}
?>

				</div>
			</div>
<?php
	require("incAVA/footer.php");	
?>