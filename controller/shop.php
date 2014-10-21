<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Defaults if a guest is viewing the page
if(!Me::$loggedIn)
{
	// preview is disabled for guests, so any base choice will suffice to get past the next page access check
	$avatarData['base'] = "white";
	// gender choice is provided when coming from the shop list
	if(isset($url[2]) and in_array($url[2], array("m", "f")))	{ $avatarData['gender'] = $url[2]; }
	else														{ header("Location: /shop-list"); exit; }
	Alert::info("Guest Access", 'You are viewing this page as a guest. If you have an account, please <a href="/login">log in</a>.');
}

// Check if you have an avatar
if(!isset($avatarData['base']))		{ header("Location: /create-avatar"); exit; }

// Check if a shop is selected
if(!isset($url[1]))					{ header("Location: /shop-list"); exit; }

// Get Important Values
$shopID = (int) $url[1];
$shopTitle = AppAvatar::getShopTitle($shopID);
$shopClearance = AppAvatar::getShopClearance($shopID);

// Check that the shop exists
if($shopTitle == "") 				{ header("Location: /shop-list"); exit; }

// Check that you're allowed to view this shop
if(Me::$clearance < $shopClearance)	{ header("Location: /shop-list"); exit; }

// Set page title
$config['pageTitle'] = "Shops > " . $shopTitle;

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Add Javascript to header
Metadata::addHeader('
<!-- javascript -->
<script src="/assets/scripts/jquery.js" type="text/javascript" charset="utf-8"></script>
<script src="/assets/scripts/jquery-ui.js" type="text/javascript" charset="utf-8"></script>
<script src="/assets/scripts/review-switch.js" type="text/javascript" charset="utf-8"></script>

<!-- javascript for touch devices, source: http://touchpunch.furf.com/ -->
<script src="/assets/scripts/jquery.ui.touch-punch.min.js" type="text/javascript" charset="utf-8"></script>
');

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
WidgetLoader::add("SidePanel", 40, '
	<div class="panel-links" style="text-align:center;">
		<a href="javascript:review_item(0);">Open Preview Window</a>
	</div>
	<br/>');

// Add list of shops
if(!Me::$loggedIn)	{ $extra = '/' . $avatarData['gender']; }
else				{ $extra = ''; }
WidgetLoader::add("SidePanel", 50, '
	<div class="panel-box"><ul class="panel-slots">
		<li class="nav-slot' . ($url[1] == 1 ? " nav-active" : "") . '"><a href="/shop/1' . $extra . '">A Cut Above<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot' . ($url[1] == 2 ? " nav-active" : "") . '"><a href="/shop/2' . $extra . '">All That Glitters<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot' . ($url[1] == 15 ? " nav-active" : "") . '"><a href="/shop/15' . $extra . '">Avatar Museum<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot' . ($url[1] == 5 ? " nav-active" : "") . '"><a href="/shop/5' . $extra . '">Body Shop<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot' . ($url[1] == 18 ? " nav-active" : "") . '"><a href="/shop/18' . $extra . '">Credit Shop<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot' . ($url[1] == 14 ? " nav-active" : "") . '"><a href="/shop/14' . $extra . '">Exotic Exhibit<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot' . ($url[1] == 6 ? " nav-active" : "") . '"><a href="/shop/6' . $extra . '">Finishing Touch<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot' . ($url[1] == 7 ? " nav-active" : "") . '"><a href="/shop/7' . $extra . '">Haute Couture<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot' . ($url[1] == 3 ? " nav-active" : "") . '"><a href="/shop/3' . $extra . '">Heart and Sole<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot' . ($url[1] == 8 ? " nav-active" : "") . '"><a href="/shop/8' . $extra . '">Junk Drawer<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot' . ($url[1] == 9 ? " nav-active" : "") . '"><a href="/shop/9' . $extra . '">Looking Glass<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot' . ($url[1] == 4 ? " nav-active" : "") . '"><a href="/shop/4' . $extra . '">Pr&ecirc;t &agrave; Porter<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot' . ($url[1] == 10 ? " nav-active" : "") . '"><a href="/shop/10' . $extra . '">Time Capsule<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot' . ($url[1] == 11 ? " nav-active" : "") . '"><a href="/shop/11' . $extra . '">Under Dressed<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot' . ($url[1] == 12 ? " nav-active" : "") . '"><a href="/shop/12' . $extra . '">Vogue Veneers<span class="icon-circle-right nav-arrow"></span></a></li>
	</ul></div>');
	
if(Me::$clearance >= 5)
{
	WidgetLoader::add("SidePanel", 60, '
	<div class="panel-box"><ul class="panel-slots">
		<li class="nav-slot' . ($url[1] == 13 ? " nav-active" : "") . '"><a href="/shop/13' . $extra . '">Archive<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot' . ($url[1] == 16 ? " nav-active" : "") . '"><a href="/shop/16' . $extra . '">Staff Shop<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot' . ($url[1] == 17 ? " nav-active" : "") . '"><a href="/shop/17' . $extra . '">Test Shop<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot' . ($url[1] == 19 ? " nav-active" : "") . '"><a href="/shop/19' . $extra . '">Wrappers<span class="icon-circle-right nav-arrow"></span></a></li>
	</ul></div>');
}

require(SYS_PATH . "/controller/includes/side-panel.php");

// Display Page
echo '
<div id="panel-right"></div>
<div id="content">' .
Alert::display();

// Shop Display
echo '
	<h2>' . $shopTitle . '</h2>';
		
// Attempt to load the cached version of this shop page
$cachedPage = "shop_" . $shopID . "_" . $avatarData['gender'];

if(!CacheFile::load($cachedPage, 0, true))
{
	// Prepare the Shop
	$html = "";
	$shopItems = AppAvatar::getShopItems($shopID);
	
	// Sort items alphabetically by title
	function items_alpha($a, $b) { return strcmp($a['title'], $b['title']); }
	usort($shopItems, "items_alpha");

	// Cycle through the shop items
	foreach($shopItems as $item)
	{
		// Skip item if not available for the gender
		if(!in_array($item['gender'], array("b", $avatarData['gender']))) { continue; }

		// Get list of colors
		$colors	= AppAvatar::getItemColors($item['position'], $item['title']);				
		if(!$colors) { continue; }
		
		// Display the Item					
		$html .= '
		<div class="item_block">
			<a href="javascript: review_item(\'' . $item['id'] . '\');"><img id="img_' . $item['id'] . '" src="/avatar_items/' . $item['position'] . '/' . $item['title'] . '/default_' . $avatarData['gender_full'] . '.png" /></a><br />
			' . $item['title'] . '<br />
			<select id="item_' . $item['id'] . '" onChange="switch_item(\'' . $item['id'] . '\', \'' . $item['position'] . '\', \'' . $item['title'] . '\', \'' . $avatarData['gender_full'] . '\');">';
			
			foreach($colors as $color)
			{
				$html .= '
				<option name="' . $color . '">' . $color . '</option>';
			}
			
			$html .= '
			</select>';
			if($item['rarity_level'] < 1)
			{
				$html .= '
			<br /><a href="/purchase-item/' . $item['id'] . '?shopID=' . $shopID . '">Buy</a>';
			}
			else
			{
				$html .= '
			<br />Preview';
			}
		$html .= '
		</div>';
	}

	// Load the cache now that it's been saved
	CacheFile::save($cachedPage, $html);
	CacheFile::load($cachedPage);
}

echo '
</div>';

// Allow staff to purchase all items (replaces the "Preview" text with a purchase link)
if(Me::$clearance >= 5)
{
?>
		
<script type='text/javascript'>
	$(".item_block").each(function(index)
	{
		var html = $(this).html();
		html = html.trim();
		if (html.substr(html.length-7) == "Preview")
		{
			var id = $(this).children("select").attr("id");
			id = id.substr(id.indexOf("_")+1);
			html = html.substr(0, html.length-7) + '<a href="/purchase-item/' + id + '?shopID=' + <?php echo $url[1]; ?> + '">Buy</a>';
			$(this).html(html);
		}
	});
</script>

<?php
}

// Indicate items you own
if(Me::$loggedIn)
{
	$items = array();
	$owned = Database::selectMultiple("SELECT DISTINCT shop_inventory.item_id FROM user_items INNER JOIN shop_inventory ON user_items.item_id=shop_inventory.item_id WHERE uni_id=? and shop_id=?", array(Me::$id, $shopID));
	foreach($owned as $own)
	{
		$items[] = $own['item_id'];
	}
	// prevent problem with javascript array
	if(count($items) == 1)
	{
		$items[] = 0;
	}
?>
		
<script type='text/javascript'>
	var owned = new Array(<?php echo implode(",", $items); ?>);
	for(i in owned)
	{
		var el = $("#img_" + owned[i]);
		if (el)
		{
			el.parents(".item_block").html(el.parents(".item_block").html() + " [&bull;]");
		}
	}
</script>

<?php
}

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
