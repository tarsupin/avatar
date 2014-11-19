<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/exotic-open");
}

// Return home if you don't have an avatar
if(!isset($avatarData['base']))
{
	header("Location: /create-avatar"); exit;
}

// Check that you own the package
if(isset($url[1]))
{
	if(!AppAvatar::checkOwnPackage(Me::$id, (int) $url[1]))
	{
		header("Location: /exotic-open"); exit;
	}
}

if(isset($url[2]))
{
	$url[1] = (int) $url[1];
	$url[2] = (int) $url[2];
	
	// get item data
	$item = AppAvatar::itemData($url[2], "id,title,position,gender");
	$item['id'] = (int) $item['id'];
	
	// check if you own the item
	if(AppAvatar::checkOwnItem(Me::$id, $item['id']))
	{
		Alert::info("Own Item", "Note: You already own this item!");
	}

	if(Form::submitted("exotic-open"))
	{
		// check if this item is in this package
		$valid = Database::selectOne("SELECT id FROM packages INNER JOIN packages_content ON packages.id=packages_content.package_id WHERE item_id=? AND package_id=?", array($item['id'], $url[1]));
		if(!$valid)
		{
			Alert::saveError("Not Available", "This item is not available in this package.");
			header("Location: /exotic-open"); exit;
		}
					
		// give item
		if(AppAvatar::receiveItem(Me::$id, $item['id'], "Chosen from EP"))
		{
			// get wrappers
			$wrappers = AppAvatar::wrappers();
		
			// remove package
			AppAvatar::dropPackage(Me::$id, $url[1], "Chose " . $item['title'] . (in_array($item['id'], $wrappers) ? " (Wrapper)" : ""));
			Alert::saveSuccess("Item Chosen", "You have received " . $item['title'] . ".");
			header("Location:/dress-avatar?position=" . $item['position']); exit;
		}
	}
}

// Set page title
$config['pageTitle'] = "Open Exotic Package";

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
Alert::display();

echo '
	<h2>Open Exotic Package</h2>';

// output all packages
if(!isset($url[2]))
{
	$space = false;

	if(!isset($url[1]))
	{
		// get packages
		$packages = Database::selectMultiple("SELECT id, title, year, month, COUNT(uni_id) AS count FROM packages INNER JOIN user_packages ON packages.id=user_packages.package_id WHERE uni_id=? GROUP BY id", array(Me::$id));

		// get item info
		foreach($packages as $key => $package)
		{
			if($space) { echo '<div class="spacer"></div>'; }
			$space = true;
			
			echo '<h3>' . $package['title'] . ' (' . $package['year'] . ')' . ($package['count'] > 1 ? ' (' . $package['count'] . ')' : "") . '</h3>
	<a href="/exotic-open/' . $package['id'] . '">' . (File::exists('assets/exotic_packages/' . lcfirst(date('F', mktime(0, 0, 0, $package['month'], 1, 1))) . '_' . $package['year'] . '.png') ? '<img src="assets/exotic_packages/' . lcfirst(date('F', mktime(0, 0, 0, $package['month'], 1, 1))) . '_' . $package['year'] . '.png"/>' : '<span class="opaque">Image coming soon.</span>') . '</a>';
		}
	}
	else
	{
		$package = Database::selectOne("SELECT id, title, year, month FROM packages INNER JOIN user_packages ON packages.id=user_packages.package_id WHERE uni_id=? AND id=?", array(Me::$id, (int) $url[1]));
	
		echo '<h3>' . $package['title'] . ' (' . $package['year'] . ')</h3>
	' . (File::exists('assets/exotic_packages/' . lcfirst(date('F', mktime(0, 0, 0, $package['month'], 1, 1))) . '_' . $package['year'] . '.png') ? '<img src="assets/exotic_packages/' . lcfirst(date('F', mktime(0, 0, 0, $package['month'], 1, 1))) . '_' . $package['year'] . '.png"/>' : 'This package') . ' can be opened to pick one of these items:<br/>';
		
		$content = Database::selectMultiple("SELECT item_id FROM packages_content WHERE package_id=?", array($package['id']));
		foreach($content as $cont)
		{
			// get item data
			$item = AppAvatar::itemData((int) $cont['item_id'], "id,title,position,gender");

			// Get list of colors
			$colors	= AppAvatar::getItemColors($item['position'], $item['title'], $avatarData['gender']);
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
		<br/><a href="/exotic-open/' . $package['id'] . '/' . $item['id'] . '">Choose ' . $item['title'] . '</a>';
			if(AppAvatar::checkOwnItem(Me::$id, (int) $item['id']))
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
	$package = Database::selectOne("SELECT id, title, year, month FROM packages INNER JOIN user_packages ON packages.id=user_packages.package_id WHERE uni_id=? AND id=?", array(Me::$id, (int) $url[1]));
	
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
	<form class="uniform" method="post">' . Form::prepare("exotic-open") . '
		<input type="submit" name="submit" value="Choose ' . $item['title'] . '" />
	</form>';
}

	echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");