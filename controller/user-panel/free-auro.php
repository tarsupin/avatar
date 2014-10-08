<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

if(ENVIRONMENT == "production")
{
	header("Location: /user-panel"); exit;
}

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/user-panel/free-auro");
}

// Run Action to get Free Auro
if($getLink = Link::clicked() and $getLink == "free-auro")
{
	$balance = Currency::check(Me::$id);
	
	if($balance < 500000)
	{
		Currency::add(Me::$id, 100000, "Free Auro");
		
		Alert::message("Free Auro", "You just got 100,000 free auro!");
	}
	else
	{
		Alert::error("Free Auro", "You probably have enough Auro now &gt;.&gt;", 7);
	}
}

// Run Header
require(APP_PATH . "/includes/user_panel_header.php");

echo '
<a class="button" href="/user-panel/free-auro?want-free=yes&' . Link::prepare("free-auro") . '">Click to get FREE Auro</a>';

// Display the Footer
require(SYS_PATH . "/controller/includes/user_panel_footer.php");
