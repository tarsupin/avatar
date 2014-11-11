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

if(!isset($url[2]))	{ header("Location: /"); exit; }

if(!$itemData = AppAvatar::itemData($url[2]))	{ header("Location: /"); exit; }
$url[2] = (int) $url[2];

if(isset($_GET['delete']))
{
	File::delete(APP_PATH . "/avatar_items/" . $itemData['position'] . "/" . $itemData['title'] . "/" . $_GET['delete'] . ".png");
}

if(Form::submitted("edit-item"))
{
	$title = Sanitize::word($_POST['title'], " ");
	if($title != $itemData['title'])
	{
		Database::query("UPDATE `items` SET title=? WHERE itemID=? LIMIT 1", array($title, $itemID));
		Dir::move(APP_PATH . "/avatar_items/" . $itemData['position'] . "/" . $itemData['title'], APP_PATH . "/avatar_items/" . $itemData['position'] . "/" . $title);
	}
	
	$position = Sanitize::variable($_POST['position']);
	if($position != $itemData['position'])
	{
		AppAvatarAdmin::editItemPosition($url[2], $position);
	}
	
	$gender = (in_array($_POST['gender'], array("b", "f", "m")) ? $_POST['gender'] : "b");
	if($gender != $itemData['gender'])
	{
		Database::query("UPDATE `items` SET gender=? WHERE itemID=? LIMIT 1", array($gender, $itemID));
	}
	
	$rarity = (int) $_POST['rarity'];
	if($rarity != $itemData['rarity_level'])
	{
		Database::query("UPDATE `items` SET rarity_level=? WHERE itemID=? LIMIT 1", array($rarity, $itemID));
	}
	
	switch($_POST['relation'])
	{
		case "above":
			Database::query("UPDATE items SET min_order=?, max_order=? WHERE id=? LIMIT 1", array(2, 99, $url[2]));
			break;
		case "below":
			Database::query("UPDATE items SET min_order=?, max_order=? WHERE id=? LIMIT 1", array(-99, -1, $url[2]));
			break;
		case "skin":
			Database::query("UPDATE items SET min_order=?, max_order=? WHERE id=? LIMIT 1", array(1, 1, $url[2]));
			break;
		default:
			Database::query("UPDATE items SET min_order=?, max_order=? WHERE id=? LIMIT 1", array(-99, 99, $url[2]));
	}
	
	$itemData = AppAvatar::itemData($url[2]);
}

if(Form::submitted("upload-item"))
{
	$count = count($_FILES['image']['name']);
	for($i=0; $i<$count; $i++)
	{
		$image = array();
		foreach($_FILES['image'] as $key => $val)
		{
			$image[$key] = $val[$i];
		}	
		$imageUpload = new ImageUpload($image);
		$imageUpload->minHeight = 0;
		$imageUpload->minWidth = 0;
		$imageUpload->maxWidth = 205;
		$imageUpload->maxHeight = 383;
		$imageUpload->maxFilesize = 1024 * 1000;
		$imageUpload->save(APP_PATH . "/avatar_items/" . $itemData['position'] . "/" . $itemData['title'] . "/" . $imageUpload->filename . "." . $imageUpload->extension, ImageUpload::MODE_OVERWRITE);
	}
}

if(isset($_GET['delete']) || Form::submitted("upload-item"))
{
	$results = Dir::getFiles(APP_PATH . "/avatar_items/" . $itemData['position'] . "/" . $itemData['title']);
	if($results)
	{
		sort($results);
		// Find respective first female and male image
		$has_gender = array("female" => false, "male" => false);
		foreach($results as $result)
		{
			if(substr($result,-11) == "_female.png" && $has_gender['female'] === false)
			{
				$has_gender['female'] = $result;
				if ($has_gender['male'] !== false)
					break;
			}
			if(substr($result,-9) == "_male.png" && $has_gender['male'] === false)
			{
				$has_gender['male'] = $result;
				if ($has_gender['female'] !== false)
					break;
			}
		}

		// Copy the images to a new location
		foreach($has_gender as $key => $val)
		{
			if($val !== false)
			{
				$image = new Image(APP_PATH . "/avatar_items/" . $itemData['position'] . "/" . $itemData['title'] . "/" . $val);
				
				if($image->height > 100) 		{ $image->autoHeight(100); }
				if($image->width > 80) 			{ $image->autoWidth(80); }
				
				$image->save(APP_PATH . "/avatar_items/" . $itemData['position'] . "/" . $itemData['title'] . "/default_" . $key . ".png");
			}
		}
	}
}

