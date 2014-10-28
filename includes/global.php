<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Main Navigation
$urlActive = (isset($url[0]) && $url[0] != "" ? $url[0] : "home");

if(Me::$loggedIn)
{
	if(isset($avatarData['base']))
	{
		// Widgets
		WidgetLoader::add("SidePanel", 10, '	
	<div class="panel-box"><ul class="panel-slots">
		<li><div style="text-align:center;padding:5px 0px;"><img src="' . $avatarData['src'] . (isset($avatarData['date_lastUpdate']) ? '?' . $avatarData['date_lastUpdate'] : "") . '" /></div></li>
		<li class="nav-slot"><a href="javascript:review_item(0);">Open Preview Window</a><span class="icon-eye nav-arrow"></span></li>
		<li class="nav-slot"><a href="/utilities/transactions">' . Currency::check(Me::$id) . ' Auro</a><span class="icon-circle-question nav-arrow"></span></li>
	</ul></div>');
	
		// Main Navigation
		WidgetLoader::add("SidePanel", 30, '
	<div class="panel-box"><ul class="panel-slots">
		<li class="nav-slot' . ($urlActive == "dress-avatar" ? " nav-active" : "") . '"><a href="/dress-avatar">Dressing Room<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot' . ($urlActive == "shop-list" ? " nav-active" : "") . '"><a href="/shop-list">Shops<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot"><a href="' . URL::avatar_unifaction_community() . '">Forum<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot' . ($urlActive == "switch-avatar" ? " nav-active" : "") . '"><a href="/switch-avatar">Switch Avatar<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot' . ($urlActive == "utilities" ? " nav-active" : "") . '"><a href="/utilities">Utilities<span class="icon-circle-right nav-arrow"></span></a></li>' .
		(Me::$clearance >= 5 ? '
		<li class="nav-slot' . ($urlActive == "staff" ? " nav-active" : "") . '"><a href="/staff">Staff<span class="icon-circle-right nav-arrow"></span></a></li>' : "") . '
	</ul></div>');
	}
	else
	{
		WidgetLoader::add("SidePanel", 10, '
	<div class="panel-box"><ul class="panel-slots">
		<li class="nav-slot' . ($urlActive == "create-avatar" ? " nav-active" : "") . '"><a href="/create-avatar">Create Avatar<span class="icon-circle-right nav-arrow"></span></a></li>
	</ul></div>');
	}
}
else
{
	// Main Navigation
	WidgetLoader::add("SidePanel", 10, '
	<div class="panel-box"><ul class="panel-slots">
		<li class="nav-slot' . ($urlActive == "login" ? " nav-active" : "") . '"><a href="/login">Login<span class="icon-circle-right nav-arrow"></span></a></li>
	</ul></div>');
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