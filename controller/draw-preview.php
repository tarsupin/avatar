<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Requires a gender, base and item to be chosen
if(!isset($_GET['item']) || !isset($_GET['gender']) || !isset($_GET['base']))
{
	exit;
}

if(!$itemData = AppAvatar::itemData((int) $_GET['item']))
{
	exit;
}

// Prepare the Preview Avatar
$order = ($itemData['max_order'] > 0 ? 2 : $itemData['max_order']);
$files = Dir::getFiles(APP_PATH . "/avatar_items/" . $itemData['position'] . "/" . $itemData['title']);
sort($files);
foreach($files as $file)
{
	$pos = strpos($file, "_" . $_GET['gender'] . ".png");
	if($pos > -1 && strpos($file, "default_") === false)
	{
		$color = substr($file, 0, $pos);
		break;
	}
}
if(!isset($color))
{
	exit;
}

$outfitArray[$order] = array($_GET['item'], $color);
AppOutfit::draw($_GET['base'], $_GET['gender'], $outfitArray);