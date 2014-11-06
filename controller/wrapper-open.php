<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/wrapper-open");
}

// Create avatar if you don't have one
if(!isset($avatarData['base']))
{
	header("Location: /create-avatar"); exit;
}

// get owned wrappers
$owned = Database::selectMultiple("SELECT id, content, replacement, COUNT(id) AS c FROM wrappers INNER JOIN user_items on wrappers.id=user_items.item_id WHERE uni_id=? GROUP BY id", array(Me::$id));

// get item info
$details = array();
foreach($owned as $key => $own)
{
	$own['id'] = (int) $own['id'];
	$own['replacement'] = (int) $own['replacement'];
	if(!isset($details[$own['id']]))
	{
		$details[$own['id']] = AppAvatar::itemData($own['id'], "id,title,position,gender");
	}
	$own['content'] = explode(",", $own['content']);
	if($own['replacement'] != 0)
	{
		$own['content'][] = $own['replacement'];
	}
	unset($owned[$key]['replacement']);
	$owned[$key]['content'] = $own['content'];
	foreach($own['content'] as $cont)
	{
		$cont = (int) $cont;
		if(!isset($details[$cont]))
		{
			$details[$cont] = AppAvatar::itemData($cont, "id,title,position,gender");
		}
	}
}

// Run Action
if(Form::submitted("wrapper-open"))
{
	if(FormValidate::pass())
	{
		// check ownership
		$_POST['id'] = (int) $_POST['id'];
		$has = false;
		foreach($owned as $own)
		{
			if($own['id'] == $_POST['id'])
			{
				$detail = $own;
				$has = true;
				break;
			}
		}		
		if(!$has)
		{
			Alert::error("Not Owned", "You do not own this wrapper!");
		}
		else
		{
			// give content
			foreach($detail['content'] as $item)
			{
				$item = $details[$item];
				if(AppAvatar::receiveItem(Me::$id, $item['id'], "Opened Wrapper"))
				{
					Alert::success("Received " . $item['title'], 'You have received ' . $item['title'] . '! [' . $item['position'] . ', ' . ($item['gender'] == "b" ? 'both genders' : ($item['gender'] == "m" ? 'male' : 'female')) . ']');
				}
				else
				{
					Alert::error("Not Received " . $item['title'], "Something went wrong. You did not receive " . $item['title'] . ".");
				}
			}
			// remove wrapper
			if(!Alert::hasErrors())
			{
				if(AppAvatar::dropItem(Me::$id, $_POST['id'], "Opened Wrapper"))
				{
					foreach($owned as $key => $own)
					{
						if($own['id'] == $_POST['id'])
						{
							if($own['c'] < 2)	{ unset($owned[$key]); }
							else				{ $owned[$key]['c'] -= 1; }
							break;
						}
					}		
					Alert::info("Wrapper Removed", "The wrapper has been removed from your inventory.");
				}
				else
				{
					Alert::error("Wrapper Not Removed", "Something went wrong. The wrapper could not be removed from your inventory.");
				}
			}
		}
	}
}

// Set page title
$config['pageTitle'] = "Open Wrapper";

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
	else	{ $gender = ($avatarData['gender'] == "m" ? "female" : "male"); }
	
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
	<h2>Open Wrapper</h2>
	<p>A wrapper is an item that can be "opened" to receive other items from "inside" it. The wrapper itself disappears in the process, but a replacement that either is identical or can be combined to look identical is given, so you lose nothing.<br/>Click on the text with the dotted border to toggle the wrapper\'s content in/out of view.</p>';

$space = false;
	
// output forms
foreach($owned as $own)
{
	if($space) { echo '<div class="spacer-giant"></div>'; }
	$space = true;
	
	echo '
	<h3>' . $details[$own['id']]['title'] . ($own['c'] > 1 ? ' (' . $own['c'] . ')' : '') . '</h3>
	' . $details[$own['id']]['html'] . ' <span class="spoiler-header" onclick="$(this).next().slideToggle(\'slow\');">will be replaced with:</span><div class="spoiler-content">';
	foreach($own['content']	as $cont)
	{
		echo $details[$cont]['html'];
	}
	echo '
	</div>
	<form class="uniform" method="post">' . Form::prepare("wrapper-open") . '
		<input type="hidden" name="id" value="' . $own['id'] . '"/>
		<input type="submit" name="open" value="Open ' . $details[$own['id']]['title'] . '"/>
	</form>';
}	
	
echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
