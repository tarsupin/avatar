<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Return home if you don't have an avatar
if(!isset($avatarData['base']))
{
	header("Location: /create-avatar"); exit;
}

if(Form::submitted("share-personal"))
{
	$user = Sanitize::variable($_POST['sharewith']);
	$recipientID = User::getDataByHandle($user);
	if($recipientID == array())
	{
		Alert::error("Recipient Missing", $user . " does not use the avatar system.");
	}
	else
	{
		$recipientID = (int) $recipientID['uni_id'];
		$duration = max(min($_POST['duration'], 30), 1);
		$duration = $duration * 86400;	
		$confirm = Confirm::create("share-equip-" . Me::$id . "-" . $recipientID, array(), $duration);
		// link already exists
		if(!$confirm)
		{
			Alert::info("Allowed", $user . " already has permission to view your equipment. You can grant them permission again after it has expired.");
		}
		else
		{
			Alert::success("Allowed", $user . " now has permission to view your equipment.");
		}
		if(rand(1, 100) == 2)	{ $confirm->purge(); }
	}
}

if(Form::submitted("share-everyone"))
{
	$recipientID = 0;

	$duration = max(min($_POST['duration'], 30), 1);
	$duration = $duration * 86400;	
	$confirm = Confirm::create("share-equip-" . Me::$id . "-0", array(), $duration);
	// link already exists
	if(!$confirm)
	{
		Alert::info("Allowed", "Everyone already has permission to view your equipment. You can grant them permission again after it has expired.");
	}
	else
	{
		Alert::success("Allowed", "Everyone may view your equipment now.");
	}
	if(rand(1, 100) == 2)	{ $confirm->purge(); }
}

if($link = Link::clicked())
{
	if($link == "share-equipment-not")
	{
		if($_GET['remove'] != "0")
		{
			$user = Sanitize::variable($_GET['remove']);
			$recipientID = User::getDataByHandle($user);
			$recipientID = (int) $recipientID['uni_id'];
		}
		else
		{
			$user = "Everyone";
			$recipientID = 0;
		}
		
		$confirm = new Confirm("share-equip-" . Me::$id . "-" . $recipientID);
		if($confirm->delete())
		{
			Alert::success("Permission Removed", $user . " may no longer view your equipment.");
		}
		if(rand(1, 100) == 2)	{ $confirm->purge(); }
	}
}

// Set page title
$config['pageTitle'] = "Share Equipment List";

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
	<div class="overwrap-line">Share Equipment List</div>
	<div class="inner-box">
		<p>In order to let others view your equipment list, you need to share <a href="/view-equipment/' . Me::$vals['handle'] . '">this link</a> and grant permission using this form.<br/>
		Any given permission will work for the set number of days unless you remove it below.</p>
		<br/>
		<form class="uniform" method="post">' . Form::prepare("share-personal") . '
			<p>I want to allow <input type="text" name="sharewith"/> to see a list of my equipment for a duration of <input type="number" min="1" max="30" name="duration" value="30" size="2"/> (1-30) days.</p>
			<p><input type="submit" value="Grant Permission"/></p>
		</form>
		<br/>
		<form class="uniform" method="post">' . Form::prepare("share-everyone") . '
			<p>I want to allow everyone to see a list of my equipment for a duration of <input type="number" min="1" max="30" name="duration" value="30" size="2"/> (1-30) days.</p>
			<p><input type="submit" value="Grant Permission"/></p>
		</form>
	</div>
</div>
<div class="overwrap-box">
	<div class="overwrap-line">Sharing With</div>
	<div class="inner-box">
		<ul>';

$given = Database::selectMultiple("SELECT confirm_val, date_expires FROM confirm_values", array());
foreach($given as $g)
{
	if(strpos($g['confirm_val'], "share-equip-" . Me::$id . "-") !== false)
	{
		$recipientID = (int) substr($g['confirm_val'], strrpos($g['confirm_val'], "-")+1);
		if($recipientID != 0)
		{
			$recipient = User::get($recipientID, "handle");
			$recipient = $recipient['handle'];
			echo '
			<li><a href="/share-equipment?remove=' . $recipient . '&' . Link::prepare("share-equipment-not") . '">&#10006;</a> ' . $recipient . ' (expires ' . Time::fuzzy($g['date_expires']) . ')</li>';
		}
		else
		{
			echo '
			<li><a href="/share-equipment?remove=0&' . Link::prepare("share-equipment-not") . '">&#10006;</a> Everyone (expires ' . Time::fuzzy($g['date_expires']) . ')</li>';
		}
	}
}

echo '
		</ul>
	</div>
</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");

