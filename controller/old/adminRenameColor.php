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
	
	// returns all directories (item names) with that color
	function finditemswithcolor($dir, $search)
	{
		$dir = escapeshellcmd($dir);
		$search = escapeshellarg($search);
		$files = shell_exec("find " . $dir . " -name " . $search . " -type f -mindepth 2 -maxdepth 2 -print");
		$files = explode("\n", trim($files));
		return $files;
	}
	
	$messages = array();
	
	if (isset($_POST))
	{
		if (isset($_POST['replace_from']))
		{
			$_POST['replace_from'] = trim($_POST['replace_from']);
			$_POST['replace_from'] = str_replace("*", "", $_POST['replace_from']);
		}
		if (isset($_POST['replace_to']))
		{
			$_POST['replace_to'] = trim($_POST['replace_to']);
			$_POST['replace_to'] = str_replace("*", "", $_POST['replace_to']);
		}
		
		$items = array();
		foreach ($_POST as $key => $val)
		{
			if (substr($key, 0, 6) == "layer_")
			{
				$items = array_merge($items, finditemswithcolor("avatars/" . substr($key, 6), $_POST['replace_from'] . "_female.png"));
				$items = array_merge($items, finditemswithcolor("avatars/" . substr($key, 6), $_POST['replace_from'] . "_male.png"));
			}
			elseif (substr($key, 0, 5) == "item_" && isset($_POST['finditems']))
				unset($_POST[$key]);
		}
		
		$res = "<div>";
		$items = array_unique($items);
		foreach ($items as $key => $val)
		{
			if ($val == "")
				unset($items[$key]);
			else
			{
				if (stristr($val, "/base/") === false)
				{
					$short = str_replace(array(" ", "/", "_", ".png"), "", $val);
					$items['item_' . $short] = $val;
					if (!isset($_POST['item_' . $short]))
						$res .= "<div style='float:left;height:75px;border:1px #a49f95 dotted;'><input type='checkbox' name='item_" . $short . "'" . (isset($_POST['item_' . $short]) ? " checked='true'" : "") . " style='vertical-align:top;'/> <img src='" . $val . "' style='max-height:75px;' title='" . $val . "'/></div>";
					if (is_numeric($key))
						unset($items[$key]);
				}
				else
					unset($items[$key]);
			}
		}
		$res .= "</div>";
	}

	if (isset($_POST['replacethese']))
	{
		if (strlen($_POST['replace_to']) < 3)
			$messages[] = "<div class='message-error'>The new color name must have at least 3 characters.</div>";
		else
		{
			foreach ($_POST as $key => $val)
			{
				if (substr($key, 0, 5) == "item_")
				{
					if (isset($items[$key]))
					{
						$file = $items[$key];
						$split = explode("/", $file);
						$newfile = $split[0] . "/" . $split[1] . "/" . $split[2] . "/" . str_replace($_POST['replace_from'], $_POST['replace_to'], $split[3]);
						if (!file_exists($_SERVER['DOCUMENT_ROOT'] . "/home/unifaction_com/subdomains/avatar/public_html/" . $newfile))
						{
							if (rename($file, $newfile))
								$messages[] = "<div class='message-success'><i>" . $file . "</i> has been renamed to <i>" . $newfile . "</i>.</div>";
							else
								$messages[] = "<div class='message-error'><i>" . $file . "</i> could not be renamed to <i>" . $newfile . "</i>.</div>";
						}
						else
							$messages[] = "<div class='message-error'><i>" . $split[2] . "</i> already exists in " . $_POST['replace_to'] . ". It has not been replaced.</div>";
					}
				}
			}
		}
	}

	$pagetitle = "[staff] Rename Color";
	require("incAVA/header.php");
?>
			<div class='category-container'>
				<div class='details-header'>
					Rename Color
				</div>
				<div class='details-body'>
