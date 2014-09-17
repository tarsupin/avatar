<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

class AvatarExists_config {
	
	
/****** Plugin Variables ******/
	public $pluginType = "api";
	public $pluginName = "AvatarExists";
	public $title = "Avatar Verification";
	public $version = 1.0;
	public $author = "Brint Paris";
	public $license = "UniFaction License";
	public $website = "http://unifaction.com";
	public $description = "Verifies if a user has an avatar or not.";
	
	public $data = array();
}