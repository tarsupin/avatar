<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

class AppTrade_config {
	
	
/****** Plugin Variables ******/
	public $pluginType = "standard";
	public $pluginName = "AppTrade";
	public $title = "Trade and Gift Functions";
	public $version = 1.0;
	public $author = "Brint Paris";
	public $license = "UniFaction License";
	public $website = "http://unifaction.com";
	public $description = "Trade and gift items and currency on the Avatar site.";
	
	
/****** Install this plugin ******/
	public function install (
	)			// <bool> RETURNS TRUE on success, FALSE on failure.
	
	// $plugin->install();
	{
		Database::exec("		
		CREATE TABLE IF NOT EXISTS `item_records` (
			  `uni_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			  `other_id`			int(10)			unsigned	NOT NULL	DEFAULT '0',
			  `item_id`				mediumint(8)	unsigned	NOT NULL	DEFAULT '0',
			  `date_exchange`		int(10)			unsigned	NOT NULL	DEFAULT '0',
			  `description`			varchar(64)					NOT NULL	DEFAULT '',
			  
			  INDEX (`uni_id`, `other_id`, `date_exchange`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 PARTITION BY KEY(uni_id) PARTITIONS 11;
		");
		
		return $this->isInstalled();
	}
	
	
/****** Check if the plugin was successfully installed ******/
	public static function isInstalled (
	)			// <bool> TRUE if successfully installed, FALSE if not.
	
	// $plugin->isInstalled();
	{
		// Make sure the newly installed table exists
		$pass = DatabaseAdmin::columnsExist("item_records", array("uni_id"));
		
		return $pass;
	}
	
}