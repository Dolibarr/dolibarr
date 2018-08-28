<?php
/* Copyright (C) 2004-2016 Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004-2010 Folke Ashberg: Some lines of code were inspired from work
 *                         of Folke Ashberg into PHP-Barcode 0.3pl2, available as GPL
 *                         source code at http://www.ashberg.de/bar.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/core/lib/barcode.lib.php
 *	\brief      Set of functions used for barcode generation
 *	\ingroup    core
 */

/* ******************************************************************** */
/*                          COLORS                                      */
/* ******************************************************************** */
$bar_color=array(0,0,0);
$bg_color=array(255,255,255);
$text_color=array(0,0,0);


/* ******************************************************************** */
/*                          FONT FILE                                   */
/* ******************************************************************** */
if (defined('DOL_DEFAULT_TTF_BOLD')) $font_loc=constant('DOL_DEFAULT_TTF_BOLD');
// Automatic-Detection of Font if running Windows
// @CHANGE LDR
if (isset($_SERVER['WINDIR']) && @file_exists($_SERVER['WINDIR'])) $font_loc=$_SERVER['WINDIR'].'\Fonts\arialbd.ttf';
if (empty($font_loc)) die('DOL_DEFAULT_TTF_BOLD must de defined with full path to a TTF font.');


/* ******************************************************************** */
/*                          GENBARCODE                                  */
/* ******************************************************************** */
/* location of 'genbarcode'
 * leave blank if you don't have them :(
* genbarcode is needed to render encodings other than EAN-12/EAN-13/ISBN
*/

if (defined('PHP-BARCODE_PATH_COMMAND')) $genbarcode_loc=constant('PHP-BARCODE_PATH_COMMAND');
else $genbarcode_loc = $conf->global->GENBARCODE_LOCATION;




/**
 * Print barcode
 *
 * @param	string	       $code		Code
 * @param	string	       $encoding	Encoding
 * @param	integer	       $scale		Scale
 * @param	string	       $mode		'png' or 'jpg' ...
 * @return	array|string   $bars		array('encoding': the encoding which has been used, 'bars': the bars, 'text': text-positioning info) or string with error message
 */
function barcode_print($code, $encoding="ANY", $scale = 2 ,$mode = "png")
{
    dol_syslog("barcode.lib.php::barcode_print $code $encoding $scale $mode");

    $bars=barcode_encode($code,$encoding);
    if (! $bars || ! empty($bars['error']))
    {
        // Return error message instead of array
        if (empty($bars['error'])) $error='Bad Value '.$code.' for encoding '.$encoding;
        else $error=$bars['error'];
        dol_syslog('barcode.lib.php::barcode_print '.$error, LOG_ERR);
        return $error;
    }
    if (! $mode) $mode="png";
    //if (preg_match("/^(text|txt|plain)$/i",$mode)) print barcode_outtext($bars['text'],$bars['bars']);
    //elseif (preg_match("/^(html|htm)$/i",$mode)) print barcode_outhtml($bars['text'],$bars['bars'], $scale,0, 0);
    //else
    barcode_outimage($bars['text'], $bars['bars'], $scale, $mode);
    return $bars;
}

/**
 * Encodes $code with $encoding using genbarcode OR built-in encoder if you don't have genbarcode only EAN-13/ISBN is possible
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
 * @param	string	$code		Code
 * @param	string	$encoding	Encoding
 * @return	array				array('encoding': the encoding which has been used, 'bars': the bars, 'text': text-positioning info)
 */
