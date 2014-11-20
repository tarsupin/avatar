<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

$config['pageTitle'] = "EPs Menu";

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
	<div class="overwrap-line">EPs Menu</div>
	<div class="inner-box">
	<p><a href="/exotic-open">Open Exotic Package</a></p>
	<p><a href="/exotic-list">List of Exotic Packages</a></p>
	</div>
	</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
