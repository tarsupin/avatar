<?php
	require("config.php");
	unset($_SESSION['trk']);
	unset($_SESSION['avatarAcct']);
	header("Location: " . UNIFACTION . "logout.php");
	exit;
?>