<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/");
}

// Make sure you are staff
if(Me::$clearance < 5)
{
	header("Location: /"); exit;
}

$config['pageTitle'] = "Staff Menu";

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
	<div class="overwrap-line">Staff Menu</div>
	<div class="inner-box">
	<p><a href="staff/shop-refresh">Refresh Shops</a></p>
	<p><a href="staff/item-create">Create Item</a></p>
	<p><a href="staff/transfer">Transfer</a></p>
	</div>
	</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
