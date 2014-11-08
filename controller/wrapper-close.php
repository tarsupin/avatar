<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/wrapper-list");
}

// Create avatar if you don't have one
if(!isset($avatarData['base']))
{
	header("Location: /create-avatar"); exit;
}

// Wrapper ID needs to be given
if(!isset($url[1]))
{
	header("Location: /wrapper-list"); exit;
}
$url[1] = (int) $url[1];

// Is wrapper?
$wrap = Database::selectOne("SELECT * FROM wrappers WHERE id=? LIMIT 1", array($url[1]));
if(!$wrap)
{
	header("Location: /wrapper-list"); exit;
}

// Get wrapper details
$details = array();
$wrap['replacement'] = (int) $wrap['replacement'];
$details[$url[1]] = AppAvatar::itemData($url[1], "id,title,position,gender");
$wrap['content'] = explode(",", $wrap['content']);
if($wrap['replacement'] != 0)
{
	$wrap['content'][] = $wrap['replacement'];
}
unset($wrap['replacement']);
$wrap['content'] = $wrap['content'];
foreach($wrap['content'] as $cont)
{
	$cont = (int) $cont;
	$details[$cont] = AppAvatar::itemData($cont, "id,title,position,gender");
}

// Check if you own all items
foreach($wrap['content'] as $cont)
{
	if(!AppAvatar::checkOwnItem(Me::$id, $cont))
	{
		Alert::error("Not Owned", "You do not have all items that came from this wrapper.");
		break;
	}
}

// Run Action
if(Form::submitted("wrapper-close"))
{
	if(FormValidate::pass() && !Alert::hasErrors())
	{
		Database::startTransaction();
		
		// give wrapper
		if(AppAvatar::receiveItem(Me::$id, $url[1], "Closed Wrapper"))
		{
			Alert::success("Received " . $details[$url[1]]['title'], 'You have received the wrapper ' . $details[$url[1]]['title'] . '! [' . $details[$url[1]]['position'] . ', ' . ($details[$url[1]]['gender'] == "b" ? 'both genders' : ($details[$url[1]]['gender'] == "m" ? 'male' : 'female')) . ']');
		}
		else
		{
			Alert::error("Wrapper Not Given", "Something went wrong. You did not receive " . $details[$url[1]]['title'] . ".");
			Database::endTransaction(false);
		}	
	
		// remove content
		if(!Alert::hasErrors())
		{
			foreach($wrap['content'] as $item)
			{
				$item = $details[$item];
				if(AppAvatar::dropItem(Me::$id, $item['id'], "Closed Wrapper"))
				{
					Alert::info("Removed " . $item['title'], $item['title'] . ' has been removed from your inventory.');
				}
				else
				{
					Alert::error("Not Removed " . $item['title'], "Something went wrong. " . $item['title'] . " could not be removed from your inventory.");
					Database::endTransaction(false);
				}
			}
		}
		if(!Alert::hasErrors())
		{
			Database::endTransaction();
		}
		
		// Check again if you own all items
		foreach($wrap['content'] as $cont)
		{
			if(!AppAvatar::checkOwnItem(Me::$id, $cont))
			{
				Alert::info("Not Owned", "You no longer have all items that came from this wrapper, so you cannot re-wrap another one.");
				break;
			}
		}
	}
}

// Set page title
$config['pageTitle'] = "Re-Wrap Wrapper";

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
	<h2>Re-Wrap Wrapper</h2>
	<p>A wrapper is an item that can be "opened" to receive other items from "inside" it. Here you can undo the opening process, provided you have all the items that came from the wrapper.</p>';
	
// output form
echo '
<h3>' . $details[$url[1]]['title'] . '</h3>';
foreach($wrap['content']	as $cont)
{
	echo $details[$cont]['html'];
}
echo ' will be replaced with:<br/>
' . $details[$url[1]]['html'];
echo '
<form class="uniform" method="post">' . Form::prepare("wrapper-close") . '
	<input type="submit" name="open" value="Re-Wrap ' . $details[$url[1]]['title'] . '"/>
</form>';
	
echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");