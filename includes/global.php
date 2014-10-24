<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Main Navigation
$urlActive = (isset($url[0]) && $url[0] != "" ? $url[0] : "home");

if(Me::$loggedIn)
{
	// Main Navigation
	WidgetLoader::add("SidePanel", 20, '
	<div class="panel-box"><ul class="panel-slots">
		<li class="nav-slot' . ($urlActive == "dress-avatar" ? " nav-active" : "") . '"><a href="/dress-avatar">Dressing Room<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot' . ($urlActive == "shop-list" ? " nav-active" : "") . '"><a href="/shop-list">Shops<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot"><a href="' . URL::avatar_unifaction_community() . '">Forum<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot' . ($urlActive == "switch-avatar" ? " nav-active" : "") . '"><a href="/switch-avatar">Switch Avatar<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot' . ($urlActive == "utilities" ? " nav-active" : "") . '"><a href="/utilities">Utilities<span class="icon-circle-right nav-arrow"></span></a></li>' .
		(Me::$clearance >= 5 ? '
		<li class="nav-slot' . ($urlActive == "staff" ? " nav-active" : "") . '"><a href="/staff">Staff<span class="icon-circle-right nav-arrow"></span></a></li>' : "") . '
	</ul></div>');
	
	// Widgets
	WidgetLoader::add("SidePanel", 30, '	
	<div class="panel-box" style="padding:5px 0px;">
		<div style="text-align:center;"><img src="' . $avatarData['src'] . (isset($avatarData['date_lastUpdate']) ? '?' . $avatarData['date_lastUpdate'] : "") . '" /></div>
	</div>');
	WidgetLoader::add("SidePanel", 35, '	
	<div class="panel-notes" style="text-align:center;padding:5px 0px;">
		' . Currency::check(Me::$id) . ' Auro
	</div>');
}
else
{
	// Main Navigation
	WidgetLoader::add("SidePanel", 10, '
	<div class="panel-box"><ul class="panel-slots">
		<li class="nav-slot"><a href="/login">Login<span class="icon-circle-right nav-arrow"></span></a></li>
	</ul></div>');
}

// Complete page title (if available)
if(isset($config['pageTitle']) and $config['pageTitle'] != "")
{
	$config['pageTitle'] = $config['site-name'] . " > " . $config['pageTitle'];
}