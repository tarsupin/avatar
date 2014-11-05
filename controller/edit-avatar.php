<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/edit-avatar");
}

// Make sure you don't already have an avatar
if(!isset($avatarData['base']))
{
	header("Location: /create-avatar"); exit;
}

// Set page title
$config['pageTitle'] = "Edit Avatar";

// Prepare Values
$races = array("white", "tan", "pacific", "dark", "light", "gray");

// Check if a base was chosen
if(isset($url[1]) && isset($url[2]))
{
	// Check if the values are legitimate
	if(in_array($url[1], array("male", "female")) && in_array($url[2], $races))
	{
		// Edit Your Avatar
		if(AppAvatar::editAvatar(Me::$id, $url[2], $url[1]))
		{
			Alert::saveSuccess("Avatar Edited", "You have switched to a " . $url[1] . " " . $url[2] . " base!");
			
			header("Location: /dress-avatar"); exit;
		}
		else
		{
			Alert::error("Avatar Edit Failed", "Base couldn't be changed!");
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
	<h2>Edit Your Avatar</h2>
	<p>Please select the avatar base that you would like to use! Currently you are using a <span style="font-weight:bold;">' . $avatarData['gender_full'] . ' ' . $avatarData['base'] . '</span> base.<br/>Changing the skin color costs 30 Auro. Changing the gender costs 1000 Auro.</p>';
		
	foreach($races as $race)
	{
		echo '
		<a href="/edit-avatar/male/' . $race . '"><img src="/assets/create-avatar/male_' . $race . '.png" style="width:120px;" onclick="return confirm(\'Are you sure you want to use this base?\');"/></a>
		<a href="/edit-avatar/female/' . $race . '"><img src="/assets/create-avatar/female_' . $race . '.png" style="width:120px;" onclick="return confirm(\'Are you sure you want to use this base?\');"/></a>';
	}
	
	echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
