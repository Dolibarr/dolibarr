<?php
/**
 * This capability profile is designed for the P-822D.
 * 
 * See
 * https://github.com/mike42/escpos-php/issues/50
 */
class P822DCapabilityProfile extends DefaultCapabilityProfile {
	function getSupportedCodePages() {
		return array(
			0 => CodePage::CP437,
			1 => false, // Katakana
			2 => CodePage::CP850,
			3 => CodePage::CP860,
			4 => CodePage::CP863,
			5 => CodePage::CP865,
			6 => false, // Western Europe
			7 => false, // Greek
			8 => false, // Hebrew
			9 => false, // Eastern europe
			10 => false, // Iran
			16 => CodePage::CP1252 ,
			17 => CodePage::CP866 ,
			18 => CodePage::CP852 ,
			19 => CodePage::CP858,
			20 => false, // Iran II
			21 => false, // latvian
			22 => false,  //Arabic
			23 => false, // PT151, 1251
			24 => CodePage::CP747,
			25 => CodePage::CP1257,
			27 => false, // Vietnam,
			28 => CodePage::CP864,
			29 => CodePage::CP1001,
			30 => false, // Uigur
			31 => false, // Hebrew
			32 => CodePage::CP1255,
			33 => CodePage::CP720,
			34 => CodePage::CP1256,
			35 => CodePage::CP1257,
			255 => false, // Thai
			
			50 => CodePage::CP437,
			51 => false, // Jatakana,
			52 => CodePage::CP437,
			53 => CodePage::CP858,
			54 => CodePage::CP852,
			55 => CodePage::CP860,
			56 => CodePage::CP861,
			57 => CodePage::CP863,
			58 => CodePage::CP865,
			59 => CodePage::CP866,
			60 => CodePage::CP855,
			61 => CodePage::CP857,
			62 => CodePage::CP862,
			63 => CodePage::CP864,
			64 => CodePage::CP737,
			65 => CodePage::CP851,
			66 => CodePage::CP869,
			67 => CodePage::CP928,
			68 => CodePage::CP772,
			69 => CodePage::CP774,
			70 => CodePage::CP874,
			71 => CodePage::CP1252,
			72 => CodePage::CP1250,
			73 => CodePage::CP1251,
			74 => CodePage::CP3840,
			75 => CodePage::CP3841,
			76 => CodePage::CP3843,
			77 => CodePage::CP3844,
			78 => CodePage::CP3845,
			79 => CodePage::CP3846,
			80 => CodePage::CP3847,
			81 => CodePage::CP3848,
			82 => CodePage::CP1001,
			83 => CodePage::CP2001,
			84 => CodePage::CP3001,
			85 => CodePage::CP3002,
			86 => CodePage::CP3011,
			87 => CodePage::CP3012,
			88 => CodePage::CP3021,
			89 => CodePage::CP3041
		);
	}
	
	public function getSupportsGraphics() {
		/* Ask the driver to use bitImage wherever possible instead of graphics */
		return false;
	}
}
