<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/outfitcode-real");
}

// Return home if you don't have an avatar
if(!isset($avatarData['base']))
{
	header("Location: /create-avatar"); exit;
}

// Run Action
if(Form::submitted("outfitcode-real"))
{
	$outfitArray = json_decode($_POST['saved'], true);
	if($outfitArray === NULL)
	{
		if($outfitArray = @unserialize($_POST['saved']))
		{
			// Uni5 code, need to index properly; existence is automatically checked
			// check ownership
			foreach($outfitArray as $key => $oa)
			{
				if($oa[0] == 0) { continue; }
				if(!AppAvatar::checkOwnItem(Me::$id, (int) $oa[0]))
				{
					$itemData = AppAvatar::itemData((int) $oa[0], "title");
					Alert::error($itemData['title'] . " Not Owned", "You do not own " . $itemData['title'] . ", so it cannot be equipped.");
					unset($outfitArray[$key]);
				}
			}
			
			$outfitArray = AppOutfit::sortAll($outfitArray, $avatarData['gender']);
		}
		else
		{
			$outfitArray[0] = array(0, $avatarData['base']);
		}
	}
	else
	{
		// Uni6 code
		foreach($outfitArray as $key => $oa)
		{
			// check existence
			$itemData = AppAvatar::itemData((int) $oa[0], "title");
			if(!$itemData)
			{
				unset($outfitArray[$key]);
				$outfitArray = AppOutfit::sortDelete($outfitArray, $key);
			}
			else
			{
				// check ownership
				if(!AppAvatar::checkOwnItem(Me::$id, (int) $oa[0]))
				{
					Alert::error($itemData['title'] . " Not Owned", "You do not own " . $itemData['title'] . ", so it cannot be equipped.");
					unset($outfitArray[$key]);
					$outfitArray = AppOutfit::sortDelete($outfitArray, $key);
				}
			}				
		}
	}
	
	// Save the changes
	unset($outfitArray[0]);
	$aviData = Avatar::imageData(Me::$id, $activeAvatar);
	AppOutfit::draw($avatarData['base'], $avatarData['gender'], $outfitArray, APP_PATH . '/' . $aviData['image_directory'] . '/' . $aviData['main_directory'] . '/' . $aviData['second_directory'] . '/' . $aviData['filename']);
	AppOutfit::save(Me::$id, $avatarData['identification'], $outfitArray);
	Alert::success("Avatar Updated", "Your outfit has been updated!");
}

// Set page title
$config['pageTitle'] = "Outfit Code (Current Avatar)";

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Run Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

echo '
<style>
textarea { width:100%; height:150px; }
@media screen and (min-width:501px) {
	.uniform { position:static; margin-right:215px; }
}
</style>';

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

echo '
<div id="panel-right"></div>
<div id="content">' .
Alert::display() . '
<div class="overwrap-box">
	<div class="overwrap-line">Outfit Code (Current Avatar)</div>
	<div class="inner-box">';
	
$wrappers = AppAvatar::wrappers();

// Output code of current outfit
$output = array();
$outfitArray = AppOutfit::get(Me::$id, $avatarData['identification']);
// add base (needed for constructing outfit from scratch)
$outfitArray[0] = array(0, $avatarData['base']);
ksort($outfitArray);

echo '
	<div id="aviblock"><img src="' . $avatarData['src'] . (isset($avatarData['date_lastUpdate']) ? '?' . $avatarData['date_lastUpdate'] : "") . '"/><br/>';
// Show equipped items in human-readable form
foreach($outfitArray as $oa)
{
	if($oa[0] == 0) { continue; }
	$itemData = AppAvatar::itemData((int) $oa[0], "title");
	echo $itemData['title'] . (in_array($oa[0], $wrappers) ? ' (Wrapper)' : '') . " (" . $oa[1] . ")" . (AppAvatar::checkOwnItem(Me::$id, (int) $oa[0]) ? " [&bull;]" : "") . "<br/>";
}
echo '</div>
	<form class="uniform" method="post">' . Form::prepare("outfitcode-real") . '
		<p>To save a list of your current outfit, copy and save the content of the following textbox.</p>
		<p><textarea onclick="$(this).select();">' . json_encode($outfitArray) . '</textarea></p>
		<p>To dress in a previously saved outfit, copy the saved code into the following textbox and click the button.<br/>Items that you do not own will not equip.</p>
		<p><textarea name="saved"></textarea></p>
		<p><input type="submit" name="submit" value="Update Current Avatar"/></p>
	</form>
	</div>
</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