function barcode_encode($code,$encoding)
{
    global $genbarcode_loc;

    if (
    (preg_match("/^ean$/i", $encoding))

    || (($encoding) && (preg_match("/^isbn$/i", $encoding))
    && (( strlen($code)==9 || strlen($code)==10) ||
    (((preg_match("/^978/", $code) && strlen($code)==12) ||
    (strlen($code)==13)))))

    || (( !isset($encoding) || !$encoding || (preg_match("/^ANY$/i", $encoding) ))
    && (preg_match("/^[0-9]{12,13}$/", $code)))
    )
    {
        /* use built-in EAN-Encoder */
        dol_syslog("barcode.lib.php::barcode_encode Use barcode_encode_ean");
        $bars=barcode_encode_ean($code, $encoding);
    }
    else if (file_exists($genbarcode_loc))	// For example C39
    {
        /* use genbarcode */
        dol_syslog("barcode.lib.php::barcode_encode Use genbarcode ".$genbarcode_loc." code=".$code." encoding=".$encoding);
        $bars=barcode_encode_genbarcode($code, $encoding);
    }
    else
    {
        print "barcode_encode needs an external programm for encodings other then EAN/ISBN (code=".$code.", encoding=".$encoding.")<BR>\n";
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
 * Calculate EAN sum
 *
 * @param	string	$ean	EAN to encode
 * @return	integer			Sum
 */
function barcode_gen_ean_sum($ean)
{
    $even=true; $esum=0; $osum=0;
    $ln=strlen($ean)-1;
    for ($i=$ln; $i>=0; $i--)
    {
        if ($even) $esum+=$ean[$i];	else $osum+=$ean[$i];
        $even=!$even;
    }
    return (10-((3*$esum+$osum)%10))%10;
}

/**
 * Encode EAN
 *
 * @param	string	$ean		Code
 * @param	string	$encoding	Encoding
 * @return	array				array('encoding': the encoding which has been used, 'bars': the bars, 'text': text-positioning info, 'error': error message if error)
 */
function barcode_encode_ean($ean, $encoding = "EAN-13")
{
    $digits=array(3211,2221,2122,1411,1132,1231,1114,1312,1213,3112);
    $mirror=array("000000","001011","001101","001110","010011","011001","011100","010101","010110","011010");
    $guards=array("9a1a","1a1a1","a1a");

    $ean=trim($ean);
    if (preg_match("/[^0-9]/i",$ean))
    {
        return array("error"=>"Invalid encoding/code. encoding=".$encoding." code=".$ean." (not a numeric)", "text"=>"Invalid encoding/code. encoding=".$encoding." code=".$ean." (not a numeric)");
    }
    $encoding=strtoupper($encoding);
    if ($encoding=="ISBN")
    {
        if (!preg_match("/^978/", $ean)) $ean="978".$ean;
    }
    if (preg_match("/^978/", $ean)) $encoding="ISBN";
    if (strlen($ean)<12 || strlen($ean)>13)
    {
        return array("error"=>"Invalid encoding/code. encoding=".$encoding." code=".$ean." (must have 12/13 numbers)", "text"=>"Invalid encoding/code. encoding=".$encoding." code=".$ean." (must have 12/13 numbers)");
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
        "error" => '',
        "encoding" => $encoding,
		"bars" => $line,
		"text" => $text
    );
}

/**
 * Encode result of genbarcode command
 *
 * @param	string	$code		Code
 * @param	string	$encoding	Encoding
 * @return	array				array('encoding': the encoding which has been used, 'bars': the bars, 'text': text-positioning info)
 */
function barcode_encode_genbarcode($code,$encoding)
{
    global $genbarcode_loc;

    // Clean parameters
    if (preg_match("/^ean$/i", $encoding) && strlen($code)==13) $code=substr($code,0,12);
    if (!$encoding) $encoding="ANY";
    $encoding=preg_replace("/[\\\|]/", "_", $encoding);
    $code=preg_replace("/[\\\|]/", "_", $code);

    $command=escapeshellarg($genbarcode_loc);
    //$paramclear=" \"".str_replace("\"", "\\\"",$code)."\" \"".str_replace("\"", "\\\"",strtoupper($encoding))."\"";
    $paramclear=" ".escapeshellarg($code)." ".escapeshellarg(strtoupper($encoding));

    $fullcommandclear=$command." ".$paramclear." 2>&1";
    //print $fullcommandclear."<br>\n";exit;

    dol_syslog("Run command ".$fullcommandclear);
    $fp=popen($fullcommandclear, "r");
    if ($fp)
    {
        $bars=fgets($fp, 1024);
        $text=fgets($fp, 1024);
        $encoding=fgets($fp, 1024);
        pclose($fp);
    }
    else
    {
        dol_syslog("barcode.lib.php::barcode_encode_genbarcode failed to run popen ".$fullcommandclear, LOG_ERR);
        return false;
    }
    //var_dump($bars);
    $ret=array(
		"bars" => trim($bars),
		"text" => trim($text),
		"encoding" => trim($encoding),
    	"error" => ""
    );
    //var_dump($ret);
    if (preg_match('/permission denied/i',$ret['bars']))
    {
    	$ret['error']=$ret['bars']; $ret['bars']='';
    	return $ret;
    }
    if (!$ret['bars']) return false;
    if (!$ret['text']) return false;
    if (!$ret['encoding']) return false;
    return $ret;
}

/**
 * Output image onto standard output, or onto disk if global filebarcode is defined
 *
 * @param	string	$text		the text-line (<position>:<font-size>:<character> ...)
 * @param	string	$bars   	where to place the bars  (<space-width><bar-width><space-width><bar-width>...)
 * @param	int		$scale		scale factor ( 1 < scale < unlimited (scale 50 will produce 5400x300 pixels when using EAN-13!!!))
 * @param	string	$mode   	png,gif,jpg (default='png')
 * @param	int		$total_y	the total height of the image ( default: scale * 60 )
 * @param	array	$space		default:  $space[top]   = 2 * $scale; $space[bottom]= 2 * $scale;  $space[left]  = 2 * $scale;  $space[right] = 2 * $scale;
 * @return	string|null
 */
function barcode_outimage($text, $bars, $scale = 1, $mode = "png", $total_y = 0, $space = '')
{
    global $bar_color, $bg_color, $text_color;
    global $font_loc, $filebarcode;

    //print "$text, $bars, $scale, $mode, $total_y, $space, $font_loc, $filebarcode<br>";
    //var_dump($text);
    //var_dump($bars);
    //var_dump($font_loc);

    /* set defaults */
    if ($scale<1) $scale=2;
    $total_y=(int) $total_y;
    if ($total_y<1) $total_y=(int) $scale * 60;
    if (!$space)
    $space=array('top'=>2*$scale,'bottom'=>2*$scale,'left'=>2*$scale,'right'=>2*$scale);

    /* count total width */
    $xpos=0;
    $width=true;
    $ln=strlen($bars);
    for ($i=0; $i<$ln; $i++)
    {
        $val=strtolower($bars[$i]);
        if ($width)
        {
            $xpos+=$val*$scale;
            $width=false;
            continue;
        }
        if (preg_match("/[a-z]/", $val))
        {
            /* tall bar */
            $val=ord($val)-ord('a')+1;
        }
        $xpos+=$val*$scale;
        $width=true;
    }

    /* allocate the image */
    $total_x=( $xpos )+$space['right']+$space['right'];
    $xpos=$space['left'];
    if (! function_exists("imagecreate"))
    {
        print "You don't have the gd2 extension enabled<br>\n";
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
    $ln=strlen($bars);
    for ($i=0; $i<$ln; $i++)
    {
        $val=strtolower($bars[$i]);
        if ($width)
        {
            $xpos+=$val*$scale;
            $width=false;
            continue;
        }
        if (preg_match("/[a-z]/", $val))
        {
            /* tall bar */
            $val=ord($val)-ord('a')+1;
            $h=$height2;
        } else $h=$height;
        imagefilledrectangle($im, $xpos, $space['top'], $xpos+($val*$scale)-1, $h, $col_bar);
        $xpos+=$val*$scale;
        $width=true;
    }

    $chars=explode(" ", $text);
    reset($chars);
    while (list($n, $v)=each($chars))
    {
        if (trim($v))
        {
            $inf=explode(":", $v);
            $fontsize=$scale*($inf[1]/1.8);
            $fontheight=$total_y-($fontsize/2.7)+2;
            imagettftext($im, $fontsize, 0, $space['left']+($scale*$inf[0])+2, $fontheight, $col_text, $font_loc, $inf[2]);
        }
    }

    /* output the image */
    $mode=strtolower($mode);
    if ($mode=='jpg' || $mode=='jpeg')
    {
        header("Content-Type: image/jpeg; name=\"barcode.jpg\"");
        imagejpeg($im);
    }
    else if ($mode=='gif')
    {
        header("Content-Type: image/gif; name=\"barcode.gif\"");
        imagegif($im);
    }
    else if (! empty($filebarcode))    // To wxrite into  afile onto disk
    {
        imagepng($im,$filebarcode);
    }
    else
    {
        header("Content-Type: image/png; name=\"barcode.png\"");
        imagepng($im);
    }
}

