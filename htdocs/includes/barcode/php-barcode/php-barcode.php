<?php
/*
 * PHP-Barcode 0.3pl1
 
 * PHP-Barcode generates
 *   - Barcode-Images using libgd2 (png, jpg, gif)
 *   - HTML-Images (using 1x1 pixel and html-table)
 *   - silly Text-Barcodes
 *
 * PHP-Barcode encodes using
 *   - a built-in EAN-13/ISBN Encoder
 *   - genbarcode (by Folke Ashberg), a command line
 *     barcode-encoder which uses GNU-Barcode
 *     genbarcode can encode EAN-13, EAN-8, UPC, ISBN, 39, 128(a,b,c),
 *     I25, 128RAW, CBR, MSI, PLS
 *     genbarcode is available at www.ashberg.de/bar 
 
 * (C) 2001,2002,2003,2004 by Folke Ashberg <folke@ashberg.de>
 
 * The newest version can be found at http://www.ashberg.de/bar
 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

 */


/* CONFIGURATION */

/* ******************************************************************** */
/*                          COLORS                                      */
/* ******************************************************************** */
$bar_color=Array(0,0,0);
$bg_color=Array(255,255,255);
$text_color=Array(0,0,0);


/* ******************************************************************** */
/*                          FONT FILE                                   */
/* ******************************************************************** */
/* location the the ttf-font */
/* the file arialbd.ttf isn't included! */

/* SAMPLE1 : 
 * use arialbd.ttf located in same directory like the script
 * which includes/requires php-barcode.php
 */
//$font_loc=dirname($_SERVER["PATH_TRANSLATED"])."/"."arialbd.ttf";

/* SAMPLE2 :
 * use font specified by full-path
 */
$font_loc = DOL_DOCUMENT_ROOT.'/includes/barcode/php-barcode/fonts/AerialBd.ttf';

/* Automatic-Detection of Font if running Windows
 * kick this lines if you don't need them! */
if (isset($_ENV['windir']) && file_exists($_ENV['windir'])){
    $font_loc=$_ENV['windir']."\Fonts\arialbd.ttf";
}

/* ******************************************************************** */
/*                          GENBARCODE                                  */
/* ******************************************************************** */
/* location of 'genbarcode'
 * leave blank if you don't have them :(
 * genbarcode is needed to render encodings other than EAN-12/EAN-13/ISBN
 */
if (isset($_ENV['windir']) && file_exists($_ENV['windir']))
{
	$genbarcode_loc = DOL_DOCUMENT_ROOT.'/includes/barcode/php-barcode/genbarcode/genbarcode.exe';
}
else
{
	//$genbarcode_loc = "/usr/local/bin/genbarcode";
	$genbarcode_loc = $conf->global->GENBARCODE_LOCATION;
}
//dol_syslog("genbarcode_loc=".$genbarcode_loc);


/* CONFIGURATION ENDS HERE */

require("encode_bars.php"); /* build-in encoders */

/* 
 * barcode_outimage(text, bars [, scale [, mode [, total_y [, space ]]]] )
 *
 *  Outputs an image using libgd
 *
 *    text   : the text-line (<position>:<font-size>:<character> ...)
 *    bars   : where to place the bars  (<space-width><bar-width><space-width><bar-width>...)
 *    scale  : scale factor ( 1 < scale < unlimited (scale 50 will produce
 *                                                   5400x300 pixels when
 *                                                   using EAN-13!!!))
 *    mode   : png,gif,jpg, depending on libgd ! (default='png')
 *    total_y: the total height of the image ( default: scale * 60 )
 *    space  : space
 *             default:
 *		$space[top]   = 2 * $scale;
 *		$space[bottom]= 2 * $scale;
 *		$space[left]  = 2 * $scale;
 *		$space[right] = 2 * $scale;
 */


