<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Prepare Notifications (if available)
if(Me::$loggedIn)
{
	WidgetLoader::add("SidePanel", 1, Notifications::sideWidget());
}

// Main Navigation
$urlActive = (isset($url[0]) && $url[0] != "" ? $url[0] : "home");

if(Me::$loggedIn)
{
	// Main Navigation
	WidgetLoader::add("SidePanel", 30, '
	<div class="panel-box"><ul class="panel-slots">
		<li class="nav-slot' . ($urlActive == "home" ? " nav-active" : "") . '"><a href="/">Home<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot"><a href="' . URL::avatar_unifaction_community() . '">Forum<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot' . ($urlActive == "dress-avatar" ? " nav-active" : "") . '"><a href="/dress-avatar">Dressing Room<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot' . ($urlActive == "shop-list" ? " nav-active" : "") . '"><a href="/shop">Shop<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot"><a href="/user-panel/auro">Free Auro<span class="icon-circle-right nav-arrow"></span></a></li>
	</ul></div>');
}
else
{
	// Main Navigation
	WidgetLoader::add("SidePanel", 10, '
	<div class="panel-box"><ul class="panel-slots">
		<li class="nav-slot"><a href="/login">Login<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot' . ($urlActive == "create-avatar" ? " nav-active" : "") . '"><a href="/create-avatar">Create Avatar<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot"><a href="/user-panel/auro">Free Auro<span class="icon-circle-right nav-arrow"></span></a></li>
	</ul></div>');
}

// Widgets
WidgetLoader::add("SidePanel", 50, '	
<div class="panel-box" style="padding:5px 0 5px 0;">
	<div style="text-align:center;"><img src="' . $avatarData['src'] . (isset($avatarData['date_lastUpdate']) ? '?' . $avatarData['date_lastUpdate'] : "") . '" /></div>
</div>');

