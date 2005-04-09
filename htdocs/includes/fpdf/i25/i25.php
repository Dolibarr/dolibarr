<?php
require('fpdf.php');

// based on the Code 39 script from The-eh
class PDF_i25 extends FPDF
{
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

	for($i=0; $i<strlen($code); $i=$i+2){
		// choose next pair of digits
		$charBar = $code{$i};
		$charSpace = $code{$i+1};
		// check whether it is a valid digit
		if(!isset($barChar[$charBar])){
			$this->Error('Invalid character in barcode: '.$charBar);
		}
		if(!isset($barChar[$charSpace])){
			$this->Error('Invalid character in barcode: '.$charSpace);
		}
		// create a wide/narrow-sequence (first digit=bars, second digit=spaces)
		$seq = '';
		for($s=0; $s<strlen($barChar[$charBar]); $s++){
			$seq .= $barChar[$charBar]{$s} . $barChar[$charSpace]{$s};
		}
		for($bar=0; $bar<strlen($seq); $bar++){
			// set lineWidth depending on value
			if($seq{$bar} == 'n'){
				$lineWidth = $narrow;
			}else{
				$lineWidth = $wide;
			}
			// draw every second value, because the second digit of the pair is represented by the spaces
			if($bar % 2 == 0){
				$this->Rect($xpos, $ypos, $lineWidth, $height, 'F');
			}
			$xpos += $lineWidth;
		}
	}
}
}

?>
