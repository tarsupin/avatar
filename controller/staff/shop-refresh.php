<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/staff");
}

// Make sure you are staff
if(Me::$clearance < 5)
{
	header("Location: /"); exit;
}

// Run Staff Tools
if($runLink = Link::clicked() && isset($_GET['refresh']))
{
	// Refresh the Shop Cache
	if($runLink == "refresh-shop")
	{
		CacheFile::load("shop_" . $_GET['refresh'] . "_male", 10);
		CacheFile::load("shop_" . $_GET['refresh'] . "_female", 10);
		Alert::success("Shop Refreshed", AppAvatar::getShopTitle($_GET['refresh']) . " has been refreshed.");
	}
}

// Set page title
$config['pageTitle'] = "Staff > Refresh Shops";

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
	<h2><a href="/staff">Staff</a> > Refresh Shops</h2>
	<a href="/staff/shop-refresh?refresh=1&' . Link::prepare("refresh-shop") . '">A Cut Above</a><br/>
	<a href="/staff/shop-refresh?refresh=2&' . Link::prepare("refresh-shop") . '">All That Glitters</a><br/>
	<a href="/staff/shop-refresh?refresh=15&' . Link::prepare("refresh-shop") . '">Avatar Museum</a><br/>
	<a href="/staff/shop-refresh?refresh=5&' . Link::prepare("refresh-shop") . '">Body Shop</a><br/>
	<a href="/staff/shop-refresh?refresh=18&' . Link::prepare("refresh-shop") . '">Credit Shop</a><br/>
	<a href="/staff/shop-refresh?refresh=14&' . Link::prepare("refresh-shop") . '">Exotic Exhibit</a><br/>
	<a href="/staff/shop-refresh?refresh=6&' . Link::prepare("refresh-shop") . '">Finishing Touch</a><br/>
	<a href="/staff/shop-refresh?refresh=7&' . Link::prepare("refresh-shop") . '">Haute Couture</a><br/>
	<a href="/staff/shop-refresh?refresh=3&' . Link::prepare("refresh-shop") . '">Heart and Sole</a><br/>
	<a href="/staff/shop-refresh?refresh=8&' . Link::prepare("refresh-shop") . '">Junk Drawer</a><br/>
	<a href="/staff/shop-refresh?refresh=9&' . Link::prepare("refresh-shop") . '">Looking Glass</a><br/>
	<a href="/staff/shop-refresh?refresh=4&' . Link::prepare("refresh-shop") . '">Pr&ecirc;t &agrave; Porter</a><br/>
	<a href="/staff/shop-refresh?refresh=10&' . Link::prepare("refresh-shop") . '">Time Capsule</a><br/>
	<a href="/staff/shop-refresh?refresh=11&' . Link::prepare("refresh-shop") . '">Under Dressed</a><br/>
	<a href="/staff/shop-refresh?refresh=12&' . Link::prepare("refresh-shop") . '">Vogue Veneers</a><br/>
	<br/>
	<a href="/staff/shop-refresh?refresh=13&' . Link::prepare("refresh-shop") . '">Archive</a><br/>
	<a href="/staff/shop-refresh?refresh=16&' . Link::prepare("refresh-shop") . '">Staff Shop</a><br/>
	<a href="/staff/shop-refresh?refresh=17&' . Link::prepare("refresh-shop") . '">Test Shop</a><br/>
	<a href="/staff/shop-refresh?refresh=19&' . Link::prepare("refresh-shop") . '">Wrappers</a>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");