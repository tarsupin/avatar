<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/");
}

// Make sure you are staff
if(Me::$clearance < 8)
{
	header("Location: /"); exit;
}

// Set page title
$config['pageTitle'] = "Exotic Stats";

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

// display data form
echo '
<div id="panel-right"></div>
<div id="content">' . Alert::display() . '	
<div class="overwrap-box">
	<div class="overwrap-line">Exotic Stats</div>
	<div class="inner-box">';

$packages = Database::selectMultiple("SELECT id, title, existing FROM packages_stats ps RIGHT JOIN packages p ON ps.package_id=p.id ORDER BY id DESC", array());
foreach($packages as $p)
{
	if(is_null($p['existing']))
	{
		AppExotic::stats((int) $p['id'], 0);
		$p['existing'] = (int) Database::selectValue("SELECT existing FROM packages_stats WHERE package_id=? LIMIT 1", array((int) $p['id']));
	}
	echo '
		<h3>' . $p['title'] . ' (' . $p['existing'] . ')</h3>
		<ul>';
	$content = Database::selectMultiple("SELECT item_id, title, existing FROM packages_content pc INNER JOIN items i ON pc.item_id=i.id WHERE package_id=? ORDER BY existing DESC", array((int) $p['id']));
	foreach($content as $c)
	{
		if(!$c['existing'])
		{
			AppExotic::stats((int) $p['id'], 0, (int) $c['item_id']);
			$c['existing'] = (int) Database::selectValue("SELECT existing FROM packages_content WHERE package_id=? AND item_id=? LIMIT 1", array((int) $p['id'], (int) $c['item_id']));
		}
		echo '
			<li>' . $c['title'] . ' (' . $c['existing'] . ')</li>';
	}
	echo '
		</ul>
		<br/>';
}

echo '
	</div>
</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");