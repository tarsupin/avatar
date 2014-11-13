<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

$config['pageTitle'] = "Shops Menu";

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
	<p><a href="/shop-list">Shop List</a></p>
	<p><a href="/exotic-purchase">Exotic Items</a></p>
	<p><a href="/shop-search">Shop Search</a></p>
	<p><a href="/wish-list">Wish List</a></p>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
