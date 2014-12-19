<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

class AppEvent_config {
	
	
/****** Plugin Variables ******/
	public $pluginType = "standard";
	public $pluginName = "AppEvent";
	public $title = "Event Functions";
	public $version = 1.0;
	public $author = "Brint Paris";
	public $license = "UniFaction License";
	public $website = "http://unifaction.com";
	public $description = "Manage events such as raffles or advent calendars.";
	
	public $data = array();
	
	
/****** Install this plugin ******/
	public function install (
	)			// <bool> RETURNS TRUE on success, FALSE on failure.
	
	// $plugin->install();
	{		
		Database::exec("
		CREATE TABLE `event_calendar_log` (
			`uni_id`	int(10)			unsigned	NOT NULL	DEFAULT '0',
			`item_id`	mediumint(8)	unsigned	NOT NULL	DEFAULT '0',
			`ip`		char(39)					NOT NULL	DEFAULT '',
			`cal_id`	smallint(5)		unsigned	NOT NULL	DEFAULT '0',
			PRIMARY KEY (`uni_id`, `item_id`),
			INDEX (`cal_id`)
		)
		ENGINE=InnoDB DEFAULT CHARSET=utf8;
		");
		
		Database::exec("
		CREATE TABLE `event_calendar_content` (
			`cal_id`	smallint(5)		unsigned	NOT NULL	DEFAULT '0',
			`year`		smallint(4)		unsigned	NOT NULL	DEFAULT '0',
			`doy`		smallint(3)		unsigned	NOT NULL	DEFAULT '0',
			`items`		char(255)					NOT NULL	DEFAULT '',
			PRIMARY KEY (`cal_id`, `year`, `day`)
		)
		ENGINE=InnoDB DEFAULT CHARSET=utf8;
		");
		
		Database::exec("
		CREATE TABLE `event_calendar` (
			`cal_id`	smallint(5)		unsigned	NOT NULL	AUTO_INCREMENT,
			`title`		char(255)					NOT NULL	DEFAULT '',
			`start`		int(10)			unsigned	NOT NULL	DEFAULT '0',
			`duration`	smallint(2)		unsigned	NOT NULL	DEFAULT '0',
			PRIMARY KEY (`cal_id`)
		)
		ENGINE=InnoDB DEFAULT CHARSET=utf8;
		");
		
		return $this->isInstalled();
	}
	
	
/****** Check if the plugin was successfully installed ******/
	public static function isInstalled (
	)			// <bool> TRUE if successfully installed, FALSE if not.
	
	// $plugin->isInstalled();
	{
		// Make sure the newly installed tables exist
		$pass1 = DatabaseAdmin::columnsExist("event_calendar_log", array("uni_id", "item_id"));
		$pass2 = DatabaseAdmin::columnsExist("event_calendar_content", array("cal_id"));
		$pass3 = DatabaseAdmin::columnsExist("event_calendar", array("cal_id"));
		
		return $pass1 && $pass2 && $pass3;
	}
	
}