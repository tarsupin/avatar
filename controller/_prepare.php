<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }
Database::initRoot();

// Save _prepare_backup.php as _prepare.php
// Use that prepare file.

/**********************************
****** Prepare Item Transfer ******
**********************************/

/*
	Step 1. Import "avatar_clothing"
	Step 2. Rename "avatar_clothing" to "_transfer_items"
*/