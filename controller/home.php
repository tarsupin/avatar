<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

if(Me::$loggedIn and !isset($avatarData['base']))
{
	header("Location: /create-avatar"); exit;
}

$config['pageTitle'] = "Home";

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

echo '
<div id="panel-right"></div>
<div id="content">
	' . Alert::display() . '
	<h2>Avatar</h2>
	<p>Welcome to the avatar site!</p>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
