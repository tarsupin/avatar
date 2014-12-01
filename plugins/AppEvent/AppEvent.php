<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

----------------------------------------
------ About the AppEvent Plugin ------
----------------------------------------

This class provides handling of events, such as calendars and raffles.

-------------------------------
------ Methods Available ------
-------------------------------

AppEvent::createCalendar("Christmas 2014", 2014, 12, 1, 24);
AppEvent::editCalendarDay(1, 2014, 6, 5, array(100,101,102));
AppEvent::getCalendarEntry(1, 2014, 6, 5);
AppEvent::claimCalendarItem(1, 102);
AppEvent::pruneCalendar(1);

*/

abstract class AppEvent {
	
	
/****** Create Calendar ******/
	public static function createCalendar
	(
		$title			// <str> The name of the calendar.
	,	$startyear		// <int> The start year of the calendar.
	,	$startmonth		// <int> The start month of the calendar.
	,	$startday		// <int> The start day of the calendar.
	,	$duration		// <int> The duration of the calendar in days.
	)					// RETURNS <int> the ID of the new calendar, or 0 if failed.
	
	// $calID = AppEvent::createCalendar("Christmas 2014", 2014, 12, 1, 24);
	{
		// insert calendar itself
		$start = (int) mktime(0, 0, 0, $startmonth, $startday, $startyear);

		if(Database::query("INSERT INTO event_calendar (title, start, duration) VALUES (?, ?, ?)", array($title, $start, $duration)))
		{
			$cal_id = Database::$lastID;

			// insert placeholders for the single days
			Database::startTransaction();
			for($i=0; $i<$duration; $i++)
			{
				$time = $start+$i*86400;
				Database::query("INSERT INTO event_calendar_content (cal_id, year, doy) VALUES (?, ?, ?)", array($cal_id, (int) date("Y", $time), (int) date("z", $time)));
			}
			Database::endTransaction();
			
			return $cal_id;
		}
		
		return 0;	
	}
	
/****** Edit Calendar Entry ******/
	public static function editCalendarDay
	(
		$cal_id			// <int> The ID of the calendar.
	,	$year			// <int> The year of the day to edit.
	,	$month			// <int> The month of the day to edit.
	,	$day			// <int> The day to edit.
	,	$items			// <int:int> The IDs of items to make available on this day.
	)					// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// AppEvent::editCalendarDay(1, 2014, 6, 5, array(100,101,102));
	{
		$time = (int) mktime(0, 0, 0, $month, $day, $year);
		return Database::query("UPDATE event_calendar_content SET items=? WHERE cal_id=? AND year=? AND doy=? LIMIT 1", array(implode(",", $items), $cal_id, (int) date("Y", $time), (int) date("z", $time)));
	}
	
/****** Get available items from Calendar ******/
	public static function getCalendarEntry
	(
		$cal_id		// <int> The ID of the calendar.
	,	$year		// <int> The year of the day to retrieve.
	,	$month		// <int> The month of the day to retrieve.
	,	$day		// <int> The day to retrieve.
	)				// RETURNS <int:int> a list of available items.
	
	// AppEvent::getCalendarEntry(1, 2014, 6, 5);
	{
		$time = (int) mktime(0, 0, 0, $month, $day, $year);
		if($items = Database::selectOne("SELECT items FROM event_calendar_content WHERE cal_id=? AND year=? AND doy=? LIMIT 1", array($cal_id, (int) date("Y", $time), (int) date("z", $time))))
		{
			if($items['items'] != '')
			{
				return explode(",", $items['items']);
			}
		}
		return array();
	}
	
/****** Claim item from Calendar ******/
	public static function claimCalendarItem
	(
		$cal_id			// <int> The ID of the calendar.
	,	$item_id		// <int> The ID of the item to claim.
	,	$cal_title = ''	// <str> The name of the calendar to avoid unnecessary queries.
	)					// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// AppEvent::claimCalendarItem(1, 102);
	{
		// check availability
		$available = self::getCalendarEntry($cal_id, (int) date("Y"), (int) date("n"), (int) date("j"));
		if(!in_array($item_id, $available))
		{
			return false;
		}

		if($cal_title == '')
		{
			if(!$title = Database::selectOne("SELECT title FROM event_calendar WHERE cal_id=? LIMIT 1", array($cal_id)))
			{
				$cal_title = "Event Calendar";
			}
			else
			{
				$cal_title = $title['title'];
			}
		}

		
		$has = Database::selectOne("SELECT cal_id FROM event_calendar_log WHERE uni_id=? AND item_id=? LIMIT 1", array(Me::$id, $item_id));
		if($has != array())
		{
			return false;
		}
		
		// grant item
		Database::startTransaction();
		if(Database::query("INSERT INTO event_calendar_log VALUES (?, ?, ?, ?)", array(Me::$id, $item_id, Sanitize::punctuation($_SERVER['REMOTE_ADDR']), $cal_id)))
		{
			if(AppAvatar::receiveItem(Me::$id, $item_id, "Event Calendar: " . $cal_title))
			{
				Database::endTransaction();
				return true;
			}
		}
		Database::endTransaction(false);
		
		return false;
	}
	
/****** Prune data of past calendars ******/
	public static function pruneCalendar
	(
		$cal_id		// <int> The ID of the calendar.
	)				// RETURNS <void>
	
	// AppEvent::pruneCalendar(1);
	{
		if($cal = Database::selectOne("SELECT start, duration FROM event_calendar WHERE cal_id=? LIMIT 1", array($cal_id)))
		{
			// calendar has ended
			if($cal['start'] + $cal['duration']*86400 < time())
			{
				Database::startTransaction();
				$pass1 = Database::query("DELETE FROM event_calendar WHERE cal_id=? LIMIT 1", array($cal_id));
				$pass2 = Database::query("DELETE FROM event_calendar_content WHERE cal_id=?", array($cal_id));
				$pass3 = Database::query("DELETE FROM event_calendar_log WHERE cal_id=?", array($cal_id));
				if($pass1 && $pass2 && $pass3)
				{
					Database::endTransaction();
				}
				else
				{
					Database::endTransaction(false);
				}
			}
		}		
	}
	
}