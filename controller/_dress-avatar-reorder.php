<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Check if you already have an avatar (return home if you do)
$avatarData = AppAvatar::avatarData(Me::$id);

if(!isset($avatarData['base']))
{
	header("Location: /"); exit;
}

// Add Javascript to header
Metadata::addHeader('
<!-- javascript -->
<script src="/assets/scripts/jquery.js" type="text/javascript" charset="utf-8"></script>
<script src="/assets/scripts/jquery-ui.js" type="text/javascript" charset="utf-8"></script>

<!-- javascript for touch devices, source: http://touchpunch.furf.com/ -->
<script src="/assets/scripts/jquery.ui.touch-punch.min.js" type="text/javascript" charset="utf-8"></script>');

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

echo '
<div id="main-content">
<div class="category-container">
	<div class="details-header">
		Dress Your Avatar
	</div>
	<div class="details-body">
	<h3>Welcome to Uni-Avatar!</h3>
	';
	
	?>
	<form id='sortable' action='dress_avatar.php?position=background#wrap' method='post'>
									<ul id='equipped' class='dragndrop'>

										<li id='o2126' class='item'>
											<div><img src='avatars/dress/Oriental Robe Purple/Purple_female.png' title='Oriental Robe Purple'/></div>
											<a class='close' href='dress_avatar.php?position=background&unequip=2126#wrap'>&#10006;</a>
											<select name='i2126'><option value='Purple' selected='selected'>Purple</option></select>
											<a class='left' href='#'>&lt;</a>
											<a class='right' href='#'>&gt;</a>
										</li>
										<li id='o165' class='item'>
											<div><img src='avatars/handheld/Stuffed Panda/Black White_female.png' title='Stuffed Panda'/></div>
											<a class='close' href='dress_avatar.php?position=background&unequip=165#wrap'>&#10006;</a>
											<select name='i165'><option value='Black White' selected='selected'>Black White</option></select>
											<a class='left' href='#'>&lt;</a>
											<a class='right' href='#'>&gt;</a>
										</li>
										<li id='o1270' class='item'>
											<div><img src='avatars/hair/Wispy Hair/Golden Brown_female.png' title='Wispy Hair'/></div>
											<a class='close' href='dress_avatar.php?position=background&unequip=1270#wrap'>&#10006;</a>
											<select name='i1270'><option value='Auburn'>Auburn</option><option value='Black'>Black</option><option value='Blue'>Blue</option><option value='Bright Ginger'>Bright Ginger</option><option value='Chestnut'>Chestnut</option><option value='Dark Blond'>Dark Blond</option><option value='Dark Blue'>Dark Blue</option><option value='Dark Brown'>Dark Brown</option><option value='Dark Green'>Dark Green</option><option value='Dark Pink'>Dark Pink</option><option value='Dark Purple'>Dark Purple</option><option value='Dark Red'>Dark Red</option><option value='Dark Teal'>Dark Teal</option><option value='Ginger'>Ginger</option><option value='Golden Brown' selected='selected'>Golden Brown</option><option value='Gray'>Gray</option><option value='Green'>Green</option><option value='Honey Blond'>Honey Blond</option><option value='Light Blond'>Light Blond</option><option value='Light Blue'>Light Blue</option><option value='Maroon'>Maroon</option><option value='Medium Blond'>Medium Blond</option><option value='Pink'>Pink</option><option value='Purple'>Purple</option><option value='Red'>Red</option><option value='Teal'>Teal</option><option value='White'>White</option><option value='Yellow'>Yellow</option></select>
											<a class='left' href='#'>&lt;</a>
											<a class='right' href='#'>&gt;</a>
										</li>
										<li id='o814' class='item'>
											<div><img src='avatars/shoes/Simple Heels/White_female.png' title='Simple Heels'/></div>
											<a class='close' href='dress_avatar.php?position=background&unequip=814#wrap'>&#10006;</a>
											<select name='i814'><option value='Allure'>Allure</option><option value='Amethyst'>Amethyst</option><option value='Bamboo'>Bamboo</option><option value='Black'>Black</option><option value='Blue'>Blue</option><option value='Bordeaux'>Bordeaux</option><option value='Brown'>Brown</option><option value='Burgundy'>Burgundy</option><option value='Butter'>Butter</option><option value='Charcoal'>Charcoal</option><option value='Cherry'>Cherry</option><option value='Chocolate'>Chocolate</option><option value='Clearwater'>Clearwater</option><option value='Desert'>Desert</option><option value='Fire'>Fire</option><option value='Gold'>Gold</option><option value='Gray'>Gray</option><option value='Green'>Green</option><option value='Hot Pink'>Hot Pink</option><option value='Ice'>Ice</option><option value='Ivory'>Ivory</option><option value='Leather'>Leather</option><option value='Lemon'>Lemon</option><option value='Lime'>Lime</option><option value='Navy'>Navy</option><option value='Nude'>Nude</option><option value='Olive'>Olive</option><option value='Orange'>Orange</option><option value='Peach'>Peach</option><option value='Peacock'>Peacock</option><option value='Pink'>Pink</option><option value='Purple'>Purple</option><option value='Red'>Red</option><option value='Ruby'>Ruby</option><option value='Sakura'>Sakura</option><option value='Seafoam'>Seafoam</option><option value='Silver'>Silver</option><option value='Sky'>Sky</option><option value='Sunflower'>Sunflower</option><option value='Teal'>Teal</option><option value='Velvet Plum'>Velvet Plum</option><option value='Vieux Rose'>Vieux Rose</option><option value='White' selected='selected'>White</option></select>
											<a class='left' href='#'>&lt;</a>
											<a class='right' href='#'>&gt;</a>
										</li>
										<li id='o1105' class='item'>
											<div><img src='avatars/face_extras/Nerd Glasses/Black_female.png' title='Nerd Glasses'/></div>
											<a class='close' href='dress_avatar.php?position=background&unequip=1105#wrap'>&#10006;</a>
											<select name='i1105'><option value='Black' selected='selected'>Black</option><option value='Blue'>Blue</option><option value='Brown'>Brown</option><option value='Dark Brown'>Dark Brown</option><option value='Gold'>Gold</option><option value='Green'>Green</option><option value='Jade'>Jade</option><option value='Orange'>Orange</option><option value='Pink'>Pink</option><option value='Purple'>Purple</option><option value='Red'>Red</option><option value='Silver'>Silver</option><option value='Teal'>Teal</option></select>
											<a class='left' href='#'>&lt;</a>
											<a class='right' href='#'>&gt;</a>
										</li>
										<li id='o91' class='item'>
											<div><img src='avatars/face/Doll Eyes/Amber_female.png' title='Doll Eyes'/></div>
											<a class='close' href='dress_avatar.php?position=background&unequip=91#wrap'>&#10006;</a>
											<select name='i91'><option value='Amber' selected='selected'>Amber</option><option value='Aqua'>Aqua</option><option value='Black'>Black</option><option value='Blue'>Blue</option><option value='Brown'>Brown</option><option value='Ebony'>Ebony</option><option value='Grass'>Grass</option><option value='Gray'>Gray</option><option value='Green'>Green</option><option value='Ice Blue'>Ice Blue</option><option value='Light Blue'>Light Blue</option><option value='Light Gray'>Light Gray</option><option value='Orange'>Orange</option><option value='Pink'>Pink</option><option value='Purple'>Purple</option><option value='Red'>Red</option><option value='Seafoam'>Seafoam</option><option value='Yellow'>Yellow</option></select>
											<a class='left' href='#'>&lt;</a>
											<a class='right' href='#'>&gt;</a>
										</li>
										<li id='o129' class='item'>
											<div><img src='avatars/face/Lips Dolly/Hot Pink_female.png' title='Lips Dolly'/></div>
											<a class='close' href='dress_avatar.php?position=background&unequip=129#wrap'>&#10006;</a>
											<select name='i129'><option value='Allure'>Allure</option><option value='Almond'>Almond</option><option value='Amethyst'>Amethyst</option><option value='Aqua'>Aqua</option><option value='Aquamarine'>Aquamarine</option><option value='Bamboo'>Bamboo</option><option value='Black'>Black</option><option value='Blue'>Blue</option><option value='Blush'>Blush</option><option value='Bordeaux'>Bordeaux</option><option value='Brass'>Brass</option><option value='Bronze'>Bronze</option><option value='Brown'>Brown</option><option value='Burgundy'>Burgundy</option><option value='Butter'>Butter</option><option value='Charcoal'>Charcoal</option><option value='Cherry'>Cherry</option><option value='Chocolate'>Chocolate</option><option value='Clearwater'>Clearwater</option><option value='Dark Blue'>Dark Blue</option><option value='Dark Brown'>Dark Brown</option><option value='Dark Green'>Dark Green</option><option value='Dark Pink'>Dark Pink</option><option value='Dark Purple'>Dark Purple</option><option value='Dark Red'>Dark Red</option><option value='Dark Yellow'>Dark Yellow</option><option value='Desert'>Desert</option><option value='Ebony'>Ebony</option><option value='Fire'>Fire</option><option value='Frost'>Frost</option><option value='Fuchsia'>Fuchsia</option><option value='Gold'>Gold</option><option value='Gray'>Gray</option><option value='Green'>Green</option><option value='Hot Pink' selected='selected'>Hot Pink</option><option value='Ice'>Ice</option><option value='Indigo'>Indigo</option><option value='Ivory'>Ivory</option><option value='Leather'>Leather</option><option value='Lemon'>Lemon</option><option value='Light Blue'>Light Blue</option><option value='Light Green'>Light Green</option><option value='Light Pink'>Light Pink</option><option value='Light Purple'>Light Purple</option><option value='Light Yellow'>Light Yellow</option><option value='Lilac'>Lilac</option><option value='Lime'>Lime</option><option value='Maroon'>Maroon</option><option value='Midnight'>Midnight</option><option value='Navy'>Navy</option><option value='Nude'>Nude</option><option value='Olive'>Olive</option><option value='Orange'>Orange</option><option value='Peach'>Peach</option><option value='Peacock'>Peacock</option><option value='Pink'>Pink</option><option value='Purple'>Purple</option><option value='Red'>Red</option><option value='Sakura'>Sakura</option><option value='Seafoam'>Seafoam</option><option value='Sienna'>Sienna</option><option value='Silver'>Silver</option><option value='Sky'>Sky</option><option value='Sunflower'>Sunflower</option><option value='Sunkissed'>Sunkissed</option><option value='Teal'>Teal</option><option value='Velvet Plum'>Velvet Plum</option><option value='Vieux Rose'>Vieux Rose</option><option value='Violet'>Violet</option><option value='White'>White</option><option value='Yellow'>Yellow</option></select>
											<a class='left' href='#'>&lt;</a>
											<a class='right' href='#'>&gt;</a>
										</li>
										<li id='o0' class='base'>
											<div style='line-height:50px;'>Base</div>
											<select name='i0' disabled><option value='white'>White</option></select>
											<a class='left' href='#'>&lt;</a>
											<a class='right' href='#'>&gt;</a>
										</li>
										<li id='o177' class='item'>
											<div><img src='avatars/background/BG Bubbles/Bubbles_female.png' title='BG Bubbles'/></div>
											<a class='close' href='dress_avatar.php?position=background&unequip=177#wrap'>&#10006;</a>
											<select name='i177'><option value='Bubbles' selected='selected'>Bubbles</option></select>
											<a class='left' href='#'>&lt;</a>
											<a class='right' href='#'>&gt;</a>
										</li>
									</ul>
									<textarea id='order' name='order' style='display:none;'></textarea>
								</form>
		<?php
	
	echo '
	</div>
</div>
</div>';

?>
<script type='text/javascript'>
	function switch_item(num, position, name, gender)
	{
		var selbox = $("#item" + num);
		var selbox2 = $("#pos" + num);
		$("#img" + num).attr("src", "avatars/" + position + "/" + name + "/" + selbox.val() + "_" +gender + ".png");
		$("#dresslink_" + num).attr("href", "dress_avatar.php?position=" + position + "&equip=" + num + "&pre=" + selbox.val() + "#wrap");
	}
	
	function review_item(itemid, id)
	{
		window.open("preview_avi.php?clothingID=" + id + "&recolor=" + $("#item" + itemid).val(), "PreviewAvatar", "width=622,height=500,toolbar=no,location=no,directories=no,status=no,menubar=no,copyhistory=no,scrollbars=yes");
	}
</script>
<script src='/assets/scripts/reorder.js' type='text/javascript' charset='utf-8'></script>

<?php

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
