<?php

/////////////Header Params
$headercolor= elgg_get_plugin_setting('header_color', 'encolor'); //real

$darkheader =  elgg_get_plugin_setting('darkheader_color', 'encolor');  //addcolordiff($headercolor,array(0.98,0.28,-24.71));
//the font here is bodybgcolor
$headerfontcolor = elgg_get_plugin_setting('invert_color', 'encolor'); 
$bodyfontcolor = elgg_get_plugin_setting('bodyfont_color', 'encolor');   //addcolordiff($headercolor,array(0.00,0.00,-100.00)); //replace 444  


/////////////Body Params
$bodybgcolor = elgg_get_plugin_setting('body_color', 'encolor');    //FFFFFF white ,   yelloe
//$bodybgcolor ='#FFFFFF';    //FFFFFF white ,   yelloe

$tabbgcolor =  elgg_get_plugin_setting('tabbg_color', 'encolor');   
//addcolordiff($bodybgcolor,array(0.00,0.00,-6.67)); //eee
$tabbordercolor =  elgg_get_plugin_setting('tabborder_color', 'encolor');  