<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Main Navigation
$urlActive = (isset($url[0]) && $url[0] != "" ? $url[0] : "home");

if(Me::$loggedIn)
{
	WidgetLoader::add("UniFactionMenu", 10, '
<div class="menu-wrap">
	<ul class="menu">
		<li onmouseover="" class="menu-slot menu-plain' . (in_array($urlActive, array("create-avatar", "edit-avatar", "switch-avatar")) ? " nav-active" : "") . '">Avatar
			<ul>
				<li class="dropdown-slot"><a href="/create-avatar">Create Avatar</a></li>
				<li class="dropdown-slot"><a href="/edit-avatar">Edit Avatar</a></li>
				<li class="dropdown-slot"><a href="/switch-avatar">Switch Avatar</a></li>
			</ul>
		</li><li onmouseover="" class="menu-slot menu-plain' . (in_array($urlActive, array("dress-avatar", "outfitcode-real")) ? " nav-active" : "") . '">Outfit
			<ul>
				<li class="dropdown-slot"><a href="/dress-avatar">Dressing Room</a></li>
				<li class="dropdown-slot"><a href="/outfitcode-real">Outfit Code</a></li>
			</ul></li><li onmouseover="" class="menu-slot menu-plain' . (in_array($urlActive, array("preview-avi", "outfitcode-preview")) ? " nav-active" : "") . '">Preview
			<ul>
				<li class="dropdown-slot"><a href="javascript:review_item(0);">Preview Window</a></li>
				<li class="dropdown-slot"><a href="/outfitcode-preview">Outfit Code</a></li>
			</ul>
		</li><li onmouseover="" class="menu-slot menu-plain' . (in_array($urlActive, array("shop-list", "shop", "shop-search", "wish-list")) ? " nav-active" : "") . '">Shops
			<ul>
				<li class="dropdown-slot"><a href="/shop-list">Shop List</a></li>
				<li class="dropdown-slot"><a href="/shop-search">Shop Search</a></li>
				<li class="dropdown-slot"><a href="/wish-list">Wish List</a></li>
			</ul>
		</li><li onmouseover="" class="menu-slot menu-plain' . (in_array($urlActive, array("gift-trade", "log-auro", "log-item")) ? " nav-active" : "") . '">Gift &amp; Trade
			<ul>
				<li class="dropdown-slot"><a href="/gift-trade">Gift &amp; Trade</a></li>
				<li class="dropdown-slot"><a href="/log-auro">Auro Log</a></li>
				<li class="dropdown-slot"><a href="/log-item">Item Log</a></li>
			</ul>
		</li><li onmouseover="" class="menu-slot menu-plain' . (in_array($urlActive, array("exotic-open", "exotic-list", "exotic-purchase")) ? " nav-active" : "") . '">EPs
			<ul>
				<li class="dropdown-slot"><a href="/exotic-open">Open Exotic Package</a></li>
				<li class="dropdown-slot"><a href="/exotic-list">List of Exotic Packages</a></li>
				<li class="dropdown-slot"><a href="/exotic-purchase">Purchase Exotic Package</a></li>
			</ul>
		</li><li onmouseover="" class="menu-slot menu-plain' . (in_array($urlActive, array("wrapper-open", "wrapper-list")) ? " nav-active" : "") . '">Wrappers
			<ul>
				<li class="dropdown-slot"><a href="/wrapper-open">Open Wrapper</a></li>
				<li class="dropdown-slot"><a href="/wrapper-list">List of Wrappers</a></li>
			</ul>
		</li>' . (Me::$clearance >= 5 ? '<li onmouseover="" class="menu-slot menu-plain' . ($urlActive == "staff" ? " nav-active" : "") . '">Staff
			<ul>
				<li class="dropdown-slot"><a href="staff/shop-refresh">Refresh Shops</a></li>
			</ul>
		</li>' : "") . '<li class="menu-slot"><a href="' . URL::avatar_unifaction_community() . '">Forum</a></li><li class="menu-slot menu-plain">' . Currency::check(Me::$id) . ' Auro</li>
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