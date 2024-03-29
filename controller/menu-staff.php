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
	' . (Me::$clearance >= 8 ? '<p><a href="staff/exotic-stats">Exotic Stats</a></p>' : '') . '
	<p><a href="staff/package-manage">Manage EPs</a></p>
	<p><a href="staff/wrapper-manage">Manage Wrappers</a></p>
	<p><a href="staff/poof">Poof Item/Package</a></p>
	<p><a href="staff/transfer5">Transfer 5 to 6</a></p>
	<p><a href="staff/transfer6">Transfer 6 to 6</a></p>
	<p><a href="staff/transferred-names">Transfer Name Changes</a></p>
	</div>
	</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
