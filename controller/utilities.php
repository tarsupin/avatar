<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/utilities");
}

// Set page title
$config['pageTitle'] = "Utilities";

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
	<h2>Utilities</h2>
	Currency > <a href="/utilities/transactions">Transaction Log</a><br />' .
	(ENVIRONMENT != "production" ? 'Currency > <a href="/utilities/free-auro">Free Auro</a><br/>' : "")	. '
	<br/>
	Transfer > <a href="/utilities/transfer">Transfer from Uni5</a><br/>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
