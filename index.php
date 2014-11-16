<?php

/****** Preparation ******/
define("CONF_PATH",		dirname(__FILE__));
define("SYS_PATH", 		dirname(CONF_PATH) . "/system");

// Load phpTesla
require(SYS_PATH . "/phpTesla.php");

// Initialize Active User
Me::initialize();

if(isset($_SESSION['u6access']) || isset($_GET['u6access']))
{
	$_SESSION['u6access'] = 1;
}
else
{
	die("The avatar site is temporarily down for changes in the Auro system. We apologize for any inconvenience caused.");
}

// Get the user's active avatar
$activeAvatar = Database::selectOne("SELECT avatar_opt FROM users WHERE uni_id=? LIMIT 1", array(Me::$id));
$activeAvatar = (int) $activeAvatar['avatar_opt'];

// Get the user's avatar
$avatarData = AppAvatar::avatarData(Me::$id, $activeAvatar);

// Determine which page you should point to, then load it
require(SYS_PATH . "/routes.php");

/****** Dynamic URLs ******
// If a page hasn't loaded yet, check if there is a dynamic load
if($url[0] != '')
{
	$profile = Database::selectOne("SELECT * FROM profile WHERE url=? LIMIT 1", array($url[0]));
	
	if(isset($profile['id']))
	{
		require(APP_PATH . '/controller/profile.php'); exit;
	}
}
//*/

/****** 404 Page ******/
// If the routes.php file or dynamic URLs didn't load a page (and thus exit the scripts), run a 404 page.
require(SYS_PATH . "/controller/404.php");