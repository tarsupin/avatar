<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/staff");
}

// Make sure you are staff
if(Me::$clearance < 5)
{
	header("Location: /home"); exit;
}

// Set page title
$config['pageTitle'] = "Staff";

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

// Display List of Tools
echo '
<div id="panel-right"></div>
<div id="content">' . Alert::display() . '	
	<h2>Staff</h2>
	Shops > <a href="staff/shop-refresh">Refresh Shops</a><br/>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
