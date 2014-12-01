<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }
Database::initRoot();

// Save _prepare_backup.php as _prepare.php
// Use that prepare file.

// Zip::package(APP_PATH . "/avatar_items", APP_PATH . "/avatar_items/avatar_items.zip");