<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/utilities/exotic-open");
}

// Return home if you don't have an avatar
if(!isset($avatarData['base']))
{
	header("Location: /create-avatar"); exit;
}

if(isset($_GET['package']) && isset($_GET['item']))
{	
	$_GET['package'] = (int) $_GET['package'];
	$_GET['item'] = (int) $_GET['item'];
	
	// get item data
	$item = AppAvatar::itemData((int) $_GET['item'], "id,title,position,gender");
	
	// check if you own the item
	if(AppAvatar::checkOwnItem(Me::$id,$item['id']))
	{
		Alert::info("Own Item", "Note: You already own this item!");
	}

	if(Form::submitted("exotic-open"))
	{
		if(FormValidate::pass())
		{
			// check if this item is in this package
			$valid = Database::selectOne("SELECT id FROM packages INNER JOIN packages_content ON packages.id=packages_content.package_id WHERE item_id=? AND package_id=?", array((int)$item['id'], (int) $_GET['package']));
			if(!$valid)
			{
				Alert::saveError("Not Available", "This item is not available in this package.");
				header("Location:/utilities/exotic-open"); exit;
			}
			
			// check if you own the package
			if(!AppAvatar::checkOwnPackage(Me::$id, $_GET['package']))
			{
				Alert::saveError("Not Owned", "You don't own this package!");
				header("Location:/utilities/exotic-open"); exit;
			}
						
			// give item
			if(AppAvatar::receiveItem(Me::$id,$item['id']))
			{
				// remove package
				AppAvatar::dropPackage(Me::$id, $_GET['package']);
				Alert::saveSuccess("Item Chosen", "You have received " . $item['title'] . ".");
				header("Location:/dress-avatar?position=" . $item['position']); exit;
			}
		}
	}
}

// Set page title
$config['pageTitle'] = "Utilities > Open Exotic Package";

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

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

echo '
<div id="panel-right"></div>
<div id="content">' .
Alert::display();

echo '
	<h2><a href="/utilities">Utilities</a> > Open Exotic Package</h2>';

// output all packages
if(!isset($_GET['package']) || !isset($_GET['item']))
{
	$space = false;

	if(!isset($_GET['package']))
	{
		// get packages
		$packages = Database::selectMultiple("SELECT id, title, year, month FROM packages INNER JOIN user_packages ON packages.id=user_packages.package_id WHERE uni_id=? ORDER BY package_id DESC", array(Me::$id));

		// get item info
		foreach($packages as $key => $package)
		{
			if($space) { echo '<div class="spacer-giant"></div>'; }
			$space = true;
			
			echo '<h3>' . $package['title'] . ' (' . $package['year'] . ')</h3>
	<a href="utilities/exotic-open?package=' . $package['id'] . '"><img src="assets/exotic_packages/' . lcfirst(date('F', mktime(0, 0, 0, $package['month'], 1, 1))) . '_' . $package['year'] . '.png"/></a>';
		}
	}
	else
	{
		$package = Database::selectOne("SELECT id, title, year, month FROM packages INNER JOIN user_packages ON packages.id=user_packages.package_id WHERE uni_id=? AND id=?", array(Me::$id, (int) $_GET['package']));
	
		echo '<h3>' . $package['title'] . ' (' . $package['year'] . ')</h3>
	<img src="assets/exotic_packages/' . lcfirst(date('F', mktime(0, 0, 0, $package['month'], 1, 1))) . '_' . $package['year'] . '.png"/>
	<span style="color:#fb7c7c;">can be opened to pick one of these items:</span><br/>';
		
		$content = Database::selectMultiple("SELECT item_id FROM packages_content WHERE package_id=?", array($package['id']));
		foreach($content as $cont)
		{
			// get item data
			$item = AppAvatar::itemData((int) $cont['item_id'], "id,title,position,gender");
			
			// Get list of colors
			$colors	= AppAvatar::getItemColors($item['position'], $item['title']);				
			if(!$colors) { continue; }
			
			if($item['gender'] == "b" || $item['gender'] == $avatarData['gender'])	{ $gender = $avatarData['gender_full']; }
			else	{ $gender = ($item['gender'] == "m" ? "male" : "female"); }

			// Display the Item					
			echo '
	<div class="item_block">
		<a href="javascript:review_item(\'' . $item['id'] . '\');"><img id="img_' . $item['id'] . '" src="/avatar_items/' . $item['position'] . '/' . $item['title'] . '/default_' . $gender . '.png" /></a><br />
		<select id="item_' . $item['id'] . '" onChange="switch_item(\'' . $item['id'] . '\', \'' . $item['position'] . '\', \'' . $item['title'] . '\', \'' . $gender . '\');">';
				
				foreach($colors as $color)
				{
					echo '
			<option name="' . $color . '">' . $color . '</option>';
				}
				
				echo '
		</select>
		<br/><a href="utilities/exotic-open?package=' . $package['id'] . '&item=' . $item['id'] . '">Choose ' . $item['title'] . '</a>';
			if(AppAvatar::checkOwnItem(Me::$id, $item['id']))
			{
				echo " [&bull;]";
			}
			echo '
	</div>';
		}
	}
}
// confirm choice of one item
else
{
	$package = Database::selectOne("SELECT id, title, year, month FROM packages INNER JOIN user_packages ON packages.id=user_packages.package_id WHERE uni_id=? AND id=?", array(Me::$id, (int) $_GET['package']));
	
	if($item['gender'] == "b" || $item['gender'] == $avatarData['gender'])	{ $gender = $avatarData['gender_full']; }
	else	{ $gender = ($item['gender'] == "m" ? "male" : "female"); }
	
	echo '<h3>' . $package['title'] . ' (' . $package['year'] . ')</h3><br/>';
	
	// get an image
	$images = Dir::getFiles(APP_PATH . "/avatar_items/" . $item['position'] . '/' . $item['title'] . '/');
	
	foreach($images as $img)
	{
		if(strpos($img, "_" . $gender . ".png") > -1 && strpos($img, "default_") === false)
		{
			echo '
	<img src="/avatar_items/' . $item['position'] . '/' . $item['title'] . '/' . $img . '" />';
			break;
		}
	}
	echo '
	<form class="uniform" action="/utilities/exotic-open?package=' . $_GET['package'] . '&item=' .$item['id'] . '" method="post">' . Form::prepare("exotic-open") . '
		<input type="submit" name="submit" value="Choose ' . $item['title'] . '" />
	</form>';
}

	echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");