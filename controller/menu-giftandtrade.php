<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

$config['pageTitle'] = "Gift &amp; Trade Menu";

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
	<div class="overwrap-line">Gift &amp; Trade Menu</div>
	<div class="inner-box">
	<p><a href="/gift-trade">Gift &amp; Trade</a></p>
	<p><a href="' . URL::karma_unifaction_com() . '/auro-transactions">Auro Log</a></p>
	<p><a href="/log-item">Item Log</a></p>
	<p><a href="/log-item">EP Log</a></p>
	</div>
	</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
