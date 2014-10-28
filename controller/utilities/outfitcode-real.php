<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/utilities/outfitcode-real");
}

// Return home if you don't have an avatar
if(!isset($avatarData['base']))
{
	header("Location: /create-avatar"); exit;
}

// Run Action
if(Form::submitted("outfitcode-real"))
{
	if(FormValidate::pass())
	{
		$outfitArray = @unserialize($_POST['saved']);
		if($outfitArray !== false)
		{
			// Uni5 code, need to index properly; ownership and existence is automatically checked
			$outfitArray = AppOutfit::sortAll($outfitArray, $avatarData['gender'], "default");
		}
		else
		{
			// Uni6 code
			$outfitArray = json_decode($_POST['saved'], true);
			foreach($outfitArray as $key => $oa)
			{
				// check existence
				$itemData = AppAvatar::itemData($oa[0]);
				if(!$itemData)
				{
					unset($outfitArray[$key]);
					$outfitArray = AppOutfit::sortDelete($outfitArray, $key);
				}
				else
				{
					// check ownership
					if(!AppAvatar::checkOwnItem(Me::$id, $oa[0]))
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
		$aviData = Avatar::imageData(Me::$id);
		AppOutfit::draw($avatarData['base'], $avatarData['gender'], $outfitArray, APP_PATH . '/' . $aviData['image_directory'] . '/' . $aviData['main_directory'] . '/' . $aviData['second_directory'] . '/' . $aviData['filename']);
		AppOutfit::save(Me::$id, "default", $outfitArray);
		Alert::success("Avatar Updated", "Your outfit has been updated!");
	}
}

// Set page title
$config['pageTitle'] = "Utilities > Outfit Code (Current Avatar)";

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Run Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

echo '
<style>
textarea { width:100%; height:100px; }
</style>';

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

echo '
<div id="panel-right"></div>
<div id="content">' .
Alert::display();

// Output code of current outfit
$output = array();
$outfitArray = AppOutfit::get(Me::$id, "default");
// add base (needed for constructing outfit from scratch)
$outfitArray[0] = array(0, $avatarData['base']);
ksort($outfitArray);

echo '
	<h2><a href="/utilities">Utilities</a> > Outfit Code (Current Avatar)</h2>
	<p>To save a list of your current outfit, copy and save the content of the following textbox.</p>
	<textarea onclick="$(this).select();">' . json_encode($outfitArray) . '</textarea>
	<br/><br/>
	<form class="uniform" action="/utilities/outfitcode-real" method="post">' . Form::prepare("outfitcode-real") . '
		<p>To dress in a previously saved outfit, copy the saved code into the following textbox and click the button.<br/>Items that you do not own will not equip.</p>
		<textarea name="saved"></textarea>
		<br/><input type="submit" name="submit" value="Update Current Avatar"/>
	</form>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
