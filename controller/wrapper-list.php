<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/wrapper-list");
}

// Return home if you don't have an avatar
if(!isset($avatarData['base']))
{
	header("Location: /create-avatar"); exit;
}

// get page
if(!isset($url[1]))	{ $url[1] = 0; }
else				{ $url[1] = (int) $url[1]; }

// get wrappers
$wrappers = Database::selectMultiple("SELECT id, content, replacement FROM wrappers ORDER BY id DESC LIMIT " . (20*$url[1]) . ", 20", array());

// get item info
$ids = array();
foreach($wrappers as $key => $wrap)
{
	$ids[] = (int) $wrap['id'];
	$wrap['content'] = explode(",", $wrap['content']);
	if($wrap['replacement'] != 0)
	{
		$wrap['content'][] = $wrap['replacement'];
	}
	unset($wrappers[$key]['replacement']);
	foreach($wrap['content'] as $cont)
	{
		$ids[] = (int) $cont;
	}	
	$wrappers[$key]['content'] = $wrap['content'];
}
$ids = array_unique($ids);

$details = array();
$details2 = Database::selectMultiple("SELECT id, title, position, gender FROM items WHERE id IN (" . implode(",", $ids) . ")", array());
foreach($details2 as $val)
{
	$details[$val['id']] = $val;
}
unset($details2);

// Set page title
$config['pageTitle'] = "List of Wrappers";

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
Alert::display() . '
<div class="overwrap-box">
	<div class="overwrap-line">List of Wrappers</div>
	<div class="inner-box">';

// get images
foreach($details as $key => $item)
{
	$html = '';

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
		</select><br/>' . $item['title'];
	$html .= '
	</div>';
	$details[$key]['html'] = $html;
}

echo '
	<p>A wrapper is an item that can be "opened" to receive other items from "inside" it.</p>';

$space = false;
	
// output lists
foreach($wrappers as $wrap)
{
	if($space) { echo '<div class="spacer"></div>'; }
	$space = true;
	
	echo '
	<h3>' . $details[$wrap['id']]['title'] . '</h3>
	If you own this wrapper, you can <a href="/wrapper-open">open it here</a>.<br/>
	If you have opened this wrapper before and still have its contents, you can <a href="/wrapper-close/' . $wrap['id'] . '">re-wrap it here</a>.<br/>
	<span class="spoiler-header" onclick="$(this).next().slideToggle(\'slow\');">' . $details[$wrap['id']]['html'] . ' can be replaced with:</span><div class="spoiler-content">';
	foreach($wrap['content']	as $cont)
	{
		echo $details[$cont]['html'];
	}
	echo '</div>';
}

	if($url[1] > 0 or isset($wrappers[19]))
	{
		echo '
	<div class="spacer"></div>';
		if($url[1] > 0)
		{
			echo '
	<a href="/wrapper-list/' . ($url[1]-1) . '">Newer <span class="icon-arrow-left"></span></a>';
		}
		if(isset($wrappers[19]))
		{
			echo '
	<a href="/wrapper-list/' . ($url[1]+1) . '"><span class="icon-arrow-right"> Older</span></a>';
		}
	}
	
echo '
	</div>
</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
