<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

$config['pageTitle'] = "Avatar Menu";

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
	<div class="overwrap-line">Avatar Menu</div>
	<div class="inner-box">
	<p><a href="/create-avatar">Create Avatar</a></p>
	<p><a href="/edit-avatar">Edit Avatar</a></p>
	<p><a href="/switch-avatar">Switch Avatar</a></p>
	</div>
	</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
