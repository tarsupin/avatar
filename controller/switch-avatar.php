<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }
 
// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/switch-avatar");
}

$config['pageTitle'] = "Switch Avatar";

// Check if an avatar was chosen
if(isset($url[1]))
{
	if(AppAvatar::switchAvatar(Me::$id, (int) $url[1]))
	{
		Alert::saveSuccess("Avatar Switched", "You have switched your avatar!");
		header("Location: /dress-avatar"); exit;
	}	
	else
	{
		Alert::error("Avatar Not Switched", "The avatar could not be switched.");
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
	<h2>Choose Your Avatar</h2>
	<p>Please select the avatar that you would like to use!</p>';
$avis = Database::selectMultiple("SELECT avatar_id, name, date_lastUpdate FROM avatars WHERE uni_id=?", array(Me::$id));
foreach($avis as $avi)
{
	$data = AppAvatar::avatarData(Me::$id, $avi['avatar_id']);
	echo '
	<div style="display:inline-block;text-align:center;"><a href="/switch-avatar/' . $avi['avatar_id'] . '"><img src="' . $data['src'] . (isset($data['date_lastUpdate']) ? '?' . $data['date_lastUpdate'] : "") . '" /></a><br/>' . ($data['name'] != '' ? $data['name'] : '<span style="font-style:italic;">name not set</span>') . '</div>';
}

echo '
	<div class="spacer-huge"></div>
	<p>You can also <a href="/create-avatar">create another avatar</a>!</p>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
