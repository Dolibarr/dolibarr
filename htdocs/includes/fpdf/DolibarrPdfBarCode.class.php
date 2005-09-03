<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 * $Source$
 *
 */

require_once(FPDF_PATH.'fpdf.php');

class DolibarrPdfBarCode extends FPDF {

  /* Author Olivier PLATHEY
   * Licence Freeware see : http://fpdf.org/fr/script/script5.php
   */
  function EAN13($x,$y,$barcode,$h=16,$w=.35)
  {
    $this->Barcode($x,$y,$barcode,$h,$w,13);
  }
  
  function UPC_A($x,$y,$barcode,$h=16,$w=.35)
  {
    $this->Barcode($x,$y,$barcode,$h,$w,12);
  }
  
  function GetCheckDigit($barcode)
  {
    //Calcule le chiffre de contrôle
    $sum=0;
    for($i=1;$i<=11;$i+=2)
      $sum+=3*$barcode{$i};
    for($i=0;$i<=10;$i+=2)
      $sum+=$barcode{$i};
    $r=$sum%10;
    if($r>0)
      $r=10-$r;
    return $r;
  }
  
  function TestCheckDigit($barcode)
  {
    //Vérifie le chiffre de contrôle
    $sum=0;
    for($i=1;$i<=11;$i+=2)
      $sum+=3*$barcode{$i};
    for($i=0;$i<=10;$i+=2)
      $sum+=$barcode{$i};
    return ($sum+$barcode{12})%10==0;
  }
  
  function Barcode($x,$y,$barcode,$h,$w,$len)
  {
    //Ajoute des 0 si nécessaire
    $barcode=str_pad($barcode,$len-1,'0',STR_PAD_LEFT);
    if($len==12)
      $barcode='0'.$barcode;
    //Ajoute ou teste le chiffre de contrôle
    if(strlen($barcode)==12)
      $barcode.=$this->GetCheckDigit($barcode);
    elseif(!$this->TestCheckDigit($barcode))
      $this->Error('Incorrect check digit');
    //Convertit les chiffres en barres
    $codes=array(
        'A'=>array(
            '0'=>'0001101','1'=>'0011001','2'=>'0010011','3'=>'0111101','4'=>'0100011',
            '5'=>'0110001','6'=>'0101111','7'=>'0111011','8'=>'0110111','9'=>'0001011'),
        'B'=>array(
            '0'=>'0100111','1'=>'0110011','2'=>'0011011','3'=>'0100001','4'=>'0011101',
            '5'=>'0111001','6'=>'0000101','7'=>'0010001','8'=>'0001001','9'=>'0010111'),
        'C'=>array(
            '0'=>'1110010','1'=>'1100110','2'=>'1101100','3'=>'1000010','4'=>'1011100',
            '5'=>'1001110','6'=>'1010000','7'=>'1000100','8'=>'1001000','9'=>'1110100')
        );
    $parities=array(
        '0'=>array('A','A','A','A','A','A'),
        '1'=>array('A','A','B','A','B','B'),
        '2'=>array('A','A','B','B','A','B'),
        '3'=>array('A','A','B','B','B','A'),
        '4'=>array('A','B','A','A','B','B'),
        '5'=>array('A','B','B','A','A','B'),
        '6'=>array('A','B','B','B','A','A'),
        '7'=>array('A','B','A','B','A','B'),
        '8'=>array('A','B','A','B','B','A'),
        '9'=>array('A','B','B','A','B','A')
        );
    $code='101';
    $p=$parities[$barcode{0}];
    for($i=1;$i<=6;$i++)
        $code.=$codes[$p[$i-1]][$barcode{$i}];
    $code.='01010';
    for($i=7;$i<=12;$i++)
        $code.=$codes['C'][$barcode{$i}];
    $code.='101';
    //Dessine les barres
    for($i=0;$i<strlen($code);$i++)
    {
        if($code{$i}=='1')
            $this->Rect($x+$i*$w,$y,$w,$h,'F');
    }
    //Imprime le texte sous le code-barres
    $this->SetFont('Arial','',12);
    $this->Text($x,$y+$h+11/$this->k,substr($barcode,-$len));
}

  /*
   * Licence See i25/info.htm
   */

