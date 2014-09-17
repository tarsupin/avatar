<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

Currency::sql();

exit;

// Get the user's avatar
$avatarData = AppAvatar::avatarData(Me::$id);

if(!isset($avatarData['base']))
{
	header("Location: /create-avatar"); exit;
}

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

echo '
<div id="main-content">
	<div class="category-container">
		<div class="details-header">
			UniAvatar
		</div>
		<div class="details-body">';
		
		echo '
		</div>
	</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
