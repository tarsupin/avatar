<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

class AppExotic_config {
	
	
/****** Plugin Variables ******/
	public $pluginType = "standard";
	public $pluginName = "AppExotic";
	public $title = "Exotic Shop System";
	public $version = 0.1;
	public $author = "Brint Paris";
	public $license = "UniFaction License";
	public $website = "http://unifaction.com";
	public $description = "Handles the stock of the exotic item/package shop.";
	
	public $data = array();
	

/****** Install this plugin ******/
	public function install (
	)			// <bool> RETURNS TRUE on success, FALSE on failure.
	
	// $plugin->install();
	{	
		Database::exec("
		CREATE TABLE `packages_stats` (
			`package_id`			smallint(5)		unsigned	NOT NULL	DEFAULT '0',
			`existing`				int(10)			unsigned	NOT NULL	DEFAULT '0',
		
			PRIMARY KEY (`package_id`)
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
		return DatabaseAdmin::columnsExist("packages_stats", array("package_id"));
	}
	
}