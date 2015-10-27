<?php
/* 
  what is Hue, satuaration
  http://www.designingfortheweb.co.uk/part4/part4_chapter17.php
  paletton.com  to get color cmbo
  http://thesassway.com/advanced/how-to-programtically-go-from-one-color-to-another-in-sass
  color pallates 
  http://www.colorexplorer.com/colorlibraries.aspx
  
  downloaded this https://github.com/hasbridge/php-color
  
  HSV to RGB http://www.rapidtables.com/convert/color/hsv-to-rgb.htm
  http://www.rapidtables.com/convert/color/rgb-to-hsv.htm
  
  */
/**
 * Describe plugin here
 */

elgg_register_event_handler('init', 'system', 'entheme_init');

function entheme_init() {

}


/*
   function toHsvFloat($rgb)
    {
        
        
        $rgbMin = min($rgb);
        $rgbMax = max($rgb);
        
        $hsv = array(
            'hue'   => 0,
            'sat'   => 0,
            'val'   => $rgbMax
        );
        
        // If v is 0, color is black
        if ($hsv['val'] == 0) {
            return $hsv;
        }
        
        // Normalize RGB values to 1
        $rgb['red'] /= $hsv['val'];
        $rgb['green'] /= $hsv['val'];
        $rgb['blue'] /= $hsv['val'];
        $rgbMin = min($rgb);
        $rgbMax = max($rgb);
        
        // Calculate saturation
        $hsv['sat'] = $rgbMax - $rgbMin;
        if ($hsv['sat'] == 0) {
            $hsv['hue'] = 0;
            return $hsv;
        }
        
        // Normalize saturation to 1
        $rgb['red'] = ($rgb['red'] - $rgbMin) / ($rgbMax - $rgbMin);
        $rgb['green'] = ($rgb['green'] - $rgbMin) / ($rgbMax - $rgbMin);
        $rgb['blue'] = ($rgb['blue'] - $rgbMin) / ($rgbMax - $rgbMin);
        $rgbMin = min($rgb);
        $rgbMax = max($rgb);
        
        // Calculate hue
        if ($rgbMax == $rgb['red']) {
            $hsv['hue'] = 0.0 + 60 * ($rgb['green'] - $rgb['blue']);
            if ($hsv['hue'] < 0) {
                $hsv['hue'] += 360;
            }
        } else if ($rgbMax == $rgb['green']) {
            $hsv['hue'] = 120 + (60 * ($rgb['blue'] - $rgb['red']));
        } else {
            $hsv['hue'] = 240 + (60 * ($rgb['red'] - $rgb['green']));
        }
        
        return $hsv;
    }
    */
function rgb2hex($rgb) {
   $hex = "#";
   $hex .= str_pad(dechex($rgb[0]), 2, "0", STR_PAD_LEFT);
   $hex .= str_pad(dechex($rgb[1]), 2, "0", STR_PAD_LEFT);
   $hex .= str_pad(dechex($rgb[2]), 2, "0", STR_PAD_LEFT);

   return $hex; // returns the hex value including the number sign (#)
}

function hex2rgb($hex) {
   $hex = str_replace("#", "", $hex);

   if(strlen($hex) == 3) {
      $r = hexdec(substr($hex,0,1).substr($hex,0,1));
      $g = hexdec(substr($hex,1,1).substr($hex,1,1));
      $b = hexdec(substr($hex,2,1).substr($hex,2,1));
   } else {
      $r = hexdec(substr($hex,0,2));
      $g = hexdec(substr($hex,2,2));
      $b = hexdec(substr($hex,4,2));
   }
   $rgb = array($r,$g,$b);
   //return implode(",", $rgb); // returns the rgb values separated by commas
   return $rgb; // returns an array with the rgb values
}

function RGBtoHSV($RGB)    // RGB values:    0-255, 0-255, 0-255
{                                // HSV values:    0-360, 0-100, 0-100
    // Convert the RGB byte-values to percentages
    $R = ($RGB[0] / 255);
    $G = ($RGB[1] / 255);
    $B = ($RGB[2] / 255);

    // Calculate a few basic values, the maximum value of R,G,B, the
    //   minimum value, and the difference of the two (chroma).
    $maxRGB = max($R, $G, $B);
    $minRGB = min($R, $G, $B);
    $chroma = $maxRGB - $minRGB;

    // Value (also called Brightness) is the easiest component to calculate,
    //   and is simply the highest value among the R,G,B components.
    // We multiply by 100 to turn the decimal into a readable percent value.
    $computedV = 100 * $maxRGB;

    // Special case if hueless (equal parts RGB make black, white, or grays)
    // Note that Hue is technically undefined when chroma is zero, as
    //   attempting to calculate it would cause division by zero (see
    //   below), so most applications simply substitute a Hue of zero.
    // Saturation will always be zero in this case, see below for details.
    if ($chroma == 0)
        return array(0, 0, $computedV);

    // Saturation is also simple to compute, and is simply the chroma
    //   over the Value (or Brightness)
    // Again, multiplied by 100 to get a percentage.
    $computedS = 100 * ($chroma / $maxRGB);

    // Calculate Hue component
    // Hue is calculated on the "chromacity plane", which is represented
    //   as a 2D hexagon, divided into six 60-degree sectors. We calculate
    //   the bisecting angle as a value 0 <= x < 6, that represents which
    //   portion of which sector the line falls on.
    if ($R == $minRGB)
        $h = 3 - (($G - $B) / $chroma);
    elseif ($B == $minRGB)
        $h = 1 - (($R - $G) / $chroma);
    else // $G == $minRGB
        $h = 5 - (($B - $R) / $chroma);

    // After we have the sector position, we multiply it by the size of
    //   each sector's arc (60 degrees) to obtain the angle in degrees.
    $computedH = 60 * $h;

    return array($computedH, $computedS, $computedV);
}


