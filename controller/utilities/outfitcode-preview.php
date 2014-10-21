<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/utilities/outfitcode-preview");
}

// Return home if you don't have an avatar
if(!isset($avatarData['base']))
{
	header("Location: /create-avatar"); exit;
}

// Run Action
if(Form::submitted("outfitcode-preview"))
{
	if(FormValidate::pass())
	{
		$outfitArray = @unserialize($_POST['saved']);
		if($outfitArray !== false)
		{
			// Uni5 code, need to index properly
			$outfitArray = AppOutfit::sortAll($outfitArray, $avatarData['gender'], "preview");
		}
		else
		{
			// Uni6 code
			$outfitArray = json_decode($_POST['saved'], true);
		}
		
		// Save the changes
		unset($outfitArray[0]);
		Alert::success("Avatar Updated", "Your outfit has been updated!");
		AppOutfit::save(Me::$id, "preview", $outfitArray);
	}
}

// Set page title
$config['pageTitle'] = "Utilities > Outfit Code (Preview Avatar)";

// Add links to nav panel
WidgetLoader::add("SidePanel", 40, '
	<div class="panel-links" style="text-align:center;">
		<a href="javascript:review_item(0);">Open Preview Window</a>
	</div>');

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Add Javascript to header
Metadata::addHeader('
<!-- javascript -->
<script src="/assets/scripts/jquery.js" type="text/javascript" charset="utf-8"></script>
<script src="/assets/scripts/jquery-ui.js" type="text/javascript" charset="utf-8"></script>
<script src="/assets/scripts/review-switch.js" type="text/javascript" charset="utf-8"></script>

<!-- javascript for touch devices, source: http://touchpunch.furf.com/ -->
<script src="/assets/scripts/jquery.ui.touch-punch.min.js" type="text/javascript" charset="utf-8"></script>
');

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

// Output code of current preview outfit
$output = array();
$outfitArray = AppOutfit::get(Me::$id, "preview");
// add base (needed for constructing outfit from scratch)
$outfitArray[0] = array(0, $avatarData['base']);
ksort($outfitArray);

echo '
	<h2><a href="/utilities">Utilities</a> > Outfit Code (Preview Avatar)</h2>
	<p>To save a list of your current outfit, copy and save the content of the following textbox.</p>
	<textarea>' . json_encode($outfitArray) . '</textarea>
	<br/><br/>
	<form class="uniform" action="/utilities/outfitcode-preview" method="post">' . Form::prepare("outfitcode-preview") . '
		<p>To dress in a previously saved outfit, copy the saved code into the following textbox and click the button.</p>
		<textarea name="saved"></textarea>
		<br/><input type="submit" name="submit" value="Update Preview Avatar"/>
	</form>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
