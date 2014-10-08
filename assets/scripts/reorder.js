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
			$("#order").html($("#equipped").sortable("toArray").reverse().toString());
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
			$("#order").html($("#equipped").sortable("toArray").reverse().toString());
			$("#sortable").submit();
		}
	}
});

$("#equipped select").change(function() {
	changed = true;
	if (!altheld)
	{
		$("#order").html($("#equipped").sortable("toArray").reverse().toString());
		$("#sortable").submit();
	}
});

$(".left").click(function(event)
{
	var el = $(event.target).parent().attr("id");
	if (go = $("#" + el).prev().attr("id"))
	{
		// clicking left arrow on base when skin equipped; move top item 2 towards right
		if ($("#" + el).attr("class") == "base" && $("#" + go).attr("class") == "skin" && $("#" + go).prev().length > 0)
		{
			var cont = $("#" + go).prev().attr("id");
			$("#" + cont).insertAfter($("#" + el));
		}
		// clicking left arrow on skin; move top item 2 towards right
		else if ($("#" + el).attr("class") == "skin" && $("#" + el).next().length > 0)
		{
			var cont = $("#" + el).next().attr("id");
			$("#" + go).insertAfter($("#" + cont));
		}
		// clicking on item below base
		else if ($("#" + go).attr("class") == "base" && $("#" + go).prev().length > 0)
		{
			var cont = $("#" + go).prev().attr("id");
			// skin equipped; move 2 towards left
			if ($("#" + cont).attr("class") == "skin")
				$("#" + el).insertBefore($("#" + cont));
			// move 1 towards left
			else
				$("#" + el).insertBefore($("#" + go));
		}
		// move 1 towards left
		else
			$("#" + el).insertBefore($("#" + go));

		changed = true;
		if (!altheld)
		{
			$("#order").html($("#equipped").sortable("toArray").reverse().toString());
			$("#sortable").submit();
		}
	}
	return false;
});

$(".right").click(function(event)
{
	var el = $(event.target).parent().attr("id");
	if (go = $("#" + el).next().attr("id"))
	{
		// clicking right arrow on base
		if ($("#" + el).attr("class") == "base" && $("#" + el).prev().length > 0)
		{
			var cont = $("#" + el).prev().attr("id");
			// skin equipped; move bottom item 2 towards left
			if ($("#" + cont).attr("class") == "skin")
				$("#" + go).insertBefore($("#" + cont));
			// move 1 towards right 
			else
				$("#" + el).insertAfter($("#" + go));
		}
		// clicking right arrow on skin; move bottom item 2 towards left
		else if ($("#" + el).attr("class") == "skin" && $("#" + go).next().length > 0)
		{
			var cont = $("#" + go).next().attr("id");
			$("#" + cont).insertBefore($("#" + el));
		}
		// clicking on item above skin; move 2 towards right
		else if ($("#" + go).attr("class") == "skin"  && $("#" + go).next().length > 0)
		{
			var cont = $("#" + go).next().attr("id");
			$("#" + el).insertAfter($("#" + cont));
		}
		// move 1 towards right
		else
			$("#" + el).insertAfter($("#" + go));
		
		changed = true;
		if (!altheld)
		{
			$("#order").html($("#equipped").sortable("toArray").reverse().toString());
			$("#sortable").submit();
		}
	}
	return false;
});