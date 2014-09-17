<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Check if the Shop Exists
if(!isset($url[1]))
{
	header("Location: /shop-list"); exit;
}

// Get Important Values
$shopID = $url[1] + 0;
$shopTitle = AppAvatar::getShopTitle($shopID);
$cacheRefresh = 0;

if(!isset($avatarData['base']))
{
	header("Location: /create-avatar"); exit;
}

// Run Moderation Tools
if($runLink = Link::clicked())
{
	// Refresh the Shop Cache
	if($runLink == "refresh-shop")
	{
		$cacheRefresh = 10;
	}
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
.shop-block { display:inline-block; padding:15px; text-align:center; }
.shop-block img { max-height:130px; }
</style>';

echo '
<div id="panel-right"></div>
<div id="content">' .
Alert::display();

// Moderator
if(Me::$clearance >= 6)
{
	echo '
	<div style="padding-bottom:20px;">
		Moderator Tools: <a href="/shop/' . $shopID . '?refresh=1&' . Link::prepare("refresh-shop") . '">Refresh Shop</a>
	</div>';
}

// Shop Dispaly
echo '
	<div class="category-container">
		<div class="details-header">
			' . $shopTitle . '
		</div>
		<div class="details-body">';
		
		// Attempt to load the cached version of this shop page
		$cachedPage = "shop_" . $shopID . "_" . $avatarData['gender'];
		
		if(!CacheFile::load($cachedPage, $cacheRefresh, true))
		{
			// Prepare the Shop
			$html = "";
			$shopItems = AppAvatar::getShopItems($shopID);
			
			// Cycle through the shop items
			foreach($shopItems as $item)
			{
				$colors	= AppAvatar::getItemColors($item['position'], $item['title']);
				
				if(!$colors) { continue; }
				
				$genderShow = ($item['gender'] == "b" ? $avatarData['gender'] : $item['gender']);
				$genderShow = ($item['gender'] == "m" ? "male" : "female");
				
				// Display the Item
				$html .= '
				<div class="shop-block">
					<a href="javascript: review_item(\'' . $item['id'] . '\');"><img id="img_' . $item['id'] . '" src="/avatar_items/' . $item['position'] . '/' . $item['title'] . '/' . $colors[0] . '_' . $genderShow . '.png" /></a><br />
					' . $item['title'] . '<br />
					<select id="item_' . $item['id'] . '" onChange="switch_item(\'' . $item['id'] . '\', \'' . $item['position'] . '\', \'' . $item['title'] . '\', \'' . $genderShow . '\');">';
					
					foreach($colors as $color)
					{
						$html .= '
						<option name="' . $color . '">' . $color . '</option>';
					}
					
					$html .= '
					</select>
					<br /><a href="/purchase-item/' . $item['id'] . '?shopID=' . $shopID . '">Buy</a>
				</div>';
			}
			
			// Load the cache now that it's been saved
			CacheFile::save($cachedPage, $html);
			CacheFile::load($cachedPage, true);
		}
		
		echo '
		</div>
	</div>
</div>';
?>

<script>
function switch_item(num, position, name, gender)
{
	var selbox = document.getElementById("item_" + num);
	var getimg = document.getElementById("img_" + num);
	
	getimg.src = "/avatar_items/" + position + "/" + name + "/" + selbox.options[selbox.selectedIndex].value + "_" + gender + ".png";
}

function review_item(id)
{
	var color = document.getElementById("item_" + id);
	
	window.open("/preview-avi?item=" + id + "&color=" + color.options[color.selectedIndex].value, "PreviewAvatar", "width=622,height=500,toolbar=no,location=no,directories=no,status=no,menubar=no,copyhistory=no,scrollbars=yes");
}
</script>

<?php

/*
<script type='text/javascript'>
var owned = new Array(1214, 1218, 1219, 1231, 1233, 1234, 1236, 1243, 1250, 1252, 1256, 1262, 1265, 1270, 1295, 1297, 1441, 1475, 1481, 1493, 1538, 1596, 1600, 1607, 1638, 1708, 1825, 1870, 2099, 2104, 2227, 2253, 2274, 2315, 2317, 2373, 2407, 2412, 2422, 2456, 2457, 1539);

for(i=0;i < owned.length; i++)
{
	if (document.getElementById('img' + owned[i]))
	{
		var el = document.getElementById('img' + owned[i]).parentNode.parentNode.getElementsByTagName('span')[0];
		el.innerHTML += ' &bull;';
	}
}

function switch_item(num, position, name, gender)
{
	var selbox = $("#item" + num);
	$("#img" + num).attr("src", "avatars/" + position + "/" + name + "/" + selbox.val() + "_" +gender + ".png");
}

function review_item(id)
{
	window.open("preview_avi.php?clothingID=" + id + "&recolor=" + $("#item" + id).val(), "PreviewAvatar", "width=622,height=500,toolbar=no,location=no,directories=no,status=no,menubar=no,copyhistory=no,scrollbars=yes");
}
</script>
*/

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
