<?php
	require("config.php");
	require("fanctions/check_and_draw.php");

	// Prepare Variables
	if (!isset($_GET['base']))
		$_GET['base'] = "white";
	if (!isset($_GET['gender']))
		exit;
	if (!isset($_GET['position']))
		exit;
	if (!isset($_GET['item']))
		exit;
	if (!isset($_GET['front']))
		false;
	if (!isset($_GET['id']))
		exit;
	if (!isset($_GET['recolor']))
		$_GET['recolor'] = "";
		
	if ($_GET['recolor'] == "" && $_GET['position'] != "" && $_GET['item'] != "")
	{
		$files = scandir("avatars/" . $_GET['position'] . "/" . $_GET['item'] . "/");
		foreach ($files as $file)
			if ($file != "." && $file != "..")
				if (strpos($file, "_" . $_GET['gender'] . ".png"))
				{
					$_GET['recolor'] = str_replace("_" . $_GET['gender'] . ".png", "", $file);
					break;
				}
	}
		
	$outfit = array();
	if (isset($_GET['bg']) && $_GET['position'] != "background")
		$outfit[] = array(1489, "Black", "BG Seamless", "background");
	if ($_GET['front'] == "true")
		$outfit[] = array(0, $_GET['base'], "Base", "base");
	$outfit[] = array($_GET['id'], $_GET['recolor'], $_GET['item'], $_GET['position']);
	if ($_GET['front'] == "false")
		$outfit[] = array(0, $_GET['base'], "Base", "base");
	
	header("Content-type: image/png", false);
	draw_image($outfit, $_GET['gender'], "", 0);
?>