function barcode_outimage($text, $bars, $scale = 1, $mode = "png",
	    $total_y = 0, $space = ''){
    global $bar_color, $bg_color, $text_color;
    global $font_loc;
    /* set defaults */
    if ($scale<1) $scale=2;
    $total_y=(int)($total_y);
    if ($total_y<1) $total_y=(int)$scale * 60;
    if (!$space)
      $space=array('top'=>2*$scale,'bottom'=>2*$scale,'left'=>2*$scale,'right'=>2*$scale);
    
    /* count total width */
    $xpos=0;
    $width=true;
    for ($i=0;$i<strlen($bars);$i++){
	$val=strtolower($bars[$i]);
	if ($width){
	    $xpos+=$val*$scale;
	    $width=false;
	    continue;
	}
	if (ereg("[a-z]", $val)){
	    /* tall bar */
	    $val=ord($val)-ord('a')+1;
	} 
	$xpos+=$val*$scale;
	$width=true;
    }

    /* allocate the image */
    $total_x=( $xpos )+$space['right']+$space['right'];
    $xpos=$space['left'];
    if (!function_exists("imagecreate")){
	print "You don't have the gd2 extension enabled<BR>\n";
	print "<BR>\n";
	print "<BR>\n";
	print "Short HOWTO<BR>\n";
	print "<BR>\n";
	print "Debian: # apt-get install php4-gd2<BR>\n";
	print "<BR>\n";
	print "SuSE: ask YaST<BR>\n";
	print "<BR>\n";
	print "OpenBSD: # pkg_add /path/php4-gd-4.X.X.tgz (read output, you have to enable it)<BR>\n";
	print "<BR>\n";
	print "Windows: Download the PHP zip package from <A href=\"http://www.php.net/downloads.php\">php.net</A>, NOT the windows-installer, unzip the php_gd2.dll to C:\PHP (this is the default install dir) and uncomment 'extension=php_gd2.dll' in C:\WINNT\php.ini (or where ever your os is installed)<BR>\n";
	print "<BR>\n";
	print "<BR>\n";
	print "The author of php-barcode will give not support on this topic!<BR>\n";
	print "<BR>\n";
	print "<BR>\n";
	print "<A HREF=\"http://www.ashberg.de/bar/\">Folke Ashberg's OpenSource PHP-Barcode</A><BR>\n";
	return "";
    }
    $im=imagecreate($total_x, $total_y);
    /* create two images */
    $col_bg=ImageColorAllocate($im,$bg_color[0],$bg_color[1],$bg_color[2]);
    $col_bar=ImageColorAllocate($im,$bar_color[0],$bar_color[1],$bar_color[2]);
    $col_text=ImageColorAllocate($im,$text_color[0],$text_color[1],$text_color[2]);
    $height=round($total_y-($scale*10));
    $height2=round($total_y-$space['bottom']);


    /* paint the bars */
    $width=true;
    for ($i=0;$i<strlen($bars);$i++){
	$val=strtolower($bars[$i]);
	if ($width){
	    $xpos+=$val*$scale;
	    $width=false;
	    continue;
	}
	if (ereg("[a-z]", $val)){
	    /* tall bar */
	    $val=ord($val)-ord('a')+1;
	    $h=$height2;
	} else $h=$height;
	imagefilledrectangle($im, $xpos, $space['top'], $xpos+($val*$scale)-1, $h, $col_bar);
	$xpos+=$val*$scale;
	$width=true;
    }
    /* write out the text */
    global $_SERVER;
    $chars=explode(" ", $text);
    reset($chars);
    while (list($n, $v)=each($chars)){
	if (trim($v)){
	    $inf=explode(":", $v);
	    $fontsize=$scale*($inf[1]/1.8);
	    $fontheight=$total_y-($fontsize/2.7)+2;
	    @imagettftext($im, $fontsize, 0, $space['left']+($scale*$inf[0])+2,
	    $fontheight, $col_text, $font_loc, $inf[2]);
	}
    }

	// DOLCHANGE LDR
    global $filebarcode;
    
    /* output the image */
    $mode=strtolower($mode);
    if ($mode=='jpg' || $mode=='jpeg'){
	header("Content-Type: image/jpeg; name=\"barcode.jpg\"");
	imagejpeg($im);
    } else if ($mode=='gif'){
	header("Content-Type: image/gif; name=\"barcode.gif\"");
	imagegif($im);

	// Begin DOLCHANGE LDR
    } else if (! empty($filebarcode))
    {
		imagepng($im,$filebarcode);
	// End DOLCHANGE LDR
		
    } else {
	header("Content-Type: image/png; name=\"barcode.png\"");
	imagepng($im);
    }
}

/*
 * barcode_outtext(code, bars)
 *
 *  Returns (!) a barcode as plain-text
 *  ATTENTION: this is very silly!
 *
 *    text   : the text-line (<position>:<font-size>:<character> ...)
 *    bars   : where to place the bars  (<space-width><bar-width><space-width><bar-width>...)
 */

