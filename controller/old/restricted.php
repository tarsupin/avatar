<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
		<title>Restricted - UniFaction 5 Avatar</title>
		<link rel='stylesheet' href='css/reset.css' type='text/css' media='screen' charset='utf-8'>
		<link rel='stylesheet' href='css/avatar.css' type='text/css' media='screen' charset='utf-8'>
		<link rel='icon' href='favicon.ico' type='image/x-icon'>
		<link rel='shortcut icon' href='favicon.ico' type='image/x-icon'> 
		<link rel='alternate' type='application/rss+xml' title='RSS' href='http://forum.unifaction.com/rss/news.xml'/>
	</head>
	<body>
		<a href='index.php'><span id='header_logo'></span></a>
		<div id='navbar-wrap'>
			<div id='navbar-user-info'>
				<span>
					Welcome, <? session_start(); echo (isset($_SESSION['avatarAcct']) ? $_SESSION['avatarAcct'] : "Guest"); ?><? echo " &bull; " . date("M j, Y g:ia", time()) . " UniTime"; ?>
					
				</span>
			</div>
			<div id='navbar'>
				<div id='navbar-upper'>
<?php
	if (isset($_SESSION['avatarAcct']))
		echo "
					<img src='css/icons/user_delete.png'/> <a href='logout.php'>Logout</a>";
	else
		echo "
					<img src='css/icons/key.png'/> <a href='auth.unifaction.com/login.php'>Login</a>";
?>

				</div>
				<div id='navbar-lower'>
					&nbsp;
				</div>
			</div>
		</div>
		<div id='main-content'>
			<div class='category-container'>
				<div class='details-header'>
					Restricted
				</div>
				<div class='details-body'>
<?php
	if (!isset($_GET['issue']))
		$_GET['issue'] = "";
	if (!isset($_GET['time']))
		$_GET['time'] = -1;

		if ($_GET['issue'] == "IP Ban")
			echo "
					An " . ($_GET['time'] == 0 ? "indefinite" : "") . " IP Ban is restricting your access to this page" . ($_GET['time'] > 0 ? " until " . date('M j, Y g:ia', $_GET['time']) . " UniTime" : "") . ". You may be temporarily or permanently banned.<br/><br/>";
		elseif ($_GET['issue'] == "Low Clearance")
			echo "
					A low clearance is restricting your access to this page. You may be temporarily or permanently banned.<br/><br/>";
		else
			echo "
					Something is restricting your access to this page. You may be temporarily or permanently banned.<br/><br/>";
		echo "
					If you have any questions about why you are seeing this page, please use the <a href='http://auth.unifaction.com/contact.php'>contact form</a>. A moderator will contact you as quickly as possible.";
?>

				</div>
			</div>
		</div>
	</body>