function review_item(id)
{
	window.open("/preview-avi?equip=" + id + "&color=" + $("#item_" + id).val(), "PreviewAvatar", "height=500,location=no,menubar=no,scrollbars=yes,toolbar=no,width=620");
}

function switch_item(id, layer, name, gender)
{
	$("#img_" + id).attr("src", "/avatar_items/" + layer + "/" + name + "/" + $("#item_" + id).val() + "_" + gender + ".png");
	if($("#item_" + id))
	{
		
	}
}