<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

$config['pageTitle'] = "Outfit Menu";

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
	<p><a href="/dress-avatar">Dressing Room</a></p>
	<p><a href="/outfitcode-real">Outfit Code</a></p>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
