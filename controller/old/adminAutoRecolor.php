<?php
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");
	require("drawgradient.php");
	
	if ($fetch_account['clearance'] < 6)
	{
		header("Location: index.php");
		exit;
	}
	
	$max = 6;
	
	if (!isset($_GET['gb']))
		$_GET['gb'] = 0;
	if (!isset($_GET['gc']))
		$_GET['gc'] = 0;
	if (!isset($_GET['gs']))
		$_GET['gs'] = 0;
	
	$path = "avatars/temp/autorecolor";
	
	if (isset($_GET['palette']))
	{
		$_GET['palette'] = strtolower($_GET['palette']);
		$files = scandir($path);
		$items = array();
		foreach ($files as $file)
			if ($file != "." && $file != "..")
			{
				$e = explode("_", $file);
				if (count($e) > 3)
				{
					if ($e[0] == $_GET['palette'])
					{
						$findgender = $e[count($e)-1];
						$findname = $e[count($e)-2];
						$findlayer = "";
						for ($i=1; $i<count($e)-2; $i++)
						{
							if ($findlayer == "")
								$findlayer .= $e[$i];
							else
								$findlayer .= "_" . $e[$i];
						}
						
						if ($findgender == "female.png" || $findgender == "male.png")
						{
							if (!file_exists("avatars/" . $findlayer . "/" . $findname))
								$items[] = array($findname, substr($findgender, 0, -4), $findlayer, "new");
							else
								$items[] = array($findname, substr($findgender, 0, -4), $findlayer, "recolor");
						}
					}					
				}										
			}
		sort($items);
	
		if (!file_exists($path . "/" . $_GET['palette'] . "palette_colors.txt"))
			file_put_contents($path . "/" . $_GET['palette'] . "palette_colors.txt", serialize(array()));
	
		$colors = file_get_contents($path . "/" . $_GET['palette'] . "palette_colors.txt");
		$colors = unserialize($colors);
		ksort($colors);
		
		if (isset($_GET['delete']))
		{
			unset($colors[$_GET['delete']]);
			file_put_contents($path . "/" . $_GET['palette'] . "palette_colors.txt", serialize($colors));
		}
		elseif (isset($_GET['add']) && isset($_GET['addto']))
		{
			$colorsplit = explode(" ", $_GET['color']);
			foreach ($colorsplit as $key => $val)
				$colorsplit[$key] = ucfirst($val);
			$_GET['color'] = implode(" ", $colorsplit);
			if ($_GET['palette'] != $_GET['addto'])
			{
				$othercolors = file_get_contents($path . "/" . $_GET['addto'] . "palette_colors.txt");
				$othercolors = unserialize($othercolors);
				for ($index=1; $index<=$max; $index++)
				{
					$othercolors[$_GET['color']]['red_' . $index] = min(max($_GET['red_' . $index], 0), 255);
					$othercolors[$_GET['color']]['green_' . $index] = min(max($_GET['green_' . $index], 0), 255);
					$othercolors[$_GET['color']]['blue_' . $index] = min(max($_GET['blue_' . $index], 0), 255);
					$othercolors[$_GET['color']]['brightness_' . $index] = min(max($_GET['brightness_' . $index], -255), 255);
					$othercolors[$_GET['color']]['contrast_' . $index] = min(max($_GET['contrast_' . $index], -255), 255);
					$othercolors[$_GET['color']]['saturation_' . $index] = min(max($_GET['saturation_' . $index], -255), 255);
					$othercolors[$_GET['color']]['index_' . $index] = min(max($_GET['index_' . $index], 0), 255);
				}
				ksort($othercolors);
				file_put_contents($path . "/" . $_GET['addto'] . "palette_colors.txt", serialize($othercolors));
			}
			else
			{
				for ($index=1; $index<=$max; $index++)
				{
					$colors[$_GET['color']]['red_' . $index] = min(max($_GET['red_' . $index], 0), 255);
					$colors[$_GET['color']]['green_' . $index] = min(max($_GET['green_' . $index], 0), 255);
					$colors[$_GET['color']]['blue_' . $index] = min(max($_GET['blue_' . $index], 0), 255);
					$colors[$_GET['color']]['brightness_' . $index] = min(max($_GET['brightness_' . $index], -255), 255);
					$colors[$_GET['color']]['contrast_' . $index] = min(max($_GET['contrast_' . $index], -255), 255);
					$colors[$_GET['color']]['saturation_' . $index] = min(max($_GET['saturation_' . $index], -255), 255);
					$colors[$_GET['color']]['index_' . $index] = min(max($_GET['index_' . $index], 0), 255);
				}
				ksort($colors);
				file_put_contents($path . "/" . $_GET['addto'] . "palette_colors.txt", serialize($colors));
			}
			$txt = $_GET['color'] . "\n";
			for ($index=1; $index<=$max; $index++)
				$txt .= $_GET['red_' . $index] . " ";
			$txt .= "red\n";
			for ($index=1; $index<=$max; $index++)
				$txt .= $_GET['green_' . $index] . " ";
			$txt .= "green\n";
			for ($index=1; $index<=$max; $index++)
				$txt .= $_GET['blue_' . $index] . " ";
			$txt .= "blue\n";
			for ($index=1; $index<=$max; $index++)
				$txt .= $_GET['brightness_' . $index] . " ";
			$txt .= "brightness\n";
			for ($index=1; $index<=$max; $index++)
				$txt .= $_GET['contrast_' . $index] . " ";
			$txt .= "contrast\n";
			for ($index=1; $index<=$max; $index++)
				$txt .= $_GET['saturation_' . $index] . " ";
			$txt .= "saturation\n";
			for ($index=1; $index<=$max; $index++)
				$txt .= $_GET['index_' . $index] . " ";
			$txt .= "index\n";
			file_put_contents($path . "/colors/" . $_GET['addto'] . "palette_" . $_GET['color'] . ".txt", $txt);
			drawgradient($_GET['addto'], $_GET['color']);
			unset($txt);
		}	
	}
	
	$pagetitle = "[staff] Recolor Items";
	require("incAVA/header.php");
