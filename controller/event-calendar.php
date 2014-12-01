<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/event-calendar");
}

// Return home if you don't have an avatar
if(!isset($avatarData['base']))
{
	header("Location: /create-avatar"); exit;
}

if(Form::submitted("receive-gift"))
{
	if(AppEvent::claimCalendarItem((int) $_POST['calendar'], (int) $_POST['item']))
	{
		Alert::success("Received", "You have received the gift!");
	}
	else
	{
		Alert::error("Not Received", "This item is not available, or you cannot receive the same gift more than once!");
	}
}

// Set page title
$config['pageTitle'] = "Event Calendar";

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

// display data form
echo '
<div id="panel-right"></div>
<div id="content">' . Alert::display() . '	
<div class="overwrap-box">
	<div class="overwrap-line">Event Calendar</div>
	<div class="inner-box">
		<p>Event calendars provide items for free. When any items are available, they are listed below. You can receive each item exactly once. Usually the available items change daily.</p>
		<p>Since this makes the items gifts from the staff, there is one rule to remember: Do not attempt to gain extras through mule accounts (secondary accounts belonging to the same person)! Ninjas and similar non-profit accounts are excempt from this rule.</p>';

$wrappers = AppAvatar::wrappers();
$calendars = Database::selectMultiple("SELECT * FROM event_calendar WHERE 1", array());
foreach($calendars as $cal)
{
	if($cal['start'] <= time() && $cal['start']+$cal['duration']*86400 > time())
	{
		$available = AppEvent::getCalendarEntry((int) $cal['cal_id'], (int) date("Y"), (int) date("n"), (int) date("j"));
		if($available != array())
		{
			echo '
		<h3>' . $cal['title'] . '</h3>';
			foreach($available as $a)
			{
				$item = AppAvatar::itemData((int) $a);
				
				if($item['gender'] == "b" || $item['gender'] == $avatarData['gender'])	{ $gender = $avatarData['gender_full']; }
				else	{ $gender = ($item['gender'] == "m" ? "male" : "female"); }
				
				// Get list of colors
				$colors	= AppAvatar::getItemColors($item['position'], $item['title'], $gender);				
				if(!$colors) { continue; }

				// Display the Item					
				echo '
		<div class="item_block' . ($gender != $avatarData['gender_full'] ? ' opaque' : '') . '">
			<a href="javascript: review_item(\'' . $item['id'] . '\');"><img id="img_' . $item['id'] . '" src="/avatar_items/' . $item['position'] . '/' . $item['title'] . '/default_' . $gender . '.png" /></a><br />
			' . $item['title'] . (in_array($item['id'], $wrappers) ? ' (Wrapper)' : '') . (AppAvatar::checkOwnItem(Me::$id, (int) $item['id']) ? ' [&bull;]' : '') . '<br/>
			<select id="item_' . $item['id'] . '" onChange="switch_item(\'' . $item['id'] . '\', \'' . $item['position'] . '\', \'' . $item['title'] . '\', \'' . $gender . '\');">';
					
					foreach($colors as $color)
					{
						echo '
				<option name="' . $color . '">' . $color . '</option>';
					}
					
					echo '
			</select>';
				echo '
			<br/>
			<form class="uniform" method="post">' . Form::prepare("receive-gift") . '
				<input type="hidden" name="item" value="' . $item['id'] . '"/>
				<input type="hidden" name="calendar" value="' . $cal['cal_id'] . '"/>
				<input type="submit" value="Receive" onclick="return confirm(\'Are you sure you want to receive this gift?\');"/>
			</form>
		</div>';
			}
		}
	}
	// leave data for a month to allow checks by staff
	elseif($cal['start'] <= time() && $cal['start']+$cal['duration']*86400 > time() + 30 * 86400)
	{
		AppEvent::pruneCalendar((int) $cal['id']);
	}
}

echo '
	</div>
</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");