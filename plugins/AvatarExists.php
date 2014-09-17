<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

/*
	[[ Description ]]
	This api confirms whether or not the user has an avatar. Returns "1" if confirmed.
	
	$packet = array(
		'uni_id'			// The uniID of the person to check if they have an avatar
	);
	
	[[ Call this API ]]
	$siteData = Network::get("avatar");
	$response = Connect::call($siteData['site_url'] . "/api/has-avatar", SITE_HANDLE, $siteData['site_key'], array("uni_id" => Me::$id));
*/

// If the proper information wasn't sent, exit the page
if(!isset($_GET['enc']) or !$key = Network::key($_GET['site'])) { exit; }

// Interpret the data sent
$apiData = API::interpret($key, $_GET['salt'], $_GET['enc'], $_GET['conf']);

if(isset($apiData['uni_id']))
{
	// Check if the user has an avatar
	if(Database::selectValue("SELECT uni_id FROM avatars WHERE uni_id=? LIMIT 1", array($apiData['uni_id'])))
	{
		echo "1"; exit;
	}
}