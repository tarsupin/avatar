<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }
 
// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/switch-avatar");
}

// Make sure you have an avatar
if(!isset($avatarData['base']))
{
	header("Location: /avatar/create-avatar"); exit;
}

$config['pageTitle'] = "Switch Avatar";

// Check if an avatar was chosen
if(isset($url[1]))
{
	// Check if you have an avatar with this identification
	$url[1] = (int) $url[1];
	$has = Database::selectOne("SELECT avatar_id FROM avatars WHERE uni_id=? AND avatar_id=?", array(Me::$id, $url[1]));
	if($has !== false)
	{
		// Switch to the chosen avatar
		// [pseudo code, this function does not exist]
		/*if(AppAvatar::switchAvatar(Me::$id, $url[1]))
		{
			Alert::saveSuccess("Avatar Switched", "You have switched your avatar!");
			header("Location: /dress-avatar"); exit;
		}
		else
		{
			Alert::error("Avatar couldn't be switched!");
		}*/
	}
	else
	{
		Alert::error("Wrong Input", "This is not your avatar.");
	}
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
	<p>Please select the avatar that you would like to use!</p>';
$avis = Database::selectMultiple("SELECT avatar_id FROM avatars WHERE uni_id=?", array(Me::$id));
foreach($avis as $avi)
{
	$data = AppAvatar::avatarData(Me::$id, $avi['avatar_id']);
	echo '
	<a href="/switch-avatar/' . $avi['avatar_id'] . '"><img src="' . $data['src'] . (isset($data['date_lastUpdate']) ? '?' . $data['date_lastUpdate'] : "") . '" /></a>';
}

echo '
	<div class="spacer-huge"></div>
	<p>You can also <a href="/create-avatar">create another avatar</a>!</p>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
