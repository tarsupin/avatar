<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }
Database::initRoot();

// Save _prepare_backup.php as _prepare.php
// Use that prepare file.

/*
	Update Avatar Items
	-------------------
	
	Step 1. Download all of the new images, replacing the old ones.
	Step 2. [HANDLED AUTOMATICALLY] Delete "items" from the database (was automatically created after running /setup)
	Step 3. Download "clothing_images"
	Step 4. [HANDLED AUTOMATICALLY] Rename "clothing_images" to "items"
	Step 5. [HANDLED AUTOMATICALLY] Make a backup of "items" called "_transfer_item_list" (important for transfering)
*/

exit;

/********************************************************
****** Prepare the proper table structure (Part 1) ******
********************************************************/

// Make sure you deleted "items" since we're going to rebuild it with the old content.
DatabaseAdmin::dropTable("items");

echo 'Now download "clothing_images" before continuing.';

exit;

/********************************************************
****** Prepare the proper table structure (Part 2) ******
********************************************************/

// You must have uploaded "clothing_images" by now.

DatabaseAdmin::renameTable("clothing_images", "items");
DatabaseAdmin::copyTable("items", "_transfer_item_list");

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
	
	Database::query("UPDATE items SET min_order=?, max_order=? WHERE position=? AND title=? LIMIT 1", array($min, $max, $l['position'], $l['title']));
	
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
$allItemList = Dir::getFolders(APP_PATH . "/avatar_items/");

foreach($allItemList as $fullL)
{
	//if($fullL < "legs") { continue; }
	
	$list = Dir::getFolders(APP_PATH . "/avatar_items/" . $fullL);
	
	Database::startTransaction();
	
	foreach($list as $l)
	{
		$stats = File::read(APP_PATH . "/avatar_items/" . $fullL . "/" . $l . "/_stats.txt");
		
		$values = explode(" ", $stats);
		
		if(!isset($values[2])) { continue; }
		
		$itemID = (int) Database::selectOne("SELECT id FROM items WHERE position=? AND title=? LIMIT 1", array($fullL, $l));
		
		AppAvatarAdmin::editItemCoordinates($itemID, $values[0], $values[1], $values[2], $values[3]);
		
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
$allItemList = Dir::getFolders(APP_PATH . "/avatar_items/");

foreach($allItemList as $fullL)
{
	//if($fullL < "shoes") { continue; }
	
	$list = Dir::getFolders(APP_PATH . "/avatar_items/" . $fullL);
	
	foreach($list as $l)
	{
		// Skip if it already exists
		if(File::exists(APP_PATH . "/avatar_items/" . $fullL . "/" . $l . "/default.png"))
		{
			continue;
		}
		
		// Cycle through all of the items and create a default image (height of 100px)
		$results = Dir::getFiles(APP_PATH . "/avatar_items/" . $fullL . "/" . $l);
		
		if($results)
		{
			// Copy the image to a new location
			$image = new Image(APP_PATH . "/avatar_items/" . $fullL . "/" . $l . "/" . $results[0]);
			
			if($image->height > 100)
			{
				$image->autoHeight(100, 80);
			}
			else if($image->width > 80)
			{
				$image->autoWidth(80, 100);
			}
			
			$image->save(APP_PATH . "/avatar_items/" . $fullL . "/" . $l . "/default.png");
			
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
	AppAvatarAdmin::createShop("The Body Shop");
	AppAvatarAdmin::createShop("The Finishing Touch");
	AppAvatarAdmin::createShop("Haute Couture");
	AppAvatarAdmin::createShop("The Junk Drawer");
	AppAvatarAdmin::createShop("The Looking Glass");
	AppAvatarAdmin::createShop("The Time Capsule");
	AppAvatarAdmin::createShop("Under Dressed");
	AppAvatarAdmin::createShop("Vogue Veneers");
	AppAvatarAdmin::createShop("Archive");
	AppAvatarAdmin::createShop("Exotic Exhibit");
	AppAvatarAdmin::createShop("Avatar Museum");
	AppAvatarAdmin::createShop("Staff Shop");
	AppAvatarAdmin::createShop("Test Shop");
	AppAvatarAdmin::createShop("Credit Shop");
	Database::endTransaction();
	
	echo "Created shops";
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
);

$start = 0;

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

echo "Added inventory to shops.";

exit;

/********************************
****** Phase 2 Item Update ******
********************************/

DatabaseAdmin::dropColumn("items", "exoticPackage");
DatabaseAdmin::dropColumn("items", "shopID");
DatabaseAdmin::dropColumn("items", "cost");
DatabaseAdmin::dropColumn("items", "cost_credits");
DatabaseAdmin::dropColumn("items", "purchase_yes");
DatabaseAdmin::dropColumn("items", "rel_to_base");

echo "Phase 2 updates of the items table is complete.";

exit;

/********************************
****** Phase 3 Item Update ******
********************************/

DatabaseAdmin::dropIndex("items", "position");
DatabaseAdmin::dropIndex("items", "clothing");

DatabaseAdmin::addIndex("items", "position, gender", "INDEX");

DatabaseAdmin::setEngine("items");

echo "Phase 3 updates of the items table is complete.";

exit;

/**********************************
****** Prepare Item Transfer ******
**********************************/

/*
	Step 1. Import "avatar_clothing"
	Step 2. Rename "avatar_clothing" to "_transfer_items"
*/










