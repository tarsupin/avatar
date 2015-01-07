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

if(Form::submitted("create-wrapper"))
{
	$_POST['id'] = (int) $_POST['id'];
	FormValidate::input("Content", $_POST['content'], 1, 255);
	$_POST['content'] = explode(",", $_POST['content']);
	foreach($_POST['content'] as $key => $val)
	{
		$_POST['content'][$key] = (int) $val;
		if($val == $_POST['id'])
		{
			unset($_POST['content'][$key]);
		}
	}
	$_POST['content'] = array_unique($_POST['content']);
	
	// check if item is already a wrapper
	if(!$wrapper = Database::selectOne("SELECT id FROM wrappers WHERE id=? LIMIT 1", array($_POST['id'])))
	{
		// check existence of wrapper and content
		if($wrap = AppAvatar::itemData($wrapper['id']))
		{
			foreach($_POST['content'] as $p)
			{
				if(!$item = AppAvatar::itemData($p, "id"))
				{
					Alert::error("Not Found", "A content item does not exist.");
					break;
				}
			}
			
			if(FormValidate::pass())
			{
				Database::startTransaction();
				
				// create duplicate if necessary
				if(isset($_POST['replacement']))
				{
					if(AppAvatarAdmin::createItem($wrap['title'], $wrap['position'], $wrap['gender'], $wrap['rarity_level'], $wrap['coord_x_male'], $wrap['coord_y_male'], $wrap['coord_x_female'], $wrap['coord_y_female']))
					{
						$replace = Database::$lastID;
						if($cost = AppAvatar::itemMinCost($wrap['id']))
						{
							if(AppAvatarAdmin::addShopItem(19, $replace, $cost))
							{
								Alert::success("Replacement Added", "The replacement item has been added.");
							}
							else
							{
								Alert::error("Replacement Not Added", "The replacement item could not be added.");
							}
						}
						else
						{
							Alert::error("No Cost", "The cost of the wrapping item could not be found.");
						}
					}
					else
					{
						Alert::error("No Replacement", "The replacement item could not be created.");
					}
				}
				
				if(FormValidate::pass())
				{
					if(Database::query("INSERT INTO wrappers VALUES (?, ?, ?)", array($wrap['id'], implode(",", $_POST['content']), (isset($replace) ? $replace : 0))))
					{
						Database::endTransaction();
						Alert::success("Wrapper Added", "The wrapper has been created.");
					}
					else
					{
						Database::endTransaction(false);
						Alert::error("Wrapper Not Added", "The wrapper could not be created.");
					}
				}
				else
				{
					Database::endTransaction(false);
				}
			}
		}
		else
		{
			Alert::error("Not Found", "The wrapping item does not exist.");
		}
	}
	else
	{
		Alert::error("Is Wrapper", "This item is already a wrapper.");
	}
}

// Set page title
$config['pageTitle'] = "Manage Wrappers";

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
<div class="overwrap-line">Create Wrapper</div>
	<div class="inner-box">
	<span style="color:red">This tool is untested! Please contact Pegasus before first use.</span>
	<form class="uniform" method="post">' . Form::prepare("create-wrapper") . '
		<p><input type="number" name="item" maxlength="8"/> wrapper ID</p>
		<p><input type="text" name="content" maxlength="255"/> content IDs (separated by comma)</p>
		<p><input type="checkbox" name="replacement"/> needs replacement (wrapping item cannot be put together from the pieces)</p>
		<p><input type="submit" value="Create"/></p>
	</form>
	</div>
</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");