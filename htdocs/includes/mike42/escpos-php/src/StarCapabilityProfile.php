<?php
class StarCapabilityProfile extends DefaultCapabilityProfile {
	function getCustomCodePages() {
		// Code table reference: http://www.starmicronics.com/support/mannualfolder/sp2000pm.pdf
		return array(
			'CP3011' => "ÇüéâäàåçêëèïîìÄÅ" . 
						"ÉæÆôöòûùÿÖÜ¢£¥₧ƒ" . 
						"áíóúñÑªº¿⌐¬½¼¡«»" . 
						"░▒▓│┤Ā╢ņ╕╣║╗╝╜╛┐" . 
						"└┴┬├─┼ā╟╚╔╩╦╠═╬╧" . 
						"Š╤čČ╘╒ģĪī┘┌█▄ūŪ▀" . 
						"αßΓπΣσµτΦΘΩδ∞φε∩" . 
						"ĒēĢķĶļĻžŽ∙·√Ņš■ ",
			'CP3012' => "АБВГДЕЖЗИЙКЛМНОП" . 
						"РСТУФХЦЧШЩЪЫЬЭЮЯ" . 
						"абвгдежзийклмноп" . 
						"░▒▓│┤Ā╢ņ╕╣║╗╝Ō╛┐" . 
						"└┴┬├─┼ā╟╚╔╩╦╠═╬╧" . 
						"Š╤čČ╘╒ģĪī┘┌█▄ūŪ▀" . 
						"рстуфхцчшщъыьэюя" . 
						"ĒēĢķĶļĻžŽ∙·√Ņš■ "
		);
	}

	function getSupportedCodePages() {
		return array(
				0 => CodePage::CP437, // "Normal"
				1 => CodePage::CP437,
				2 => CodePage::CP932,
				3 => CodePage::CP437, 
				4 => CodePage::CP858,
				5 => CodePage::CP852,
				6 => CodePage::CP860,
				7 => CodePage::CP861,
				8 => CodePage::CP863,
				9 => CodePage::CP865,
				10 => CodePage::CP866,
				11 => CodePage::CP855,
				12 => CodePage::CP857,
				13 => CodePage::CP862,
				14 => CodePage::CP864,
				15 => CodePage::CP737,
				16 => CodePage::CP851,
				17 => CodePage::CP869,
				18 => CodePage::CP928,
				19 => CodePage::CP772,
				20 => CodePage::CP774,
				21 => CodePage::CP874,
				32 => CodePage::CP1252,
				33 => CodePage::CP1250,
				34 => CodePage::CP1251,
				64 => CodePage::CP3840,
				65 => CodePage::CP3841,
				66 => CodePage::CP3843,
				67 => CodePage::CP3844,
				68 => CodePage::CP3845,
				69 => CodePage::CP3846,
				70 => CodePage::CP3847,
				71 => CodePage::CP3848,
				72 => CodePage::CP1001,
				73 => CodePage::CP2001,
				74 => CodePage::CP3001,
				75 => CodePage::CP3002,
				76 => 'custom:CP3011',
				77 => 'custom:CP3012',
				78 => CodePage::CP3021,
				79 => CodePage::CP3041,
				96 => false, // Thai Character Code 42
				97 => false, // Thai Character Code 11
				98 => false, // Thai Character Code 13
				99 => false, // Thai Character Code 14
				100 => false, // Thai Character Code 16
				101 => false, // Thai Character Code 17
				102 => false, // Thai Character Code 18
				255 => false);
	}

	function getSupportsStarCommands() {
		/* Allows Escpos.php to substitute emulated ESC/POS commands with native ones for this printer. */ 
		return true;
	}
}