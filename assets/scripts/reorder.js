var altheld = false;
var changed = false;

$("html").keydown(function(event)
{
	if (event.which == 18)
		altheld = true;
});

$("html").keyup(function(event)
{
	if (event.which == 18)
	{
		altheld = false;
		if (changed)
		{
			var items = getColors();
			$("#order").html(items.toString());
			$("#sortable").submit();
		}
	}
});

$("#equipped").sortable({
	containment: "#equipped",
	cursorAt: {top: 25},
	forceHelperSize: true,
	forcePlaceholderSize: true,
	items: "li",
	zIndex: 499,
	update: function(event, ui)
	{
		changed = true;
		if (!altheld)
		{
			var items = getColors();
			$("#order").html(items.toString());
			$("#sortable").submit();
		}
	}
});

$("#equipped select").change(function() {
	changed = true;
	if (!altheld)
	{
		var items = getColors();
		$("#order").html(items.toString());
		$("#sortable").submit();
	}
});

function getColors()
{
	var items = $("#equipped").sortable("toArray").reverse();
	for (i in items)
	{
		item = items[i];
		num = item.substr(item.indexOf("_")+1);
		items[i] = num + "#" + $("#color_" + num).val();
	}
	return items;
}