  function i25($xpos, $ypos, $code, $basewidth=1, $height=10){

    $wide = $basewidth;
    $narrow = $basewidth / 3 ;
    
    // wide/narrow codes for the digits
    $barChar['0'] = 'nnwwn';
    $barChar['1'] = 'wnnnw';
    $barChar['2'] = 'nwnnw';
    $barChar['3'] = 'wwnnn';
    $barChar['4'] = 'nnwnw';
    $barChar['5'] = 'wnwnn';
    $barChar['6'] = 'nwwnn';
    $barChar['7'] = 'nnnww';
    $barChar['8'] = 'wnnwn';
    $barChar['9'] = 'nwnwn';
    $barChar['A'] = 'nn';
    $barChar['Z'] = 'wn';
    
    // add leading zero if code-length is odd
    if(strlen($code) % 2 != 0){
      $code = '0' . $code;
    }
    
    $this->SetFont('Arial','',10);
    $this->Text($xpos, $ypos + $height + 4, $code);
    $this->SetFillColor(0);
    
    // add start and stop codes
    $code = 'AA'.strtolower($code).'ZA';
    
    for($i=0; $i<strlen($code); $i=$i+2)
      {
	// choose next pair of digits
	$charBar = $code{$i};
	$charSpace = $code{$i+1};
	// check whether it is a valid digit
	if(!isset($barChar[$charBar]))
	  {
	    $this->Error('Invalid character in barcode: '.$charBar);
	  }
	if(!isset($barChar[$charSpace]))
	  {
	  $this->Error('Invalid character in barcode: '.$charSpace);
	  }
	// create a wide/narrow-sequence (first digit=bars, second digit=spaces)
	$seq = '';
	for($s=0; $s<strlen($barChar[$charBar]); $s++)
	  {
	    $seq .= $barChar[$charBar]{$s} . $barChar[$charSpace]{$s};
	  }
	for($bar=0; $bar<strlen($seq); $bar++){
	  // set lineWidth depending on value
	  if($seq{$bar} == 'n')
	    {
	      $lineWidth = $narrow;
	    }
	  else
	    {
	      $lineWidth = $wide;
	    }
	// draw every second value, because the second digit of the pair is represented by the spaces
	  if($bar % 2 == 0)
	    {
	      $this->Rect($xpos, $ypos, $lineWidth, $height, 'F');
	    }
	  $xpos += $lineWidth;
	}
      }
  }
  
  
  /*
   * Copyright ehavet@yahoo.fr
   * Emmanuel Havet 
   * License: Freeware
   *
   */
  
  function Code39($x, $y, $code, $ext = true, $cks = false, $w = 0.4, $h = 20, $wide = true)
  {    
    //Display code
    $this->SetFont('Arial', '', 10);
    $this->Text($x, $y+$h+4, $code);
    $this->SetFillColor(0,0,0);
    
    if($ext)
      {
	//Extended encoding
	$code = $this->encode_code39_ext($code);
      }
    else
      {
	//Convert to upper case
	$code = strtoupper($code);
	//Check validity
	if(!preg_match('|^[0-9A-Z. $/+%-]*$|', $code))
	  $this->Error('Invalid barcode value: '.$code);
      }
    
    //Compute checksum
    if ($cks)
      $code .= $this->checksum_code39($code);
    
    //Add start and stop characters
    $code = '*'.$code.'*';
    
    //Conversion tables
    $narrow_encoding = array (
		'0' => '101001101101', '1' => '110100101011', '2' => '101100101011',
		'3' => '110110010101', '4' => '101001101011', '5' => '110100110101',
		'6' => '101100110101', '7' => '101001011011', '8' => '110100101101',
		'9' => '101100101101', 'A' => '110101001011', 'B' => '101101001011',
		'C' => '110110100101', 'D' => '101011001011', 'E' => '110101100101',
		'F' => '101101100101', 'G' => '101010011011', 'H' => '110101001101',
		'I' => '101101001101', 'J' => '101011001101', 'K' => '110101010011',
		'L' => '101101010011', 'M' => '110110101001', 'N' => '101011010011',
		'O' => '110101101001', 'P' => '101101101001', 'Q' => '101010110011',
		'R' => '110101011001', 'S' => '101101011001', 'T' => '101011011001',
		'U' => '110010101011', 'V' => '100110101011', 'W' => '110011010101',
		'X' => '100101101011', 'Y' => '110010110101', 'Z' => '100110110101',
		'-' => '100101011011', '.' => '110010101101', ' ' => '100110101101',
		'*' => '100101101101', '$' => '100100100101', '/' => '100100101001',
		'+' => '100101001001', '%' => '101001001001' );

    $wide_encoding = array (
		'0' => '101000111011101', '1' => '111010001010111', '2' => '101110001010111',
		'3' => '111011100010101', '4' => '101000111010111', '5' => '111010001110101',
		'6' => '101110001110101', '7' => '101000101110111', '8' => '111010001011101',
		'9' => '101110001011101', 'A' => '111010100010111', 'B' => '101110100010111',
		'C' => '111011101000101', 'D' => '101011100010111', 'E' => '111010111000101',
		'F' => '101110111000101', 'G' => '101010001110111', 'H' => '111010100011101',
		'I' => '101110100011101', 'J' => '101011100011101', 'K' => '111010101000111',
		'L' => '101110101000111', 'M' => '111011101010001', 'N' => '101011101000111',
		'O' => '111010111010001', 'P' => '101110111010001', 'Q' => '101010111000111',
		'R' => '111010101110001', 'S' => '101110101110001', 'T' => '101011101110001',
		'U' => '111000101010111', 'V' => '100011101010111', 'W' => '111000111010101',
		'X' => '100010111010111', 'Y' => '111000101110101', 'Z' => '100011101110101',
		'-' => '100010101110111', '.' => '111000101011101', ' ' => '100011101011101',
		'*' => '100010111011101', '$' => '100010001000101', '/' => '100010001010001',
		'+' => '100010100010001', '%' => '101000100010001');

    $encoding = $wide ? $wide_encoding : $narrow_encoding;
    
    //Inter-character spacing
    $gap = ($w > 0.29) ? '00' : '0';
    
    //Convert to bars
    $encode = '';
    for ($i = 0; $i< strlen($code); $i++)
      $encode .= $encoding[$code{$i}].$gap;
    
    //Draw bars
    $this->draw_code39($encode, $x, $y, $w, $h);
  }
  
