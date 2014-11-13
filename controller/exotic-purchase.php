<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/exotic-purchase");
}

// Require avatar
if(!isset($avatarData['base']))
{
	header("Location: /create-avatar"); exit;
}

if(Me::$clearance < 8)
{
	die("Sorry, this page is not available yet!");
}

// Set page title
$config['pageTitle'] = "Purchase Exotic Item";

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Run Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

echo '
<div id="panel-right"></div>
<div id="content">' .
Alert::display() . '
<h2>Purchase Exotic Item</h2>';

// determine items for slots and display
for($i=0; $i<4; $i++)
{
	$slot = AppExotic::chooseItem($i);
	if($slot)
	{
		// adjust gender if item not available for the gender
		$gender = $avatarData['gender_full'];
		if(!in_array($slot['itemData']['gender'], array("b", $avatarData['gender']))) { $gender = ($avatarData['gender_full'] == "male" ? "female" : "male"); }
		
		// Get list of colors
		$colors = AppAvatar::getItemColors($slot['itemData']['position'], $slot['itemData']['title'], (in_array($slot['itemData']['gender'], array($avatarData['gender'], "b")) ? $avatarData['gender'] : ($avatarData['gender'] == "m" ? "f" : "m")));
		if(!$colors) { continue; }
		
		// Display the Item					
		echo '
		<div class="item_block' . ($avatarData['gender_full'] != $gender ? " opaque" : "") . '">
			' . ($avatarData['gender_full'] == $gender ? '<a href="javascript:review_item(\'' . $slot['itemData']['id'] . '\');">' : '') . '<img id="img_' . $slot['itemData']['id'] . '" src="/avatar_items/' . $slot['itemData']['position'] . '/' . $slot['itemData']['title'] . '/default_' . $gender . '.png" />' . ($avatarData['gender_full'] == $gender ? '</a>' : '') . '<br />' . $slot['itemData']['title'] . '<br/><span style="font-size:0.6em;">' . date("F", mktime(0, 0, 0, $slot['month'])) . ' ' . $slot['year'] . '<br/>leaves ' . Time::fuzzy($slot['expire']) . '<br/>Stock: ' . $slot['stock'] . '</span>
			<select id="item_' . $slot['itemData']['id'] . '" onChange="switch_item(\'' . $slot['itemData']['id'] . '\', \'' . $slot['itemData']['position'] . '\', \'' . $slot['itemData']['title'] . '\', \'' . $gender . '\');">';
			
		foreach($colors as $color)
		{
			echo '
				<option name="' . $color . '">' . $color . '</option>';
		}
			
		echo '
			</select>' . $slot['cost'] . ' Credits';
		if(AppAvatar::checkOwnItem(Me::$id, (int) $slot['itemData']['id']))
		{
			echo ' [&bull;]';
		}
		echo '
		</div>';
	}
}

/*
- replace when item or timer runs out
- sales?
- way to set prices individually though mostly automatically
*/

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