<?php
	/*if ($fetch_account['account'] == "Pegasus")
	{
		$files = shell_exec("find avatars -name *.png -type f -mindepth 3 -maxdepth 3 -print");
		$files = explode("\n", trim($files));
		//$tellme = array();
		$errors = array();
		foreach ($files as $key => $val)
		{
			$split = explode("/", $val);
			if ($split[1] != "temp")
			{
				$name = $split[count($split)-1];
				if (substr($name, -9) != "_male.png" && substr($name, -11) != "_female.png")
					$errors[] = "[wrong file name] " . $val;
				elseif (strlen(substr($name, 0, -9)) > 27)
					$errors[] = "[color name too long] " . $val;
				$name = str_replace(array("_female.png", "_male.png"), "", $name);
				//if (!in_array($name, $tellme))
					//$tellme[] = $name;
			}
		}
		unset($files);
		if ($errors != array())
		{
			asort($errors);
			echo "
					<div class='spoiler'><div class='spoiler_header' onclick='$(this).next().slideToggle(\"slow\");'>" . count($errors) . " Errors</div><div class='spoiler_content'>" . implode("<br/>", $errors) . "</div></div>";
		}
		unset($errors);
		asort($tellme);
		echo "
					<div class='spoiler'><div class='spoiler_header' onclick='$(this).next().slideToggle(\"slow\");'>" . count($tellme) . " Colors</div><div class='spoiler_content'>" . implode(", ", $tellme) . "</div></div>";
	}*/

	echo "
					<form method='post'>";

	$layers = scandir("avatars");
	foreach ($layers as $key => $val)
		if (in_array($val, array(".", "..", "base", "temp", ".cache")) || !is_dir("avatars/" . $val))
			unset($layers[$key]);
	$percol = ceil(count($layers) / 7);
	$counter = 0;
	echo "
						<table style='text-align:left;'>
							<tr>
								<td style='vertical-align:top;'>";
	foreach ($layers as $val)
	{
		$counter++;
		echo "
									<input type='checkbox' name='layer_" . $val . "'" . (isset($_POST['layer_' . $val]) ? " checked=true" : "") . "/> " . $val . "<br/>";
		if ($counter % $percol == 0 && $counter < count($layers))
			echo "
								</td>
								<td style='vertical-align:top;'>";
	}
	echo "
								</td>
							</tr>
						</table>
						<br/><input onclick='var ins=document.getElementsByTagName(\"input\"); if (this.checked==true) for (var i=0; i<ins.length-1; i++) {if (ins[i].type==\"checkbox\" && ins[i].name.substr(0,6) == \"layer_\") ins[i].checked=true;} else for (var i=0; i<ins.length-1; i++) {if (ins[i].type==\"checkbox\" && ins[i].name.substr(0,6) == \"layer_\") ins[i].checked=false;}' type='checkbox'/> <b>Select/Deselect All</b>
						<br/><br/><input type='text' name='replace_from' value='" . (isset($_POST['replace_from']) ? $_POST['replace_from'] : "") . "'/> old color name (case sensitive)
						<br/><br/><input type='submit' name='finditems' value='find items on the selected layers which have this color'/>
						<br/><br/>";
					unset($layers);
					
					if (isset($_POST['finditems']) || isset($_POST['replacethese']))
					{
						echo "
						" . $res . "<div style='clear:both;'></div>
						<br/><input onclick='var ins=document.getElementsByTagName(\"input\"); if (this.checked==true) for (var i=0; i<ins.length-1; i++) {if (ins[i].type==\"checkbox\" && ins[i].name.substr(0,5) == \"item_\") ins[i].checked=true;} else for (var i=0; i<ins.length-1; i++) {if (ins[i].type==\"checkbox\" && ins[i].name.substr(0,5) == \"item_\") ins[i].checked=false;}' type='checkbox'/> <b>Select/Deselect All</b>
						<br/><br/><input type='text' name='replace_to' value='" . (isset($_POST['replace_to']) ? $_POST['replace_to'] : "") . "'/> new color name
						<br/><br/><input onclick='return confirm(\"Are you sure you want to rename?\");' type='submit' name='replacethese' value='replace color names in the selected items where possible'/>";
						unset($items);
					}
?>

					</form>
				</div>
			</div>
<?php
	require("incAVA/footer.php");
?>