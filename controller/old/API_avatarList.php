<?php
	// Require Parameters, or return nothing
	if (!isset($_GET['account']))
		exit;

	require("config.php");
	require("incAVA/dbAVAconnect.php");

	$result = mysql_query("SELECT `max` FROM `max_avatars` WHERE `account`='" . protectSQL($_GET['account']) . "' LIMIT 1");
	if (!$fetch_maxavis = mysql_fetch_assoc($result))
		$fetch_maxavis['max'] = 3;

	// Prepare Variables
	$comma = "";

	// Scan for Avatars
	$result = mysql_query("SELECT `id`, `avatar`, `base`, `gender` FROM `avatars` WHERE account='" . protectSQL($_GET['account']) . "' LIMIT " . ($fetch_maxavis['max'] + 0));

	while ($fetch_avatars = mysql_fetch_assoc($result))
	{
		echo $comma . $fetch_avatars['id'] . ":" . $fetch_avatars['avatar'] . ":" . $fetch_avatars['gender'] . ":" . $fetch_avatars['base'];
		$comma = "|";
	}
?>