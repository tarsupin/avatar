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
					if(!Database::query("INSERT INTO user_max_avatars VALUES (?, ?)", array(Me::$id, (int) $max['max'])))
					{
						Alert::error("Slot Transfer", "The avatar slot transfer has failed.");
						Database::endTransaction(false);
					}
					else
					{
						Database::query("DELETE FROM _transfer_max_avatars WHERE account=? LIMIT 1", array($pass['account']));
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
Alert::display();

echo '
	<h2>Transfer from Uni5</h2>
	<p>This will transfer your Auro, items, donation packages and additional avatar slots (if applicable). Credits need to be transferred in a separate process that can only be done by the admin, so you might not have access to them right away. They are NOT lost.</p>
	
	<form class="uniform" method="post">' . Form::prepare("transfer") . '
		<h4>Uni5 Account Name</h4>
		<p><input type="text" name="account"/></p>
		<h4>Uni5 Password</h4>
		<p><input type="password" name="password"/></p>
		<input type="submit" name="submit" value="Transfer" />
	</form>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
