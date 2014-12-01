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


$eps = Database::selectMultiple("SELECT * FROM packages WHERE 1 ORDER BY id DESC", array());
$pass = false;
if(isset($url[2]))
{
	foreach($eps as $ep)
	{
		if($ep['id'] == $url[2])
		{
			$pass['id'] = (int) $ep['id'];
			$pass['title'] = $ep['title'];
			$pass['year'] = (int) $ep['year'];
			$pass['month'] = (int) $ep['month'];
			break;
		}
	}
}
if($pass == false)
{
	unset($url[2]);
}

if(Form::submitted("create-package"))
{
	$_POST['year'] = (int) $_POST['year'];
	$_POST['month'] = (int) $_POST['month'];
	FormValidate::number("Year", $_POST['year'], 2014);
	FormValidate::number("Month", $_POST['month'], 1, 12);
	FormValidate::input("Title", $_POST['title'], 1, 20);
	if(FormValidate::pass())
	{
		$exists = Database::selectOne("SELECT id FROM packages WHERE year=? AND month=? LIMIT 1", array($_POST['year'], $_POST['month']));
		if($exists == array())
		{
			if(Database::query("INSERT INTO packages (title, year, month) VALUES (?, ?, ?)", array($_POST['title'], $_POST['year'], $_POST['month'])))
			{
				header("Location: /staff/package-manage/" . Database::$lastID);
				exit;
			}
		}
		else
		{
			Alert::error("Exists", "An EP for this month already exists.");
		}
	}
}

if(Form::submitted("edit-package"))
{
	FormValidate::input("Title", $_POST['title'], 1, 20);
	if(FormValidate::pass())
	{
		if(Database::query("UPDATE packages SET title=? WHERE id=? LIMIT 1", array($_POST['title'], $pass['id'])))
		{
			$pass['title'] = $_POST['title'];
		}
	}
}


if(Form::submitted("upload-package"))
{
	if(File::exists(APP_PATH . '/assets/exotic_packages/' . lcfirst(date("F", mktime(0, 0, 0, $pass['month'], 1))) . '_' . $pass['year'] . '.png'))
	{
		File::delete(APP_PATH . '/assets/exotic_packages/' . lcfirst(date("F", mktime(0, 0, 0, $pass['month'], 1))) . '_' . $pass['year'] . '.png');
	}
	$imageUpload = new ImageUpload($_FILES['image']);
	$imageUpload->minHeight = 0;
	$imageUpload->minWidth = 0;
	$imageUpload->maxWidth = 205;
	$imageUpload->maxHeight = 383;
	$imageUpload->maxFilesize = 1024 * 1000;
	$imageUpload->save(APP_PATH . '/assets/exotic_packages/' . lcfirst(date("F", mktime(0, 0, 0, $pass['month'], 1))) . '_' . $pass['year'] . '.png', ImageUpload::MODE_OVERWRITE);
}

// Set page title
$config['pageTitle'] = "Manage EPs";

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
<div id="content">' . Alert::display() . '	';
if(isset($url[2]))
{
	echo '
<div class="overwrap-box">
<div class="overwrap-line">Edit ' . $pass['title'] . '</div>
	<div class="inner-box">
	<form class="uniform" method="post">' . Form::prepare("edit-package") . '
		<p><input type="text" name="title" maxlength="20" value="' . $pass['title'] . '"/> <input type="submit" value="Edit"/></p>
	</form>';
	if(File::exists('assets/exotic_packages/' . lcfirst(date("F", mktime(0, 0, 0, $pass['month'], 1))) . '_' . $pass['year'] . '.png'))
	{
		echo '<img src="assets/exotic_packages/' . lcfirst(date("F", mktime(0, 0, 0, $pass['month'], 1))) . '_' . $pass['year'] . '.png"/>';
	}
	echo '
	<form class="uniform" method="post" enctype="multipart/form-data">' . Form::prepare("upload-package") . '
		<p><input type="file" class="button" name="image"> <input type="submit" value="Upload/Replace"></p>
	</form>
	</div>
</div>
</div>';
}
else
{
	echo '
<div class="overwrap-box">
<div class="overwrap-line">Create EP</div>
	<div class="inner-box">
	<form class="uniform" method="post">' . Form::prepare("create-package") . '
		<p><input type="text" name="title" maxlength="20"/> title</p>
		<p><input type="text" name="year" maxlength="4" value="' . date("Y", mktime(0, 0, 0, date("n")+1)) . '"/> year</p>
		<p><input type="text" name="month" maxlength="2" value="' . date("n", mktime(0, 0, 0, date("n")+1)) . '"/> month</p>
		<p><input type="submit" value="Create"/></p>
	</form>
	</div>
</div>
<div class="overwrap-box">
<div class="overwrap-line">Edit EP</div>
	<div class="inner-box">';
	foreach($eps as $ep)
	{
		echo '<a href="/staff/package-manage/' . $ep['id'] . '">' . $ep['title'] . '</a><br/>';
	}
	echo '
	</div>
</div>';
}
echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");