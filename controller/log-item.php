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
table { width:100%; }
table tr:first-child td { text-align:center; }
</style>';

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

echo '
<div id="panel-right"></div>
<div id="content">' .
Alert::display() . '
<div class="overwrap-box">
	<div class="overwrap-line">Item Log</div>
	<div class="inner-box">
	<table class="mod-table">
		<tr>
			<td>Sent</td>
			<td>Received</td>
			<td>To/From</td>
			<td>Description</td>
			<td>Date</td>
		</tr>';
		
// get wrappers
$wrappers = AppAvatar::wrappers();

foreach($transactions as $t)
{
	$other = "";
	if($t['other_id'] != Me::$id && $t['other_id'] != 0)
	{
		$other = User::get((int) $t['other_id'], "handle");
		$other = $other['handle'];
	}
	elseif($t['other_id'] == Me::$id && $t['uni_id'] != 0)
	{
		$other = User::get((int) $t['uni_id'], "handle");
		$other = $other['handle'];
	}
	
	$itemData = AppAvatar::itemData((int) $t['item_id'], "title");
	if($itemData)
	{
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
	</div>
</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
