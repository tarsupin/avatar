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
	if(!AppAvatar::checkOwnItem(Me::$id, (int) $cont))
	{
		Alert::error("Not Owned", "You do not have all items that came from this wrapper.");
		break;
	}
}

// Run Action
if(Form::submitted("wrapper-close"))
{
	if(!Alert::hasErrors())
	{
		Database::startTransaction();
		
		// give wrapper
		if(!AppAvatar::receiveItem(Me::$id, $url[1], "Closed Wrapper"))
		{
			Database::endTransaction(false);
			Alert::error("Wrapper Not Given", "Something went wrong. The wrapper could not be closed.");
		}	
	
		// remove content
		if(!Alert::hasErrors())
		{
			foreach($wrap['content'] as $item)
			{
				if(!AppAvatar::dropItem(Me::$id, (int) $item, "Closed Wrapper"))
				{
					Database::endTransaction(false);
					Alert::error("Wrapper Not Given", "Something went wrong. The wrapper could not be closed.");
					break;
				}
			}
		}
		if(!Alert::hasErrors())
		{
			Database::endTransaction();
			Alert::success("Received " . $details[$url[1]]['title'], 'You have received the wrapper ' . $details[$url[1]]['title'] . '! [' . $details[$url[1]]['position'] . ', ' . ($details[$url[1]]['gender'] == "b" ? 'both genders' : ($details[$url[1]]['gender'] == "m" ? 'male' : 'female')) . ']');
			foreach($wrap['content'] as $item)
			{
				$item = $details[$item];
				Alert::info("Removed " . $item['title'], $item['title'] . ' has been removed from your inventory.');
			}
		}
		
		// Check again if you own all items
		foreach($wrap['content'] as $cont)
		{
			if(!AppAvatar::checkOwnItem(Me::$id, (int) $cont))
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
Alert::display() . '
<div class="overwrap-box">
	<div class="overwrap-line">Re-Wrap Wrapper</div>
	<div class="inner-box">';

// get images
foreach($details as $key => $item)
{
	$html = '';

	if($item['gender'] == "b" || $item['gender'] == $avatarData['gender'])	{ $gender = $avatarData['gender_full']; }
	else	{ $gender = ($avatarData['gender'] == "m" ? "female" : "male"); }
	
	// Get list of colors
	$colors	= AppAvatar::getItemColors($item['position'], $item['title'], $gender);				
	if(!$colors) { continue; }
	
	// Display the Item					
	$html .= '
	<div class="item_block">
		<a href="javascript: review_item(\'' . $item['id'] . '\');"><img id="img_' . $item['id'] . '" src="/avatar_items/' . $item['position'] . '/' . $item['title'] . '/default_' . $gender . '.png" /></a><br />' . $item['title'] . ($item['id'] == $url[1] ? ' (Wrapper)' : '') . '<br/>
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
	<p>A wrapper is an item that can be "opened" to receive other items from "inside" it. Here you can undo the opening process, provided you have all the items that came from the wrapper.</p>';
	
// output form
echo '
<h3>' . $details[$url[1]]['title'] . '</h3>';
foreach($wrap['content'] as $cont)
{
	$cont = (int) $cont;
	echo $details[$cont]['html'];
}
echo ' will be replaced with:<br/>
' . $details[$url[1]]['html'];
echo '
<br/>
<form class="uniform" method="post">' . Form::prepare("wrapper-close") . '
	<input type="submit" name="open" value="Re-Wrap ' . $details[$url[1]]['title'] . '"/>
</form>';
	
echo '
	</div>
</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
