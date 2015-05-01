<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Main Navigation
$urlActive = (isset($url[0]) && $url[0] != "" ? $url[0] : "home");

if(Me::$loggedIn)
{
	WidgetLoader::add("UniFactionMenu", 10, '
<div class="menu-wrap hide-600">
	<ul class="menu">
		<li onmouseover="" class="menu-slot' . (in_array($urlActive, array("create-avatar", "edit-avatar", "switch-avatar")) ? " nav-active" : "") . '"><a href="/switch-avatar">Avatar</a>
			<ul>
				<li class="dropdown-slot"><a href="/create-avatar">Create Avatar</a></li>
				<li class="dropdown-slot"><a href="/edit-avatar">Edit Avatar</a></li>
			</ul>
		</li><li onmouseover="" class="menu-slot' . (in_array($urlActive, array("dress-avatar", "outfitcode-real")) ? " nav-active" : "") . '"><a href="/dress-avatar">Outfit</a>
			<ul>
				<li class="dropdown-slot"><a href="/outfitcode-real">Outfit Code</a></li>
			</ul></li><li onmouseover="" class="menu-slot' . (in_array($urlActive, array("preview-avi", "outfitcode-preview")) ? " nav-active" : "") . '"><a href="javascript:review_item(0);">Preview</a>
			<ul>
				<li class="dropdown-slot"><a href="/outfitcode-preview">Outfit Code</a></li>
			</ul>
		</li><li onmouseover="" class="menu-slot' . (in_array($urlActive, array("shop-list", "shop", "shop-search", "exotic-purchase")) ? " nav-active" : "") . '"><a href="/shop-list">Shops</a>
			<ul>
				<li class="dropdown-slot"><a href="/exotic-purchase">Exotic Items</a></li>
				<li class="dropdown-slot"><a href="/shop-search">Shop Search</a></li>
			</ul>
		</li><li onmouseover="" class="menu-slot' . (in_array($urlActive, array("gift-trade", "log-auro", "log-item")) ? " nav-active" : "") . '"><a href="/gift-trade">Gift &amp; Trade</a>
			<ul>
				<li class="dropdown-slot"><a href="' . URL::karma_unifaction_com() . '/auro-transactions">Auro Log</a></li>
				<li class="dropdown-slot"><a href="/log-item">Item Log</a></li>
				<li class="dropdown-slot"><a href="/log-package">EP Log</a></li>
			</ul>
		</li><li onmouseover="" class="menu-slot' . (in_array($urlActive, array("exotic-open", "exotic-list")) ? " nav-active" : "") . '"><a href="/exotic-open">EPs</a>
			<ul>
				<li class="dropdown-slot"><a href="/exotic-list">List of Exotic Packages</a></li>
			</ul>
		</li><li onmouseover="" class="menu-slot' . (in_array($urlActive, array("wrapper-open", "wrapper-list")) ? " nav-active" : "") . '"><a href="/wrapper-open">Wrappers</a>
			<ul>
				<li class="dropdown-slot"><a href="/wrapper-list">List of Wrappers</a></li>
			</ul>
		</li><li onmouseover="" class="menu-slot' . (in_array($urlActive, array("wish-list", "view-wishlist", "share-equipment")) ? " nav-active" : "") . '"><a href="menu-misc">Misc</a>
			<ul>
				<li class="dropdown-slot"><a href="/wish-list">My Wish List</a></li>
				<li class="dropdown-slot"><a href="/view-wishlist">Share Wish List</a></li>
				<li class="dropdown-slot"><a href="/share-equipment">Share Equipment List</a></li>
				<li class="dropdown-slot"><a href="/event-calendar">Event Calendar</a></li>
			</ul>
		</li>' . (Me::$clearance >= 5 ? '<li onmouseover="" class="menu-slot' . ($urlActive == "staff" ? " nav-active" : "") . '"><a href="/menu-staff">Staff</a>
			<ul>
				<li class="dropdown-slot"><a href="staff/shop-refresh">Refresh Shops</a></li>
				<li class="dropdown-slot"><a href="staff/item-create">Create Item</a></li>
				' . (Me::$clearance >= 8 ? '<li class="dropdown-slot"><a href="staff/exotic-stats">Exotic Stats</a></li>' : '') . '
				<li class="dropdown-slot"><a href="staff/package-manage">Manage EPs</a></li>
				<li class="dropdown-slot"><a href="staff/wrapper-manage">Manage Wrappers</a></li>
				<li class="dropdown-slot"><a href="staff/poof">Poof Item/Package</a></li>
				<li class="dropdown-slot"><a href="staff/transfer5">Transfer 5 to 6</a></li>
				<li class="dropdown-slot"><a href="staff/transfer6">Transfer 6 to 6</a></li>
				<li class="dropdown-slot"><a href="staff/transferred-names">Transfer Name Changes</a></li>
			</ul>
		</li>' : "") . '<li onmouseover="" class="menu-slot"><a href="' . URL::avatar_unifaction_community() . '">Forum</a></li>
	</ul>
</div>');
// Main Navigation
WidgetLoader::add("MobilePanel", 50, '
<div class="panel-box">
	<ul class="panel-slots">
		<li class="nav-slot"><a href="/menu-avatar">Avatar<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot"><a href="/menu-outfit">Outfit</a><span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot"><a href="/menu-preview">Preview<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot"><a href="/menu-shops">Shops<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot"><a href="/menu-giftandtrade">Gift &amp; Trade</a><span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot"><a href="menu-eps">EPs<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot"><a href="menu-wrappers">Wrappers<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot"><a href="menu-misc">Misc</a><span class="icon-circle-right nav-arrow"></span></a></li>'
		 . (Me::$clearance >= 5 ? '<li class="nav-slot"><a href="/menu-staff">Staff<span class="icon-circle-right nav-arrow"></span></a></li>' : '') . '
		<li class="nav-slot"><a href="' . URL::avatar_unifaction_community() . '">Forum</a><span class="icon-circle-right nav-arrow"></span></a></li>
	</ul>
</div>');
}
else
{
	WidgetLoader::add("UniFactionMenu", 10, '
<div class="menu-wrap">
	<ul class="menu">
		<li class="dropdown-slot' . ($urlActive == "login" ? " nav-active" : "") . '"><a href="/login">Login</a></li>
	</ul>
</div>');
}
// Complete page title (if available)
if(isset($config['pageTitle']) and $config['pageTitle'] != "")
{
	$config['pageTitle'] = $config['site-name'] . " > " . $config['pageTitle'];
}

// Add Javascript to header
Metadata::addHeader('
<!-- javascript -->
<script src="/assets/scripts/jquery.js" type="text/javascript" charset="utf-8"></script>
<script src="/assets/scripts/jquery-ui.js" type="text/javascript" charset="utf-8"></script>
<script src="/assets/scripts/review-switch.js" type="text/javascript" charset="utf-8"></script>

<!-- javascript for touch devices, source: http://touchpunch.furf.com/ -->
<script src="/assets/scripts/jquery.ui.touch-punch.min.js" type="text/javascript" charset="utf-8"></script>
');