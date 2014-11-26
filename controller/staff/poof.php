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

if(Form::submitted("poof-item"))
{
	$_POST['amount'] = (int) $_POST['amount'];
	$_POST['item'] = Sanitize::word($_POST['item'], " ");
	$_POST['account'] = Sanitize::variable($_POST['account'], ".");
	$_POST['reason'] = Sanitize::punctuation($_POST['reason']);
	$account = User::getIDByHandle($_POST['account']);	
	if($account != 0)
	{
		$item = Database::selectMultiple("SELECT id, title FROM items WHERE title=? LIMIT 2", array($_POST['item']));
		if(count($item) == 1)
		{
			Database::startTransaction();
			for($i=0; $i<$_POST['amount']; $i++)
			{
				if(!AppAvatar::receiveItem($account, (int) $item[0]['id'], $_POST['reason']))
				{
					Alert::error("Package Not Given", $item[0]['title'] . " could not be given to " . $_POST['account'] . ".");
					Database::endTransaction(false);
					break;
				}
			}
			
			if(!Alert::hasErrors())
			{
				Database::endTransaction();
				Alert::success("Package Given", $_POST['amount'] . " " . $item[0]['title'] . "(s) was/were given to " . $_POST['account'] . ".");
			}
		}
		else
		{
			Alert::error("Not Exist", "This item does not exist, or there are multiple items by the same name. Please give it manually.");
		}
	}
	else
	{
		Alert::error("Not Found", $_POST['account'] . " does not use the Avatar system.");
	}
}

$packages = array();
$pack = Database::selectMultiple("SELECT id, title FROM packages", array());
foreach($pack as $p)
{
	$packages[$p['id']] = $p['title'];
}
krsort($packages);
unset($pack);

if(Form::submitted("poof-package"))
{
	$_POST['amount'] = (int) $_POST['amount'];
	$_POST['package'] = (int) $_POST['package'];
	$_POST['account'] = Sanitize::variable($_POST['account'], ".");
	$_POST['reason'] = Sanitize::punctuation($_POST['reason']);
	$account = User::getIDByHandle($_POST['account']);	
	if($account != 0)
	{
		if(isset($packages[$_POST['package']]))
		{
			Database::startTransaction();
			for($i=0; $i<$_POST['amount']; $i++)
			{
				if(!AppAvatar::receivePackage($account, $_POST['package'], $_POST['reason']))
				{
					Alert::error("Package Not Given", $packages[$_POST['package']] . " could not be given to " . $_POST['account'] . ".");
					Database::endTransaction(false);
					break;
				}
			}
			
			if(!Alert::hasErrors())
			{
				Database::endTransaction();
				Alert::success("Package Given", $_POST['amount'] . " " . $packages[$_POST['package']] . "(s) was/were given to " . $_POST['account'] . ".");
			}
		}
		else
		{
			Alert::error("Not Exist", "This package does not exist.");
		}
	}
	else
	{
		Alert::error("Not Found", $_POST['account'] . " does not use the Avatar system.");
	}
}

// Set page title
$config['pageTitle'] = "Poof Item/Package";

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
Alert::display() . '
<div class="overwrap-box">
	<div class="overwrap-line">Poof Item</div>
	<div class="inner-box">	
	<form class="uniform" method="post">' . Form::prepare("poof-item") . '
		<h4>Handle</h4>
		<p><input type="text" name="account"/></p>
		<h4>Item</h4>
		<p><input type="text" name="item" maxlength="30"/></p>
		<h4>Amount</h4>
		<p><input type="number" name="amount" value="1" min="1" max="100"/></p>
		<h4>Reason</h4>
		<p><input type="text" name="reason" value="Official Event Prize" maxlength="64"/></p>
		<input type="submit" value="Give Item" />
	</form>
	</div>
</div>

<div class="overwrap-box">
	<div class="overwrap-line">Poof Package</div>
	<div class="inner-box">	
	<form class="uniform" method="post">' . Form::prepare("poof-package") . '
		<h4>Handle</h4>
		<p><input type="text" name="account"/></p>
		<h4>Package</h4>
		<p><select name="package">';
foreach($packages as $key => $title)
{
	echo '
			<option value="' . $key . '">' . $title . '</option>';
}
echo '
		</select></p>
		<h4>Amount</h4>
		<p><input type="number" name="amount" value="1" min="1" max="100"/></p>
		<h4>Reason</h4>
		<p><input type="text" name="reason" value="Official Event Prize" maxlength="64"/></p>
		<input type="submit" value="Give Package" />
	</form>
	</div>
</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
