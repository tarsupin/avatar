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

// Run Action to Transfer
if(Form::submitted("transfer-staff"))
{
	$_POST['accountfrom'] = Sanitize::variable($_POST['accountfrom'], ".");
	$_POST['accountto'] = Sanitize::variable($_POST['accountto'], ".");
	
	$from = User::getIDByHandle($_POST['accountfrom']);		
	$to = User::getIDByHandle($_POST['accountto']);		
	if($from != 0 && $to != 0)
	{
		Database::startTransaction();
		
		// transfer items
		$success = Database::query("UPDATE user_items SET uni_id=? WHERE uni_id=?", array($to, $from));
		if(!$success)
		{
			Alert::error("Item Transfer", "The item transfer has failed.");
			Database::endTransaction(false);
		}
		else
		{
			Alert::success("Item Transfer", 'The items have been transferred.');
		}
	
		// transfer packages
		if(!Alert::hasErrors())
		{
			$success = Database::query("UPDATE user_packages SET uni_id=? WHERE uni_id=?", array($to, $from));
			if(!$success)
			{
				Alert::error("Package Transfer", "The package transfer has failed.");
				Database::endTransaction(false);
			}
			else
			{
				Alert::success("Package Transfer", 'The packages have been transferred.');
			}
		}
		
		// transfer extra avatar slots
		if(!Alert::hasErrors())
		{
			if($max = Database::selectOne("SELECT max FROM _transfer_max_avatars WHERE account=? LIMIT 1", array($from)))
			{
				$has_slots = Database::selectOne("SELECT max FROM user_max_avatars WHERE uni_id=? LIMIT 1", array($to));
				if($has_slots == array())
				{
					if(!Database::query("INSERT INTO user_max_avatars VALUES (?, ?)", array($to, (int) $max['max'])))
					{
						Alert::error("Slot Transfer", "The avatar slot transfer has failed.");
						Database::endTransaction(false);
					}
					else
					{
						Alert::success("Avatar Slot Transfer", 'The extra avatar slots have been transferred.');
						Database::query("DELETE FROM user_max_avatars WHERE uni_id=? LIMIT 1", array($from));
					}
				}
				else
				{
					if($has_slots['max'] + $max['max'] - 3 <= 9)
					{
						if(!Database::query("UPDATE user_max_avatars SET max=max+? WHERE uni_id=? LIMIT 1", array((int) $max['max']-3, $to)))
						{
							Alert::error("Slot Transfer", "The avatar slot transfer has failed.");
							Database::endTransaction(false);
						}
						else
						{
							Alert::success("Avatar Slot Transfer", 'The extra avatar slots have been transferred.');
							Database::query("DELETE FROM user_max_avatars WHERE uni_id=? LIMIT 1", array($from));
						}
					}
					else
					{
						Alert::error("Slot Transfer", $_POST['accountto'] . " will have more than 9 avatar slots! Please take care of this manually before trying the transfer again.");
						Database::endTransaction(false);
					}
				}
			}
		}
		
		if(!Alert::hasErrors())
		{
			Database::query("UPDATE _transfer_accounts SET uni6_id=? WHERE uni6_id=?", array($to, $from));
			Cache::delete("invLayers:" . $from);
			Cache::delete("invLayers:" . $to);
			Database::endTransaction();
		}
	}
	else
	{
		Alert::error("Not Found", "One or both do not use the Avatar system.");
	}
}

// Set page title
$config['pageTitle'] = "Transfer Uni6 to Uni6";

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
	<div class="overwrap-line">Transfer Uni6 to Uni6</div>
	<div class="inner-box">
	<p>This staff tool allows to move a user\'s Uni6 inventory (items, EPs, avatar slots if possible) to a different Uni6 account. Auro and UniJoule must be transferred manually, either by the user or the admin, because we cannot determine the owned amount automatically.</p>
	
	<form class="uniform" method="post">' . Form::prepare("transfer-staff") . '
		<h4>From (Account Name)</h4>
		<p><input type="text" name="accountfrom"/></p>
		<h4>To (Account Name)</h4>
		<p><input type="text" name="accountto"/></p>
		<input type="submit" name="submit" value="Transfer" />
	</form>
	</div>
</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
