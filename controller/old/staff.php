<?php
	require("config.php");
	require("incAVA/dbAVAconnect.php");
	require("incAVA/global.php");
	
	if ($fetch_account['clearance'] < 6)
	{
		header("Location: index.php");
		exit;
	}

	$pagetitle = "[staff] Staff Page";
	require("incAVA/header.php");
?>
			<div class='category-container'>
				<div class='details-header'>
					Staff Page
				</div>
				<div class='details-body'>
					<ul>
						<li><a href='adminCreateItem.php'>Create New Item</a></li>
						<li><a href='adminItemList.php'>Review Item List</a></li>
						<li><a href='adminExoticPackages.php'>Manage Exotic Packages</a></li>
						<li><a href='shop_refresh.php'>Refresh Shops</a></li>
					</ul>
					<br/><br/>
					<ul>
						<li><a href='exotic_list.php'>List of Exotic Items</a> (public) | <a href='exotic_list.php?force_update'>Update List</a></li>
						<li><a href='adminUnusedItems.php'>Unused Items</a></li>
					</ul>
					<br/><br/>
					<ul>
						<li><a href='surprise_staff.php'>Manage Event Calendar</a></li>
						<li><a href='raffle_staff.php'>Manage Raffle</a></li>
						<li><a href='treasure_staff.php'>Manage Treasure Hunt</a> | <a href='treasure_open.php'>Reveal Treasure</a></li>
						<li><a href='wrap_staff.php'>Manage Wrapped Sets</a> | <a href='wrap_open.php'>Open Wrapped Set</a> (for users)</li>
					</ul>
				</div>
			</div>
<?php
	require("incAVA/footer.php");
?>