function barcode_outtext($code,$bars){
    $width=true;
    $xpos=$heigh2=0;
    $bar_line="";
    for ($i=0;$i<strlen($bars);$i++){
	$val=strtolower($bars[$i]);
	if ($width){
	    $xpos+=$val;
	    $width=false;
	    for ($a=0;$a<$val;$a++) $bar_line.="-";
	    continue;
	}
	if (ereg("[a-z]", $val)){
	    $val=ord($val)-ord('a')+1;
	    $h=$heigh2;
	    for ($a=0;$a<$val;$a++) $bar_line.="I";
	} else for ($a=0;$a<$val;$a++) $bar_line.="#";
	$xpos+=$val;
	$width=true;
    }
    return $bar_line;
}

/* 
 * barcode_outhtml(text, bars [, scale [, total_y [, space ]]] )
 *
 *  returns(!) HTML-Code for barcode-image using html-code (using a table and with black.png and white.png)
 *
 *    text   : the text-line (<position>:<font-size>:<character> ...)
 *    bars   : where to place the bars  (<space-width><bar-width><space-width><bar-width>...)
 *    scale  : scale factor ( 1 < scale < unlimited (scale 50 will produce
 *                                                   5400x300 pixels when
 *                                                   using EAN-13!!!))
 *    total_y: the total height of the image ( default: scale * 60 )
 *    space  : space
 *             default:
 *		$space[top]   = 2 * $scale;
 *		$space[bottom]= 2 * $scale;
 *		$space[left]  = 2 * $scale;
 *		$space[right] = 2 * $scale;
 */



function barcode_outhtml($code, $bars, $scale = 1, $total_y = 0, $space = ''){
    /* set defaults */
    $total_y=(int)($total_y);
    if ($scale<1) $scale=2;
    if ($total_y<1) $total_y=(int)$scale * 60;
    if (!$space)
      $space=array('top'=>2*$scale,'bottom'=>2*$scale,'left'=>2*$scale,'right'=>2*$scale);


    /* generate html-code */
    $height=round($total_y-($scale*10));
    $height2=round($total_y)-$space['bottom'];
    $out=
      '<Table border=0 cellspacing=0 cellpadding=0 bgcolor="white">'."\n".
      '<TR><TD><img src=white.png height="'.$space['top'].'" width=1></TD></TR>'."\n".
      '<TR><TD>'."\n".
      '<IMG src=white.png height="'.$height2.'" width="'.$space['left'].'">';
    
    $width=true;
    for ($i=0;$i<strlen($bars);$i++){
	$val=strtolower($bars[$i]);
	if ($width){
	    $w=$val*$scale;
	    if ($w>0) $out.="<IMG src=white.png height=\"$total_y\" width=\"$w\" align=top>";
	    $width=false;
	    continue;
	}
	if (ereg("[a-z]", $val)){
	    //hoher strich
	    $val=ord($val)-ord('a')+1;
	    $h=$height2;
	}else $h=$height;
	$w=$val*$scale;
	if ($w>0) $out.='<IMG src="black.png" height="'.$h.'" width="'.$w.'" align=top>';
	$width=true;
    }
    $out.=
      '<IMG src=white.png height="'.$height2.'" width=".'.$space['right'].'">'.
      '</TD></TR>'."\n".
      '<TR><TD><img src="white.png" height="'.$space['bottom'].'" width="1"></TD></TR>'."\n".
      '</TABLE>'."\n";
    //for ($i=0;$i<strlen($bars);$i+=2) print $line[$i]."<B>".$line[$i+1]."</B>&nbsp;";
    return $out;
}


/* barcode_encode_genbarcode(code, encoding)
 *   encodes $code with $encoding using genbarcode
 *
 *   return:
 *    array[encoding] : the encoding which has been used
 *    array[bars]     : the bars
 *    array[text]     : text-positioning info
 */
