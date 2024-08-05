<?php
/* Copyright (C) 2004-2016 Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024		Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2004-2010 Folke Ashberg: Some lines of code were inspired from work
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/core/lib/barcode.lib.php
 *	\brief      Set of functions used for barcode generation (internal lib, also code 'phpbarcode')
 *	\ingroup    core
 */

/* ******************************************************************** */
/*                          COLORS                                      */
/* ******************************************************************** */
$bar_color = array(0, 0, 0);
$bg_color = array(255, 255, 255);
$text_color = array(0, 0, 0);


/* ******************************************************************** */
/*                          FONT FILE                                   */
/* ******************************************************************** */
if (defined('DOL_DEFAULT_TTF_BOLD')) {
	$font_loc = constant('DOL_DEFAULT_TTF_BOLD');
}
// Automatic-Detection of Font if running Windows
// @CHANGE LDR
if (isset($_SERVER['WINDIR']) && @file_exists($_SERVER['WINDIR'])) {
	$font_loc = $_SERVER['WINDIR'].'\Fonts\arialbd.ttf';
}
if (empty($font_loc)) {
	die('DOL_DEFAULT_TTF_BOLD must de defined with full path to a TTF font.');
}


/* ******************************************************************** */
/*                          GENBARCODE                                  */
/* ******************************************************************** */
/* location of 'genbarcode'
 * leave blank if you don't have them :(
* genbarcode is needed to render encodings other than EAN-12/EAN-13/ISBN
*/

if (defined('PHP-BARCODE_PATH_COMMAND')) {
	$genbarcode_loc = constant('PHP-BARCODE_PATH_COMMAND');
} else {
	$genbarcode_loc = '';
	if (getDolGlobalString('GENBARCODE_LOCATION')) {
		$genbarcode_loc = getDolGlobalString('GENBARCODE_LOCATION');
	}
}




/**
 * Print barcode
 *
 * @param	string	       	$code			Code
 * @param	string	       	$encoding		Encoding ('EAN13', 'ISBN', 'C128', 'UPC', 'CBR', 'QRCODE', 'DATAMATRIX', 'ANY'...)
 * @param	int<1,max>     	$scale			Scale
 * @param	string	       	$mode			'png', 'gif', 'jpg', 'jpeg' ...
 * @param	string			$filebarcode	Filename to store barcode image file if defined
 * @return	array{encoding:string,bars:string,text:string}|string   $bars		array('encoding': the encoding which has been used, 'bars': the bars, 'text': text-positioning info) or string with error message
 */
function barcode_print($code, $encoding = "ANY", $scale = 2, $mode = "png", $filebarcode = '')
{
	dol_syslog("barcode.lib.php::barcode_print $code $encoding $scale $mode");

	$bars = barcode_encode($code, $encoding);
	if (!$bars || !empty($bars['error'])) {
		// Return error message instead of array
		if (empty($bars['error'])) {
			$error = 'Bad Value '.$code.' for encoding '.$encoding;
		} else {
			$error = $bars['error'];
		}
		dol_syslog('barcode.lib.php::barcode_print '.$error, LOG_ERR);
		return $error;
	}
	if (!$mode) {
		$mode = "png";
	}
	//if (preg_match("/^(text|txt|plain)$/i",$mode)) print barcode_outtext($bars['text'],$bars['bars']);
	//elseif (preg_match("/^(html|htm)$/i",$mode)) print barcode_outhtml($bars['text'],$bars['bars'], $scale,0, 0);
	//else

	barcode_outimage($bars['text'], $bars['bars'], $scale, $mode, 0, [], $filebarcode);

	return $bars;
}

/**
 * Encodes $code with $encoding using genbarcode OR built-in encoder if you don't have genbarcode only EAN-13/ISBN or UPC is possible
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
 * @return	array{encoding:string,bars:string,text:string}|false			array('encoding': the encoding which has been used, 'bars': the bars, 'text': text-positioning info)
 */
