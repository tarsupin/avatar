<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/");
}

// Make sure you are staff
if(Me::$clearance < 5)
{
	header("Location: /"); exit;
}

// Set page title
$config['pageTitle'] = "Transfer Name Changes";

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

echo '
<style>
	table tr:first-child { text-align:center; }
</style>';

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

// Display List of Tools
echo '
<div id="panel-right"></div>
<div id="content">' . Alert::display() . '	
<div class="overwrap-box">
	<div class="overwrap-line">Changed Names</div>
	<div class="inner-box">
		<table class="mod-table">
			<tr><td>Uni5 Name</td><td>Uni6 Name</td></tr>';

$results = Database::selectMultiple("SELECT account, handle FROM users INNER JOIN _transfer_accounts ON _transfer_accounts.uni6_id=users.uni_id", array());
sort($results);
$same = array();
$similar = array();
foreach($results as $result)
{
	if($result['account'] == $result['handle'])
	{
		$same[] = $result;
	}
	elseif(strtolower($result['account']) == strtolower($result['handle']))
	{
		$similar[] = $result;
	}
	else
	{
		echo '
			<tr><td>' . $result['account'] . '</td><td>' . $result['handle'] . '</td></tr>';
	}
}
echo '
		</table>
	</div>
</div>
<div class="overwrap-box">
	<div class="overwrap-line">Similar Names</div>
	<div class="inner-box">
		<table class="mod-table">
			<tr><td>Uni5 Name</td><td>Uni6 Name</td></tr>';
foreach($similar as $result)
{
	echo '
			<tr><td>' . $result['account'] . '</td><td>' . $result['handle'] . '</td></tr>';
}

echo '
		</table>
	</div>
</div>
<div class="overwrap-box">
	<div class="overwrap-line">Unchanged Names</div>
	<div class="inner-box">
		<table class="mod-table">
			<tr><td>Uni5 Name</td><td>Uni6 Name</td></tr>';
foreach($same as $result)
{
	echo '
			<tr><td>' . $result['account'] . '</td><td>' . $result['handle'] . '</td></tr>';
}

echo '
		</table>
	</div>
</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");