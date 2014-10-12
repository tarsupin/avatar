<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }
 
// Make sure you're logged in
if(!Me::$loggedIn)
{
	header("Location: /login?logAct=switch"); exit;
}

// Make sure you have an avatar
if(!isset($avatarData['base']))
{
	header("Location: /shop-list"); exit;
}

$config['pageTitle'] = "Switch Avatar";

// Check if an avatar was chosen
if(isset($url[1]))
{
	// [pseudo code, these functions do not exist]
	// Check if this avatar belongs to your profile
	/*if(AppAvatar::isMine(Me::$id, $url[1]))
	{
		// Switch to the chosen avatar
		if(AppAvatar::switchAvatar(Me::$id, $url[1]))
		{		
			Alert::saveSuccess("Avatar Switched", "You have switched your avatar!");
			header("Location: /dress-avatar"); exit;
		}
		else
		{
			Alert::error("Avatar couldn't be switched!");
		}
	}
	else
	{
		Alert::error("Wrong Input", "This is not your avatar.");
	}*/
}

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

Alert::info("Multiple Avatars", "Multiple avatars per profile are not implemented yet.");

echo '
<div id="panel-right"></div>
<div id="content">' . Alert::display() . '
	<h2>Choose Your Avatar</h2>
	<p>Please select the Avatar that you would like to use!</p>
	<a href="/switch-avatar/"><img src="' . $avatarData['src'] . (isset($avatarData['date_lastUpdate']) ? '?' . $avatarData['date_lastUpdate'] : "") . '" /></a>
	<div class="spacer-huge"></div>
	<p>You can also <a href="create-avatar">create another avatar</a>!</p>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
