<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// create avatar if you have none
if(Me::$loggedIn && !isset($avatarData['base']))
{
	header("Location: /create-avatar"); exit;
}

// set dressing room as the start page unless you were sent here with messages
if(Me::$loggedIn && Alert::display() == "")
{
	header("Location: /dress-avatar"); exit;
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
<div class="overwrap-box">
<div class="overwrap-line">Avatar</div>
	<div class="inner-box">
	<p>Welcome to the avatar site!</p>
	<p>This is where you can create your avatar doll and dress it up in various high quality items. Explore the endless combination possibilities our intuitive and flexible dressing system has to offer!</p>
	</div>
</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
