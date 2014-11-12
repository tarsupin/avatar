<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/create-avatar");
}

// Set page title
$config['pageTitle'] = "Create Avatar";

// Prepare Values
$races = array("white", "tan", "pacific", "dark", "light", "gray");

$number = Database::selectOne("SELECT MAX(avatar_id) AS max FROM avatars WHERE uni_id=?", array(Me::$id));
if($number['max'])
{
	Alert::info("Has Avatars", 'You currently have ' . $number['max'] . ' avatars which you can view or activate on <a href="/switch-avatar">this page</a>.<br/>Are you sure you want to create another?');
}

// check max number
if(!$max = Database::selectOne("SELECT max FROM user_max_avatars WHERE uni_id=? LIMIT 1", array(Me::$id)))
{
	$max['max'] = 3;
}

if(Form::submitted("extra-slot"))
{
	if($max['max'] < 9)
	{
		Database::startTransaction();
		$cost = 15.00;
		if($max['max'] == 3)	{ $cost = 10.00; }
		if($response = Credits::chargeInstant(Me::$id, $cost, "Avatar Slot #" . ($max['max'] + 1)))
		{
			if($max['max'] == 3)
			{
				if(Database::query("INSERT INTO user_max_avatars VALUES (?, ?)", array(Me::$id, 4)))
				{
					Database::endTransaction();
					Alert::success("Slot Purchased", "You have unlocked an additional avatar slot!");
					$max['max']++;
				}
				else
				{
					Database::endTransaction(false);
					Alert::error("Slot Not Purchased", "Something went wrong! The slot could not be purchased.");
				}
			}
			else
			{
				if(Database::query("UPDATE user_max_avatars SET", array(Me::$id, $max['max'] + 1)))
				{
					Database::endTransaction();
					Alert::success("Slot Purchased", "You have unlocked an additional avatar slot!");
					$max['max']++;
				}
				else
				{
					Database::endTransaction(false);
					Alert::error("Slot Not Purchased", "Something went wrong! The slot could not be purchased.");
				}
			}
		}
		else
		{
			Alert::error("Too Expensive", "You do not have enough credits for this purchase!");
		}
	}
	else
	{
		Alert::error("Enough Avatars", "You have reached the maximum number of avatar slots. You cannot purchase more.");
	}
}

// Check if a base was chosen
if(isset($url[1]) && isset($url[2]))
{
	// Check if the values are legitimate
	if(in_array($url[1], array("male", "female")) && in_array($url[2], $races))
	{		
		if($number['max'] >= $max)
		{
			Alert::error("Enough Avatars", "You cannot create more than " . $max['max'] . " avatars.");
		}
		else
		{
			// Create Your Avatar
			if(AppAvatar::createAvatar(Me::$id, $url[2], $url[1]))
			{
				Alert::saveSuccess("Avatar Created", "You have created your avatar!");
				
				header("Location: /dress-avatar"); exit;
			}
			else
			{
				Alert::error("Avatar Failed", "Avatar couldn't be created! Possible server permission issues.", 3);
			}
		}
	}
}

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

echo '
<div id="panel-right"></div>
<div id="content">' . Alert::display() . '
	<h2>Create Your Avatar</h2>
	<p>Please select the avatar base that you would like to use! You can change it later, as well as set a name for the new avatar.</p>';
		
	foreach($races as $race)
	{
		echo '
		<a href="/create-avatar/male/' . $race . '"><img src="/assets/create-avatar/male_' . $race . '.png" style="width:120px;" onclick="return confirm(\'Are you sure you want to create an avatar with this base?\');" /></a>
		<a href="/create-avatar/female/' . $race . '"><img src="/assets/create-avatar/female_' . $race . '.png" style="width:120px;" onclick="return confirm(\'Are you sure you want to create an avatar with this base?\');" /></a>';
	}
	
	echo '
    <p><ul><li>You can have 3 avatars for free and up to 6 additional (9 total) in exchange for Credits:
        <ul><li>- The first additional avatar slot costs 10 Credits.
        <li>- Any further additional avatar slots cost 15 Credits each.</li></ul></li>
    <li>You have unlocked ' . ($max['max'] - 3) . ' additional slots, therefore you may have up to ' . $max['max'] . ' avatars.</li></ul></p>
	<p><form class="uniform" method="post"/>' . Form::prepare("extra-slot") . '
		<input type="submit" value="Purchase Extra Slot" onclick="return confirm(\'Are you sure you want to purchase an extra slot?\');"/>
	</form></p>';
	
	echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
