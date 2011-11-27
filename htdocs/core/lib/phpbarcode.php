<?php
/* Copyright (C) 2004-2011          Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2004-2010			Folke Ashberg: Some lines of code were inspired from work
 *                                  of Folke Ashberg into PHP-Barcode 0.3pl2, available as GPL
 *                                  source code at http://www.ashberg.de/bar.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
if (defined('DOL_DEFAULT_TTF_BOLD')) $font_loc=constant('DOL_DEFAULT_TTF_BOLD');
// Automatic-Detection of Font if running Windows
// DOL_CHANGE LDR
if (isset($_SERVER['WINDIR']) && @file_exists($_SERVER['WINDIR'])) $font_loc=$_SERVER['WINDIR'].'\Fonts\arialbd.ttf';
if (empty($font_loc)) die('DOL_DEFAULT_TTF_BOLD must de defined with full path to a TTF font.');


/* ******************************************************************** */
/*                          GENBARCODE                                  */
/* ******************************************************************** */
/* location of 'genbarcode'
 * leave blank if you don't have them :(
* genbarcode is needed to render encodings other than EAN-12/EAN-13/ISBN
*/

// DOL_CHANGE LDR
if (defined('PHP-BARCODE_PATH_COMMAND')) $genbarcode_loc=constant('PHP-BARCODE_PATH_COMMAND');
else $genbarcode_loc = $conf->global->GENBARCODE_LOCATION;
//dol_syslog("genbarcode_loc=".$genbarcode_loc." - env_windows=".$_SERVER['WINDIR']);


/* CONFIGURATION ENDS HERE */


/**
 * Built-In Encoders
 * Part of PHP-Barcode 0.3pl1
 *
 * (C) 2001,2002,2003,2004 by Folke Ashberg <folke@ashberg.de>
 *
 * The newest version can be found at http://www.ashberg.de/bar
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
function barcode_gen_ean_sum($ean)
{
    $even=true; $esum=0; $osum=0;
    for ($i=strlen($ean)-1;$i>=0;$i--)
    {
        if ($even) $esum+=$ean[$i];	else $osum+=$ean[$i];
        $even=!$even;
    }
    return (10-((3*$esum+$osum)%10))%10;
}

/**
 * barcode_encode_ean(code [, encoding])
 *   encodes $ean with EAN-13 using builtin functions
 *
 *   return:
 *    array[encoding] : the encoding which has been used (EAN-13)
 *    array[bars]     : the bars
 *    array[text]     : text-positioning info
 */
function barcode_encode_ean($ean, $encoding = "EAN-13")
{
    $digits=array(3211,2221,2122,1411,1132,1231,1114,1312,1213,3112);
    $mirror=array("000000","001011","001101","001110","010011","011001","011100","010101","010110","011010");
    $guards=array("9a1a","1a1a1","a1a");

    $ean=trim($ean);
    if (preg_match("/[^0-9]/i",$ean))
    {
        return array("text"=>"Invalid EAN-Code");
    }
    $encoding=strtoupper($encoding);
    if ($encoding=="ISBN")
    {
        if (!preg_match("/^978/", $ean)) $ean="978".$ean;
    }
    if (preg_match("/^978/", $ean)) $encoding="ISBN";
    if (strlen($ean)<12 || strlen($ean)>13)
    {
        return array("text"=>"Invalid $encoding Code (must have 12/13 numbers)");
    }

    $ean=substr($ean,0,12);
    $eansum=barcode_gen_ean_sum($ean);
    $ean.=$eansum;
    $line=$guards[0];
    for ($i=1;$i<13;$i++)
    {
        $str=$digits[$ean[$i]];
        if ($i<7 && $mirror[$ean[0]][$i-1]==1) $line.=strrev($str); else $line.=$str;
        if ($i==6) $line.=$guards[1];
    }
    $line.=$guards[2];

    /* create text */
    $pos=0;
    $text="";
    for ($a=0;$a<13;$a++)
    {
        if ($a>0) $text.=" ";
        $text.="$pos:12:{$ean[$a]}";
        if ($a==0) $pos+=12;
        else if ($a==6) $pos+=12;
        else $pos+=7;
    }

    return array(
		"encoding" => $encoding,
		"bars" => $line,
		"text" => $text
    );
}


