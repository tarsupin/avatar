<?php
	// written by Pegasus
	// do not edit, copy or otherwise use without permission
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");
	require("layerorder.php");
	
	if ($fetch_account['clearance'] < 6)
	{
		header("Location: index.php");
		exit;
	}
	
	$path = "avatars/temp/autorecolor";
	
	$files = scandir($path);
	$items = array();
	foreach ($files as $file)
		if (is_dir($path . "/" . $file) && $file != "." && $file != ".." && $file != "colors" && strpos($file, "archive") === false)
			$items[] = $file;
	sort($items);
	
	function combine($topitem, $bottomitem, $top, $bottom, $name, $gender, $order)
	{
		$img_top = imagecreatefrompng("avatars/temp/autorecolor/" . $topitem . "/" . $top . "_" . $gender . ".png");
		$img_bottom = imagecreatefrompng("avatars/temp/autorecolor/" . $bottomitem . "/" . $bottom . "_" . $gender . ".png");
		$res = imagecreatetruecolor(max(imagesx($img_top), imagesx($img_bottom)), max(imagesy($img_top), imagesy($img_bottom)));
		$background_color = imagecolorallocatealpha($res, 0, 255, 0, 127);
		imagefill($res, 0, 0, $background_color);
		imagecolortransparent($res, $background_color);
		imagecopy($res, $img_bottom, 0, 0, 0, 0, imagesx($img_bottom), imagesy($img_bottom));
		imagecopy($res, $img_top, 0, 0, 0, 0, imagesx($img_top), imagesy($img_top));		
		imagedestroy($img_top);
		imagedestroy($img_bottom);
		imagealphablending($res, true);
		imagesavealpha($res, true);
		if (!file_exists("avatars/temp/autorecolor/" . $name))
			mkdir("avatars/temp/autorecolor/" . $name);
		if ($order == "topfirst")
			imagepng($res, "avatars/temp/autorecolor/" . $name . "/" . $top . " " . $bottom . "_" . $gender . ".png");
		elseif ($order == "bottomfirst")
			imagepng($res, "avatars/temp/autorecolor/" . $name . "/" . $bottom . " " . $top . "_" . $gender . ".png");
		elseif ($order == "toponly")
			imagepng($res, "avatars/temp/autorecolor/" . $name . "/" . $top . "_" . $gender . ".png");
		elseif ($order == "bottomonly")
			imagepng($res, "avatars/temp/autorecolor/" . $name . "/" . $bottom . "_" . $gender . ".png");
		imagedestroy($res);
	}
	
	if (isset($_GET['submit']))
	{
		$_GET['itemname'] = trim($_GET['itemname']);
		if ($_GET['top'] != "" && $_GET['bottom'] != "" && $_GET['top'] != $_GET['bottom'] && in_array($_GET['top'], $items) && in_array($_GET['bottom'], $items))
		{
			if ($_GET['itemname'] != "" && $_GET['itemname'] != $_GET['top'] && $_GET['itemname'] != $_GET['bottom'])
			{
				$top_female = array();
				$top_male = array();
				$topfiles = scandir($path . "/" . $_GET['top']);
				foreach ($topfiles as $top)
				{
					if (substr($top, -11) == "_female.png")
						$top_female[] = substr($top, 0, strlen($top)-11);
					elseif (substr($top, -9) == "_male.png")
						$top_male[] = substr($top, 0, strlen($top)-9);
				}
				$bottom_female = array();
				$bottom_male = array();
				$bottomfiles = scandir($path . "/" . $_GET['bottom']);
				foreach ($bottomfiles as $bottom)
				{
					if (substr($bottom, -11) == "_female.png")
						$bottom_female[] = substr($bottom, 0, strlen($bottom)-11);
					elseif (substr($bottom, -9) == "_male.png")
						$bottom_male[] = substr($bottom, 0, strlen($bottom)-9);
				}
				foreach ($top_female as $tf)
					foreach ($bottom_female as $bf)
						combine($_GET['top'], $_GET['bottom'], $tf, $bf, $_GET['itemname'], "female", $_GET['order']);
				foreach ($top_male as $tm)
					foreach ($bottom_male as $bm)
						combine($_GET['top'], $_GET['bottom'], $tm, $bm, $_GET['itemname'], "male", $_GET['order']);
			}
			else
				$messages[] = "<div class='message-error'>The item name must be different from the source items.</div>";
		}
		else
			$messages[] = "<div class='message-error'>You need to select two different items.</div>";
	}
	
	$pagetitle = "[staff] Combine Items";
	require("incAVA/header.php");
?>
			<div class='category-container'>
				<div class='details-header'>
					Combine Items
				</div>
				<div class='details-body'>
					<form method='get'>
						<table class='alternate_with_th'>
							<tr>
								<th>Top Layer</th>
								<th>Bottom Layer</th>
								<th>Name Order</th>
								<th>New Item Name</th>
								<th>&nbsp;</th>
							</tr>
							<tr>
								<td>
									<select name='top'>
										<option value=''>&nbsp;</option>
<?php
	foreach ($items as $item)
		echo "
										<option value='" . $item . "'" . (isset($_GET['top']) && $_GET['top'] == $item ? " selected='selected'" : "") . ">" . $item . "</option>";		
	echo "
									</select>
								</td>
								<td>
									<select name='bottom'>
										<option value=''>&nbsp;</option>";
	foreach ($items as $item)
		echo "
										<option value='" . $item . "'" . (isset($_GET['bottom']) && $_GET['bottom'] == $item ? " selected='selected'" : "") . ">" . $item . "</option>";		
	echo "
									</select>
								</td>
								<td>
									<select name='order'>
										<option value='topfirst'" . (isset($_GET['order']) && $_GET['order'] == "topfirst" ? " selected='selected'" : "") . ">Top Color First</option>
										<option value='bottomfirst'" . (isset($_GET['order']) && $_GET['order'] == "bottomfirst" ? " selected='selected'" : "") . ">Bottom Color First</option>
										<option value='toponly'" . (isset($_GET['order']) && $_GET['order'] == "toponly" ? " selected='selected'" : "") . ">Top Color Only</option>
										<option value='bottomonly'" . (isset($_GET['order']) && $_GET['order'] == "bottomonly" ? " selected='selected'" : "") . ">Bottom Color Only</option>
									</select>
								</td>
								<td>
									<input name='itemname' type='text' size='30' maxlength='30'" . (isset($_GET['itemname']) ? " value='" . $_GET['itemname'] . "'" : "") . "/>
								</td>
								<td>
									<input name='submit' type='submit' value='Start'/>
								</td>
							</tr>
						</table>
					</form>";
				
	if (isset($_GET['itemname']))
	{
		if (file_exists($path . "/" . $_GET['itemname']))
		{
			$show = array();
			$files = scandir($path . "/" . $_GET['itemname']);
			foreach ($files as $file)
				if ($file != "." && $file != "..")
					$show[] = "<img src='" . $path . "/" . $_GET['itemname'] . "/" . $file . "' alt='" . $file . "' title='" . $file . "'/>";
			sort($show);
			foreach ($show as $s)
				echo $s;
		}
	}
?>

				</div>
			</div>
<?php
	require("incAVA/footer.php");
?>