function review_item(id)
{
	if(id != 0)
		window.open("/preview-avi?equip=" + id + "&color=" + $("#item_" + id).val(), "PreviewAvatar", "height=539,location=no,menubar=no,scrollbars=yes,toolbar=no,width=617");
	else
		window.open("/preview-avi", "PreviewAvatar", "height=539,location=no,menubar=no,scrollbars=yes,toolbar=no,width=617");
}

function switch_item(id, layer, name, gender)
{
	$("#img_" + id).attr("src", "/avatar_items/" + layer + "/" + name + "/" + $("#item_" + id).val() + "_" + gender + ".png");
}