/**
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
function barcode_outimage($text, $bars, $scale = 1, $mode = "png", $total_y = 0, $space = '')
{
    global $bar_color, $bg_color, $text_color;
    global $font_loc;

    //var_dump($text);
    //var_dump($bars);
    //var_dump($font_loc);

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
        if (preg_match("/[a-z]/", $val)){
            /* tall bar */
            $val=ord($val)-ord('a')+1;
        }
        $xpos+=$val*$scale;
        $width=true;
    }

    /* allocate the image */
    $total_x=( $xpos )+$space['right']+$space['right'];
    $xpos=$space['left'];
    if (!function_exists("imagecreate"))
    {
        print "You don't have the gd2 extension enabled<BR>\n";
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
        if (preg_match("/[a-z]/", $val)){
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


/**
 * barcode_encode_genbarcode(code, encoding)
 *   encodes $code with $encoding using genbarcode
 *
 *   return:
 *    array[encoding] : the encoding which has been used
 *    array[bars]     : the bars
 *    array[text]     : text-positioning info
 */
function barcode_encode_genbarcode($code,$encoding)
{
    global $genbarcode_loc;
    /* delete EAN-13 checksum */
    if (preg_match("/^ean$/i", $encoding) && strlen($code)==13) $code=substr($code,0,12);
    if (!$encoding) $encoding="ANY";
    $encoding=preg_replace("/[\\\|]/", "_", $encoding);
    $code=preg_replace("/[\\\|]/", "_", $code);
    $cmd=$genbarcode_loc." \""
    .str_replace("\"", "\\\"",$code)."\" \""
    .str_replace("\"", "\\\"",strtoupper($encoding))."\" 2>&1";
    //print "'$cmd'<BR>\n";

    $fp=popen($cmd, "r");
    if ($fp)
    {
        $bars=fgets($fp, 1024);
        $text=fgets($fp, 1024);
        $encoding=fgets($fp, 1024);
        pclose($fp);
    }
    else
    {
        dol_syslog("phpbarcode::barcode_encode_genbarcode failed to run popen ".$cmd, LOG_ERR);
        return false;
    }
    //var_dump($bars);
    $ret=array(
		"encoding" => trim($encoding),
		"bars" => trim($bars),
		"text" => trim($text)
    );
    //var_dump($ret);
    if (!$ret['encoding']) return false;
    if (!$ret['bars']) return false;
    if (!$ret['text']) return false;
    return $ret;
}

/**
 * barcode_encode(code, encoding)
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
function barcode_encode($code,$encoding)
{
    global $genbarcode_loc;

    if (
    ((preg_match("/^ean$/i", $encoding)
    && ( strlen($code)==12 || strlen($code)==13)))

    || (($encoding) && (preg_match("/^isbn$/i", $encoding))
    && (( strlen($code)==9 || strlen($code)==10) ||
    (((preg_match("/^978/", $code) && strlen($code)==12) ||
    (strlen($code)==13)))))

    || (( !isset($encoding) || !$encoding || (preg_match("/^ANY$/i", $encoding) ))
    && (preg_match("/^[0-9]{12,13}$/", $code)))
    )
    {
        /* use built-in EAN-Encoder */
        dol_syslog("phpbarcode.php::barcode_encode Use barcode_encode_ean");
        $bars=barcode_encode_ean($code, $encoding);
    }
    else if (file_exists($genbarcode_loc))
    {
        /* use genbarcode */
        dol_syslog("phpbarcode.php::barcode_encode Use genbarcode ".$genbarcode_loc." code=".$code." encoding=".$encoding);
        $bars=barcode_encode_genbarcode($code, $encoding);
    }
    else
    {
        print "barcode_encode needs an external programm for encodings other then EAN/ISBN<BR>\n";
        print "<UL>\n";
        print "<LI>download gnu-barcode from <A href=\"http://www.gnu.org/software/barcode/\">www.gnu.org/software/barcode/</A>\n";
        print "<LI>compile and install them\n";
        print "<LI>download genbarcode from <A href=\"http://www.ashberg.de/bar/\">www.ashberg.de/bar/</A>\n";
        print "<LI>compile and install them\n";
        print "<LI>specify path the genbarcode in barcode module setup\n";
        print "</UL>\n";
        print "<BR>\n";
        return false;
    }
    return $bars;
}

/**
 * barcode_print(code [, encoding [, scale [, mode ]]] );
 *
 *  encodes and prints a barcode
 *
 *   return:
 *    array[encoding] : the encoding which has been used
 *    array[bars]     : the bars
 *    array[text]     : text-positioning info
 */
function barcode_print($code, $encoding="ANY", $scale = 2 ,$mode = "png")
{
    // DOLCHANGE LDR Add log
    dol_syslog("phpbarcode.php::barcode_print $code $encoding $scale $mode");

    $bars=barcode_encode($code,$encoding);
    if (!$bars)
    {
        // DOLCHANGE LDR Return error message instead of array
        $error='Bad Value '.$code.' for encoding '.$encoding;
        dol_syslog('phpbarcode.php::barcode_print '.$error, LOG_ERR);
        return $error;
    }
    if (!$mode) $mode="png";
    //if (preg_match("/^(text|txt|plain)$/i",$mode)) print barcode_outtext($bars['text'],$bars['bars']);
    //elseif (preg_match("/^(html|htm)$/i",$mode)) print barcode_outhtml($bars['text'],$bars['bars'], $scale,0, 0);
    //else
    barcode_outimage($bars['text'],$bars['bars'],$scale, $mode);
    return $bars;
}

?>
