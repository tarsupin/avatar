<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/trade-gift");
}

// Require avatar
if(!isset($avatarData['base']))
{
	header("Location: /create-avatar"); exit;
}

// Set page title
$config['pageTitle'] = "Gift & Trade";

// initializing a transaction
if(Form::submitted("initialize-transaction"))
{
	// check that recipient exists
	$recipient = Sanitize::variable($_POST['recipient']);
	$recipientID = User::getDataByHandle($recipient);
	if($recipientID == array())
	{
		Alert::error("Recipient Missing", $recipient . " does not use the avatar system.");
	}
	// prepare transaction
	else
	{
		$recipientID = (int) $recipientID['uni_id'];		
		$transactionID = Transaction::create("Gift/Trade " . Me::$id . " to " . $recipientID);
		Transaction::addUser($transactionID, Me::$id);
		Transaction::addUser($transactionID, $recipientID);
		Alert::saveSuccess("Transaction Started", "You have started a transaction with " . $recipient . "!");
		header("Location: /gift-trade/" . $transactionID . '?position=auro'); exit;
	}
}

// get information about pending transaction
if(isset($url[1]) && $url[1] != "new")
{
	$url[1] = (int) $url[1];
	
	// search for transaction
	$mine = false;
	$users = Database::selectMultiple("SELECT uni_id, has_agreed, message FROM transactions_users WHERE transaction_id=?", array($url[1]));
	$approval = array();
	$mymessage = "";
	$contrib = array();
	foreach($users as $trans)
	{
		$trans['uni_id'] = (int) $trans['uni_id'];
		$approval[$trans['uni_id']] = (int) $trans['has_agreed'];
		if($trans['uni_id'] == Me::$id)
		{
			$mine = true;
			$mymessage = $trans['message'];
		}
		else
		{
			$recipientID = $trans['uni_id'];
			$recipient = User::get($recipientID, "handle");
			$recipient = $recipient['handle'];
			
			if($trans['message'] != "")
			{
				Alert::info("Message", "Message from " . $recipient . ":<br/>" . $trans['message']);
			}
		}
		
		$contrib[$trans['uni_id']] = Database::selectOne("SELECT id FROM transactions_entries WHERE transaction_id=? AND uni_id=? LIMIT 1", array($url[1], $trans['uni_id']));
		if($contrib[$trans['uni_id']] != array())	{ $contrib[$trans['uni_id']] = true; }
		else										{ $contrib[$trans['uni_id']] = false; }
	}
	
	// go to overview if not your transaction
	if(!$mine)	{ header("Location: /gift-trade"); exit; }
	
	// get user name
	$sender = Me::$vals['handle'];
	
	// submit gift or trade
	if(Form::submitted("gift-trade"))
	{
		if(isset($_POST['cancel']))
		{
			Transaction::delete($url[1]);
			Notifications::create($recipientID, SITE_URL . "/dress-avatar", $sender . ' has cancelled a transaction with you.');
			Alert::saveSuccess("Cancelled", "You have cancelled the transaction with " . $recipient . ".");
			header("Location: /gift-trade"); exit;
		}
	
		elseif(isset($_POST['gift']) || isset($_POST['gift_anon']))
		{
			// check that it is indeed a gift and the other person has not added anything
			if(!$contrib[$recipientID])
			{
				$entries = Database::selectMultiple("SELECT id, process_parameters FROM transactions_entries WHERE transaction_id=?", array($url[1]));
				// set all entries to anonymous
				if(isset($_POST['gift_anon']))
				{
					foreach($entries as $entry)
					{
						$entry['process_parameters'] = json_decode($entry['process_parameters'], true);
						if(count($entry['process_parameters']) < 5)
						{
							$entry['process_parameters'][] = true;
							$entry['process_parameters'] = json_encode($entry['process_parameters']);
							Database::query("UPDATE transactions_entries SET process_parameters=? WHERE id=? LIMIT 1", array($entry['process_parameters'], $entry['id']));
						}
					}
				}
				// set all entries to normal
				else
				{
					foreach($entries as $entry)
					{
						$entry['process_parameters'] = json_decode($entry['process_parameters'], true);
						if(count($entry['process_parameters']) == 5)
						{
							unset($entry['process_parameters'][4]);
							$entry['process_parameters'] = json_encode($entry['process_parameters']);
							Database::query("UPDATE transactions_entries SET process_parameters=? WHERE id=? LIMIT 1", array($entry['process_parameters'], $entry['id']));
						}
					}
				}
				
				Transaction::approve($url[1], $recipientID);
				$pass = Transaction::approve($url[1], Me::$id);
				if($pass)
				{
					Alert::saveSuccess("Gift Sent", "Your gift has been sent to " . $recipient . "!");
					Notifications::create($recipientID, SITE_URL . "/dress-avatar", 'You have received a gift from ' . (isset($_POST['gift_anon']) ? 'an anonymous gifter' : $sender) . '! Check the logs for details.');
					Transaction::delete($url[1]);
					header("Location: /gift-trade"); exit;
				}
				else
				{
					Alert::error("Gift Not Sent", "You do not have enough Auro and/or own all of the items you wish to send.");
				}
			}
			else
			{
				Alert::error("Not Gift", $recipient . ' has added entries to this transaction. Therefore you cannot send it as a gift. Please try the "Trade" button instead.');
			}
		}
		
		elseif(isset($_POST['trade']))
		{
			// check that both sides contribute
			if(!$contrib[Me::$id] || !$contrib[$recipientID])
			{
				Alert::error("One Sided", "Trades require both participants to send something. If this is not what you wish to do, please use the Gift function instead.");
			}
		
			// check that the transaction still is as you saw it
			$cache = Cache::get("transaction-" . $url[1] . "-" . Me::$id);
			if($cache)
			{
				$transaction = Database::selectMultiple("SELECT id, uni_id, display FROM transactions_entries WHERE transaction_id=?", array($url[1]));
				if($cache != json_encode($transaction))
				{
					Alert::error("Not Current", 'It seems ' . $recipient . ' has changed and approved their side of the trade while you have been looking at this page.<br/>Please re-check and click the "Trade" button again if you agree to the changes.');
				}
			}
			
			if(!Alert::hasErrors())
			{
				// approve own side, check whether trade can complete
				$approval[Me::$id] = 1;
				if(Transaction::approve($url[1], Me::$id))
				{
					Alert::saveSuccess("Trade Completed", "The trade has been successfully completed!");
					Notifications::create($recipientID, SITE_URL . "/dress-avatar", 'You have completed a trade with ' . $sender . '! Check the logs for details.');
					Transaction::delete($url[1]);
					header("Location: /gift-trade"); exit;
				}
				elseif($approval[$recipientID] == 0)
				{
					Alert::success("Trade Waiting", 'The trade has been successfully updated and now requires confirmation or updating from ' . $recipient . '!');
					Notifications::create($recipientID, SITE_URL . "/gift-trade/" . $url[1], $sender . ' has started or updated a trade with you!');
				}
				else
				{
					Alert::error("Trade Not Sent", "The trade could not be completed. There are one or more entries that cannot be processed. Please make sure that both you and " . $recipient . " have enough Auro and all the Exotic Packages and items you wish to trade.");
				}
			}
		}
	}
	
	if(Form::submitted("gift-trade-message"))
	{
		$_POST['message'] = Sanitize::punctuation($_POST['message']);
		if(Database::query("UPDATE transactions_users SET message=? WHERE transaction_id=? AND uni_id=? LIMIT 1", array($_POST['message'], $url[1], Me::$id)))
		{
			$mymessage = $_POST['message'];
		}
	}

	// add Auro
	if(Form::submitted("auro-transaction"))
	{
		$_POST['auro'] = max(0, round($_POST['auro']));
		
		if($_POST['auro'] > 0)
		{
			// check for previous Auro entries and remove if necessary
			if($transaction = Database::selectOne("SELECT id, process_method FROM transactions_entries WHERE transaction_id=? AND uni_id=? AND process_method=? LIMIT 1", array($url[1], Me::$id, "sendAuro_doTransaction")))
			{
				$transaction['id'] = (int) $transaction['id'];
				Transaction::removeEntry($transaction['id']);
				$approval[Me::$id] = 0;
				$approval[$recipientID] = 0;
			}
		
			if(Transaction::addEntry(Me::$id, $url[1], "AppTrade", "sendAuro", array(Me::$id, $recipientID, $_POST['auro'], "Transaction " . $url[1]), array("image" => "gold.png", "caption" => $_POST['auro'] . " Auro", "description" => $sender . " sends " . $_POST['auro'] . " Auro to " . $recipient . ".")))
			{
				$approval[Me::$id] = 0;
				$approval[$recipientID] = 0;
				$contrib[Me::$id] = true;
				Alert::success("Entry Added", "The Auro have been added to the transaction.");
			}
			else
			{
				Alert::error("Not Added", "The entry could not be added to the transaction.");
			}
		}
	}
	
	// get wrappers for display
	$wrappers = AppAvatar::wrappers();
	
	// add item
	if(isset($_GET['add']) && $link = Link::clicked())
	{
		if($link == "add-" . $_GET['add'])
		{
			$_GET['add'] = (int) $_GET['add'];
			if(!isset($_GET['position']) || $_GET['position'] != "ep")
			{
				// check ownership
				if(AppAvatar::checkOwnItem(Me::$id, $_GET['add']))
				{
					// check item data
					$item = AppAvatar::itemData($_GET['add'], "id, title");
					if($item)
					{
						$item['id'] = (int) $item['id'];
						if(Transaction::addEntry(Me::$id, $url[1], "AppTrade", "sendItem", array(Me::$id, $recipientID, $item['id'], "Transaction " . $url[1]), array("image" => "item.png", "caption" => $_GET['add'] . " " . $item['title'], "description" => $sender . " sends " . $item['title'] . (in_array($item['id'], $wrappers) ? " (Wrapper)" : "") . " to " . $recipient . ".")))
						{
							$approval[Me::$id] = 0;
							$approval[$recipientID] = 0;
							$contrib[Me::$id] = true;
							Alert::success("Entry Added", "The item has been added to the transaction.");
						}
						else
						{
							Alert::error("Not Added", "The entry could not be added to the transaction.");
						}
					}
				}
				else
				{
					Alert::error("Not Owned", "You do not own this item.");
				}
			}
			else
			{
				// check ownership
				if(AppAvatar::checkOwnPackage(Me::$id, $_GET['add']))
				{
					// check package data
					$package = Database::selectOne("SELECT id, title, year FROM packages WHERE id=? LIMIT 1", array($_GET['add']));
					if($package)
					{
						$package['id'] = (int) $package['id'];
						if(Transaction::addEntry(Me::$id, $url[1], "AppTrade", "sendPackage", array(Me::$id, $recipientID, $package['id'], "Transaction " . $url[1]), array("image" => "package.png", "caption" => $_GET['add'] . " " . $package['title'], "description" => $sender . " sends " . $package['title'] . " (" . $package['year'] . ") to " . $recipient . ".")))
						{
							$approval[Me::$id] = 0;
							$approval[$recipientID] = 0;
							$contrib[Me::$id] = true;
							Alert::success("Entry Added", "The package has been added to the transaction.");
						}
						else
						{
							Alert::error("Not Added", "The entry could not be added to the transaction.");
						}
					}
				}
				else
				{
					Alert::error("Not Owned", "You do not own this package.");
				}
			}
		}
	}
	
	// remove entry
	if(isset($_GET['remove']))
	{
		$_GET['remove'] = (int) $_GET['remove'];
		if($transaction = Transaction::getEntry($_GET['remove'], "uni_id"))
		{
			if($transaction['uni_id'] == Me::$id)
			{
				if(Transaction::removeEntry($_GET['remove']))
				{
					$approval[Me::$id] = 0;
					$approval[$recipientID] = 0;					
					$contrib[$contrib[Me::$id]] = Database::selectOne("SELECT id FROM transactions_entries WHERE transaction_id=? AND uni_id=? LIMIT 1", array($url[1], $contrib[Me::$id]));
					if($contrib[$contrib[Me::$id]] == array())	{ $contrib[$contrib[Me::$id]] = false; }					
					Alert::success("Entry Removed", "The entry has been removed from the transaction.");
				}
			}
		}
	}
	
	// output info
	Alert::info("Approval", 'You have' . ((int) $approval[Me::$id] == 0 ? ' not' : '') . ' approved the transaction.<br/>' . $recipient . ' has' . ((int) $approval[$recipientID] == 0 ? ' not' : '') . ' approved the transaction.');
	
	$transaction = Database::selectMultiple("SELECT id, uni_id, display FROM transactions_entries WHERE transaction_id=?", array($url[1]));
	$info = '
	<ul>';
	foreach($transaction as $trans)
	{
		$details = json_decode($trans['display'], true);
		$info .= '
		<li>' . ($trans['uni_id'] == Me::$id ? '<a href="/gift-trade/' . $url[1] . '?' . (isset($_GET['position']) ? 'position=' . $_GET['position'] . '&' : '') . 'remove=' . $trans['id'] . '" onclick="alert(\'Are you sure you want to remove this entry?\');">&#10006;</a> ' : '') . $details['description'] . '</li>';
	}
	$info .= '
	</ul>';
	if($transaction != array())
	{
		Alert::info("Details", $info);
	}
}

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
	<div class="overwrap-line">Gift &amp; Trade</div>
	<div class="inner-box">';

