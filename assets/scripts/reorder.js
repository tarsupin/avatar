function getColors(){var e=$("#equipped").sortable("toArray").reverse();for(i in e){item=e[i];num=item.substr(item.indexOf("_")+1);e[i]=num+"#"+$("#color_"+num).val()}return e}var altheld=false;var changed=false;$("html").keydown(function(e){if(e.which==18)altheld=true});$("html").keyup(function(e){if(e.which==18){altheld=false;if(changed){var t=getColors();$("#order").html(t.toString());$("#sortable").submit()}}});$("#equipped").sortable({containment:"#equipped",cursorAt:{top:25},forceHelperSize:true,forcePlaceholderSize:true,items:"li",zIndex:499,update:function(e,t){changed=true;if(!altheld){var n=getColors();$("#order").html(n.toString());$("#sortable").submit()}}});$("#equipped select").change(function(){changed=true;if(!altheld){var e=getColors();$("#order").html(e.toString());$("#sortable").submit()}})