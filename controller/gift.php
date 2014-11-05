<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/gift");
}

// Set page title
$config['pageTitle'] = "Gift Auro and Items";

// get wrappers
$wrap = Database::selectMultiple("SELECT id FROM wrappers", array());
$wrappers = array();
foreach($wrap as $w)
{
	$wrappers[] = $w['id'];
}
unset($wrap);

if(isset($_POST['submit']) || isset($_POST['confirm']))
{
	if(Form::submitted("gift"))
	{
		// check that recipient exists
		$recipient = Sanitize::variable($_POST['recipient']);
		$recipientID = User::getDataByHandle($recipient);
		if($recipientID == array())
		{
			Alert::error("Recipient Missing", "The recipient does not use the avatar system.");
		}
		else
		{
			// For confirmation
			$_POST['auro'] = round($_POST['auro'], 2);
			
			$itemsToSend = array();
			foreach($_POST as $key => $val)
			{
				if(substr($key, 0, 5) == "gift_")
				{
					$val = (int) $val;
					if($val > 0)
					{
						// get item title
						$itemID = (int) substr($key, 5);
						if($itemData = AppAvatar::itemData($itemID, "id, title"))
						{
							for($i=1; $i<=$val; $i++)
							{
								$itemsToSend[] = $itemData;
							}
						}
					}
				}
			}
			
			if(!isset($_POST['confirm']))
			{
				$message = "Is this what you wish to send to " . $recipient . "?<br/><br/><ul>";
				if($_POST['auro'] > 0)
				{
					$message .= "<li>" . $_POST['auro'] . " Auro</li>";
				}
				foreach($itemsToSend as $item)
				{
					$message .= "<li>" . $item['title'] . (in_array($item['id'], $wrappers) ? " (Wrapper)" : "") . "</li>";
				}
				$message .= '</ul><br/>To send this gift, please click the "Confirm" button at the end of the page.';
				Alert::info("Confirm Sending", $message);
			}
			else
			{		
				$recipientID = $recipientID['uni_id'];
				$pass = true;
				
				// get user name
				$sender = Me::$vals['handle'];
			
				// prepare transaction
				$transactionID = Transaction::create("Gift " . Me::$id . " to " . $recipientID);
				Transaction::addUser($transactionID, Me::$id);
				Transaction::addUser($transactionID, $recipientID);
				
				// Auro
				if($_POST['auro'] > 0)
				{
					$pass = Transaction::addEntry(Me::$id, $transactionID, "AppTrade", "sendAuro", array(Me::$id, $recipientID, $_POST['auro'], Sanitize::text($_POST['message'])), array("image" => "gold.png", "caption" => $_POST['auro'] . " Auro", "description" => $sender . " sends " . $_POST['auro'] . " Auro to " . $recipient . "."));
				}
				
				// Items
				if($pass)
				{
					foreach($itemsToSend as $item)
					{
						$pass = Transaction::addEntry(Me::$id, $transactionID, "AppTrade", "sendItem", array(Me::$id, $recipientID, $item['id'], Sanitize::text($_POST['message'])), array("image" => "item.png", "caption" => $val . " " . $item['title'], "description" => $sender . " sends " . $item['title'] . (in_array($item['id'], $wrappers) ? " (Wrapper)" : "") . " to " . $recipient . "."));
						if(!$pass)
						{
							break;
						}
					}					
				}
				// approve and execute transaction if possible
				if(!$pass)
				{
					Alert::error("Not Added", "A transaction entry could not be added successfully.");
				}
				else
				{
					Transaction::approve($transactionID, Me::$id);
					if(!Transaction::approve($transactionID, $recipientID))
					{
						Alert::error("Transaction Not Possible", "The transaction couldn't be completed! You don't have enough Auro or all the items you're trying to send.");
						//Transaction::delete($transactionID);
					}
					else
					{
						Alert::saveSuccess("Transaction Completed", 'The transaction has been completed! Check the <a href="/log-auro">Auro Log</a> or <a href="/log-item">Item Log</a>.');
						header("Location: /dress-avatar"); exit;
						//Transaction::delete($transactionID);
					}
				}
			}
		}
	}
}

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Run Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

echo '
<style>
table input[type="number"] { width:3em; }
</style>';

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

echo '
<div id="panel-right"></div>
<div id="content">' .
Alert::display() . '
<h2>Gift Auro and Items</h2>
<form class="uniform" method="post">' . Form::prepare("gift") . '
	<p>
		<strong>Recipient</strong><br />
		<input type="text" maxlength="22" name="recipient"' . (isset($_POST['recipient']) ? ' value="' . Sanitize::variable($_POST['recipient']) . '"' : "") . '/>
	</p>
	<p>
		<strong>Message</strong> (optional, max 64 characters)<br />
		<input type="text" maxlength="64" name="message"' . (isset($_POST['message']) ? ' value="' . Sanitize::text($_POST['message']) . '"' : "") . '/>
	</p>
	<p>
		<strong>Auro</strong><br />
		<input type="number" name="auro" ' . (isset($_POST['auro']) ? ' value="' .  (float) $_POST['auro'] . '"' : ' value="0"') . ' step="any"/>
	</p>
	<p>
		<strong>Items</strong><br />
		<table class="mod-table">';		

		// get positions
$positions = AppAvatar::getInvPositions(Me::$id);
foreach($positions as $pos)
{
	echo '<tr><td>' . $pos . '</td><td>';
	$userItems = AppAvatar::getUserItems(Me::$id, $pos, "", true);
	foreach($userItems as $item)
	{
		echo '
		<input type="number" name="gift_' . $item['id'] . '" min="0" max="' . $item['count'] . '" step="1"' . (isset($_POST['gift_' . $item['id']]) ? ' value="' . $_POST['gift_' . $item['id']] . '"' : ' value="0"') . '/> ' . $item['title'] . ($item['count'] > 1 ? ' (' . $item['count'] . ')' : '') . (in_array($item['id'], $wrappers) ? ' (Wrapper)' : '') . '<br/>';
	}
	echo '</td></tr>';
}

echo '
		</table>
	</p>
	<p><input class="button" type="submit" name="submit" value="Check"/>';
if(isset($_POST['submit']))
{
	echo '
	<input class="button" type="submit" name="confirm" value="Confirm"/>';
}
echo '</p>
</form>';

echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
