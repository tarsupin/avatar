<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/user-panel/transactions");
}

$transactions = Database::selectMultiple("SELECT * FROM currency_records WHERE uni_id=? ORDER BY date_exchange DESC LIMIT 0, 20", array(Me::$id));

// Run Header
require(SYS_PATH . "/controller/includes/user_panel_header.php");

echo '
<table class="mod-table">
	<tr>
		<td>Exchanged</td>
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
		<td>Exchanged</td>
		<td>' . ($t['amount'] < 0 ? number_format($t['amount'], 2) : '&nbsp;') . '</td>
		<td>' . ($t['amount'] > 0 ? number_format($t['amount'], 2) : '&nbsp;') . '</td>
		<td>' . number_format($t['running_total'], 2) . '</td>
		<td>' . $t['description'] . '</td>
		<td>' . Time::fuzzy($t['date_exchange']) . '</td>
	</tr>';
}

echo '
	</tr>
</table>';

// Display the Footer
require(SYS_PATH . "/controller/includes/user_panel_footer.php");
