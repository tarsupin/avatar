<?php
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");
	
	if (!isset($_GET['id']))
	{
		header("Location: index.php");
		exit;
	}
	
	$running = mysql_query("SELECT * FROM `raffle_staff` WHERE id=" . ($_GET['id'] + 0) . " LIMIT 1");
	if (!$raffle = mysql_fetch_assoc($running))
	{
		header("Location: index.php");
		exit;
	}
	
	if (isset($_POST['amountpurchased']))
	{
		if (is_numeric($_POST['amountpurchased']) && $_POST['amountpurchased'] > 0)
		{
			$_POST['amountpurchased'] = floor($_POST['amountpurchased']);
			if ($raffle['start'] <= time() && $raffle['end'] > time())
			{
				$tickets = mysql_query("SELECT * FROM `raffle_tickets` WHERE `raffle_id`=" . ($raffle['id'] + 0) . " AND `participant`='" . protectSQL($fetch_account['account']) . "'");
				$numberparticipant =  mysql_num_rows($tickets);
				if ($raffle['maxtotal'] > 0 && $numberparticipant + $_POST['amountpurchased'] > $raffle['maxtotal'])
				{
					$messages[] = "<div class='message-error'>You may only have up to " . $raffle['maxtotal'] . " tickets total.</div>";
				}
				else
				{
					$ticketsrecent = mysql_query("SELECT * FROM `raffle_tickets` WHERE `raffle_id`=" . ($raffle['id'] + 0) . " AND `participant`='" . protectSQL($fetch_account['account']) . "' AND `timestamp`>=" . (time()-((time()-$raffle['start']) % 86400)));
					$ticketstoday = mysql_num_rows($ticketsrecent);
					if ($raffle['maxdaily'] > 0 && $_POST['amountpurchased']+$ticketstoday > $raffle['maxdaily'])
					{
						$messages[] = "<div class='message-error'>You may only purchase up to " . $raffle['maxdaily'] . " tickets per day. You have already purchased " . $ticketstoday . ".</div>";
					}
					else
					{
						$value = file_get_contents(UNIFACTION . "API_autoSpendAuro.php?account=" . $fetch_account['account'] . "&amount=" . ($_POST['amountpurchased']*$raffle['ticketprice']) . "&site=" . $siteName . "&hash=" . hash("sha256", "@uto$37@uro" . $siteName . $siteKey . $fetch_account['account']));
						if ($value == "SUCCESS")
						{
							for ($i=0; $i<$_POST['amountpurchased']; $i++)
								mysql_query("INSERT INTO `raffle_tickets` VALUES ('" . ($raffle['id'] + 0) . "', '" . protectSQL($fetch_account['account']) . "', '" . time() . "')");
							$messages[] = "<div class='message-success'>Your purchase has been successful.</div>";
						}
						else
							$messages[] = "<div class='message-error'>You do not have enough Auro.</div>";
					}
				}
			}
			else
				$messages[] = "<div class='message-error'>This raffle is not active.</div>";
		}
		else
			"<div class='message-error'>You need to enter a valid number of tickets.</div>";
	}
	
	require("fanctions/func_bbcode.php");
	
	$pagetitle = "Raffle " . $raffle['id'] . ": " . $raffle['title'];
	require("incAVA/header.php");
	
	if ($raffle['start'] <= time() && $raffle['end'] > time())
	{
		echo "
			<div class='category-container'>
				<div class='details-header'>
					Raffle " . $raffle['id'] . ": " . $raffle['title'] . ($fetch_account['clearance'] < 6 ? "" : " (<a href='raffle_staff.php'>Manage</a>)") . "
				</div>
				<div class='details-body'>
					<ul>
						<li>This raffle will end at " . date("M j, Y g:ia", $raffle['end']) . " UniTime.</li>
						<li>There will be " . $raffle['winners'] . " winner" . ($raffle['winners'] != 1 ? "s" : "") . ".</li>
						<li>Each ticket costs " . $raffle['ticketprice'] . " Auro.</li>";
		if ($raffle['maxtotal'] > 0)
			echo "
						<li>You may purchase up to " . $raffle['maxtotal'] . " tickets total.</li>";
		if ($raffle['maxdaily'] > 0)
			echo "
						<li>You may purchase up to " . $raffle['maxdaily'] . " tickets per day. For this raffle the day changes at " . date("g:ia", $raffle['start']) . " UniTime.</li>";
		echo "
						<li>Prize:<br/>" . nl2br(BBParser::bb2html(htmlspecialchars($raffle['prize']), false)) . "</li>";
		echo "
					</ul>
				</div>
			</div>";
		$tickets = mysql_query("SELECT `participant` FROM `raffle_tickets` WHERE `raffle_id`=" . ($raffle['id'] + 0));
		$numbertotal = mysql_num_rows($tickets);
		$tickets = mysql_query("SELECT * FROM `raffle_tickets` WHERE `raffle_id`=" . ($raffle['id'] + 0) . " AND `participant`='" . protectSQL($fetch_account['account']) . "'");
		$numberparticipant =  mysql_num_rows($tickets);
		echo "
			<div class='category-container'>
				<div class='details-header'>
					Your Tickets
				</div>
				<div class='details-body'>
					You currently have " . $numberparticipant . " tickets (of " . $numbertotal . " tickets total).
					<form method='post'>
						Purchase <input type='text' name='amountpurchased'/> Tickets <input type='submit' value='Submit'/>
					</form>
				</div>
			</div>";
	}
	elseif ($raffle['end'] <= time())
	{
		echo "
			<div class='category-container'>
				<div class='details-header'>
					Raffle " . $raffle['id'] . ": " . $raffle['title'] . ($fetch_account['clearance'] < 6 ? "" : " (<a href='raffle_staff.php'>Manage</a>)") . "
				</div>
				<div class='details-body'>
					<ul>
						<li>This raffle has ended.</li>
						<li>Prize:<br/>" . nl2br(BBParser::bb2html(htmlspecialchars($raffle['prize']), false)) . "</li>";
		echo "
					</ul>
				</div>
			</div>";
		if ($raffle['confirmed'] == "yes")
		{
			echo "
			<div class='category-container'>
				<div class='details-header'>
					Winners
				</div>
				<div class='details-body'>";
					$winners = explode(",", $raffle['selected']);
					foreach ($winners as $winner)
						echo $winner . "<br/>";
			echo "
				</div>
			</div>";
		}
		else
		{
			echo "
			<div class='category-container'>
				<div class='details-header'>
					Winners
				</div>
				<div class='details-body'>
					Winners will be announced soon.
				</div>
			</div>";
		}
	}
	elseif ($raffle['start'] > time())
	{
		echo "
			<div class='category-container'>
				<div class='details-header'>
					Raffle " . $raffle['id'] . ": " . $raffle['title'] . ($fetch_account['clearance'] < 6 ? "" : " (<a href='raffle_staff.php'>Manage</a>)") . "
				</div>
				<div class='details-body'>
					This raffle will start at " . date("M j, Y g:ia", $raffle['start']) . ".
				</div>
			</div>";
	}
	
	require("incAVA/footer.php");		
?>