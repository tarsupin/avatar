<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/outfitcode-preview");
}

// Return home if you don't have an avatar
if(!isset($avatarData['base']))
{
	header("Location: /create-avatar"); exit;
}

// Run Action
if(Form::submitted("outfitcode-preview"))
{
	$outfitArray =  json_decode($_POST['saved'], true);	
	if($outfitArray === NULL)
	{
		if($outfitArray = @unserialize($_POST['saved']))
		{
			// Uni5 code, need to index properly
			$outfitArray = AppOutfit::sortAll($outfitArray, $avatarData['gender']);
		}
		else
		{
			$outfitArray[0] = array(0, $avatarData['base']);
		}
	}
	else
	{
		// Uni6 code, done above
	}
	
	// Save the changes
	unset($outfitArray[0]);
	Alert::success("Avatar Updated", "Your outfit has been updated!");
	AppOutfit::save(Me::$id, "preview", $outfitArray);
}

// Set page title
$config['pageTitle'] = "Outfit Code (Preview Avatar)";

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
	<div class="overwrap-line">Outfit Code (Preview Avatar)</div>
	<div class="inner-box">';
	
$wrappers = AppAvatar::wrappers();

// Output code of current preview outfit
$output = array();
$outfitArray = AppOutfit::get(Me::$id, "preview");
// add base (needed for constructing outfit from scratch)
$outfitArray[0] = array(0, $avatarData['base']);
ksort($outfitArray);

echo '
	<div id="aviblock"><img src="' . AppOutfit::drawSrc("preview") . '"/><br/>';
// Show equipped items in human-readable form
foreach($outfitArray as $oa)
{
	if($oa[0] == 0) { continue; }
	$itemData = AppAvatar::itemData((int) $oa[0], "title");
	echo $itemData['title'] . (in_array($oa[0], $wrappers) ? ' (Wrapper)' : '') . " (" . $oa[1] . ")" . (AppAvatar::checkOwnItem(Me::$id, (int) $oa[0]) ? " [&bull;]" : "") . "<br/>";
}
echo '</div>
	<form class="uniform" method="post">' . Form::prepare("outfitcode-preview") . '
		<p>To save a list of your preview outfit, copy and save the content of the following textbox.</p>
		<p><textarea onclick="$(this).select();">' . json_encode($outfitArray) . '</textarea></p>
		<p>To dress in a previously saved outfit, copy the saved code into the following textbox and click the button.</p>
		<p><textarea name="saved"></textarea></p>
		<p><input type="submit" name="submit" value="Update Preview Avatar"/></p>
	</form>
	</div>
</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
