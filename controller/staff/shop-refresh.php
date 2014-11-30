<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/");
}

// Make sure you are staff
if(Me::$clearance < 5)
{
	header("Location: /"); exit;
}

// Run Staff Tools
if(isset($_GET['refresh']))
{
	touch("cache/shop_" . $_GET['refresh'] . "_m.html", time()-86401);
	touch("cache/shop_" . $_GET['refresh'] . "_f.html", time()-86401);
	Alert::success("Shop Refreshed", AppAvatar::getShopTitle((int) $_GET['refresh']) . " has been refreshed.");
}

// Set page title
$config['pageTitle'] = "Refresh Shops";

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
<div class="overwrap-box">
	<div class="overwrap-line">Refresh Shops</div>
	<div class="inner-box">
	<a href="/staff/shop-refresh?refresh=1">A Cut Above</a><br/>
	<a href="/staff/shop-refresh?refresh=2">All That Glitters</a><br/>
	<a href="/staff/shop-refresh?refresh=15">Avatar Museum</a><br/>
	<a href="/staff/shop-refresh?refresh=5">Body Shop</a><br/>
	<a href="/staff/shop-refresh?refresh=18">Credit Shop</a><br/>
	<a href="/staff/shop-refresh?refresh=14">Exotic Exhibit</a><br/>
	<a href="/staff/shop-refresh?refresh=6">Finishing Touch</a><br/>
	<a href="/staff/shop-refresh?refresh=7">Haute Couture</a><br/>
	<a href="/staff/shop-refresh?refresh=3">Heart and Sole</a><br/>
	<a href="/staff/shop-refresh?refresh=8">Junk Drawer</a><br/>
	<a href="/staff/shop-refresh?refresh=9">Looking Glass</a><br/>
	<a href="/staff/shop-refresh?refresh=4">Pr&ecirc;t &agrave; Porter</a><br/>
	<a href="/staff/shop-refresh?refresh=10">Time Capsule</a><br/>
	<a href="/staff/shop-refresh?refresh=11">Under Dressed</a><br/>
	<a href="/staff/shop-refresh?refresh=12">Vogue Veneers</a><br/>
	<br/>
	<a href="/staff/shop-refresh?refresh=13">Archive</a><br/>
	<a href="/staff/shop-refresh?refresh=16">Staff Shop</a><br/>
	<a href="/staff/shop-refresh?refresh=17">Test Shop</a><br/>
	<a href="/staff/shop-refresh?refresh=19">Wrapper Replacements</a>
	</div>
</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");