// Overwiew page
if(!isset($url[1]))
{
	echo '
	<p>This page combines gift and trade functionality. This is how it works:</p>
	<p><ol style="list-style-type:decimal;margin-left:1em;">
		<li><a href="/gift-trade/new">Start a new transaction</a> or click on a pending one in the list below.</li>
		<li>Add Auro to the transaction (if applicable).</li>
		<li>Add Exotic Packages to the transaction (if applicable).</li>
		<li>Layer by layer, add the items you wish to send (if applicable).</li>
		<li>There will be a list detailing what is part of the transaction. You can remove Auro, Exotic Packages and items from it.</li>
		<li>After you\'ve made sure that the transaction contains exactly what you want it to, click either the "Gift" or "Trade" button.<br/>- "Gifts" don\'t require any action from the recipient.<br/>- "Trades" need to be updated by the recipient and will then return to you for confirmation.</li>
		<li>Done! The transaction is on its way to the recipient now. They will receive a notification.</li>
	</ol></p>
	</div>
</div>
<div class="overwrap-box">
	<div class="overwrap-line">Pending Transactions</div>
	<div class="inner-box">
	<div class="spacer"></div>
	<ul>';
	
	$pending = Database::selectMultiple("SELECT transaction_id FROM transactions_users WHERE uni_id=?", array(Me::$id));
	foreach($pending as $pend)
	{
		$pend['transaction_id'] = (int) $pend['transaction_id'];
		// blend out transactions that have been approved by neither side (still being created)
		$approved = Database::selectOne("SELECT uni_id FROM transactions_users WHERE transaction_id=? AND has_agreed=? LIMIT 1", array($pend['transaction_id'], 1));
		// don't blend out transactions that you contribute to
		if(!$approved)
		{
			$contrib = Database::selectOne("SELECT id FROM transactions_entries WHERE transaction_id=? AND uni_id=? LIMIT 1", array($pend['transaction_id'], Me::$id));
		}
		if($approved || $contrib)
		{
			echo '
		<li><a href="/gift-trade/' . $pend['transaction_id'] . '"' . (!$approved ? ' class="opaque"' : '') . '>Transaction #' . $pend['transaction_id'];
		$users = Transaction::getUsers($pend['transaction_id']);
			foreach($users as $user)
			{
				if($user != Me::$id)
				{
					$other = User::get($user, "handle");
					echo ' with ' . $other['handle'];
					break;
				}
			}
			echo '</a></li>';
		}
	}
	echo '
	</ul>';
}