if(Form::submitted("shop-item"))
{
	foreach($_POST as $key => $val)
	{
		if(substr($key, 0, 5) == "shop_")
		{
			$val = (float) $val;
			$shop = (int) substr($key, strpos($key, "_") + 1);
			AppAvatarAdmin::deleteShopItem($shop, $url[2]);
			if($val > 0)
			{
				AppAvatarAdmin::addShopItem($shop, $url[2], $val);
			}
		}
	}
}

if(Form::submitted("ep-item"))
{
	Database::query("DELETE FROM packages_content WHERE item_id=? LIMIT 1", array($url[2]));
	$_POST['ep'] = (int) $_POST['ep'];
	if($_POST['ep'] > 0)
	{
		if($exists = Database::selectOne("SELECT id FROM packages WHERE id=? LIMIT 1", array($_POST['ep'])))
		{
			Database::query("INSERT INTO packages_content VALUES (?, ?)", array($url[2], (int) $exists['id']));
		}
	}
}

if(Form::submitted("coordinates-item"))
{
	AppAvatarAdmin::editItemCoordinates($url[2], (int) $_POST['mX'], (int) $_POST['mY'], (int) $_POST['fX'], (int) $_POST['fY']);	
	$itemData = AppAvatar::itemData($url[2]);
}

// Set page title
$config['pageTitle'] = "Edit Item";

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
	<h2>Edit Item</h2>
	<form class="uniform" action="/staff/item-edit/' . $url[2] . '" method="post">' . Form::prepare("edit-item") . '
		<p><input type="text" name="title" maxlength="30" value="' . $itemData['title'] . '"/> title</p>
		<p><select name="position">';
$positions = AppAvatar::positions();
foreach($positions as $position)
{
	echo '<option value="' . $position . '"' . ($itemData['position'] == $position ? ' selected' : '') . '>' . $position . '</option>';
}
echo '</select> layer</p>
		<p><select name="gender"><option value="b"' . ($itemData['gender'] == "b" ? ' selected' : '') . '>both</option><option value="f"' . ($itemData['gender'] == "f" ? ' selected' : '') . '>female</option><option value="m" ' . ($itemData['gender'] == "m" ? ' selected' : '') . '>male</option></select> gender</p>
		<p><select name="rarity"><option value="0"' . ($itemData['rarity_level'] == 0 ? ' selected' : '') . '>freely purchasable</option><option value="1"' . ($itemData['rarity_level'] == 1 ? ' selected' : '') . '>not purchasable</option><option value="2"' . ($itemData['rarity_level'] == 2 ? ' selected' : '') . '>EP item</option></select> rarity</p>
		<p><select name="relation"><option value="free">free</option><option value="above"' . ($itemData['min_order'] == 2 && $itemData['max_order'] == 99 ? ' selected' : '') . '>above base</option><option value="skin"' . ($itemData['min_order'] == 1 && $itemData['max_order'] == 1 ? ' selected' : '') . '>on base (skin)</option><option value="below"' . ($itemData['min_order'] == -99 && $itemData['max_order'] == -1 ? ' selected' : '') . '>below base</option></select> relation to base</p>
		<p><input type="submit" value="Edit"/></p>
	</form>
	<div class="spacer"></div>';
	
