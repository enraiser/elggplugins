<?php

//$headercolor= '#60B8F7'; //real
//$headercolor = '#006400';//green
$headercolor = '#FDF3E7';//   yellowgrey ;
$rgb = hex2rgb($headercolor);
   //  echo $color." = ".$rgb[0]."/".$rgb[1]."/".$rgb[2]." hm<br>";

$hsv = RGBtoHSV($rgb);



$hsvfont = $hsv;

//echo "<br>";
$hsvfont = $hsv;
$hsvfont[1] = 100.00 - $hsvfont[1];
$hsvfont[2] = 100.00 - $hsvfont[2];
$hsvfont[0]= ($hsvfont[0] + 180) % 360;



$headerfontcolor = rgb2hex(HSVtoRGB($hsvfont));//addcolordiff($headercolor,array(0.00,0.00,-100.00)); //replace 444


$darkheader =  addcolordiff($headercolor,array(0.98,0.28,-24.71));
//the font here is bodybgcolor


///////////////////////////

$bodybgcolor ='#FFFFFF';    //FFFFFF white ,   yelloe
$bodyfontcolor = addcolordiff($headercolor,array(0.00,0.00,-100.00)); //replace 444


$tabbgcolor =  addcolordiff($bodybgcolor,array(0.00,0.00,-6.67)); //eee

$tabbordercolor =  addcolordiff($tabbgcolor,array(0.00,0.00,-7.06));//DFDFDF, I killed DCDCDC here