function barcode_encode_genbarcode($code,$encoding){
    global $genbarcode_loc;
    /* delete EAN-13 checksum */
    if (eregi("^ean$", $encoding) && strlen($code)==13) $code=substr($code,0,12);
    if (!$encoding) $encoding="ANY";
    $encoding=ereg_replace("[|\\]", "_", $encoding);
    $code=ereg_replace("[|\\]", "_", $code);
    $cmd=$genbarcode_loc." \""
	.str_replace("\"", "\\\"",$code)."\" \""
	.str_replace("\"", "\\\"",strtoupper($encoding))."\"";
    //print "'$cmd'<BR>\n";
    $fp=popen($cmd, "r");
    if ($fp){
	$bars=fgets($fp, 1024);
	$text=fgets($fp, 1024);
	$encoding=fgets($fp, 1024);
	pclose($fp);
    } else return false;
    $ret=array(
		"encoding" => trim($encoding),
		"bars" => trim($bars),
		"text" => trim($text)
	      );
    if (!$ret['encoding']) return false;
    if (!$ret['bars']) return false;
    if (!$ret['text']) return false;
    return $ret;
}

/* barcode_encode(code, encoding)
 *   encodes $code with $encoding using genbarcode OR built-in encoder
 *   if you don't have genbarcode only EAN-13/ISBN is possible
 *
 * You can use the following encodings (when you have genbarcode):
 *   ANY    choose best-fit (default)
 *   EAN    8 or 13 EAN-Code
 *   UPC    12-digit EAN 
 *   ISBN   isbn numbers (still EAN-13) 
 *   39     code 39 
 *   128    code 128 (a,b,c: autoselection) 
 *   128C   code 128 (compact form for digits)
 *   128B   code 128, full printable ascii 
 *   I25    interleaved 2 of 5 (only digits) 
 *   128RAW Raw code 128 (by Leonid A. Broukhis)
 *   CBR    Codabar (by Leonid A. Broukhis) 
 *   MSI    MSI (by Leonid A. Broukhis) 
 *   PLS    Plessey (by Leonid A. Broukhis)
 * 
 *   return:
 *    array[encoding] : the encoding which has been used
 *    array[bars]     : the bars
 *    array[text]     : text-positioning info
 */
function barcode_encode($code,$encoding){
    global $genbarcode_loc;
    if (
		((eregi("^ean$", $encoding)
		 && ( strlen($code)==12 || strlen($code)==13)))
		 
		|| (($encoding) && (eregi("^isbn$", $encoding))
		 && (( strlen($code)==9 || strlen($code)==10) ||
		 (((ereg("^978", $code) && strlen($code)==12) ||
		  (strlen($code)==13)))))

		|| (( !isset($encoding) || !$encoding || (eregi("^ANY$", $encoding) ))
		 && (ereg("^[0-9]{12,13}$", $code)))
	      
		){
	/* use built-in EAN-Encoder */
	$bars=barcode_encode_ean($code, $encoding);
    } else if (file_exists($genbarcode_loc)){
	/* use genbarcode */
	$bars=barcode_encode_genbarcode($code, $encoding);
    } else {
	print "php-barcode needs an external programm for encodings other then EAN/ISBN<BR>\n";
	print "<UL>\n";
	print "<LI>download gnu-barcode from <A href=\"http://www.gnu.org/software/barcode/\">www.gnu.org/software/barcode/</A>\n";
	print "<LI>compile and install them\n";
	print "<LI>download genbarcode from <A href=\"http://www.ashberg.de/bar/\">www.ashberg.de/bar/</A>\n";
	print "<LI>compile and install them\n";
	print "<LI>specify path the genbarcode in php-barcode.php\n";
	print "</UL>\n";
	print "<BR>\n";
	print "<A HREF=\"http://www.ashberg.de/bar/\">Folke Ashberg's OpenSource PHP-Barcode</A><BR>\n";
	return false;
    }
    return $bars;
}

/* barcode_print(code [, encoding [, scale [, mode ]]] );
 *
 *  encodes and prints a barcode
 *
 *   return:
 *    array[encoding] : the encoding which has been used
 *    array[bars]     : the bars
 *    array[text]     : text-positioning info
 */
function barcode_print($code, $encoding="ANY", $scale = 2 ,$mode = "png" ){
	dol_syslog("$code $encoding $scale $mode");
	$bars=barcode_encode($code,$encoding);
    if (!$bars) return;
    if (!$mode) $mode="png";
    if (eregi($mode,"^(text|txt|plain)$")) print barcode_outtext($bars['text'],$bars['bars']);
    elseif (eregi($mode,"^(html|htm)$")) print barcode_outhtml($bars['text'],$bars['bars'], $scale,0, 0);
    else barcode_outimage($bars['text'],$bars['bars'],$scale, $mode);
    return $bars;
}

?>