function HSVtoRGB($HSV) {
         $iH = $HSV[0];
         $iS = $HSV[1];
         $iV = $HSV[2];
        if($iH < 0)   $iH = 0;   // Hue:
        if($iH > 360) $iH = 360; //   0-360
        if($iS < 0)   $iS = 0;   // Saturation:
        if($iS > 100) $iS = 100; //   0-100
        if($iV < 0)   $iV = 0;   // Lightness:
        if($iV > 100) $iV = 100; //   0-100
        $dS = $iS/100.0; // Saturation: 0.0-1.0
        $dV = $iV/100.0; // Lightness:  0.0-1.0
        $dC = $dV*$dS;   // Chroma:     0.0-1.0
        $dH = $iH/60.0;  // H-Prime:    0.0-6.0
        $dT = $dH;       // Temp variable
        while($dT >= 2.0) $dT -= 2.0; // php modulus does not work with float
        $dX = $dC*(1-abs($dT-1));     // as used in the Wikipedia link
        switch($dH) {
            case($dH >= 0.0 && $dH < 1.0):
                $dR = $dC; $dG = $dX; $dB = 0.0; break;
            case($dH >= 1.0 && $dH < 2.0):
                $dR = $dX; $dG = $dC; $dB = 0.0; break;
            case($dH >= 2.0 && $dH < 3.0):
                $dR = 0.0; $dG = $dC; $dB = $dX; break;
            case($dH >= 3.0 && $dH < 4.0):
                $dR = 0.0; $dG = $dX; $dB = $dC; break;
            case($dH >= 4.0 && $dH < 5.0):
                $dR = $dX; $dG = 0.0; $dB = $dC; break;
            case($dH >= 5.0 && $dH < 6.0):
                $dR = $dC; $dG = 0.0; $dB = $dX; break;
            default:
                $dR = 0.0; $dG = 0.0; $dB = 0.0; break;
        }
        $dM  = $dV - $dC;
        $dR += $dM; $dG += $dM; $dB += $dM;
        $dR *= 255; $dG *= 255; $dB *= 255;
       // echo  round($dR,2).";".round($dG,2).";".round($dB,2);
       return array(round($dR), round($dG), round($dB));
    }

function colordiff($hex1,$hex2) {
   $rgb1 = hex2rgb($hex1);
   $rgb2 = hex2rgb($hex2);
   
   //echo $rgb1[0]."/".$rgb1[1]."/".$rgb1[2]." - ".$rgb2[0]."/".$rgb2[1]."/".$rgb2[2]."<br>";
   $hsv1 = RGBtoHSV($rgb1);
   $hsv2 = RGBtoHSV($rgb2);
   //echo $hsv1[0]."/".$hsv1[1]."/".$hsv1[2]." - ".$hsv2[0]."/".$hsv2[1]."/".$hsv2[2]."<br>";
   $diffH = $hsv2[0] - $hsv1[0];
   $diffS =$hsv2[1] - $hsv1[1];
   $diffV = $hsv2[2] - $hsv1[2];
  // echo $diffH."/".$diffS."/".$diffV."<br>";
   
   
   return array(round($diffH,2),round( $diffS,2),round( $diffV,2));
}

function addcolordiff($color,$diff){
   // echo "<br>addcolordiff<br>".$diff[0]."/".$diff[1]."/".$diff[2];
    $rgb = hex2rgb($color);
   //  echo $color." = ".$rgb[0]."/".$rgb[1]."/".$rgb[2]." hm<br>";
    $hsv = RGBtoHSV($rgb);
    //echo "hsv = ".$hsv[0]."/".$hsv[1]."/".$hsv[2]." hm<br>";
    $hsv2[0] = floatval($hsv[0]) +floatval($diff[0]);
    $hsv2[1] = $hsv[1] +$diff[1];
    $hsv2[2] = $hsv[2] +$diff[2];
  //  echo "hsv2 = ".$hsv2[0]."/".$hsv2[1]."/".$hsv2[2]." <br>";
    $rgb2 = HSVtoRGB($hsv2);
   // echo "rgb2 = ".$rgb2[0]."/".$rgb2[1]."/".$rgb2[2]." hm<br>";
    $hex2 = rgb2hex($rgb2);
    return $hex2;
}