<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

if(ENVIRONMENT == "production")
{
	header("Location: /"); exit;
}

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/free-auro");
}

// Run Action to get Free Auro
if($getLink = Link::clicked() and $getLink == "free-auro")
{
	$balance = Currency::check(Me::$id);
	
	if($balance < 500000)
	{
		Currency::add(Me::$id, (float) 100000, "Free Auro");
		
		Alert::success("Free Auro", "You just got 100,000 free auro!");
	}
	else
	{
		Alert::error("Free Auro", "You probably have enough Auro now &gt;.&gt;", 7);
	}
}

// Set page title
$config['pageTitle'] = "Free Auro";

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Run Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

echo '
<div id="panel-right"></div>
<div id="content">' .
Alert::display();

echo '
	<h2>Free Auro</h2>
	<a class="button" href="/free-auro?want-free=yes&' . Link::prepare("free-auro") . '">Click to get FREE Auro</a>';

		echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
