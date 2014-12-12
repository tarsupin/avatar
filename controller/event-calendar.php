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
	if(AppEvent::claimCalendarItem((int) $_POST['calendar'], (int) $_POST['item'], $_POST['title']))
	{
		Alert::success("Received", "You have received the gift!");
	}
	else
	{
		Alert::error("Not Received", "This item is not available, or you cannot receive the same gift more than once!");
	}
}

if(Form::submitted("new-calendar") && Me::$id >= 5)
{
	FormValidate::text("Title", $_POST['title'], 1, 255);
	FormValidate::number("Year", $_POST['year'], 2014);
	FormValidate::number("Month", $_POST['month'], 1, 12);
	FormValidate::number("Day", $_POST['day'], 1, 31);
	FormValidate::number("Duration", $_POST['duration'], 1);
	if(FormValidate::pass())
	{
		if(AppEvent::createCalendar($_POST['title'], (int) $_POST['year'], (int) $_POST['month'], (int) $_POST['day'], (int) $_POST['duration']))
		{
			Alert::success("Calendar Created", "The event calendar has been created.");
		}
	}
}

if(Form::submitted("edit-calendar-entry") && Me::$id >= 5)
{
	$items = array();
	$_POST['items'] = explode(",", $_POST['items']);
	foreach($_POST['items'] as $item)
	{
		$item = (int) $item;
		if($item > 0)
		{
			$items[] = (int) $item;
		}
	}	
	
	if(AppEvent::editCalendarDay((int) $_POST['calendar'], (int) $_POST['year'], (int) $_POST['month'], (int) $_POST['day'], $items))
	{
		Alert::success("Calendar Edited", "The entry has been edited.");
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
				$item = AppAvatar::itemData($a);
				
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
				<input type="hidden" name="title" value="' . $cal['title'] . '"/>
				<input type="hidden" name="calendar" value="' . $cal['cal_id'] . '"/>
				<input type="submit" value="Receive" onclick="return confirm(\'Are you sure you want to receive this gift?\');"/>
			</form>
		</div>';
			}
		}
	}
	// leave data for a month to allow checks by staff
	elseif($cal['start'] + ($cal['duration'] + 30) * 86400 <= time())
	{
		AppEvent::pruneCalendar((int) $cal['id']);
	}
}

echo '
	</div>
</div>';

// Calendar Management
if(Me::$id >= 5)
{
	echo '
<div class="overwrap-box">
<div class="overwrap-line">New Event Calendar</div>
	<div class="inner-box">
		<form class="uniform" method="post">' . Form::prepare("new-calendar") . '
			<p><input type="text" name="title" maxlength="255"/> title</p>
			<p><input type="number" name="year" maxlength="4" min="2014" value="' . date("Y") . '"/> start year</p>
			<p><input type="number" name="month" maxlength="2" min="1" max="12" value="' . date("n") . '"/> start month</p>
			<p><input type="number" name="day" maxlength="2" min="1" max="31" value="' . date("j") . '"/> start day</p>
			<p><input type="number" name="duration" min="1"/> duration in days</p>
			<p><input type="submit" value="Create Calendar"/></p>
		</form>
	</div>
</div>

<div class="overwrap-box">
<div class="overwrap-line">Edit Event Calendar Entries</div>
	<div class="inner-box">';
foreach($calendars as $cal)
{
	echo '<h3>' . $cal['title'] . '</h3>';
	for($i=0; $i<$cal['duration']; $i++)
	{
		$time = $cal['start']+$i*86400;
		if($time >= time())
		{
			$entry = AppEvent::getCalendarEntry((int) $cal['cal_id'], (int) date("Y", $time), (int) date("n", $time), (int) date("j", $time));
			echo '
		<form class="uniform" method="post">' . Form::prepare("edit-calendar-entry") . '
			<input type="hidden" name="calendar" value="' . $cal['cal_id'] . '"/>
			<input type="hidden" name="year" value="' . date("Y", $time) . '"/>
			<input type="hidden" name="month" value="' . date("n", $time) . '"/>
			<input type="hidden" name="day" value="' . date("j", $time) . '"/>
			<p><input type="text" name="items" value="' . implode(",", $entry) . '" maxlength="255"> ' . date("M j", $time) . ' <input type="submit" value="Edit Entry"/></p>
		</form>';
		}
	}
}
echo '
	</div>
</div>';
}
echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");