?>
			<div class='category-container'>
				<div class='details-header'>
					Recolor Items
				</div>
				<div class='details-body'>
<?php			
	$files = scandir("avatars/temp/autorecolor");
	$palettes = array();
	$palettes2 = array();
	foreach ($files as $file)
		if ($file != "." && $file != "..")
		{
			if (substr($file, -18) == "palette_colors.txt")
			{
				$palettename = substr($file, 0, -18);
				$palettes[] = "<a href='adminAutoRecolor.php?palette=" . $palettename . "'>" . ucfirst($palettename) . " Palette</a>";	
				$palettes2[] = $palettename;
			}
		}
	sort($palettes);
	echo "
					" . implode(" | ", $palettes);
	
				
	if (isset($_GET['palette']))
	{
		echo "
					<br/><br/>
					<ul>
						<li>Files must be put in: <i>avatar/public_html/avatars/temp/autorecolor</i></li>
						<li>Files must be named as: <i>palette_layer_item name_gender.png</i></li>
					</ul>
					<br/>
					<form method='get'>
						<input type='hidden' name='palette' value='" . $_GET['palette'] . "'/>
						<input id='gb' type='text' size='4' maxlength='4' value='" . $_GET['gb'] . "' style='text-align:center;' onchange='change();'/> Global Brightness (-255...255)<br/>
						<input id='gc' type='text' size='4' maxlength='4' value='" . $_GET['gc'] . "' style='text-align:center;' onchange='change();'/> Global Contrast (-255...255)<br/>
						<input id='gs' type='text' size='4' maxlength='4' value='" . $_GET['gs'] . "' style='text-align:center;' onchange='change();'/> Global Saturation (-255...255)
					</form>
					<br/>
					<form method='get'>
						<input type='hidden' name='palette' value='" . $_GET['palette'] . "'/>
						<table class='alternate_with_th'>
							<tr>
								<th>&nbsp;</th>
								<th>&nbsp;</th>
								<th>Red<br/>0...255</th>
								<th>Green<br/>0...255</th>
								<th>Blue<br/>0...255</th>
								<th>Brightness<br/>-255...255</th>
								<th>Contrast<br/>-255...255</th>
								<th>Saturation<br/>-255...255</th>
								<th>Index At<br/>0...255</th>
								<th>&nbsp;</th>
								<th>&nbsp;</th>
							</tr>";
						foreach ($colors as $key => $val)
						{
							echo "
							<tr>
								<td><a onclick='return confirm(\"Are you sure you want to remove " . $key . "?\");' href='adminAutoRecolor.php?palette=" . $_GET['palette'] . "&delete=" . $key . "'>&#10006;</a></td>
								<td>" . $key . "</td>";
							for ($upto=$max; $upto>=1; $upto--)
								if ($val['index_' . $upto] != "")
									break;
							echo "
								<td>";
							for ($index=1; $index<=$upto; $index++)
								echo $val['red_' . $index] . "<br/>";
							echo "
								</td>
								<td>";
							for ($index=1; $index<=$upto; $index++)
								echo $val['green_' . $index] . "<br/>";
							echo "
								</td>
								<td>";
							for ($index=1; $index<=$upto; $index++)
								echo $val['blue_' . $index] . "<br/>";
							echo "
								</td>
								<td>";
							for ($index=1; $index<=$upto; $index++)
								echo $val['brightness_' . $index] . "<br/>";
							echo "
								</td>
								<td>";
							for ($index=1; $index<=$upto; $index++)
								echo $val['contrast_' . $index] . "<br/>";
							echo "
								</td>
								<td>";
							for ($index=1; $index<=$upto; $index++)
								echo $val['saturation_' . $index] . "<br/>";
							echo "
								</td>
								<td>";
							for ($index=1; $index<=$upto; $index++)
								echo $val['index_' . $index] . "<br/>";
							echo "
								</td>
								<td>
									<input type='button' onclick='fill(\"" . $key . "\", ";
							for ($index=1; $index<=$max; $index++)
								echo "\"" . $val['red_' . $index] . "\", ";
							for ($index=1; $index<=$max; $index++)
								echo "\"" . $val['green_' . $index] . "\", ";
							for ($index=1; $index<=$max; $index++)
								echo "\"" . $val['blue_' . $index] . "\", ";
							for ($index=1; $index<=$max; $index++)
								echo "\"" . $val['brightness_' . $index] . "\", ";
							for ($index=1; $index<=$max; $index++)
								echo "\"" . $val['contrast_' . $index] . "\", ";
							for ($index=1; $index<=$max; $index++)
								echo "\"" . $val['saturation_' . $index] . "\", ";
							for ($index=1; $index<$max; $index++)
								echo "\"" . $val['index_' . $index] . "\", ";
							echo "\"" . $val['index_' . $index] . "\");'/>
								</td>
								<td>";
							if (!file_exists("avatars/temp/autorecolor/colors/" . $_GET['palette'] . "palette_" . $key . ".png"))
								drawgradient($_GET['palette'], $key);
							echo "
								<img src='avatars/temp/autorecolor/colors/" . $_GET['palette'] . "palette_" . $key . ".png'/>
								</td>
							</tr>";
						}
		echo "
							<tr>
								<td><input type='submit' name='add' value='Add'/></td>
								<td><input type='text' name='color' size='27' maxlength='27' style='text-align:center;'></td>
								<td>";
							for ($index=1; $index<=$max; $index++)
								echo "<input type='text' name='red_" . $index . "' size='3' maxlength='3' style='text-align:center;'><br/>";
							echo "
								</td>
								<td>";
							for ($index=1; $index<=$max; $index++)
								echo "<input type='text' name='green_" . $index . "' size='3' maxlength='3' style='text-align:center;'><br/>";
							echo "
								</td>
								<td>";
							for ($index=1; $index<=$max; $index++)
								echo "<input type='text' name='blue_" . $index . "' size='3' maxlength='3' style='text-align:center;'><br/>";
							echo "
								</td>
								<td>";
							for ($index=1; $index<=$max; $index++)
								echo "<input type='text' name='brightness_" . $index . "' size='4' maxlength='4' style='text-align:center;'><br/>";
							echo "
								</td>
								<td>";
							for ($index=1; $index<=$max; $index++)
								echo "<input type='text' name='contrast_" . $index . "' size='4' maxlength='4' style='text-align:center;'><br/>";
							echo "
								</td>
								<td>";
							for ($index=1; $index<=$max; $index++)
								echo "<input type='text' name='saturation_" . $index . "' size='4' maxlength='4' style='text-align:center;'><br/>";
							echo "
								</td>
								<td>";
							for ($index=1; $index<=$max; $index++)
								echo "<input type='text' name='index_" . $index . "' size='3' maxlength='3' style='text-align:center;'><br/>";
							echo "
								</td>
								<td>&nbsp;</td>
								<td>
									<select name='addto'>";
		foreach ($palettes2 as $pal2)
			echo "
										<option value='" . $pal2 . "'" . ($_GET['palette'] == $pal2 ? " selected='selected'" : "") . ">Add to " . ucfirst($pal2) . " Palette</option>";
		echo "
									</select>
								</td>
							</tr>
						</table>
					</form>
					<br/>
					<b>" . count($items) . " Items</b><br/>";
		foreach ($items as $item)
			echo "
					<img src='" . $path . "/" . $_GET['palette'] . "_" . $item[2] . "_" . $item[0] . "_" . $item[1] . ".png' title='" . $_GET['palette'] . "_" . $item[2] . "_" . $item[0] . "_" . $item[1] . "' style='max-height:75px;'/>";
		echo "
					<div class='spoiler'><div class='spoiler_header' onclick='$(this).next().slideToggle(\"slow\")'>Recolors</div><div class='spoiler_content'>";
		foreach ($colors as $key => $val)
		{
			echo "<b>" . $key . "</b><br/>";
			$cols = array();
			foreach ($items as $item)
			{
				if ($item[3] == "recolor")
				{
					$cols[] = "<a href='drawrecolor.php?palette=" . $_GET['palette'] . "&src=" . urlencode($path . "/" . $_GET['palette'] . "_" . $item[2] . "_" . $item[0] . "_" . $item[1] . ".png") . "&color=" . $key . "&gender=" . $item[1] . "&title=" . $item[0] . "&layer=" . $item[2] . "&gb=" . $_GET['gb'] . "&gc=" . $_GET['gc'] . "&gs=" . $_GET['gs'] . "'>" . $item[0] . " (" . substr($item[1], 0, 1) . ")</a>";
				}
			}
			echo implode(", ", $cols) . "<br/><br/>";
		}
		
		echo "
					</div></div>
					<div class='spoiler'><div class='spoiler_header' onclick='$(this).next().slideToggle(\"slow\")'>New Items</div><div class='spoiler_content'>";				
		foreach ($items as $item)
		{
			if ($item[3] == "new")
			{
				echo "<b>" . $item[0] . "</b><br/>";
				$cols = array();
				foreach ($colors as $key => $val)
				{
					$cols[] = "<a href='drawrecolor.php?palette=" . $_GET['palette'] . "&src=" . urlencode($path . "/" . $_GET['palette'] . "_" . $item[2] . "_" . $item[0] . "_" . $item[1] . ".png") . "&color=" . $key . "&gender=" . $item[1] . "&title=" . $item[0] . "&layer=" . $item[2] . "&gb=" . $_GET['gb'] . "&gc=" . $_GET['gc'] . "&gs=" . $_GET['gs'] . "'>" . $key . "</a>";
				}
				echo implode(", ", $cols) . "<br/><br/>";
			}
		}
		echo "
					</div></div>";
	}

	echo "
				</div>
			</div>";
	
	if (isset($_GET['palette']))
	{
		echo "
			<script type='text/javascript'>
				function change()
				{
					var gb = document.getElementById('gb').value;
					var gc = document.getElementById('gc').value;
					var gs = document.getElementById('gs').value;
					window.location = 'adminAutoRecolor.php?palette=" . $_GET['palette'] . "&gb=' + gb + '&gc=' + gc + '&gs=' + gs;
				}
				
				function fill(title, ";
		for ($index=1; $index<=$max; $index++)
			echo "red_" . $index . ", ";
		for ($index=1; $index<=$max; $index++)
			echo "green_" . $index . ", ";
		for ($index=1; $index<=$max; $index++)
			echo "blue_" . $index . ", ";
		for ($index=1; $index<=$max; $index++)
			echo "brightness_" . $index . ", ";
		for ($index=1; $index<=$max; $index++)
			echo "contrast_" . $index . ", ";
		for ($index=1; $index<=$max; $index++)
			echo "saturation_" . $index . ", ";
		for ($index=1; $index<$max; $index++)
			echo "index_" . $index . ", ";
		echo "index_" . $max . ")
				{
					document.getElementsByName('color')[0].value=title;";
		for ($index=1; $index<=$max; $index++)
			echo "document.getElementsByName('red_" . $index . "')[0].value=red_" . $index . ";";
		for ($index=1; $index<=$max; $index++)
			echo "document.getElementsByName('green_" . $index . "')[0].value=green_" . $index . ";";
		for ($index=1; $index<=$max; $index++)
			echo "document.getElementsByName('blue_" . $index . "')[0].value=blue_" . $index . ";";
		for ($index=1; $index<=$max; $index++)
			echo "document.getElementsByName('brightness_" . $index . "')[0].value=brightness_" . $index . ";";
		for ($index=1; $index<=$max; $index++)
			echo "document.getElementsByName('contrast_" . $index . "')[0].value=contrast_" . $index . ";";
		for ($index=1; $index<=$max; $index++)
			echo "document.getElementsByName('saturation_" . $index . "')[0].value=saturation_" . $index . ";";
		for ($index=1; $index<=$max; $index++)
			echo "document.getElementsByName('index_" . $index . "')[0].value=index_" . $index . ";";
		echo "
				}
			</script>
";
	}

	require("incAVA/footer.php");
?>