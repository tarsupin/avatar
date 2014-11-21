<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/transfer");
}

// Make sure you have an avatar
if(!isset($avatarData['base']))
{
	header("Location: /create-avatar"); exit;
}

// Run Action to Transfer
if(Form::submitted("transfer"))
{
	$_POST['account'] = Sanitize::variable($_POST['account'], ".");
	$_POST['password'] = trim($_POST['password']);
	$pass = Database::selectOne("SELECT account, password, auro FROM _transfer_accounts WHERE account=? AND uni6_id=? LIMIT 1", array($_POST['account'], 0));
	if(!$pass)
	{
		Alert::error("Wrong Username", "The user " . $_POST['account'] . " does not exist on Uni5, or you have already transferred.");
	}
	else
	{
		// check password
		if($pass['password'] == sha1($_POST['password']))
		{
			Database::startTransaction();
		
			// transfer Auro
			$pass['auro'] = (int) $pass['auro'];
			if($pass['auro'] > 0)
			{
				if(Auro::grant(Me::$id, $pass['auro'], "Transfer from Uni5", $config['site-name']))
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
				if(AppTransfer::transferItems(Me::$id, $pass['account']))
				{
					Alert::success("Item Transfer", 'Your items have been transferred. You can view them <a href="/dress-avatar">here</a>.');
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
				if(AppTransfer::transferPackages(Me::$id, $pass['account']))
				{
					Alert::success("Package Transfer", 'Your packages have been transferred. You can view them <a href="/exotic-open">here</a>.');
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
					$has_slots = Database::selectOne("SELECT max FROM user_max_avatars WHERE uni_id=? LIMIT 1", array(Me::$id));
					if($has_slots == array())
					{
						if(!Database::query("INSERT INTO user_max_avatars VALUES (?, ?)", array(Me::$id, (int) $max['max'])))
						{
							Alert::error("Slot Transfer", "The avatar slot transfer has failed.");
							Database::endTransaction(false);
						}
						else
						{
							Alert::success("Avatar Slot Transfer", 'The extra avatar slots have been transferred.');
							Database::query("DELETE FROM _transfer_max_avatars WHERE account=? LIMIT 1", array($pass['account']));
						}
					}
					else
					{
						if($has_slots['max'] + $max['max'] - 3 <= 9)
						{
							if(!Database::query("UPDATE user_max_avatars SET max=max+? WHERE uni_id=? LIMIT 1", array((int) $max['max']-3, Me::$id)))
							{
								Alert::error("Slot Transfer", "The avatar slot transfer has failed.");
								Database::endTransaction(false);
							}
							else
							{
								Alert::success("Avatar Slot Transfer", 'The extra avatar slots have been transferred.');
								Database::query("DELETE FROM _transfer_max_avatars WHERE account=? LIMIT 1", array($pass['account']));
							}
						}
						else
						{
							Alert::error("Slot Transfer", "You will have more than 9 avatar slots! Please have an admin or programmer take care of this manually before trying the transfer again.");
							Database::endTransaction(false);
						}
					}
				}
			}
			
			if(!Alert::hasErrors())
			{
				Database::query("UPDATE _transfer_accounts SET uni6_id=? WHERE account=? LIMIT 1", array(Me::$id, $pass['account']));
				Cache::delete("invLayers:" . Me::$id);
				Database::endTransaction();
			}
		}
		else
		{
			Alert::error("Wrong Password", "The password does not match.");
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
	<p>This will transfer your Auro, items, donation packages and additional avatar slots (if applicable).<br/>Credits need to be transferred in a separate process <a href="' . URL::unijoule_com() . '/transfer-unijoule">on this page</a>.</p>
	
	<form class="uniform" method="post">' . Form::prepare("transfer") . '
		<h4>Uni5 Account Name</h4>
		<p><input type="text" name="account"/></p>
		<h4>Uni5 Password</h4>
		<p><input type="password" name="password"/></p>
		<input type="submit" name="submit" value="Transfer" />
	</form>
	</div>
</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
