<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	header("Location: /login?logAct=switch"); exit;
}

// Get starting point for query
if(!isset($url[2]))	{ $url[2] = 0; }
else				{ $url[2] = (int) $url[2]; }
if ($url[2] < 0)	{ $url[2] = 0; }

$transactions = Database::selectMultiple("SELECT * FROM currency_records WHERE uni_id=? ORDER BY date_exchange DESC LIMIT " . (20*$url[2]) . ", 20", array(Me::$id));

// Set page title
$config['pageTitle'] = "Transaction Log";

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Run Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

echo '
<div id="panel-right"></div>
<div id="content">' .
Alert::display();

echo '
	<h2>Transaction Log</h2>
	<table class="mod-table">
		<tr>
			<td>Sent</td>
			<td>Received</td>
			<td>Balance</td>
			<td>Description</td>
			<td>Date</td>
		</tr>';

foreach($transactions as $t)
{
	// Recognize Integers
	$t['amount'] = (float) $t['amount'];
	$t['running_total'] = (float) $t['running_total'];
	
	// Display Row
	echo '
		<tr>
			<td>' . ($t['amount'] < 0 ? number_format($t['amount'], 2) : '&nbsp;') . '</td>
			<td>' . ($t['amount'] > 0 ? number_format($t['amount'], 2) : '&nbsp;') . '</td>
			<td>' . number_format($t['running_total'], 2) . '</td>
			<td>' . $t['description'] . '</td>
			<td>' . Time::fuzzy((int) $t['date_exchange']) . '</td>
		</tr>';
}

echo '
	</table>';
	if($url[2] > 0 or isset($transactions[19]))
	{
		echo '
	<div class="spacer-huge"></div>';
		if($url[2] > 0)
		{
			echo '
	<a href="utilities/transactions/' . ($url[2]-1) . '"><span class="icon-arrow-left"></span> Newer</a>';
		}
		if(isset($transactions[19]))
		{
			echo '
	<a href="utilities/transactions/' . ($url[2]+1) . '">Older <span class="icon-arrow-right"></span></a>';
		}
	}
echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
