<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/shop" . (isset($url[1]) ? "/" . $url[1] : "-list"));
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

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
WidgetLoader::add("SidePanel", 20, '
	<div class="panel-box"><ul class="panel-slots">
		<li class="nav-slot"><a href="/shop-search">Shop Search<span class="icon-search-plus nav-arrow"></span></a></li>
	</ul></div>');

require(SYS_PATH . "/controller/includes/side-panel.php");

// Display Page
echo '
<div id="panel-right"></div>
<div id="content">' .
Alert::display();

// Shop Display
$shops = array(
	1 => "A Cut Above",
	2 => "All That Glitters",
	5 => "Body Shop",
	6 => "Finishing Touch",
	7 => "Haute Couture",
	3 => "Heart and Sole",
	8 => "Junk Drawer",
	9 => "Looking Glass",
	4 => "Pr&ecirc;t &agrave; Porter",
	10 => "Time Capsule",
	11 => "Under Dressed",
	12 => "Vogue Veneers",
	15 => "Avatar Museum",
	18 => "Credit Shop",
	14 => "Exotic Exhibit"
);
if(Me::$clearance >= 5)
{
	$shops[13] = "Archive";
	$shops[16] = "Staff Shop";
	$shops[17] = "Test Shop";
	$shops[19] = "Wrappers";
}
echo '
	<div class="redlinks">';
foreach($shops as $key => $shop)
{
	echo '
		<a href="/shop/' . $key . '"' . ($url[1] == $key ? ' class="category-active"' : '') . '>' . $shop . '</a>';
}
echo '
	</div>';
unset($shops);
		
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
			
			$html .= '<br /><a href="utilities/wish-list?add=' . $item['id'] . '">Wish</a>';
			if((int) $item['rarity_level'] == 0)
			{
				$html .= '
			 | <a href="/purchase-item/' . $item['id'] . '?shopID=' . $shopID . '">Buy</a>';
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
		if (html.substr(html.length-7) != "Buy</a>")
		{
			var id = $(this).children("select").attr("id");
			id = id.substr(id.indexOf("_")+1);
			html = html + ' | <a href="/purchase-item/' + id + '?shopID=' + <?php echo $url[1]; ?> + '">Buy</a>';
			$(this).html(html);
		}
	});
</script>

<?php
}

// Indicate items you own
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

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