  function checksum_code39($code) {
    
    //Compute the modulo 43 checksum
    
    $chars = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
		   'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K',
		   'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V',
		   'W', 'X', 'Y', 'Z', '-', '.', ' ', '$', '/', '+', '%');
    $sum = 0;
    for ($i=0 ; $i<strlen($code); $i++)
      {
	$a = array_keys($chars, $code{$i});
	$sum += $a[0];
      }
    $r = $sum % 43;
    return $chars[$r];
  }
  
  function encode_code39_ext($code) {
    
    //Encode characters in extended mode
    
    $encode = array(
		chr(0) => '%U', chr(1) => '$A', chr(2) => '$B', chr(3) => '$C',
		chr(4) => '$D', chr(5) => '$E', chr(6) => '$F', chr(7) => '$G',
		chr(8) => '$H', chr(9) => '$I', chr(10) => '$J', chr(11) => '£K',
		chr(12) => '$L', chr(13) => '$M', chr(14) => '$N', chr(15) => '$O',
		chr(16) => '$P', chr(17) => '$Q', chr(18) => '$R', chr(19) => '$S',
		chr(20) => '$T', chr(21) => '$U', chr(22) => '$V', chr(23) => '$W',
		chr(24) => '$X', chr(25) => '$Y', chr(26) => '$Z', chr(27) => '%A',
		chr(28) => '%B', chr(29) => '%C', chr(30) => '%D', chr(31) => '%E',
		chr(32) => ' ', chr(33) => '/A', chr(34) => '/B', chr(35) => '/C',
		chr(36) => '/D', chr(37) => '/E', chr(38) => '/F', chr(39) => '/G',
		chr(40) => '/H', chr(41) => '/I', chr(42) => '/J', chr(43) => '/K',
		chr(44) => '/L', chr(45) => '-', chr(46) => '.', chr(47) => '/O',
		chr(48) => '0', chr(49) => '1', chr(50) => '2', chr(51) => '3',
		chr(52) => '4', chr(53) => '5', chr(54) => '6', chr(55) => '7',
		chr(56) => '8', chr(57) => '9', chr(58) => '/Z', chr(59) => '%F',
		chr(60) => '%G', chr(61) => '%H', chr(62) => '%I', chr(63) => '%J',
		chr(64) => '%V', chr(65) => 'A', chr(66) => 'B', chr(67) => 'C',
		chr(68) => 'D', chr(69) => 'E', chr(70) => 'F', chr(71) => 'G',
		chr(72) => 'H', chr(73) => 'I', chr(74) => 'J', chr(75) => 'K',
		chr(76) => 'L', chr(77) => 'M', chr(78) => 'N', chr(79) => 'O',
		chr(80) => 'P', chr(81) => 'Q', chr(82) => 'R', chr(83) => 'S',
		chr(84) => 'T', chr(85) => 'U', chr(86) => 'V', chr(87) => 'W',
		chr(88) => 'X', chr(89) => 'Y', chr(90) => 'Z', chr(91) => '%K',
		chr(92) => '%L', chr(93) => '%M', chr(94) => '%N', chr(95) => '%O',
		chr(96) => '%W', chr(97) => '+A', chr(98) => '+B', chr(99) => '+C',
		chr(100) => '+D', chr(101) => '+E', chr(102) => '+F', chr(103) => '+G',
		chr(104) => '+H', chr(105) => '+I', chr(106) => '+J', chr(107) => '+K',
		chr(108) => '+L', chr(109) => '+M', chr(110) => '+N', chr(111) => '+O',
		chr(112) => '+P', chr(113) => '+Q', chr(114) => '+R', chr(115) => '+S',
		chr(116) => '+T', chr(117) => '+U', chr(118) => '+V', chr(119) => '+W',
		chr(120) => '+X', chr(121) => '+Y', chr(122) => '+Z', chr(123) => '%P',
		chr(124) => '%Q', chr(125) => '%R', chr(126) => '%S', chr(127) => '%T');

    $code_ext = '';
    for ($i = 0 ; $i<strlen($code); $i++) {
      if (ord($code{$i}) > 127)
	$this->Error('Invalid character: '.$code{$i});
      $code_ext .= $encode[$code{$i}];
    }
    return $code_ext;
  }
  
  function draw_code39($code, $x, $y, $w, $h)
  {    
    //Draw bars
    
    for($i=0; $i<strlen($code); $i++)
      {
	if($code{$i} == '1')
	  $this->Rect($x+$i*$w, $y, $w, $h, 'F');
      }
  }
}
