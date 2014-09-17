<?php
	if (!isset($_GET['palette']) || !isset($_GET['src']) || !isset($_GET['color']) || !isset($_GET['gender']) || !isset($_GET['title']) || !isset($_GET['gb']) || !isset($_GET['gc']) || !isset($_GET['gs']))
	{
		exit;
	}
	
	$path = "avatars/temp/autorecolor";	
	$colors = file_get_contents($path . "/" . $_GET['palette'] . "palette_colors.txt");
	$colors = unserialize($colors);
	$apply = $colors[$_GET['color']];
	
	$number = 1;
	while (isset($apply['index_' . $number]) && $apply['index_' . $number] != "")
		$number++;
	$number--;
	
	for ($k=0; $k<$apply['index_' . $number]; $k++)
	{
		$red[$k] = $apply['red_' . $number];
		$green[$k] = $apply['green_' . $number];
		$blue[$k] = $apply['blue_' . $number];
		$brightness[$k] = $apply['brightness_' . $number] + $_GET['gb'];
		$con = $apply['contrast_' . $number] + $_GET['gc'];
		$con = max($con, -255);
		$con = min($con, 255);
		$contrast[$k] = (259 * ($con + 255)) / (255 * (259 - $con));
		$saturation[$k] = $apply['saturation_' . $number] + $_GET['gs'];
		$saturation[$k] = $saturation[$k] / 255 + 1;
	}
	
	for ($l=$number; $l>1; $l--)
	{
		for ($k=$apply['index_' . $l]; $k<$apply['index_' . ($l-1)]; $k++)
		{
			$f = ($k - $apply['index_' . $l]) / ($apply['index_' . ($l-1)] - $apply['index_' . $l]);
			$red[$k] = (1 - $f) * $apply['red_' . $l] + $f * $apply['red_' . ($l-1)];
			$green[$k] = (1 - $f) * $apply['green_' . $l] + $f * $apply['green_' . ($l-1)];
			$blue[$k] = (1 - $f) * $apply['blue_' . $l] + $f * $apply['blue_' . ($l-1)];
			$brightness[$k] = (1 - $f) * $apply['brightness_' . $l] + $f * $apply['brightness_' . ($l-1)] + $_GET['gb'];
			$con = (1 - $f) * $apply['contrast_' . $l] + $f * $apply['contrast_' . ($l-1)] + $_GET['gc'];
			$con = max($con, -255);
			$con = min($con, 255);
			$contrast[$k] = (259 * ($con + 255)) / (255 * (259 - $con));
			$saturation[$k] = (1 - $f) * $apply['saturation_' . $l] + $f * $apply['saturation_' . ($l-1)] + $_GET['gs'];
			$saturation[$k] = $saturation[$k] / 255 + 1;
		}
	}
	
	for ($k=$apply['index_1']; $k<256; $k++)
	{
		$red[$k] = $apply['red_1'];
		$green[$k] = $apply['green_1'];
		$blue[$k] = $apply['blue_1'];
		$brightness[$k] = $apply['brightness_1'] + $_GET['gb'];
		$con = $apply['contrast_1'] + $_GET['gc'];
		$con = max($con, -255);
		$con = min($con, 255);
		$contrast[$k] = (259 * ($con + 255)) / (255 * (259 - $con));
		$saturation[$k] = $apply['saturation_1'] + $_GET['gs'];
		$saturation[$k] = $saturation[$k] / 255 + 1;
	}

	$img = imagecreatefrompng(urldecode($_GET['src']));
	$res = imagecreatetruecolor(imagesx($img), imagesy($img));
	$background_color = imagecolorallocatealpha($res, 0, 255, 0, 127);
	imagefill($res, 0, 0, $background_color);
	imagecolortransparent($res, $background_color);
	
	for ($i=0; $i<imagesx($img); $i++)
		for ($j=0; $j<imagesy($img); $j++)
		{
			$rgb = imagecolorat($img, $i, $j);
			$alpha = ($rgb >> 24) & 0xFF;
			$r = ($rgb >> 16) & 0xFF;
			$g = ($rgb >> 8) & 0xFF;
			$b = $rgb & 0xFF;
			$bright = ($r*299 + $g*587 + $b*114) / 1000;
			$bright = max($bright, 0);
			$bright = min($bright, 255);
			$bright = round($bright);
			$r = min(max($bright + $brightness[$bright], 0), 255) / 255 * $red[$bright];
			$g = min(max($bright + $brightness[$bright], 0), 255) / 255 * $green[$bright];
			$b = min(max($bright + $brightness[$bright], 0), 255) / 255 * $blue[$bright];
			$r = min(max($contrast[$bright] * ($r - 128) + 128, 0), 255);
			$g = min(max($contrast[$bright] * ($g - 128) + 128, 0), 255);
			$b = min(max($contrast[$bright] * ($b - 128) + 128, 0), 255);
			$sat = sqrt($r*$r*0.299 + $g*$g*0.587 + $b*$b*0.114);
			$r = $sat + ($r - $sat) * $saturation[$bright];
			$g = $sat + ($g - $sat) * $saturation[$bright];
			$b = $sat + ($b - $sat) * $saturation[$bright];
			$r = min(max($r, 0), 255);
			$g = min(max($g, 0), 255);
			$b = min(max($b, 0), 255);
			$color = imagecolorallocatealpha($img, $r, $g, $b, $alpha);
			imagesetpixel($res, $i, $j, $color);
		}
		
	imagedestroy($img);

	imagealphablending($res, true);
	imagesavealpha($res, true);
	header("Content-type: image/png", false);
	// new item
	if (!file_exists("avatars/" . $_GET['layer'] . "/" . $_GET['title']))
	{
		if (!file_exists($path . "/" . $_GET['title']))
			mkdir($path . "/" . $_GET['title']);
		imagepng($res, $path . "/" . $_GET['title'] . "/" . $_GET['color'] . "_" . $_GET['gender'] . ".png");
	}
	// additional colors
	else
	{
		if (!file_exists("avatars/temp/" . $_GET['color']))
			mkdir("avatars/temp/" . $_GET['color']);
		imagepng($res, "avatars/temp/" . $_GET['color'] . "/" . $_GET['layer'] . "_" . $_GET['title'] . "_" . $_GET['gender'] . ".png");
	}
	imagepng($res);
	imagedestroy($res);
?>