<?php
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");
	
	$t = time();
	if (isset($_POST['duration']) && (!is_numeric($_POST['duration']) || $_POST['duration'] < 1 || $_POST['duration'] > 30))
		$messages[] = "<div class='message-error'>Invalid duration entered.</div>";
	elseif (isset($_POST['duration']))
	{
		$_POST['duration'] = floor($_POST['duration']);
		if (isset($_POST['sharewith']) && isset($_POST['personal']))
		{
			$result = mysql_query("SELECT `account` FROM `account_info` WHERE `account`='" . protectSQL($_POST['sharewith']) . "' LIMIT 1");
			if ($fetch_viewer = mysql_fetch_assoc($result))
				$messages[] = "<div class='message-neutral'>Give the following link to " . $fetch_viewer['account'] . ". They, and only they, will be able to use it to see a list of your equipment.<br/><a href='view_equipment.php?user=" . $fetch_account['account'] . "&viewer=" . $fetch_viewer['account'] . "&time=" . $t . "&days=" . $_POST['duration'] . "&pass=" . sha1($fetch_account['account'] . $t . $_POST['duration'] . $fetch_viewer['account'] . $siteKey) . "'>http://avatar.unifaction.com/view_equipment.php?user=" . $fetch_account['account'] . "&viewer=" . $fetch_viewer['account'] . "&time=" . $t . "&days=" . $_POST['duration'] . "&pass=" . sha1($fetch_account['account'] . $t . $_POST['duration'] . $fetch_viewer['account'] . $siteKey) . "</a></div>";
			else
				$messages[] = "<div class='message-error'>User \"" . $_POST['sharewith'] . "\" not found.</div>";
		}
		elseif (isset($_POST['everyone']))
		{
			$messages[] = "<div class='message-neutral'>Share the following link. Everyone who has it will be able to see a list of your equipment.<br/><a href='view_equipment.php?user=" . $fetch_account['account'] . "&time=" . $t . "&days=" . $_POST['duration'] . "&pass=" . sha1($fetch_account['account'] . $t . $_POST['duration'] . "equip4@ll" . $siteKey) . "'>http://avatar.unifaction.com/view_equipment.php?user=" . $fetch_account['account'] . "&time=" . $t . "&days=" . $_POST['duration'] . "&pass=" . sha1($fetch_account['account'] . $t . $_POST['duration'] . "equip4@ll" . $siteKey) . "</a></div>";
		}
	}

	$pagetitle = "Share Equipment List";
	require("incAVA/header.php");
?>
			<div class='category-container'>
				<div class='details-header'>
					Share Equipment List
				</div>
				<div class='details-body'>
					<ul>
						<li>In order to let others view your equipment list, you need to generate a link and share it with the person or people whom you want to show.</li>
						<li>The list they'll see will always be up to date, meaning it will reflect all changes you make.</li>
						<li>Any generated link will work for the set number of days, so once you share it, you can't take the permission back during that time.</li>
					</ul>
					<br/>
					<form method='post'>
						I want to allow <input type='text' name='sharewith'/> to see a list of my equipment for a duration of <input type='text' name='duration' value='30' size='2'/> (1-30) days.
						<input type='submit' name='personal' value='Generate Link'/>
					</form>
					<form method='post'>
						I want to allow everyone to see a list of my equipment for a duration of <input type='text' name='duration' value='30' size='2'/> (1-30) days.
						<input type='submit' name='everyone' value='Generate Link'/>
					</form>
				</div>
			</div>
<?php
	require("incAVA/footer.php");	
?>