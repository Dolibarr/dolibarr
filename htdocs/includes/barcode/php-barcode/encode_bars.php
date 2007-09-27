<?php
/*

 * Built-In Encoders
 * Part of PHP-Barcode 0.3pl1
 
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

function barcode_gen_ean_sum($ean){
  $even=true; $esum=0; $osum=0;
  for ($i=strlen($ean)-1;$i>=0;$i--){
	if ($even) $esum+=$ean[$i];	else $osum+=$ean[$i];
	$even=!$even;
  }
  return (10-((3*$esum+$osum)%10))%10;
}

/* barcode_encode_ean(code [, encoding])
 *   encodes $ean with EAN-13 using builtin functions
 *
 *   return:
 *    array[encoding] : the encoding which has been used (EAN-13)
 *    array[bars]     : the bars
 *    array[text]     : text-positioning info
 */
function barcode_encode_ean($ean, $encoding = "EAN-13"){
    $digits=array(3211,2221,2122,1411,1132,1231,1114,1312,1213,3112);
    $mirror=array("000000","001011","001101","001110","010011","011001","011100","010101","010110","011010");
    $guards=array("9a1a","1a1a1","a1a");

    $ean=trim($ean);
    if (eregi("[^0-9]",$ean)){
	return array("text"=>"Invalid EAN-Code");
    }
    $encoding=strtoupper($encoding);
    if ($encoding=="ISBN"){
	if (!ereg("^978", $ean)) $ean="978".$ean;
    }
    if (ereg("^978", $ean)) $encoding="ISBN";
    if (strlen($ean)<12 || strlen($ean)>13){
	return array("text"=>"Invalid $encoding Code (must have 12/13 numbers)");
    }

    $ean=substr($ean,0,12);
    $eansum=barcode_gen_ean_sum($ean);
    $ean.=$eansum;
    $line=$guards[0];
    for ($i=1;$i<13;$i++){
	$str=$digits[$ean[$i]];
	if ($i<7 && $mirror[$ean[0]][$i-1]==1) $line.=strrev($str); else $line.=$str;
	if ($i==6) $line.=$guards[1];
    }
    $line.=$guards[2];

    /* create text */
    $pos=0;
    $text="";
    for ($a=0;$a<13;$a++){
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

?>