function barcode_encode($code, $encoding)
{
	global $genbarcode_loc;

	if ((preg_match("/^upc$/i", $encoding))
	&& (preg_match("/^[0-9]{11,12}$/", $code))
	) {
		/* use built-in UPC-Encoder */
		dol_syslog("barcode.lib.php::barcode_encode Use barcode_encode_upc");
		$bars = barcode_encode_upc($code, $encoding);
	} elseif ((preg_match("/^ean$/i", $encoding))

	|| (($encoding) && (preg_match("/^isbn$/i", $encoding))
	&& ((strlen($code) == 9 || strlen($code) == 10) ||
	(((preg_match("/^978/", $code) && strlen($code) == 12) ||
	(strlen($code) == 13)))))

	|| ((!isset($encoding) || !$encoding || (preg_match("/^ANY$/i", $encoding)))
	&& (preg_match("/^[0-9]{12,13}$/", $code)))
	) {
		/* use built-in EAN-Encoder */
		dol_syslog("barcode.lib.php::barcode_encode Use barcode_encode_ean");
		$bars = barcode_encode_ean($code, $encoding);
	} elseif (file_exists($genbarcode_loc)) {	// For example C39
		/* use genbarcode */
		dol_syslog("barcode.lib.php::barcode_encode Use genbarcode ".$genbarcode_loc." code=".$code." encoding=".$encoding);
		$bars = barcode_encode_genbarcode($code, $encoding);
	} else {
		print "barcode_encode needs an external program for encodings other then EAN/ISBN (code=".dol_escape_htmltag($code).", encoding=".dol_escape_htmltag($encoding).")<BR>\n";
		print "<UL>\n";
		print "<LI>download gnu-barcode from <A href=\"https://www.gnu.org/software/barcode/\">www.gnu.org/software/barcode/</A>\n";
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
 * @return	int<0,9>		EAN Sum
 */
function barcode_gen_ean_sum($ean)
{
	$even = true;
	$esum = 0;
	$osum = 0;
	$ln = strlen($ean) - 1;
	for ($i = $ln; $i >= 0; $i--) {
		if ($even) {
			$esum += $ean[$i];
		} else {
			$osum += $ean[$i];
		}
		$even = !$even;
	}
	return (10 - ((3 * $esum + $osum) % 10)) % 10;
}


/**
 * Generate EAN bars
 *
 * @param	string	$ean	EAN to encode
 * @return	string			Encoded EAN
 */
function barcode_gen_ean_bars($ean)
{
	$digits = array('3211', '2221', '2122', '1411', '1132', '1231', '1114', '1312', '1213', '3112');
	$mirror = array("000000", "001011", "001101", "001110", "010011", "011001", "011100", "010101", "010110", "011010");
	$guards = array("9a1a", "1a1a1", "a1a7");

	$line = $guards[0];
	for ($i = 1; $i < 13; $i++) {
		$str = $digits[(int) $ean[$i]];
		if ($i < 7 && $mirror[(int) $ean[0]][$i - 1] == 1) {
			$line .= strrev($str);
		} else {
			$line .= $str;
		}
		if ($i == 6) {
			$line .= $guards[1];
		}
	}
	$line .= $guards[2];

	return $line;
}

/**
 * Encode EAN
 *
 * @param	string	$ean		Code
 * @param	string	$encoding	Encoding
 * @return	array{encoding:string,bars:string,text:string,error:string}|array{text:string,error:string}	array('encoding': the encoding which has been used, 'bars': the bars, 'text': text-positioning info, 'error': error message if error)
 */
function barcode_encode_ean($ean, $encoding = "EAN-13")
{
	$ean = trim($ean);
	if (preg_match("/[^0-9]/i", $ean)) {
		return array("error" => "Invalid encoding/code. encoding=".$encoding." code=".$ean." (not a numeric)", "text" => "Invalid encoding/code. encoding=".$encoding." code=".$ean." (not a numeric)");
	}
	$encoding = strtoupper($encoding);
	if ($encoding == "ISBN") {
		if (!preg_match("/^978/", $ean)) {
			$ean = "978".$ean;
		}
	}
	if (preg_match("/^97[89]/", $ean)) {
		$encoding = "ISBN";
	}
	if (strlen($ean) < 12 || strlen($ean) > 13) {
		return array("error" => "Invalid encoding/code. encoding=".$encoding." code=".$ean." (must have 12/13 numbers)", "text" => "Invalid encoding/code. encoding=".$encoding." code=".$ean." (must have 12/13 numbers)");
	}

	$ean = substr($ean, 0, 12);
	$eansum = barcode_gen_ean_sum($ean);
	$ean .= $eansum;
	$bars = barcode_gen_ean_bars($ean);

	/* create text */
	$pos = 0;
	$text = "";
	for ($a = 0; $a < 13; $a++) {
		if ($a > 0) {
			$text .= " ";
		}
		$text .= $pos.":12:".$ean[$a];
		if ($a == 0) {
			$pos += 12;
		} elseif ($a == 6) {
			$pos += 12;
		} else {
			$pos += 7;
		}
	}

	return array(
		"error" => '',
		"encoding" => $encoding,
		"bars" => $bars,
		"text" => $text
	);
}

/**
 * Encode UPC
 *
 * @param	string	$upc		Code
 * @param	string	$encoding	Encoding
 * @return	array{encoding:string,bars:string,text:string,error:string}|array{text:string,error:string}	array('encoding': the encoding which has been used, 'bars': the bars, 'text': text-positioning info, 'error': error message if error)
 */
function barcode_encode_upc($upc, $encoding = "UPC")
{
	$upc = trim($upc);
	if (preg_match("/[^0-9]/i", $upc)) {
		return array("error" => "Invalid encoding/code. encoding=".$encoding." code=".$upc." (not a numeric)", "text" => "Invalid encoding/code. encoding=".$encoding." code=".$upc." (not a numeric)");
	}
	$encoding = strtoupper($encoding);
	if (strlen($upc) < 11 || strlen($upc) > 12) {
		return array("error" => "Invalid encoding/code. encoding=".$encoding." code=".$upc." (must have 11/12 numbers)", "text" => "Invalid encoding/code. encoding=".$encoding." code=".$upc." (must have 11/12 numbers)");
	}

	$upc = substr("0".$upc, 0, 12);
	$eansum = barcode_gen_ean_sum($upc);
	$upc .= $eansum;
	$bars = barcode_gen_ean_bars($upc);

	/* create text */
	$pos = 0;
	$text = "";
	for ($a = 1; $a < 13; $a++) {
		if ($a > 1) {
			$text .= " ";
		}
		$text .= $pos.":12:".$upc[$a];
		if ($a == 1) {
			$pos += 15;
		} elseif ($a == 6) {
			$pos += 17;
		} elseif ($a == 11) {
			$pos += 15;
		} else {
			$pos += 7;
		}
	}

	return array(
		"error" => '',
		"encoding" => $encoding,
		"bars" => $bars,
		"text" => $text
	);
}

/**
 * Encode result of genbarcode command
 *
 * @param	string	$code		Code
 * @param	string	$encoding	Encoding
 * @return	array{encoding:string,bars:string,text:string}|false			array('encoding': the encoding which has been used, 'bars': the bars, 'text': text-positioning info)
 */
function barcode_encode_genbarcode($code, $encoding)
{
	global $conf, $db, $genbarcode_loc;

	// Clean parameters
	if (preg_match("/^ean$/i", $encoding) && strlen($code) == 13) {
		$code = substr($code, 0, 12);
	}
	if (!$encoding) {
		$encoding = "ANY";
	}
	$encoding = dol_string_nospecial($encoding, '_');
	$code = dol_string_nospecial($code, "_");

	$command = escapeshellarg($genbarcode_loc);
	$paramclear = " ".escapeshellarg($code)." ".escapeshellarg(strtoupper($encoding));

	$fullcommandclear = $command." ".$paramclear." 2>&1";
	//print $fullcommandclear."<br>\n";exit;

	dol_syslog("Run command ".$fullcommandclear);

	$outputfile = $conf->user->dir_temp.'/genbarcode.tmp'; // File used with popen method

	// Execute a CLI
	include_once DOL_DOCUMENT_ROOT.'/core/class/utils.class.php';
	$utils = new Utils($db);
	$result = $utils->executeCLI($fullcommandclear, $outputfile);

	if (!empty($result['output'])) {
		$tmparr = explode("\n", $result['output']);
		$bars = $tmparr[0];
		$text = $tmparr[1];
		$encoding = $tmparr[2];
	} else {
		dol_syslog("barcode.lib.php::barcode_encode_genbarcode failed to run ".$fullcommandclear, LOG_ERR);
		return false;
	}

	//var_dump($bars);
	$ret = array(
		"bars" => trim($bars),
		"text" => trim($text),
		"encoding" => trim($encoding),
		"error" => ""
	);
	//var_dump($ret);
	if (preg_match('/permission denied/i', $ret['bars'])) {
		$ret['error'] = $ret['bars'];
		$ret['bars'] = '';
		return $ret;
	}
	if (!$ret['bars']) {
		return false;
	}
	if (!$ret['text']) {
		return false;
	}
	if (!$ret['encoding']) {
		return false;
	}
	return $ret;
}

/**
 * Output image onto standard output, or onto disk if $filebarcode is defined
 *
 * @param	string		$text			the text-line (<position>:<font-size>:<character> ...)
 * @param	string		$bars   		where to place the bars  (<space-width><bar-width><space-width><bar-width>...)
 * @param	int<1,max>	$scale			scale factor ( 1 < scale < unlimited (scale 50 will produce 5400x300 pixels when using EAN-13!!!))
 * @param	string		$mode   		Mime 'png', 'gif', 'jpg', 'jpeg' (default='png') or file disk if empty.
 * @param	int			$total_y		the total height of the image ( default: scale * 60 )
 * @param	array{}|array{top:int,bottom:int,left:int,right:int}	$space		default:  $space[top]   = 2 * $scale; $space[bottom]= 2 * $scale;  $space[left]  = 2 * $scale;  $space[right] = 2 * $scale;
 * @param	string		$filebarcode	Filename to store barcode image file
 * @return	void
 */
function barcode_outimage($text, $bars, $scale = 1, $mode = "png", $total_y = 0, $space = [], $filebarcode = '')
{
	global $bar_color, $bg_color, $text_color, $font_loc;

	//print "$text, $bars, $scale, $mode, $total_y, $space, $font_loc, $filebarcode<br>";

	/* set defaults */
	if ($scale < 1) {
		$scale = 2;
	}
	$total_y = (int) $total_y;
	if ($total_y < 1) {
		$total_y = (int) $scale * 60;
	}
	if (!is_array($space) || empty($space)) {
		$space = array('top' => 2 * $scale, 'bottom' => 2 * $scale, 'left' => 2 * $scale, 'right' => 2 * $scale);
	}

	/* count total width */
	$xpos = 0;
	$width = true;
	$ln = strlen($bars);
	for ($i = 0; $i < $ln; $i++) {
		$val = strtolower($bars[$i]);
		if ($width) {
			$xpos += (int) $val * $scale;
			$width = false;
			continue;
		}
		if (preg_match("/[a-z]/", $val)) {
			/* tall bar */
			$val = ord($val) - ord('a') + 1;
		}
		$xpos += $val * $scale;
		$width = true;
	}

	/* allocate the image */
	$total_x = ($xpos) + $space['right'] + $space['right'];
	$xpos = $space['left'];
	if (!function_exists("imagecreate")) {
		print "You don't have the gd2 extension enabled<br>\n";
		return;
	}
	$im = imagecreate($total_x, $total_y);
	/* create two images */
	$col_bg = imagecolorallocate($im, $bg_color[0], $bg_color[1], $bg_color[2]);
	$col_bar = imagecolorallocate($im, $bar_color[0], $bar_color[1], $bar_color[2]);
	$col_text = imagecolorallocate($im, $text_color[0], $text_color[1], $text_color[2]);
	$height = (int) round($total_y - ($scale * 10));
	$height2 = (int) round($total_y - $space['bottom']);

	/* paint the bars */
	$width = true;
	$ln = strlen($bars);
	for ($i = 0; $i < $ln; $i++) {
		$val = strtolower($bars[$i]);
		if ($width) {
			$xpos += (float) $val * $scale;
			$width = false;
			continue;
		}
		if (preg_match("/[a-z]/", $val)) {
			/* tall bar */
			$val = ord($val) - ord('a') + 1;
			$h = $height2;
		} else {
			$h = $height;
		}
		imagefilledrectangle($im, $xpos, $space['top'], $xpos + (int) ((float) $val * $scale) - 1, $h, $col_bar);
		$xpos += $val * $scale;
		$width = true;
	}

	$chars = explode(" ", $text);
	foreach ($chars as $v) {
		if (trim($v)) {
			$inf = explode(":", $v);
			$fontsize = $scale * ((float) $inf[1] / 1.8);
			$fontheight = (int) round($total_y - ($fontsize / 2.7) + 2);
			imagettftext($im, $fontsize, 0, $space['left'] + (int) ($scale * (float) $inf[0]) + 2, $fontheight, $col_text, $font_loc, $inf[2]);
		}
	}

	/* output the image */
	$mode = strtolower($mode);
	if (!empty($filebarcode) && (empty($mode) || $mode == 'png')) {
		// To write into a file onto disk
		imagepng($im, $filebarcode);
	} elseif ($mode == 'jpg' || $mode == 'jpeg') {
		top_httphead('image/jpeg; name="barcode.jpg"');
		imagejpeg($im);
	} elseif ($mode == 'gif') {
		top_httphead('image/gif; name="barcode.gif"');
		imagegif($im);
	} elseif ($mode == 'png') {
		top_httphead('image/png; name="barcode.png"');
		imagepng($im);
	}

	return;
}

/**
 * Check if EAN13 code is valid
 *
 * @param string $ean	Code
 * @return bool
 */
function isAValidEAN13($ean)
{
	$sumEvenIndexes = 0;
	$sumOddIndexes  = 0;

	$eanAsArray = array_map('intval', str_split($ean));

	if (!(count($eanAsArray) === 13)) {
		return false;
	};

	$num = (count($eanAsArray) - 1);
	for ($i = 0; $i < $num; $i++) {
		if ($i % 2 === 0) {
			$sumOddIndexes  += $eanAsArray[$i];
		} else {
			$sumEvenIndexes += $eanAsArray[$i];
		}
	}

	$rest = ($sumOddIndexes + (3 * $sumEvenIndexes)) % 10;

	if ($rest !== 0) {
		$rest = 10 - $rest;
	}

	return $rest === $eanAsArray[12];
}
