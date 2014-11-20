<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

$config['pageTitle'] = "Preview Menu";

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
	<div class="overwrap-line">Preview Menu</div>
	<div class="inner-box">
	<p><a href="javascript:review_item(0);">Preview Window</a></p>
	<p><a href="/outfitcode-preview">Outfit Code</a></p>
	</div>
	</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