// shop
$costs = array();
$shops = Database::selectMultiple("SELECT shop_id, cost FROM shop_inventory WHERE item_id=?", array($url[2]));
foreach($shops as $shop)
{
	$costs[$shop['shop_id']] = $shop['cost'];
}
echo '
	<form class="uniform" action="/staff/item-edit/' . $url[2] . '" method="post">' . Form::prepare("shop-item") . '
		<p>The availability/Auro cost in different shops. Set to 0 if not available in the shop.<br/>The Wrappers shop is not listed here because it is handled automatically.</p>
		<p><input type="number" name="shop_1" step="any" value="' . (isset($costs[1]) ? $costs[1] : 0) .  '"/> A Cut Above<br/>
		<input type="number" name="shop_4" step="any" value="' . (isset($costs[4]) ? $costs[4] : 0) .  '"/> Pr&ecirc;t &agrave; Porter<br/>
		<input type="number" name="shop_7" step="any" value="' . (isset($costs[7]) ? $costs[7] : 0) .  '"/> Haute Couture<br/>
		<input type="number" name="shop_10" step="any" value="' . (isset($costs[10]) ? $costs[10] : 0) .  '"/> Time Capsule<br/>
		<input type="number" name="shop_2" step="any" value="' . (isset($costs[2]) ? $costs[2] : 0) .  '"/> All That Glitters<br/>
		<input type="number" name="shop_5" step="any" value="' . (isset($costs[5]) ? $costs[5] : 0) .  '"/> Body Shop<br/>
		<input type="number" name="shop_8" step="any" value="' . (isset($costs[8]) ? $costs[8] : 0) .  '"/> Junk Drawer<br/>
		<input type="number" name="shop_11" step="any" value="' . (isset($costs[11]) ? $costs[11] : 0) .  '"/> Under Dressed<br/>
		<input type="number" name="shop_3" step="any" value="' . (isset($costs[3]) ? $costs[3] : 0) .  '"/> Heart and Sole<br/>
		<input type="number" name="shop_6" step="any" value="' . (isset($costs[6]) ? $costs[6] : 0) .  '"/> Finishing Touch<br/>
		<input type="number" name="shop_9" step="any" value="' . (isset($costs[9]) ? $costs[9] : 0) .  '"/> Looking Glass<br/>
		<input type="number" name="shop_12" step="any" value="' . (isset($costs[12]) ? $costs[12] : 0) .  '"/> Vogue Veneers</p>
		<p><input type="number" name="shop_15" step="any" value="' . (isset($costs[15]) ? $costs[15] : 0) .  '"/> Avatar Museum<br/>
		<input type="number" name="shop_18" step="any" value="' . (isset($costs[18]) ? $costs[18] : 0) .  '"/> Credit Shop<br/>
		<input type="number" name="shop_14" step="any" value="' . (isset($costs[14]) ? $costs[14] : 0) .  '"/> Exotic Exhibit</p>
		<p><input type="number" name="shop_13" step="any" value="' . (isset($costs[13]) ? $costs[13] : 0) .  '"/> Archive<br/>
		<input type="number" name="shop_16" step="any" value="' . (isset($costs[16]) ? $costs[16] : 0) .  '"/> Staff Shop<br/>
		<input type="number" name="shop_17" step="any" value="' . (isset($costs[17]) ? $costs[17] : 0) .  '"/> Test Shop</p>
		<p><input type="submit" value="Set"/></p>
	</form>
	<div class="spacer"></div>';
	
// EP
$eps = Database::selectMultiple("SELECT id, title FROM packages WHERE 1 ORDER BY id DESC", array());
if(!$in = Database::selectOne("SELECT package_id FROM packages_content WHERE item_id=? LIMIT 1", array($url[2])))
{
	$in['package_id'] = 0;
}
echo '
	<form class="uniform" action="/staff/item-edit/' . $url[2] . '" method="post">' . Form::prepare("ep-item") . '
		<p><select name="ep"><option value="0"></option>';
foreach($eps as $ep)
{
	echo '<option value="' . $ep['id'] . '"' . ($in['package_id'] == $ep['id'] ? ' selected' : '') . '>' . $ep['title'] . '</option>';
}
echo '</select> EP</p>
		<p><input type="submit" value="Set"/></p>
	</form>
	<div class="spacer"></div>';
	
// display positioning
if($itemData['gender'] != "m")
{
	echo '
	<img src="draw-preview?item=' . $url[2] . '&gender=female&base=white"/>';
}
if($itemData['gender'] != "f")
{
	echo '
	<img src="draw-preview?item=' . $url[2] . '&gender=male&base=white"/>';
}
echo '<br/>';
if($itemData['gender'] != "m")
{
	echo '
	<img src="draw-preview?item=' . $url[2] . '&gender=female&base=dark"/>';
}
if($itemData['gender'] != "f")
{
	echo '
	<img src="draw-preview?item=' . $url[2] . '&gender=male&base=dark"/>';
}
echo '
	<form class="uniform" action="/staff/item-edit/' . $url[2] . '" method="post">' . Form::prepare("coordinates-item") .  '
		<p><input type="number" name="fX" value="' . $itemData['coord_x_female'] . '"/> <input type="number" name="fY" value="' . $itemData['coord_y_female'] . '"/> female</p>
		<p><input type="number" name="mX" value="' . $itemData['coord_x_male'] . '"/> <input type="number" name="mY" value="' . $itemData['coord_y_male'] . '"/> male</p>
		<p><input type="submit" value="Move"/></p>
	</form>
	<div class="spacer"></div>';
	
// display image form
echo '
	<form class="uniform" method="post" action="/staff/item-edit/' . $url[2] . '" enctype="multipart/form-data">' . Form::prepare("upload-item") . '
		<p><input type="file" class="button" name="image[]" multiple> <input type="submit" value="Upload"></p>
	</form>
	<div class="spacer"></div>';
	
// display file list to remove from
$files = Dir::getFiles(APP_PATH . "/avatar_items/" . $itemData['position'] . "/" . $itemData['title']);
foreach($files as $file)
{
	if(strpos($file, "default_") === false)
	{
		echo '
	<a href="/staff/item-edit/' . $url[2] . '?delete=' . substr($file, 0, -4) . '" onclick="return confirm(\'Are you sure you want to delete this file?\');">&#10006;</a> ' . $file . "<br/>";
	}
}

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");