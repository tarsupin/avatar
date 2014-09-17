<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you have an avatar
if(!isset($avatarData['base']))
{
	exit;
}

// Requires a type to be chosen
if(!isset($_GET['type']))
{
	exit;
}

// Prepare the Preview Avatar
$outfitItems = AppOutfit::get(Me::$id, Sanitize::word($_GET['type']));

AppOutfit::draw($avatarData['base'], $avatarData['gender'], $outfitItems);