<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/exotic-list");
}

// Return home if you don't have an avatar
if(!isset($avatarData['base']))
{
	header("Location: /create-avatar"); exit;
}

// get page
if(!isset($url[1]) || $url[1] < 2009 || $url[1] > date("Y"))	{ $url[1] = date("Y"); }
else															{ $url[1] = (int) $url[1]; }

// Set page title
$config['pageTitle'] = "List of Exotic Packages (" . $url[1] . ")";

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
	<h2>List of Exotic Packages (' . $url[1] . ')</h2>
	<p>Click on the text with the dotted border to toggle the package\'s content in/out of view.</p>';

// Attempt to load the cached version of this page
$cachedPage = "exotic_" . $url[1] . "_" . $avatarData['gender'];

if(CacheFile::load($cachedPage, 86400, true) === false)
{
	$html = "";
	$space = false;
	
	// get packages
	if($url[1] == date("Y"))
	{
		$packages = Database::selectMultiple("SELECT * FROM packages WHERE year=? AND month<=? ORDER BY month DESC", array($url[1], (int) date("n")));
	}
	else
	{
		$packages = Database::selectMultiple("SELECT * FROM packages WHERE year=? ORDER BY month DESC", array($url[1]));
	}

	// get item info
	foreach($packages as $key => $package)
	{
		if($space) { $html .= '<div class="spacer"></div>'; }
		$space = true;

		if($package['title'] == '')
		{
			$package['title'] = date("F", mktime(0, 0, 0, $package['month'], 1)) . ' Package';
		}
		
		$html .= '
		<h3>' . $package['title'] . '</h3>
		If you own this package, you can <a href="/exotic-open/' . $package['id'] . '">open it here</a>.<br/>' . (File::exists('assets/exotic_packages/' . lcfirst(date("F", mktime(0, 0, 0, $package['month'], 1))) . '_' . $package['year'] . '.png') ? '<img src="assets/exotic_packages/' . lcfirst(date("F", mktime(0, 0, 0, $package['month'], 1))) . '_' . $package['year'] . '.png"/>' : 'This package') . ' <span class="spoiler-header" onclick="$(this).next().slideToggle(\'slow\');">can be opened to receive one of these items:</span><div class="spoiler-content"' . ($package['year'] == date("Y") && $package['month'] == date("n") ? ' style="display:block;"' : "") . '>';
	
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
			$html .= '
			<div class="item_block">
				<a href="javascript: review_item(\'' . $item['id'] . '\');"><img id="img_' . $item['id'] . '" src="/avatar_items/' . $item['position'] . '/' . $item['title'] . '/default_' . $gender . '.png" /></a><br />
				<select id="item_' . $item['id'] . '" onChange="switch_item(\'' . $item['id'] . '\', \'' . $item['position'] . '\', \'' . $item['title'] . '\', \'' . $gender . '\');">';
				
				foreach($colors as $color)
				{
					$html .= '
					<option name="' . $color . '">' . $color . '</option>';
				}
				
			$html .= '
				</select>
				' . $item['title'] . '
			</div>';
		}
		$html .= '</div>';
	}

	// Load the cache now that it's been saved
	CacheFile::save($cachedPage, $html);
	echo CacheFile::load($cachedPage);
}

if($url[1] > 2009 or $url[1] < date("Y"))
{
	echo '
<div class="spacer"></div>';
	if($url[1] < date("Y"))
	{
		echo '
<a href="/exotic-list/' . ($url[1]+1) . '">Newer <span class="icon-arrow-left"></span></a>';
	}
	if($url[1] > 2009)
	{
		echo '
<a href="/exotic-list/' . ($url[1]-1) . '"><span class="icon-arrow-right"> Older</span></a>';
	}
}

	echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
