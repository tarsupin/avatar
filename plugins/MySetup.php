<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

abstract class AppInstall {

/****** AppInstall Class ******
* This class handles all of the local setups during installation of the site. This includes any special scripts that
* need to be run, any additional tables that need to be included, etc.
* 
****** Methods Available ******
* AppInstall::run()
* AppInstall::user()
* AppInstall::search()
*/
	
	
/****** Run the local setup for this site ******/
	public static function run (
	)				// RETURNS <void>
	
	// AppInstall::run();
	{
		// Fast SQL
		AppAvatarAdmin::sql();
		
		// Search Setup
		self::search();
		
		// Site Connections
		Scripts::siteInsert("unijoule");
	}
	
	
/****** Run a special user function ******/
	public static function user (
	)				// RETURNS <void>
	
	// AppInstall::user();
	{
		// No special user function
	}
	
	
/****** Run search functionality / setup ******/
	public static function search (
	)				// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// AppInstall::search();
	{
		// Default Search Options
		Scripts::defaultSearch();
		Scripts::defaultCategorySearch();
		
		// Custom Search Options (for this site only)
		Search::$activeTable = "search_primary";
		
		Database::startTransaction();
		
		// Add Custom Options
		// Search::setKeyword("example",		"auth",		"/",					1);
		
		// End the Script
		return Database::endTransaction();
	}
	
}
