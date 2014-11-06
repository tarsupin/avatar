<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

class AppAvatar_config {
	
	
/****** Plugin Variables ******/
	public $pluginType = "standard";
	public $pluginName = "AppAvatar";
	public $title = "Avatar Functions";
	public $version = 1.0;
	public $author = "Brint Paris";
	public $license = "UniFaction License";
	public $website = "http://unifaction.com";
	public $description = "Acquire, move, and change items and images on the Avatar site.";
	
	public $data = array();
	
	
/****** Install this plugin ******/
	public function install (
	)			// <bool> RETURNS TRUE on success, FALSE on failure.
	
	// $plugin->install();
	{
		Database::exec("
		CREATE TABLE IF NOT EXISTS `user_items`
		(
			`uni_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`item_id`				mediumint(8)	unsigned	NOT NULL	DEFAULT '0',
			
			INDEX (`uni_id`, `item_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;
		");
		
		Database::exec("
		CREATE TABLE IF NOT EXISTS `user_wish`
		(
			`uni_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`item_id`				mediumint(8)	unsigned	NOT NULL	DEFAULT '0',
			
			PRIMARY KEY (`uni_id`, `item_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;
		");
		
		Database::exec("
		CREATE TABLE IF NOT EXISTS `user_packages`
		(
			`uni_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`package_id`			smallint(5)		unsigned	NOT NULL	DEFAULT '0',
			
			INDEX (`uni_id`, `package_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;
		");
		
		Database::exec("
		CREATE TABLE IF NOT EXISTS `user_outfits`
		(
			`uni_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`type`					varchar(12)					NOT NULL	DEFAULT '',
			`outfit_json`			text						NOT NULL	DEFAULT '',
			
			UNIQUE (`uni_id`, `type`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;
		");
		
		Database::exec("
		CREATE TABLE IF NOT EXISTS `avatars`
		(
			`uni_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`avatar_id`				tinyint(1)		unsigned	NOT NULL	DEFAULT '0',
			`base`					varchar(8)					NOT NULL	DEFAULT '',
			`gender`				char(1)						NOT NULL	DEFAULT '',
			`name`					varchar(20)					NOT NULL	DEFAULT '',
			`date_lastUpdate`		int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			UNIQUE (`uni_id`, `avatar_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;
		");
		
		Database::exec("
		CREATE TABLE IF NOT EXISTS `items`
		(
			`id`					int(10)			unsigned	NOT NULL	AUTO_INCREMENT,
			`title`					varchar(32)					NOT NULL	DEFAULT '',
			
			`position`				varchar(12)					NOT NULL	DEFAULT '',
			`gender`				char(1)						NOT NULL	DEFAULT '',
			
			`coord_x_male`			tinyint(3)		unsigned	NOT NULL	DEFAULT '0',
			`coord_y_male`			smallint(3)		unsigned	NOT NULL	DEFAULT '0',
			
			`coord_x_female`		tinyint(3)		unsigned	NOT NULL	DEFAULT '0',
			`coord_y_female`		smallint(3)		unsigned	NOT NULL	DEFAULT '0',
			
			`min_order`				tinyint(2)					NOT NULL	DEFAULT '0',
			`max_order`				tinyint(2)					NOT NULL	DEFAULT '0',
			
			`rarity_level`			tinyint(1)		unsigned	NOT NULL	DEFAULT '0',
			
			PRIMARY KEY (`id`),
			INDEX (`position`, `gender`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;
		");
		
		Database::exec("
		CREATE TABLE IF NOT EXISTS `shop`
		(
			`id`					int(10)			unsigned	NOT NULL	AUTO_INCREMENT,
			`title`					varchar(32)					NOT NULL	DEFAULT '',
			`clearance`				tinyint(1)		unsigned	NOT NULL	DEFAULT '0',
			
			PRIMARY KEY (`id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;
		");
		
		Database::exec("
		CREATE TABLE IF NOT EXISTS `shop_inventory`
		(
			`shop_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			`item_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`cost`					smallint(6)		unsigned	NOT NULL	DEFAULT '0',
			
			UNIQUE (`shop_id`, `item_id`, `cost`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;
		");
		
		Database::exec("
		CREATE TABLE IF NOT EXISTS `packages_content`
		(
			`item_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`package_id`			smallint(5)		unsigned	NOT NULL	DEFAULT '0',
			
			UNIQUE (`item_id`, `package_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;
		");
		
		return $this->isInstalled();
	}
	
	
/****** Check if the plugin was successfully installed ******/
	public static function isInstalled (
	)			// <bool> TRUE if successfully installed, FALSE if not.
	
	// $plugin->isInstalled();
	{
		// Make sure the newly installed tables exist
		$pass = DatabaseAdmin::columnsExist("user_items", array("uni_id", "item_id"));
		$pass2 = DatabaseAdmin::columnsExist("user_outfits", array("uni_id", "outfit_json"));
		$pass3 = DatabaseAdmin::columnsExist("avatars", array("uni_id", "base", "gender"));
		
		if($pass and $pass2 and $pass3)
		{
			$pass4 = DatabaseAdmin::columnsExist("avatar_equipped", array("uni_id", "item_id"));
			$pass5 = DatabaseAdmin::columnsExist("items", array("id", "title", "position"));
			$pass6 = DatabaseAdmin::columnsExist("shop", array("id", "title"));
			$pass7 = DatabaseAdmin::columnsExist("shop_inventory", array("shop_id", "item_id", "cost"));
			
			$pass = ($pass4 and $pass5 and $pass6 and $pass7);
		}
		else
		{
			$pass = false;
		}
		
		return $pass;
	}
	
}