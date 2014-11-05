<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/log-item");
}

// Get starting point for query
if(!isset($url[1]))	{ $url[1] = 0; }
else				{ $url[1] = (int) $url[1]; }
if ($url[1] < 0)	{ $url[1] = 0; }

$transactions = Database::selectMultiple("SELECT * FROM item_records WHERE uni_id=? OR other_id=? ORDER BY date_exchange DESC LIMIT " . (20*$url[1]) . ", 20", array(Me::$id, Me::$id));

// Set page title
$config['pageTitle'] = "Item Log";

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Run Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

echo '
<style>
table tr:first-child td { text-align:center; }
</style>';

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

echo '
<div id="panel-right"></div>
<div id="content">' .
Alert::display();

echo '
	<h2>Item Log</h2>
	<table class="mod-table">
		<tr>
			<td>Sent</td>
			<td>Received</td>
			<td>To/From</td>
			<td>Description</td>
			<td>Date</td>
		</tr>';
		
// get wrappers
$wrap = Database::selectMultiple("SELECT id FROM wrappers", array());
$wrappers = array();
foreach($wrap as $w)
{
	$wrappers[] = $w['id'];
}
unset($wrap);

foreach($transactions as $t)
{	
	if($t['other_id'] != Me::$id)
	{
		$other = User::get($t['other_id'], "handle");
		$other = $other['handle'];
	}
	else
	{
		$other = User::get($t['uni_id'], "handle");
		$other = $other['handle'];
	}
	
	$itemData = AppAvatar::itemData($t['item_id'], "title");
	
	// Display Row
	echo '
		<tr>
			<td>' . ($t['other_id'] != Me::$id ? $itemData['title'] . (in_array($t['item_id'], $wrappers) ? " (Wrapper)" : "") : '&nbsp;') . '</td>
			<td>' . ($t['other_id'] == Me::$id ? $itemData['title'] . (in_array($t['item_id'], $wrappers) ? " (Wrapper)" : "") : '&nbsp;') . '</td>
			<td>' . ($other != "" ? $other : '&nbsp;') . '</td>
			<td>' . $t['description'] . '</td>
			<td>' . Time::fuzzy((int) $t['date_exchange']) . '</td>
		</tr>';
}

echo '
	</table>';
	if($url[1] > 0 or isset($transactions[19]))
	{
		echo '
	<br/>';
		if($url[1] > 0)
		{
			echo '
	<a href="/log-item/' . ($url[1]-1) . '">Newer <span class="icon-arrow-left"></span></a>';
		}
		if(isset($transactions[19]))
		{
			echo '
	<a href="/log-item/' . ($url[1]+1) . '"><span class="icon-arrow-right"></span> Older</a>';
		}
	}
echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
