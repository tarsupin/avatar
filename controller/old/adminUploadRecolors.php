<?php
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");

	// Prevent Access
	if ($fetch_account['clearance'] < 6)
	{
		header("Location: index.php");
		exit;
	}
	
	function finditems($dir)
	{
		$colors = scandir($dir);
		$res = array();
		foreach ($colors as $color)
		{
			if ($color != "autorecolor")
			{
				if (is_dir($dir . "/" . $color))
				{
					$files = scandir($dir . "/" . $color);
					foreach ($files as $file)
						if ($file != "." && $file != ".." && $file != "autorecolor" && substr($file,-4) == ".png")
							if (count(explode("_", $file)) >= 3)
								$res[] = $color . "/" . $file;
				}
			}
		}
		return $res;
	}
	
	if (isset($_GET['delete']) && $_GET['delete'] != "autorecolor")
	{
		$_GET['delete'] = trim($_GET['delete']);
		if ($files = scandir("avatars/temp/" . $_GET['delete']))
		{
			foreach ($files as $file)
				if ($file != ".." && $file != ".")
					@unlink("avatars/temp/" . $_GET['delete'] . "/" . $file);
			@rmdir("avatars/temp/" . $_GET['delete']);
		}
		header("Location: adminUploadRecolors.php");
		exit;
	}
	
	if (isset($_POST['submit']))
	{
		$items = finditems("avatars/temp");
		foreach ($items as $item)
		{
			$short = str_replace(array(" ", "/", "_", ".png"), "", $item);
			if (isset($_POST['item_' . $short]))
			{
				$split = explode("_", $item);
				$split2 = explode("/", $split[0]);
				if (count($split) > 3)
					$res = array($split2[1] . "_" . $split[1], $split[2], substr($split[3],0,-4));
				else
					$res = array($split2[1], $split[1], substr($split[2],0,-4));
				$color = $split2[0];
				$file = "avatars/temp/" . $item;
				$newfile = "avatars/" . trim($res[0]) . "/" . trim($res[1]) . "/" . trim($color) . "_" . trim($res[2]) . ".png";
				
				if (!in_array($res[2], array("male", "female")) || count($split) > 4)
					$messages[] = "<div class='message-error'>Something is wrong with the file name <i>" . $item . "</i>.</div>";
				elseif (file_exists("avatars/" . trim($res[0]) . "/" . trim($res[1])))
				{
					if (!file_exists($newfile) || isset($_POST['overwrite']))
					{
						if (rename($file, $newfile))
							$messages[] = "<div class='message-success'><i>" . $file . "</i> has been moved and renamed to <i>" . $newfile . "</i>.</div>";
						else
							$messages[] = "<div class='message-error'><i>" . $file . "</i> could not be moved and renamed to <i>" . $newfile . "</i>.</div>";
					}
					else
						$messages[] = "<div class='message-error'><i>" . trim($color) . "_" . trim($res[2]) . ".png</i> already exists in <i>avatars/" . trim($res[0]) . "/" . trim($res[1]) . "</i>. It has not been moved.</div>";
				}
				else
					$messages[] = "<i>" . trim($res[1]) . "<i> does not exist on the <i>" . trim($res[0]) . "</i> layer.";
			}
		}
	}

	$pagetitle = "[staff] Upload Recolors";
	require("incAVA/header.php");
?>
			<div class='category-container'>
				<div class='details-header'>
					Upload Recolors
				</div>
				<div class='details-body'>
					<ul>
						<li>Files must be put in: <i>avatar/public_html/avatars/temp/colorname</i></li>
						<li>Files must be named as: <i>layer_item name_gender.png</i></li>
					</ul>
					<form method='post'>
<?php					
	$cols = array();
	$items = finditems("avatars/temp");					
	foreach ($items as $item)
	{
		$split = explode("_", $item);
		$split2 = explode("/", $split[0]);
		if (count($split) > 3)
			$res = array($split2[1] . "_" . $split[1], $split[2], substr($split[3],0,-4));
		else
			$res = array($split2[1], $split[1], substr($split[2],0,-4));
		$color = $split2[0];
		if (!in_array($color, $cols))
			$cols[] = $color;
		$short = str_replace(array(" ", "/", "_", ".png"), "", $item);
		echo "
						<div style='float:left;height:132px;border:1px #a49f95 dotted;'><input type='checkbox' name='item_" . $short . "'" . (isset($_POST['item_' . $short]) ? " checked='true'" : "") . " style='vertical-align:top;'/> <img src='avatars/temp/" . $item . "' style='max-height:75px;' title='" . $item . "'/><br/>" . $color . "<br/>" . $res[0] . "<br/>" . $res[1] . "<br/>" . $res[2] . "</div>";
	}
					
	echo "
						<div style='clear:both;'></div>
						<br/><input onclick='var ins=document.getElementsByTagName(\"input\"); if (this.checked==true) for (var i=0; i<ins.length-1; i++) {if (ins[i].type==\"checkbox\" && ins[i].name.substr(0,5) == \"item_\") ins[i].checked=true;} else for (var i=0; i<ins.length-1; i++) {if (ins[i].type==\"checkbox\" && ins[i].name.substr(0,5) == \"item_\") ins[i].checked=false;}' type='checkbox'/> <strong>Select/Deselect All</strong>
						<br/><br/>
						<input onclick='return confirm(\"Are you sure you want to rename and move these files?\");' type='submit' name='submit' value='Submit'/> <input type='checkbox' name='overwrite'/> Overwrite";
	unset($items);

	echo "
					</form>";
	foreach ($cols as $col)
		echo "
					<a onclick='return confirm(\"Are you sure you want to delete these files?\");' href='adminUploadRecolors.php?delete=" . $col . "'>Delete all files in " . $col . "</a><br/>";
?>

				</div>
			</div>
<?php
	require("incAVA/footer.php");
?>