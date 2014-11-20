<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Return home if you don't have an avatar
if(!isset($avatarData['base']))
{
	header("Location: /create-avatar"); exit;
}

if(!isset($url[1]))
{
	header("Location: /dress-avatar"); exit;
}

// get user
$user = Sanitize::variable($url[1]);
$recipientID = User::getDataByHandle($user);
if($recipientID == array())
{
	header("Location: /dress-avatar"); exit;
}
$recipientID = (int) $recipientID['uni_id'];

// check permission
$confirm1 = new Confirm("share-equip-" . $recipientID . "-" . Me::$id);
$confirm2 = new Confirm("share-equip-" . $recipientID . "-0");
if(!$confirm1->validate() && !$confirm2->validate() && $recipientID != Me::$id)
{
	header("Location: /dress-avatar"); exit;
}

// Get the layers you can search between
$positions = AppAvatar::getInvPositions($recipientID);

// Set page title
$config['pageTitle'] = "View " . $user . "'s Equipment";
if(isset($_GET['position']))
{
	$config['pageTitle'] .= " > " . $_GET['position'];
}

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

echo '
<div id="panel-right"></div>
<div id="content">' . Alert::display() . '
<div class="overwrap-box">
	<div class="overwrap-line">View ' . $user . '\'s Equipment</div>
	<div class="inner-box">';
	
	// Show the layers you have access to
	echo '
	<div class="redlinks">';
	
	foreach($positions as $pos)
	{
		echo '
		' . (isset($_GET['position']) && $_GET['position'] == $pos ? '<span class="nav-active">' : '') . '<a href="/view-equipment/' . $url[1] . '?position=' . $pos . '">' . $pos . '</a>' . (isset($_GET['position']) && $_GET['position'] == $pos ? '</span>' : '');
	}
	
	echo '
	</div>';
	
	if(isset($_GET['position']))
	{
		// Show the items within the category selected
		$userItems = AppAvatar::getUserItems($recipientID, $_GET['position']);
		$userItemsOther = array();
		
		// If you have no items, say so
		if(count($userItems) == 0)
		{
			echo "<p>You have no items in " . $_GET['position'] . ".</p>";
		}
		
		foreach($userItems as $key => $item)
		{
			if(!in_array($item['gender'], array($avatarData['gender'], "b")))
			{
				unset($userItems[$key]);
				$userItemsOther[] = $item;
				continue;
			}
			
			$colors = AppAvatar::getItemColors($_GET['position'], $item['title'], $avatarData['gender']);
			
			// Display the item block
			echo '
			<div class="item_block">
				<a href="javascript:review_item(' . $item['id'] . ');"><img id="pic_' . $item['id'] . '" src="/avatar_items/' . $_GET['position'] . '/' . $item['title'] . '/default_' . $avatarData['gender_full'] . '.png" /></a>
				<br />' . $item['title'] . ($item['count'] > 1 ? ' (' . $item['count'] . ')' : "") . '
				<select id="item_' . $item['id'] . '" onChange="switch_item_inventory(\'' . $item['id'] . '\', \'' . $_GET['position'] . '\', \'' . $item['title'] . '\', \'' . $avatarData['gender_full'] . '\');">';
			
			foreach($colors as $color)
			{
				echo '
					<option value="' . $color . '">' . $color . '</option>';
			}
			
			echo '
				</select>
			</div>';
		}

		foreach($userItemsOther as $item)
		{			
			$colors = AppAvatar::getItemColors($_GET['position'], $item['title'], ($avatarData['gender'] == "m" ? "f" : "m"));
			
			// Display the item block
			echo '
			<div class="item_block opaque">
				<a href="javascript:review_item(' . $item['id'] . ');"><img id="pic_' . $item['id'] . '" src="/avatar_items/' . $_GET['position'] . '/' . $item['title'] . '/default_' . ($avatarData['gender_full'] == "male" ? "female" : "male") . '.png" /></a>
				<br />' . $item['title'] . ($item['count'] > 1 ? ' (' . $item['count'] . ')' : "") . '
				<select id="item_' . $item['id'] . '" onChange="switch_item_inventory(\'' . $item['id'] . '\', \'' . $_GET['position'] . '\', \'' . $item['title'] . '\', \'' . ($avatarData['gender_full'] == "male" ? "female" : "male") . '\');">';
			
			foreach($colors as $color)
			{
				echo '
					<option value="' . $color . '">' . $color . '</option>';
			}
			
			echo '
				</select>
				<br /><a href="/sell-item/' . $item['id'] . '">Sell</a>
			</div>';
		}
	}
	
	echo '
	</div></div>
</div>';

?>

<script src="/assets/scripts/reorder.js" type="text/javascript" charset="utf-8"></script>

<script>
function switch_item_inventory(id, layer, name, gender)
{
	$("#pic_" + id).attr("src", "/avatar_items/" + layer + "/" + name + "/" + $("#item_" + id).val() + "_" + gender + ".png");
}
</script>

<?php

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
