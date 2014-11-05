<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }
Database::initRoot();

// Save _prepare_backup.php as _prepare.php
// Use that prepare file.

/*
	Update Avatar Items
	-------------------
	
	Step 1. Download all of the new images, replacing the old ones.
	Step 2. [HANDLED AUTOMATICALLY] Delete "items" from the database (was automatically created after running /setup)
	Step 3. Download "clothing_images" and "exotic_packages"
	Step 4. [HANDLED AUTOMATICALLY] Rename "clothing_images" to "items" and "exotic_packages" to "packages"
	Step 5. [HANDLED AUTOMATICALLY] Make a backup of "items" called "_transfer_item_list" (important for transfering)
*/

exit;

/*****************************************************
***** Fix possibly outdated database structures ******
*****************************************************/

if(!DatabaseAdmin::columnsExist("avatars", array("avatar_id")))
{
	DatabaseAdmin::addColumn("avatars", "avatar_id", "tinyint(2) unsigned NOT NULL", 0);
	Database::exec("ALTER TABLE `avatars` DROP PRIMARY KEY");
	Database::exec("ALTER TABLE `avatars` ADD PRIMARY KEY(uni_id, avatar_id)");
}

Database::exec("
CREATE TABLE IF NOT EXISTS `transactions`
(
	`id`					int(10)			unsigned	NOT NULL	AUTO_INCREMENT,
	`title`					varchar(32)					NOT NULL	DEFAULT '',
	
	`date_created`			int(10)			unsigned	NOT NULL	DEFAULT '0',
	`date_end`				int(10)			unsigned	NOT NULL	DEFAULT '0',
	
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

Database::exec("
CREATE TABLE IF NOT EXISTS `transactions_users`
(
	`uni_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
	`transaction_id`		int(10)			unsigned	NOT NULL	DEFAULT '0',
	`has_agreed`			tinyint(1)		unsigned	NOT NULL	DEFAULT '0',
	
	UNIQUE (`uni_id`, `transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

Database::exec("
		CREATE TABLE IF NOT EXISTS `transactions_entries`
		(
			`id`					int(10)			unsigned	NOT NULL	AUTO_INCREMENT,
			
			`transaction_id`		int(10)			unsigned	NOT NULL	DEFAULT '0',
			`uni_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			`class`					varchar(24)					NOT NULL	DEFAULT '',
			`process_method`		varchar(32)					NOT NULL	DEFAULT '',
			`process_parameters`	varchar(255)				NOT NULL	DEFAULT '',
			
			`display`				text						NOT NULL	DEFAULT '',
			
			PRIMARY KEY (`id`),
			INDEX (`transaction_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		");

exit;

/********************************************************
****** Prepare the proper table structure (Part 1) ******
********************************************************/

// Make sure you deleted "items" since we're going to rebuild it with the old content.
DatabaseAdmin::dropTable("items");

echo 'Now download "clothing_images" and "exotic_packages" before continuing.';

exit;

/********************************************************
****** Prepare the proper table structure (Part 2) ******
********************************************************/

// You must have uploaded "clothing_images" and "exotic_packages" by now.

DatabaseAdmin::renameTable("clothing_images", "items");
DatabaseAdmin::copyTable("items", "_transfer_item_list");
DatabaseAdmin::renameTable("exotic_packages", "packages");
DatabaseAdmin::copyTable("packages", "_transfer_packages_list");

echo 'Initial table structure has been prepared.';

exit;

/********************************************
****** Update the Item Table (Phase 1) ******
********************************************/

DatabaseAdmin::renameColumn("items", "clothingID", "id");
DatabaseAdmin::renameColumn("items", "clothing", "title");
DatabaseAdmin::renameColumn("items", "used_by", "gender");

DatabaseAdmin::editColumn("items", "gender", "char(1) NOT NULL", "");

DatabaseAdmin::addColumn("items", "coord_x_male", "tinyint(3) unsigned NOT NULL", 0);
DatabaseAdmin::addColumn("items", "coord_y_male", "smallint(3) unsigned NOT NULL", 0);
DatabaseAdmin::addColumn("items", "coord_x_female", "tinyint(3) unsigned NOT NULL", 0);
DatabaseAdmin::addColumn("items", "coord_y_female", "smallint(3) unsigned NOT NULL", 0);

DatabaseAdmin::addColumn("items", "min_order", "tinyint(2) NOT NULL", 0);
DatabaseAdmin::addColumn("items", "max_order", "tinyint(2) NOT NULL", 0);

DatabaseAdmin::addColumn("items", "rarity_level", "tinyint(1) unsigned NOT NULL", 0);

echo "Phase #1 Updates to the item table are finished.";

DatabaseAdmin::showTable("items");

exit;

/*****************************************************
****** Update min and max order in the database ******
*****************************************************/

// Run each directory in avatar_items
$list = Database::selectMultiple("SELECT title, position, rel_to_base FROM items", array());

Database::startTransaction();

foreach($list as $l)
{
	if($l['rel_to_base'] == "above")
	{
		$min = 2;
		$max = 99;
	}
	else if($l['rel_to_base'] == "below")
	{
		$min = -99;
		$max = -1;
	}
	else if($l['rel_to_base'] == "on")
	{
		$min = 1;
		$max = 1;
	}
	else
	{
		$min = -99;
		$max = 99;
	}
	
	Database::query("UPDATE items SET min_order=?, max_order=? WHERE position=? AND title=? LIMIT 2", array($min, $max, $l['position'], $l['title']));
	
	echo "Finished " . $l['position'] . '->' . $l['title'] . "<br />";
}

Database::endTransaction();

exit;

/**********************************************************
****** Update coordinates of items into the database ******
**********************************************************/

// For a total of 2 minutes (note: you could run the script again if necessary)
ini_set('max_execution_time', 120);

// Run each directory in avatar_items
$allItemList = Dir::getFolders(APP_PATH . "/assets/avatar_items/");

foreach($allItemList as $fullL)
{
	//if($fullL < "legs") { continue; }
	
	$list = Dir::getFolders(APP_PATH . "/assets/avatar_items/" . $fullL);
	
	Database::startTransaction();
	
	foreach($list as $l)
	{
		$stats = File::read(APP_PATH . "/assets/avatar_items/" . $fullL . "/" . $l . "/_stats.txt");
		
		$values = explode(" ", $stats);
		
		if(!isset($values[3])) { continue; }
		
		$itemIDs = (array) Database::selectMultiple("SELECT id FROM items WHERE position=? AND title=? LIMIT 2", array($fullL, $l));
		foreach($itemIDs as $itemID)
			AppAvatarAdmin::editItemCoordinates((int) $itemID['id'], (int) $values[0], (int) $values[1], (int) $values[2], (int) $values[3]);
		
		echo "Completed " . $fullL . "->" . $l . "<br />";
	}
	
	Database::endTransaction();
}

exit;

/********************************************************
****** Prepare default images for all avatar items ******
********************************************************/

// For a total of 2 minutes (note: you could run the script again if necessary)
ini_set('max_execution_time', 120);

// Run each directory in avatar_items
$allItemList = Dir::getFolders(APP_PATH . "/assets/avatar_items/");

foreach($allItemList as $fullL)
{
	//if($fullL < "shoes") { continue; }

	if($fullL == "base") { continue; }
	if($fullL == "temp")
	{
		// temp is not a layer position, but was used for recoloring purposes
		// that functionality will be moved elsewhere and important files have been backed up, so this isn't needed
		if(Dir::exists(APP_PATH . "/assets/avatar_items/" . $fullL))
		{
			Dir::delete(APP_PATH . "/assets/avatar_items/" . $fullL);
		}
		continue;
	}
	
	$list = Dir::getFolders(APP_PATH . "/assets/avatar_items/" . $fullL);
	
	foreach($list as $l)
	{
		// Skip if it already exists
		if(File::exists(APP_PATH . "/assets/avatar_items/" . $fullL . "/" . $l . "/default_male.png"))
		{
			continue;
		}
		if(File::exists(APP_PATH . "/assets/avatar_items/" . $fullL . "/" . $l . "/default_female.png"))
		{
			continue;
		}
		
		// Cleanup of previous non-gender-specific default images
		if(File::exists(APP_PATH . "/assets/avatar_items/" . $fullL . "/" . $l . "/default.png"))
		{
			File::delete(APP_PATH . "/assets/avatar_items/" . $fullL . "/" . $l . "/default.png");
		}
		
		// Cycle through all of the items and create a default image (max height of 100px and max width of 80)
		$results = Dir::getFiles(APP_PATH . "/assets/avatar_items/" . $fullL . "/" . $l);
		
		if($results)
		{
			// Find respective first female and male image
			$has_gender = array("female" => false, "male" => false);
			foreach($results as $result)
			{
				if(substr($result,-11) == "_female.png" && $has_gender['female'] === false)
				{
					$has_gender['female'] = $result;
					if ($has_gender['male'] !== false)
						break;
				}
				if(substr($result,-9) == "_male.png" && $has_gender['male'] === false)
				{
					$has_gender['male'] = $result;
					if ($has_gender['female'] !== false)
						break;
				}
			}
			
			// Copy the images to a new location
			foreach($has_gender as $key => $val)
			{
				if($val !== false)
				{
					$image = new Image(APP_PATH . "/assets/avatar_items/" . $fullL . "/" . $l . "/" . $val);
					
					if($image->height > 100) 		{ $image->autoHeight(100); }
					if($image->width > 80) 			{ $image->autoWidth(80); }
					
					$image->save(APP_PATH . "/assets/avatar_items/" . $fullL . "/" . $l . "/default_" . $key . ".png");
				}
			}
			
			echo "Completed " . $fullL . "->" . $l . "<br />";
		}
	}
}

exit;

/*********************************
****** Create the Shop List ******
*********************************/
$value = (int) Database::selectValue("SELECT id FROM shop LIMIT 1", array());

if($value == false)
{
	Database::startTransaction();
	AppAvatarAdmin::createShop("A Cut Above");
	AppAvatarAdmin::createShop("All That Glitters");
	AppAvatarAdmin::createShop("Heart and Sole");
	AppAvatarAdmin::createShop("Pret a Porter");
	AppAvatarAdmin::createShop("Body Shop");
	AppAvatarAdmin::createShop("Finishing Touch");
	AppAvatarAdmin::createShop("Haute Couture");
	AppAvatarAdmin::createShop("Junk Drawer");
	AppAvatarAdmin::createShop("Looking Glass");
	AppAvatarAdmin::createShop("Time Capsule");
	AppAvatarAdmin::createShop("Under Dressed");
	AppAvatarAdmin::createShop("Vogue Veneers");
	AppAvatarAdmin::createShop("Archive", 5);
	AppAvatarAdmin::createShop("Exotic Exhibit");
	AppAvatarAdmin::createShop("Avatar Museum");
	AppAvatarAdmin::createShop("Staff Shop", 5);
	AppAvatarAdmin::createShop("Test Shop", 5);
	AppAvatarAdmin::createShop("Credit Shop");
	AppAvatarAdmin::createShop("Wrappers", 5);
	Database::endTransaction();
	
	echo "Created shops.";
}

exit;

/**************************************************
****** Create Shop Inventory & Update Rarity ******
**************************************************/

$shopList = array(
		5		=> 1
	,	55		=> 2
	,	92		=> 13
	,	65		=> 14
	,	40		=> 3
	,	70		=> 15
	,	20		=> 4
	,	90		=> 16
	,	91		=> 17
	,	15		=> 5
	,	50		=> 6
	,	25		=> 7
	,	60		=> 8
	,	10		=> 9
	,	30		=> 10
	,	45		=> 11
	,	35		=> 12
	,	75		=> 18
	,	93		=> 19
);

Database::startTransaction();

$results = Database::selectMultiple("SELECT * FROM items", array());

foreach($results as $result)
{
	// Recognize Integers
	$result['id'] = (int) $result['id'];
	$result['cost'] = (int) $result['cost'];
	$result['exoticPackage'] = (int) $result['exoticPackage'];
	$result['cost_credits'] = (int) $result['cost_credits'];
	
	// Prepare Values
	$exotic = 0;
	
	if($result['exoticPackage'] > 0)			
	{
		$exotic = 2;
		Database::query("INSERT INTO `packages_content` VALUES (?, ?)", array($result['id'], $result['exoticPackage']));
	}
	else if($result['purchase_yes'] == "deny")
	{
		$exotic = 1;
	}
	
	if($result['cost_credits'] > 0)
	{
		$exotic = 2;
	}
	
	// Get New Shop ID
	$shopID = (int) $shopList[$result['shopID']];
	
	// Update Item
	if($exotic != 0)
	{
		Database::query("UPDATE items SET rarity_level=? WHERE id=? LIMIT 1", array($exotic, $result['id']));
	}
	
	// Update Shop Setup
	AppAvatarAdmin::addShopItem($shopID, $result['id'], $result['cost']);
	
}

Database::endTransaction();

echo "Added inventory to shops and packages.";

exit;

/********************************
****** Phase 2 Item Update ******
********************************/

DatabaseAdmin::dropColumn("items", "exoticPackage");
DatabaseAdmin::dropColumn("items", "shopID");
DatabaseAdmin::dropColumn("items", "cost");
DatabaseAdmin::dropColumn("items", "cost_gems");
DatabaseAdmin::dropColumn("items", "cost_credits");
DatabaseAdmin::dropColumn("items", "purchase_yes");
DatabaseAdmin::dropColumn("items", "rel_to_base");

DatabaseAdmin::dropColumn("packages", "image");

echo "Phase 2 updates of the items and packages tables is complete.";

exit;

/********************************
****** Phase 3 Item Update ******
********************************/

DatabaseAdmin::dropIndex("items", "position");
DatabaseAdmin::dropIndex("items", "clothing");

DatabaseAdmin::addIndex("items", "position, gender", "INDEX");

DatabaseAdmin::setEngine("items");
DatabaseAdmin::setEngine("packages");

echo "Phase 3 updates of the items and packages tables is complete.";

exit;





/*
	Prepare Item Transfer
	-------------------
	Step 1. Import "avatar_clothing" and "exotic_packages_owned".
	Step 2. [HANDLED AUTOMATICALLY] Rename "avatar_clothing" to "_transfer_items" and "exotic_packages_owned" to "_transfer_packages".
	Step 3. Import "s4u_accounts" and "s4u_account_trackers".
	Step 4. [HANDLED AUTOMATICALLY] Combine Uni5 password and Auro amount into "_transfer_accounts".
	Step 5. Import "wrap_items_staff".
	Step 6. [HANDLED AUTOMATICALLY] Rename "wrap_items_staff" to "wrappers" and change to new structure.
*/

echo 'Now download "avatar_clothing" and "exotic_packages_owned" before continuing.';

exit;


/********************************************************
****** Prepare the proper table structure (Part 3) ******
********************************************************/

// You must have uploaded "avatar_clothing" and "exotic_packages_owned" by now.

DatabaseAdmin::renameTable("avatar_clothing", "_transfer_items");
DatabaseAdmin::renameTable("exotic_packages_owned", "_transfer_packages");
DatabaseAdmin::dropColumn("_transfer_items", "in_trade");
DatabaseAdmin::dropColumn("_transfer_packages", "in_trade");

echo 'Tables have been prepared.';

exit;


/******************************************
****** Get remaining individual data ******
*******************************************/

echo 'Now download "s4u_accounts" and "s4u_account_trackers" before continuing.';

exit;


/********************************************************
****** Prepare the proper table structure (Part 4) ******
********************************************************/

// You must have uploaded "s4u_accounts" and "s4u_account_trackers" by now.

DatabaseAdmin::renameTable("s4u_accounts", "_transfer_accounts");

Database::exec("ALTER TABLE `_transfer_accounts` CHANGE `id` `id` int(10) unsigned NOT NULL");
Database::exec("ALTER TABLE `_transfer_accounts` DROP PRIMARY KEY");

DatabaseAdmin::dropIndex("_transfer_accounts", "account");
Database::exec("ALTER TABLE `_transfer_accounts` ADD PRIMARY KEY(account)");

DatabaseAdmin::dropIndex("_transfer_accounts", "clearance");

DatabaseAdmin::dropColumn("_transfer_accounts", "id");
DatabaseAdmin::dropColumn("_transfer_accounts", "clearance");

DatabaseAdmin::addColumn("_transfer_accounts", "auro", "float(10,2) unsigned NOT NULL", "0.00");
DatabaseAdmin::addColumn("_transfer_accounts", "uni6_id", "int(10) unsigned NOT NULL", "0");

echo "Table structure has been prepared.";

exit;

/**************************************************
****** Combine Uni5 password and Auro amount ******
***************************************************/

Database::startTransaction();

$results = Database::selectMultiple("SELECT account, auro FROM s4u_account_trackers", array());
foreach($results as $result)
{
	Database::query("UPDATE _transfer_accounts SET auro=? WHERE account=? LIMIT 1", array((float) $result['auro'], $result['account']));
}

Database::endTransaction();

DatabaseAdmin::dropTable("s4u_account_trackers");

echo "Auro amount has been moved to the _transfer_accounts table.";

// You should drop the s4u_account_trackers table.

exit;


/******************************************
****** Get wrapper data ******
*******************************************/

echo 'Now download "wrap_items_staff" before continuing.';

exit;


/***********************************************
****** Adjust structure of wrappers table ******
************************************************/

// You must have uploaded "wrap_items_staff" by now.

DatabaseAdmin::renameTable("wrap_items_staff", "wrappers");
DatabaseAdmin::copyTable("wrappers", "_transfer_wrappers_list");

Database::exec("ALTER TABLE `wrappers` DROP PRIMARY KEY");

DatabaseAdmin::renameColumn("wrappers", "item_id", "id");
DatabaseAdmin::editColumn("wrappers", "id", "mediumint(8) unsigned NOT NULL", "0");
DatabaseAdmin::dropColumn("wrappers", "may_keep");
DatabaseAdmin::addColumn("wrappers", "replacement", "mediumint(8) unsigned NOT NULL", "0");

Database::exec("ALTER TABLE `wrappers` ADD PRIMARY KEY(id)");

DatabaseAdmin::setEngine("wrappers");

echo "Table structure has been prepared.";

exit;


/**********************************************
****** Set replacement items for wrappers *****
***********************************************/

Database::startTransaction();

$results = Database::selectMultiple("SELECT id, content FROM wrappers", array());
foreach($results as $result)
{
	// check whether the wrapper was a "staying" one and has a replacement included
	$wrap = Database::selectOne("SELECT id, title FROM items WHERE id=?", array($result['id']));	
	$content = explode(",", $result['content']);
	$item = (int) array_pop($content);
	$content = implode(",", $content);
	$cont = Database::selectOne("SELECT title FROM items WHERE id=?", array($item));
	if($wrap['title'] == $cont['title'])
	{
		Database::query("UPDATE wrappers SET content=?, replacement=? WHERE id=? LIMIT 1", array($content, $item, (int) $wrap['id']));
	}
}

Database::endTransaction();

echo "Replacements have been switched to their own column.";

exit;