// Start transaction
elseif($url[1] == "new")
{
	echo '
	<p>Who do you wish to start this transaction with?</p>
	<form class="uniform" method="post">' . Form::prepare("initialize-transaction") . '
		<p>
			<strong>Recipient</strong><br />
			<input type="text" maxlength="22" name="recipient"/>
		</p>
		<p><input class="button" type="submit" name="submit" value="Start"/></p>
	</form>';
}
// Update transaction
else
{
	// Show Auro and the layers you have access to
	$positions = AppAvatar::getInvPositions(Me::$id);
	
	if(!isset($_GET['position']) || (!in_array($_GET['position'], $positions) && $_GET['position'] != "ep"))
	{
		$_GET['position'] = "auro";
	}
	
	echo '
	<div class="redlinks">
		' . (isset($_GET['position']) && $_GET['position'] == "auro" ? '<span class="nav-active">' : '') . '<a href="/gift-trade/' . $url[1] . '?position=auro"' . (isset($_GET['position']) && $_GET['position'] == 'auro' ? ' class="nav-active"' : '') . '>auro</a>' . (isset($_GET['position']) && $_GET['position'] == "auro" ? '</span>' : '') . '
		' .	(isset($_GET['position']) && $_GET['position'] == "ep" ? '<span class="nav-active">' : '') . '<a href="/gift-trade/' . $url[1] . '?position=ep"' . (isset($_GET['position']) && $_GET['position'] == 'ep' ? ' class="nav-active"' : '') . '>ep</a>' . (isset($_GET['position']) && $_GET['position'] == "ep" ? '</span>' : '');
	
		foreach($positions as $pos)
		{
			echo '
		' . (isset($_GET['position']) && $_GET['position'] == $pos ? '<span class="nav-active">' : '') . '<a href="/gift-trade/' . $url[1] . '?position=' . $pos . '">' . $pos . '</a>' . (isset($_GET['position']) && $_GET['position'] == $pos ? '</span>' : '');
		}
	
	echo '
	</div>';
	
	// Auro
	if($_GET['position'] == "auro")
	{
		echo '
	<br/>
	<form class="uniform" action="/gift-trade/' . $url[1] . '/' . (isset($_GET['position']) ? '?position=' . $_GET['position'] : '') . '" method="post">' . Form::prepare("auro-transaction") . '
		<p><input type="number" name="auro" value="0" step="any"/></p>
		<p><input type="submit" value="Set Auro"/></p>
	</form>';
	}
	// Packages
	elseif($_GET['position'] == "ep")
	{
		// get packages
		$packages = Database::selectMultiple("SELECT id, title, year, month, COUNT(uni_id) as count FROM packages INNER JOIN user_packages ON packages.id=user_packages.package_id WHERE uni_id=? GROUP BY id", array(Me::$id));

		// get item info
		foreach($packages as $key => $package)
		{
			echo '
			<div class="item_block">
				<img src="assets/exotic_packages/' . lcfirst(date('F', mktime(0, 0, 0, $package['month'], 1, 1))) . '_' . $package['year'] . '.png"/>
				<br/>' . $package['title'] . ' (' . $package['year'] . ')' . ($package['count'] > 1 ? ' (' . $package['count'] . ')' : "") . '
				<br /><a id="link_' . $package['id'] . '" href="/gift-trade/' . $url[1] . '?position=' . $_GET['position'] . '&add=' . $package['id'] . '&' . Link::prepare("add-" . $package['id']) . '">Add to Transaction</a>
			</div>';
		}
	}
	// Items
	else
	{
		// Show the items within the category selected
		$userItems = AppAvatar::getUserItems(Me::$id, $_GET['position']);
		$userItemsOther = array();
		
		// If you have no items, say so
		if(count($userItems) == 0)
		{
			echo "<p>You have no items in " . $_GET['position'] . ".</p>";
		}
		
		foreach($userItems as $key => $item)
		{
			if(!in_array($item['gender'], array($avatarData['gender'], "b")))
			{
				unset($userItems[$key]);
				$userItemsOther[] = $item;
				continue;
			}
			
			// Display the item block
			echo '
			<div class="item_block">
				<img id="pic_' . $item['id'] . '" src="/avatar_items/' . $_GET['position'] . '/' . $item['title'] . '/default_' . $avatarData['gender_full'] . '.png" />
				<br />' . $item['title'] . ($item['count'] > 1 ? ' (' . $item['count'] . ')' : "") . '
				<br /><a id="link_' . $item['id'] . '" href="/gift-trade/' . $url[1] . '?position=' . $_GET['position'] . '&add=' . $item['id'] . '&' . Link::prepare("add-" . $item['id']) . '">Add to Transaction</a>
			</div>';
		}

		foreach($userItemsOther as $item)
		{			
			// Display the item block
			echo '
			<div class="item_block opaque">
				<img id="pic_' . $item['id'] . '" src="/avatar_items/' . $_GET['position'] . '/' . $item['title'] . '/default_' . ($avatarData['gender_full'] == "male" ? "female" : "male") . '.png" />
				<br />' . $item['title'] . ($item['count'] > 1 ? ' (' . $item['count'] . ')' : "") . '
				<br /><a id="link_' . $item['id'] . '" href="/gift-trade/' . $url[1] . '?position=' . $_GET['position'] . '&add=' . $item['id'] . '&' . Link::prepare("add-" . $item['id']) . '">Add to Transaction</a>
			</div>';
		}
	}
	
	// save current state in cache since the transaction may have been changed and approved by the other person, but you haven't seen the change and accidentally approve something you may not have wanted
	Cache::set("transaction-" . $url[1] . "-" . Me::$id, json_encode($transaction), 300);

	// submit options
	echo '
	<div class="spacer"></div>
	<p>If you are trading, your trade partner can see a message from you. Would you like to set one?</p>
	<form class="uniform" action="/gift-trade/' . $url[1] . '" method="post">' . Form::prepare("gift-trade-message") . '
		<p><input type="text" name="message" maxlength="100"' . ($mymessage != '' ? ' value="' . $mymessage . '"' : '') . '/> (max 100 characters)</p>
		<p><input type="submit" value="Set Message"/></p>
	</form>
	<div class="spacer"></div>
	<form class="uniform" action="/gift-trade/' . $url[1] . '" method="post">' . Form::prepare("gift-trade") . '
	<p>';
	$comma = "";
	if(!$contrib[Me::$id])
	{
		echo '
	Please add something to the transaction. ';
	}
	else
	{
		if(!$contrib[$recipientID])
		{
			echo '
		<input class="button" type="submit" name="gift_anon" value="Gift anonymously to ' . $recipient . '"/> OR <input class="button" type="submit" name="gift" value="Gift to ' . $recipient . '"/>';
			$comma = " OR ";
		}		
		echo ''
		. $comma . '<input class="button" type="submit" name="trade" value="Trade with ' . $recipient . '"/>';
		$comma = " OR ";
	}
	echo ''
		. $comma . '<input type="submit" name="cancel" value="Cancel Transaction"/>
	</p>
	</form>';
}

echo '
	</div>
</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
