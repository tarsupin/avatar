<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

$config['pageTitle'] = "Menu";

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
	<div class="overwrap-line">Menu</div>
	<div class="inner-box">
	<p><a href="/menu-avatar">Avatar</a></p>
	<p><a href="/menu-outfit">Outfit</a></p>
	<p><a href="/menu-preview">Preview</a></p>
	<p><a href="/menu-shops">Shops</a></p>
	<p><a href="/menu-giftandtrade">Gift &amp; Trade</a></p>
	<p><a href="menu-eps">EPs</a></p>
	<p><a href="menu-wrappers">Wrappers</a></p>
	<p><a href="menu-misc">Misc</a></p>';
if(Me::$clearance >= 5)
{
	echo '
	<p><a href="/menu-staff">Staff</a></p>';
}
echo '
	</div>
	</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
