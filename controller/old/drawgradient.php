<?php
	function drawgradient($getpalette, $getcolor)
	{
		$colors = file_get_contents("avatars/temp/autorecolor/" . $getpalette . "palette_colors.txt");
		$colors = unserialize($colors);
		$apply = $colors[$getcolor];
		
		$number = 1;
		while (isset($apply['index_' . $number]) && $apply['index_' . $number] != "")
			$number++;
		$number--;
		
		for ($k=0; $k<$apply['index_' . $number]; $k++)
		{
			$red[$k] = $apply['red_' . $number];
			$green[$k] = $apply['green_' . $number];
			$blue[$k] = $apply['blue_' . $number];
			$brightness[$k] = $apply['brightness_' . $number];
			$con = $apply['contrast_' . $number];
			$con = max($con, -255);
			$con = min($con, 255);
			$contrast[$k] = (259 * ($con + 255)) / (255 * (259 - $con));
			$saturation[$k] = $apply['saturation_' . $number];
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
				$brightness[$k] = (1 - $f) * $apply['brightness_' . $l] + $f * $apply['brightness_' . ($l-1)];
				$con = (1 - $f) * $apply['contrast_' . $l] + $f * $apply['contrast_' . ($l-1)];
				$con = max($con, -255);
				$con = min($con, 255);
				$contrast[$k] = (259 * ($con + 255)) / (255 * (259 - $con));
				$saturation[$k] = (1 - $f) * $apply['saturation_' . $l] + $f * $apply['saturation_' . ($l-1)];
				$saturation[$k] = $saturation[$k] / 255 + 1;
			}
		}
		
		for ($k=$apply['index_1']; $k<256; $k++)
		{
			$red[$k] = $apply['red_1'];
			$green[$k] = $apply['green_1'];
			$blue[$k] = $apply['blue_1'];
			$brightness[$k] = $apply['brightness_1'];
			$con = $apply['contrast_1'];
			$con = max($con, -255);
			$con = min($con, 255);
			$contrast[$k] = (259 * ($con + 255)) / (255 * (259 - $con));
			$saturation[$k] = $apply['saturation_1'];
			$saturation[$k] = $saturation[$k] / 255 + 1;
		}

		$res = imagecreatetruecolor(256, 20);
		$background_color = imagecolorallocatealpha($res, 0, 255, 0, 127);
		imagefill($res, 0, 0, $background_color);
		imagecolortransparent($res, $background_color);
		
		$color = imagecolorallocate($res, 112, 112, 112);
		for ($i=1; $i<=$number; $i++)
		{
			imageline($res, $apply['index_' . $i], 0, $apply['index_' . $i], 2, $color);
			imageline($res, $apply['index_' . $i], 18, $apply['index_' . $i], 20, $color);
		}
		
		for ($i=0; $i<256; $i++)
		{
			$bright = ($red[$i]*299 + $green[$i]*587 + $blue[$i]*114) / 1000;
			$bright = max($bright, 0);
			$bright = min($bright, 255);
			$r = min(max($bright + $brightness[$i], 0), 255) / 255 * $red[$i];
			$g = min(max($bright + $brightness[$i], 0), 255) / 255 * $green[$i];
			$b = min(max($bright + $brightness[$i], 0), 255) / 255 * $blue[$i];
			$r = min(max($contrast[$i] * ($r - 128) + 128, 0), 255);
			$g = min(max($contrast[$i] * ($g - 128) + 128, 0), 255);
			$b = min(max($contrast[$i] * ($b - 128) + 128, 0), 255);
			$sat = sqrt($r*$r*0.299 + $g*$g*0.587+$b*$b*0.114);
			$r = $sat + ($r - $sat) * $saturation[$i];
			$g = $sat + ($g - $sat) * $saturation[$i];
			$b = $sat + ($b - $sat) * $saturation[$i];
			$r = min(max($r, 0), 255);
			$g = min(max($g, 0), 255);
			$b = min(max($b, 0), 255);			
			$color = imagecolorallocate($res, $r, $g, $b);
			imageline($res, $i, 2, $i, 17, $color);
		}

		imagepng($res, "avatars/temp/autorecolor/colors/" . $getpalette . "palette_" . $getcolor . ".png");
		imagedestroy($res);
	}
?>