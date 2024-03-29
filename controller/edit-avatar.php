<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/edit-avatar");
}

// Make sure you don't already have an avatar
if(!isset($avatarData['base']))
{
	header("Location: /create-avatar"); exit;
}

// Set page title
$config['pageTitle'] = "Edit Avatar";

// change avatar name
if(isset($_POST['aviname']))
{
	if(Form::submitted("editname"))
	{
		$sanitized = trim(Sanitize::safeword($_POST['aviname']));
		if($sanitized != $_POST['aviname'])
		{
			Alert::info("Sanitized Name", "Your chosen name contained characters that are not allowed. Those characters have been removed.");
		}
		if(Database::query("UPDATE avatars SET name=? WHERE uni_id=? AND avatar_id=? LIMIT 1", array($sanitized, Me::$id, $avatarData['avatar_id'])))
		{
			Alert::success("Name Changed", "Your avatar's name has been set.");
			$avatarData['name'] = $sanitized;
		}
		else
		{
			Alert::error("Name Not Changed", "Your avatar's name could not be set.");
		}
	}
}

// Prepare Values
$races = array("white", "tan", "pacific", "dark", "light", "gray");

// Check if a base was chosen
if(isset($url[1]) && isset($url[2]))
{
	// Check if the values are legitimate
	if(in_array($url[1], array("male", "female")) && in_array($url[2], $races))
	{
		// Edit Your Avatar
		if(AppAvatar::editAvatar(Me::$id, $url[2], $url[1], $activeAvatar))
		{
			Alert::saveSuccess("Avatar Edited", "You have switched to a " . $url[1] . " " . $url[2] . " base!");
			
			header("Location: /dress-avatar"); exit;
		}
		else
		{
			Alert::error("Avatar Edit Failed", "Base couldn't be changed!");
		}
	}
}

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

echo '
<div id="panel-right"></div>
<div id="content">' . Alert::display() . '
<div class="overwrap-box">
	<div class="overwrap-line">Edit Your Avatar</div>
	<div class="inner-box">
	<p>You can set or change your avatar\'s name.</p>
	<form class="uniform" method="post">' . Form::prepare("editname") . '
		<p><input type="text" name="aviname" maxlength="20" value="' . $avatarData['name'] . '"/> (max 20 characters)</p>
		<p><input type="submit" value="Set Name"/></p>
	</form>
	<br/>
	<p>Please select the avatar base that you would like to use! Currently you are using a <strong>' . $avatarData['gender_full'] . ' ' . $avatarData['base'] . '</strong> base.<br/>Changing the skin color costs 30 Auro. Changing the gender costs 1000 Auro.</p>';
		
	foreach($races as $race)
	{
		echo '
		<a href="/edit-avatar/male/' . $race . '"><img src="/assets/create-avatar/male_' . $race . '.png" style="width:120px;" onclick="return confirm(\'Are you sure you want to use this base?\');"/></a>
		<a href="/edit-avatar/female/' . $race . '"><img src="/assets/create-avatar/female_' . $race . '.png" style="width:120px;" onclick="return confirm(\'Are you sure you want to use this base?\');"/></a>';
	}
	
	echo '
	</div>
</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
