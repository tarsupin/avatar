<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/shop-list");
}

// Set page title
$config['pageTitle'] = "Shops";

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

require(SYS_PATH . "/controller/includes/side-panel.php");

// CSS for positioning of shop signs
echo '
<style>
.shop-button>img { padding: 20px 20px 40px 39px; }
.shop-button2>img { padding: 20px 20px 21px 20px; }
</style>';

// Display Public Shops
echo '
<div id="panel-right"></div>
<div id="content">' . Alert::display() . '
<div class="overwrap-box">
	<div class="overwrap-line">Regular Shops</div>
	<div class="inner-box">
	<a class="shop-button" href="/shop/1"><img src="/assets/shop-icons/a_cut_above.png" alt="A Cut Above" /></a>
	<a class="shop-button" href="/shop/4"><img src="/assets/shop-icons/pret_a_porter.png" alt="Pr&ecirc;t &agrave; Porter" /></a>
	<a class="shop-button" href="/shop/7"><img src="/assets/shop-icons/haute_couture.png" alt="Haute Couture" /></a>
	<a class="shop-button" href="/shop/10"><img src="/assets/shop-icons/the_time_capsule.png" alt="Time Capsule" /></a>
	<a class="shop-button" href="/shop/2"><img src="/assets/shop-icons/all_that_glitters.png" alt="All That Glitters" /></a>
	<a class="shop-button" href="/shop/5"><img src="/assets/shop-icons/the_body_shop.png" alt="Body Shop" /></a>
	<a class="shop-button" href="/shop/8"><img src="/assets/shop-icons/the_junk_drawer.png" alt="Junk Drawer" /></a>
	<a class="shop-button" href="/shop/11"><img src="/assets/shop-icons/under_dressed.png" alt="Under Dressed" /></a>
	<a class="shop-button" href="/shop/3"><img src="/assets/shop-icons/heart_and_sole.png" alt="Heart and Sole" /></a>
	<a class="shop-button" href="/shop/6"><img src="/assets/shop-icons/the_finishing_touch.png" alt="Finishing Touch" /></a>
	<a class="shop-button" href="/shop/9"><img src="/assets/shop-icons/the_looking_glass.png" alt="Looking Glass" /></a>
	<a class="shop-button" href="/shop/12"><img src="/assets/shop-icons/vogue_veneers.png" alt="Vogue Veneers" /></a>
	</div>
</div>
<div class="overwrap-box">
	<div class="overwrap-line">Preview Shops</div>
	<div class="inner-box">	
	<a class="shop-button2" href="/shop/15"><img src="/assets/shop-icons/uf_museum.png" alt="Avatar Museum" /></a>
	<a class="shop-button2" href="/shop/18"><img src="/assets/shop-icons/credit_shop.png" alt="Credit Shop" /></a>
	<a class="shop-button2" href="/shop/14"><img src="/assets/shop-icons/exotic_display.png" alt="Exotic Exhibit" /></a>
	</div>
</div>';
	
// Display Staff Shops
if(Me::$clearance >= 5)
{
	echo '
<div class="overwrap-box">
	<div class="overwrap-line">Staff Shops</div>
	<div class="inner-box">
	<a class="shop-button2" href="/shop/13"><img src="/assets/shop-icons/default.png" alt="Archive" title="Archive" /></a>
	<a class="shop-button2" href="/shop/16"><img src="/assets/shop-icons/default.png" alt="Staff Shop" title="Staff Shop" /></a>
	<a class="shop-button2" href="/shop/17"><img src="/assets/shop-icons/default.png" alt="Test Shop" title="Test Shop" /></a>
	<a class="shop-button2" href="/shop/19"><img src="/assets/shop-icons/default.png" alt="Wrapper Replacements" title="Wrapper Replacements" /></a>
	</div>
</div>';
}
echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
