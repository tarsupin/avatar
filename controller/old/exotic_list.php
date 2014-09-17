<?php
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");

	$pagetitle = "List of Exotic Items";
	require("incAVA/header.php");

	$notcurrent = false;

	if (!file_exists("content/exotic_list.html") || filemtime("content/exotic_list.html") < time() - 2592000 || isset($_GET['force_update']))
	{
		$contentHTML = "";
		
		$getps = mysql_query("SELECT `id`, `title`, `image`, `year` FROM `exotic_packages` ORDER BY `id` DESC");

		while ($getp = mysql_fetch_assoc($getps))
		{
			$contentHTML .= "
			<div class='category-container'>
				<div class='details-header'>
					" . $getp['year'] . " " . $getp['title'] . "
				</div>
				<div class='details-body'>";
			$names = array();
			$content1 = '';
			$content2 = '';
			$getcs = mysql_query("SELECT `clothing`, `position` FROM `clothing_images` WHERE `exoticPackage`=" . ($getp['id'] + 0) . " ORDER BY `clothing`");
			while ($getc = mysql_fetch_assoc($getcs))
			{
				array_push($names, $getc['clothing']);
				$files = scandir("avatars/" . $getc['position'] . "/" . $getc['clothing']);
				foreach ($files as $file)
				{
					if (strpos($file, "_female.png"))
						$content1 .= "<img src='avatars/" . $getc['position'] . "/" . $getc['clothing'] . "/" . $file . "'/>";
				}
				$files = scandir("avatars/" . $getc['position'] . "/" . $getc['clothing']);
				foreach ($files as $file)
				{
					if (strpos($file, "_male.png"))					
						$content2 .= "<img src='avatars/" . $getc['position'] . "/" . $getc['clothing'] . "/" . $file . "'/>";
				}
			}
			$contentHTML .= "
					<div class='spoiler' style='text-align:center;'>
						<div class='spoiler_header' onclick='$(this).next().slideToggle(600);'>
							<img src='images/exotic_packages/" . $getp['image'] . "'/><br/>
							" . implode(", ", $names) . "
						</div>
						<div class='spoiler_content' " . ($notcurrent ? "" : "style='display:block;'") . ">";
			$contentHTML .= $content1 . "<br/>" . $content2;
			$contentHTML .= "
						</div>
					</div>";
			$contentHTML .= "
				</div>
			</div>";
			
			$notcurrent = true;
		}
		
		file_put_contents("content/exotic_list.html", $contentHTML);
	}

	include ("content/exotic_list.html");
?>

<?php
	require("incAVA/footer.php");
?>