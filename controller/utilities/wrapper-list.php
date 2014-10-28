<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/utilities/wrappers-list");
}

// Return home if you don't have an avatar
if(!isset($avatarData['base']))
{
	header("Location: /create-avatar"); exit;
}

// get page
if(!isset($url[2]))	{ $url[2] = 0; }
else				{ $url[2] = (int) $url[2]; }

// get wrappers
$wrappers = Database::selectMultiple("SELECT id, content, replacement FROM wrappers ORDER BY id DESC LIMIT " . (20*$url[2]) . ", 20", array());

// get item info
// doing this here to avoid potential duplicates
$details = array();
foreach($wrappers as $key => $wrap)
{
	$wrap['id'] = (int) $wrap['id'];
	$wrap['replacement'] = (int) $wrap['replacement'];
	if(!isset($details[$wrap['id']]))
	{
		$details[$wrap['id']] = AppAvatar::itemData($wrap['id'], "id,title,position,gender");
	}
	$wrap['content'] = explode(",", $wrap['content']);
	if($wrap['replacement'] != 0)
	{
		$wrap['content'][] = $wrap['replacement'];
	}
	unset($wrappers[$key]['replacement']);
	$wrappers[$key]['content'] = $wrap['content'];
	foreach($wrap['content'] as $cont)
	{
		$cont = (int) $cont;
		if(!isset($details[$cont]))
		{
			$details[$cont] = AppAvatar::itemData($cont, "id,title,position,gender");
		}
	}
}

// Set page title
$config['pageTitle'] = "Utilities > List of Wrappers";

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

// get images
foreach($details as $key => $item)
{
	$html = '';

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
	$details[$key]['html'] = $html;
}

echo '
	<h2><a href="/utilities">Utilities</a> > List of Wrappers</h2>';

$space = false;
	
// output lists
foreach($wrappers as $wrap)
{
	if($space) { echo '<div class="spacer-giant"></div>'; }
	$space = true;
	
	echo '
	<h3>' . $details[$wrap['id']]['title'] . '</h3>
	If you own this wrapper, you can <a href="/utilities/wrapper-open/' . $wrap['id'] . '">open it here</a>.<br/>
	If you have opened this wrapper before and still have its contents, you can <a href="/utilities/wrapper-close/' . $wrap['id'] . '">re-wrap it here</a>.<br/>
	' . $details[$wrap['id']]['html'] . ' can be replaced with:<br/>';
	foreach($wrap['content']	as $cont)
	{
		echo $details[$cont]['html'];
	}
}

	if($url[2] > 0 or isset($wrappers[19]))
	{
		echo '
	<div class="spacer-huge"></div>';
		if($url[2] > 0)
		{
			echo '
	<a href="utilities/wrapper-list/' . ($url[2]-1) . '">Newer <span class="icon-arrow-left"></span></a>';
		}
		if(isset($wrappers[19]))
		{
			echo '
	<a href="utilities/wrapper-list/' . ($url[2]+1) . '"><span class="icon-arrow-right"> Older</span></a>';
		}
	}
	
echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
