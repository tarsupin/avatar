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

// Check if a base was chosen
if(isset($url[1]) && isset($url[2]))
{
	// Check if the values are legitimate
	if(in_array($url[1], array("male", "female")) && in_array($url[2], $races))
	{
		if($number['max'] >= 9)
		{
			Alert::error("Enough Avatars", "You cannot create more than 9 avatars.");
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
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
