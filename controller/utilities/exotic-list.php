<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/utilities/exotic-list");
}

// Return home if you don't have an avatar
if(!isset($avatarData['base']))
{
	header("Location: /create-avatar"); exit;
}

// get page
if(!isset($url[2]) || $url[2] < 2009 || $url[2] > date("Y"))	{ $url[2] = date("Y"); }
else															{ $url[2] = (int) $url[2]; }

// Set page title
$config['pageTitle'] = "Utilities > List of Exotic Packages (" . $url[2] . ")";

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
	<h2><a href="/utilities">Utilities</a> > List of Exotic Packages (' . $url[2] . ')</h2>';

// Attempt to load the cached version of this page
$cachedPage = "exotic_" . $url[2];

if(!CacheFile::load($cachedPage, 0, true))
{
	$html = "";
	$space = false;
	
	// get packages
	$packages = Database::selectMultiple("SELECT * FROM packages WHERE year=? ORDER BY month DESC", array($url[2]));

	// get item info
	foreach($packages as $key => $package)
	{
		if($space) { $html .= '<div class="spacer-giant"></div>'; }
		$space = true;
	
		$html .= '
		<h3>' . $package['title'] . '</h3>
		<span style="color:#fb7c7c;"><img src="assets/exotic_packages/' . lcfirst(date('F', mktime(0, 0, 0, $package['month'], 1, 1))) . '_' . $package['year'] . '.png"/> can be opened to pick one of these items:</span><br/>';
	
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
				</select>';
			$html .= '
			</div>';
		}
	}

	// Load the cache now that it's been saved
	CacheFile::save($cachedPage, $html);
	CacheFile::load($cachedPage);
}

if($url[2] > 2009 or $url[2] < date("Y"))
{
	echo '
<br/>';
	if($url[2] < date("Y"))
	{
		echo '
<a href="utilities/exotic-list/' . ($url[2]+1) . '">Newer <span class="icon-arrow-left"></span></a>';
	}
	if($url[2] > 2009)
	{
		echo '
<a href="utilities/exotic-list/' . ($url[2]-1) . '"><span class="icon-arrow-right"> Older</span></a>';
	}
}

	echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
