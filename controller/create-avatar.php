<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you don't already have an avatar
if(isset($avatarData['base']))
{
	header("Location: /"); exit;
}

// Prepare Values
$races = array("white", "tan", "pacific", "dark", "light", "gray");

// Check if an avatar was chosen
if(isset($url[1]) && isset($url[2]))
{
	// Make sure you're logged in
	if(!Me::$loggedIn)
	{
		header("Location: /login?logAct=switch"); exit;
	}
	
	// Check if the values are legitimate
	if(in_array($url[1], array("male", "female")) && in_array($url[2], $races))
	{
		// Create Your Avatar
		if(AppAvatar::createAvatar(Me::$id, $url[2], $url[1]))
		{
			Alert::saveMessage("Avatar Created", "You have selected your avatar!");
			
			header("Location: /"); exit;
		}
		else
		{
			Alert::error("Avatar Failed", "Avatar couldn't be created! Possible server permission issues.", 3);
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
	
	<div class="category-container">
		<div class="details-header">
			Choose Your Avatar Base
		</div>
		<div class="details-body">
		<h3>Welcome to Uni-Avatar!</h3>
		<p>Please select the Avatar that you would like to use!</p>';
		
		foreach($races as $race)
		{
			echo '
			<a href="/create-avatar/male/' . $race . '"><img src="/assets/create-avatar/male_' . $race . '.png" style="width:120px;" /></a>
			<a href="/create-avatar/female/' . $race . '"><img src="/assets/create-avatar/female_' . $race . '.png" style="width:120px;" /></a>';
		}
		
		echo '
		</div>
	</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
