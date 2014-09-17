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

	// Add New Exotic Package
	if (isset($_POST['submit']) && isset($_POST['title']) && isset($_POST['image']) && isset($_POST['year']) && isset($_POST['month']))
	{
		// Check Title Errors
		if (strlen($_POST['title']) < 6)
			$messages[] = "<div class='message-error'>Your Title is too short.</div>";
		
		// Check Year
		if ($_POST['year'] < 2009 || $_POST['year'] > (date("Y", time()) + 1))
			$messages[] = "<div class='message-error'>Please select an appropriate Year.</div>";
		
		// Check Month
		if ($_POST['month'] < 1 || $_POST['month'] > 12)
			$messages[] = "<div class='message-error'>Please select an appropriate Month.</div>";
		
		// Confirm that Year and Month doesn't already exist
		$result = mysql_query("SELECT `id` FROM `exotic_packages` WHERE `year`='" . ($_POST['year'] + 0) . "' AND `month`='" . ($_POST['month'] + 0) . "' LIMIT 1");
		
		if ($conf_ym = mysql_fetch_assoc($result))
			$messages[] = "<div class='message-error'>You already have a Package with this Year and Month.</div>";
		
		if ($messages == array())
		{
			if (substr($_POST['image'], strlen($_POST['image'])-4, 4) != ".png")
				$_POST['image'] .= ".png";
			
			// Add Exotic Package
			mysql_query("INSERT INTO `exotic_packages` (`title`, `image`, `year`, `month`) VALUES ('" . protectSQL($_POST['title']) . "', '" . protectSQL($_POST['image']) . "', '" . ($_POST['year'] + 0) . "', '" . ($_POST['month'] + 0) . "')");
			
			$messages[] = "<div class='message-success'>You have successfully added the " . $_POST['title'] . " Package!</div>";
		}
	}

	$pagetitle = "[staff] Manage Exotic Packages";
	require("incAVA/header.php");
?>
			<div class='category-container'>
				<div class='details-header'>
					Manage Exotic Packages
				</div>
				<div class='details-body'>
					<form method='post'>
						<table class='alternate_with_th'>
							<tr>
								<th>ID</th>
								<th>Image</th>
								<th>Title</th>
								<th>Image</th>
								<th>Year</th>
								<th>Month</th>
								<td>&nbsp;</td>
							</tr>
							<tr>
								<td>&nbsp;</td>
								<td>&nbsp;</td>
								<td><input type='text' name='title' value='' size='15' maxlength='20'/></td>
								<td><input type='text' name='image' value='' size='15' maxlength='20'/></td>
								<td><input type='text' name='year' value='' size='5' maxlength='4'/></td>
								<td><input type='text' name='month' value='' size='5' maxlength='2'/></td>
								<td><input type='submit' name='submit' value='Add'/></td>
							</tr>
<?php
					
					// Show Exotic Packages
					$result = mysql_query("SELECT `id`, `title`, `image`, `year`, `month` FROM `exotic_packages` ORDER BY `year` DESC, `month` DESC");
					
					while($fetch = mysql_fetch_assoc($result))
					{
						echo "
							<tr>
								<td>" . $fetch['id'] . "</td>
								<td><img src='images/exotic_packages/" . $fetch['image'] . "' style='max-height:30px;'/></td>
								<td>" . $fetch['title'] . "</td>
								<td>" . $fetch['image'] . "</td>
								<td>" . $fetch['year'] . "</td>
								<td>" . $fetch['month'] . "</td>
								<td>&nbsp;</td>
							</tr>";
					}
?>

						</table>
					</form>
				</div>
			</div>
<?php
	require("incAVA/footer.php");
?>