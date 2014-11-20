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
	$_POST['account5'] = Sanitize::variable($_POST['account5'], ".");
	$_POST['account6'] = Sanitize::variable($_POST['account6'], ".");
	$pass = Database::selectOne("SELECT account, auro FROM _transfer_accounts WHERE account=? AND uni6_id=? LIMIT 1", array($_POST['account5'], 0));
	if(!$pass)
	{
		Alert::error("Wrong Username", "The user " . $_POST['account5'] . " does not exist on Uni5, or has already transferred.");
	}
	else
	{
		$recipient = User::getIDByHandle($_POST['account6']);		
		if($recipient !== false)
		{
			Database::startTransaction();
		
			// transfer Auro
			$pass['auro'] = (int) $pass['auro'];
			if($pass['auro'] > 0)
			{
				if(Auro::grant($recipient, $pass['auro'], "Transfer from Uni5", $config['site-name']))
				{
					Database::query("UPDATE _transfer_accounts SET auro=? WHERE account=? LIMIT 1", array(0, $pass['account']));
					Alert::success("Auro Transfer", $pass['auro'] . " Auro have been transferred.");
				}
				else
				{
					Alert::error("Auro Transfer", "The Auro transfer has failed.");
					Database::endTransaction(false);
				}
			}
			
			// transfer items
			if(!Alert::hasErrors())
			{
				if(AppTransfer::transferItems($recipient, $pass['account']))
				{
					Alert::success("Item Transfer", 'The items have been transferred.');
				}
				else
				{
					Alert::error("Item Transfer", "The item transfer has failed.");
					Database::endTransaction(false);
				}
			}
			
			// transfer packages
			if(!Alert::hasErrors())
			{
				if(AppTransfer::transferPackages($recipient, $pass['account']))
				{
					Alert::success("Package Transfer", 'The packages have been transferred.');
				}
				else
				{
					Alert::error("Package Transfer", "The package transfer has failed.");
					Database::endTransaction(false);
				}
			}
			
			// transfer extra avatar slots
			if(!Alert::hasErrors())
			{
				if($max = Database::selectOne("SELECT max FROM _transfer_max_avatars WHERE account=? LIMIT 1", array($pass['account'])))
				{
					$has_slots = Database::selectOne("SELECT max FROM user_max_avatars WHERE uni_id=? LIMIT 1", array($recipient));
					if($has_slots == array())
					{
						if(!Database::query("INSERT INTO user_max_avatars VALUES (?, ?)", array($recipient, (int) $max['max'])))
						{
							Alert::error("Slot Transfer", "The avatar slot transfer has failed.");
							Database::endTransaction(false);
						}
						else
						{
							Database::query("DELETE FROM _transfer_max_avatars WHERE account=? LIMIT 1", array($pass['account']));
						}
					}
					else
					{
						if($has_slots['max'] + $max['max'] <= 9)
						{
							if(!Database::query("UPDATE user_max_avatars SET max=max+? WHERE uni_id=? LIMIT 1", array((int) $max['max'], $recipient)))
							{
								Alert::error("Slot Transfer", "The avatar slot transfer has failed.");
								Database::endTransaction(false);
							}
							else
							{
								Database::query("DELETE FROM _transfer_max_avatars WHERE account=? LIMIT 1", array($pass['account']));
							}
						}
						else
						{
							Alert::error("Slot Transfer", $_POST['account6'] . " will have more than 9 avatar slots! Please take care of this manually before trying the transfer again.");
							Database::endTransaction(false);
						}
					}
				}
			}
			
			if(!Alert::hasErrors())
			{
				Database::query("UPDATE _transfer_accounts SET uni6_id=? WHERE account=? LIMIT 1", array($recipient, $pass['account']));
				Cache::delete("invLayers:" . $recipient);
				Database::endTransaction();
			}
		}
		else
		{
			Alert::error("Not Found", "The user " . $_POST['account6'] . " does not use the Avatar system.");
		}
	}
}

// Set page title
$config['pageTitle'] = "Transfer from Uni5";

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
	<div class="overwrap-line">Transfer from Uni5</div>
	<div class="inner-box">
	<p>This is the STAFF tool! It does the same as the public one, but please do not use it without good reason (such as forgotten passwords).</p>
	
	<form class="uniform" method="post">' . Form::prepare("transfer-staff") . '
		<h4>Uni5 Account Name</h4>
		<p><input type="text" name="account5"/></p>
		<h4>Uni6 Handle (without @)</h4>
		<p><input type="text" name="account6"/></p>
		<input type="submit" name="submit" value="Transfer" />
	</form>
	</div>
</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
