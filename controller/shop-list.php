<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure the user has an avatar
if(!isset($avatarData['base']))
{
	header("Location: /create-avatar"); exit;
}

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

echo '
<style>
.shop-button>img { padding: 20px 10px 21px 10px; }
</style>';

echo '
<div id="panel-right"></div>
<div id="content">' . Alert::display() . '
	
	<h2>List of Shops</h2>
	<a class="shop-button" href="shop/1"><img src="/assets/shop-icons/a_cut_above.png" alt="A Cut Above" /></a>
	<a class="shop-button" href="shop/2"><img src="/assets/shop-icons/all_that_glitters.png" alt="All That Glitters" /></a>
	<a class="shop-button" href="shop/3"><img src="/assets/shop-icons/heart_and_sole.png" alt="Heart and Sole" /></a>
	<a class="shop-button" href="shop/4"><img src="/assets/shop-icons/pret_a_porter.png" alt="Pr&ecirc;t &agrave; Porter" /></a>
	<a class="shop-button" href="shop/5"><img src="/assets/shop-icons/the_body_shop.png" alt="Body Shop" /></a>
	<a class="shop-button" href="shop/6"><img src="/assets/shop-icons/the_finishing_touch.png" alt="Finishing Touch" /></a>
	<a class="shop-button" href="shop/7"><img src="/assets/shop-icons/haute_couture.png" alt="Haute Couture" /></a>
	<a class="shop-button" href="shop/8"><img src="/assets/shop-icons/the_junk_drawer.png" alt="Junk Drawer" /></a>
	<a class="shop-button" href="shop/9"><img src="/assets/shop-icons/the_looking_glass.png" alt="Looking Glass" /></a>
	<a class="shop-button" href="shop/10"><img src="/assets/shop-icons/the_time_capsule.png" alt="Time Capsule" /></a>
	<a class="shop-button" href="shop/11"><img src="/assets/shop-icons/under_dressed.png" alt="Under Dressed" /></a>
	<a class="shop-button" href="shop/12"><img src="/assets/shop-icons/vogue_veneers.png" alt="Vogue Veneers" /></a>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
