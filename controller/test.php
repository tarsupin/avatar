<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/");
}

if(Me::$clearance < 8)
{
	header("Location: /"); exit;
}

$local = Database::selectMultiple("SELECT uni_id, amount FROM currency");
Database::startTransaction();
$ok = true;
foreach($local as $l)
{
	if($l['amount'] == 0)	{ continue; }
	$success1 = Auro::grant((int) $l['uni_id'], (int) $l['amount'], "Initial Auro Transfer", $config['site-name']);
	$success2 = Currency::subtract((int) $l['uni_id'], (int) $l['amount'], "Initial Auro Transfer");
	if(!$success1 || !$success2)
	{
		$ok = false;
		break;
	}
}
if(!$ok)	{ Database::endTransaction(false); }
else		{ Database::endTransaction(); }
