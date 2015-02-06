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

if(Form::submitted("create-item"))
{
	$title = Sanitize::word($_POST['title'], " 0123456789");
	$position = Sanitize::variable($_POST['position']);
	$gender = (in_array($_POST['gender'], array("b", "f", "m")) ? $_POST['gender'] : "b");
	$rarityLevel = (int) $_POST['rarity'];
	if(AppAvatarAdmin::createItem($title, $position, $gender, $rarityLevel, 0, 0, 0, 0))
	{
		$insertID = Database::$lastID;
		switch($_POST['relation'])
		{
			case "above":
				Database::query("UPDATE items SET min_order=?, max_order=? WHERE id=? LIMIT 1", array(2, 99, $insertID));
				break;
			case "below":
				Database::query("UPDATE items SET min_order=?, max_order=? WHERE id=? LIMIT 1", array(-99, -1, $insertID));
				break;
			case "skin":
				Database::query("UPDATE items SET min_order=?, max_order=? WHERE id=? LIMIT 1", array(1, 1, $insertID));
				break;
			default:
				Database::query("UPDATE items SET min_order=?, max_order=? WHERE id=? LIMIT 1", array(-99, 99, $insertID));
		}
		header("Location: /staff/item-edit/" . $insertID); exit;
	}
}

// Set page title
$config['pageTitle'] = "Create Item";

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

echo '
<div id="panel-right"></div>
<div id="content">' . Alert::display() . '	
<div class="overwrap-box">
	<div class="overwrap-line">Create Item</div>
	<div class="inner-box">
	<form class="uniform" method="post">' . Form::prepare("create-item") . '
		<p><input type="text" name="title" maxlength="30"/> title</p>
		<p><select name="position">';
$positions = AppAvatar::positions();
foreach($positions as $position)
{
	echo '<option value="' . $position . '">' . $position . '</option>';
}
echo '</select> layer</p>
		<p><select name="gender"><option value="b">both</option><option value="f">female</option><option value="m">male</option></select> gender</p>
		<p><select name="rarity"><option value="0">freely purchasable</option><option value="1">not purchasable</option><option value="2">EP item</option></select> rarity</p>
		<p><select name="relation"><option value="free">free</option><option value="above">above base</option><option value="skin">on base (skin)</option><option value="below">below base</option></select> relation to base</p>
		<p><input type="submit" value="Create"/></p>
	</form>
	</div>
	</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");