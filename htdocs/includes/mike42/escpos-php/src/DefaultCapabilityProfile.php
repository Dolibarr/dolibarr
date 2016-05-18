<?php
/**
 * This capability profile matches many recent Epson-branded thermal receipt printers.
 * 
 * For non-Epson printers, try the SimpleCapabilityProfile.
 */
class DefaultCapabilityProfile extends AbstractCapabilityProfile {
	function getCustomCodePages() {
		return array();
	}

	function getSupportedCodePages() {
		/* Character code tables which the printer understands, mapping to known encoding standards we may be able to encode to.
		 * 
		 * See CodePage.php for the mapping of these standards to encoding names for use in the backing library.
		 * 
		 * Any entry with 'false' means I haven't compared the print-out of the code page to a table.
		 */
		return array(
			0 => CodePage::CP437,
			1 => CodePage::CP932,
			2 => CodePage::CP850,
			3 => CodePage::CP860,
			4 => CodePage::CP863,
			5 => CodePage::CP865,
			6 => false, // Hiragana
			7 => false, // One-pass printing Kanji characters
			8 => false, // Page 8 [One-pass printing Kanji characters]
			11 => CodePage::CP851,
			12 => CodePage::CP853,
			13 => CodePage::CP857,
			14 => CodePage::CP737,
			15 => CodePage::ISO8859_7,
			16 => CodePage::CP1252,
			17 => CodePage::CP866,
			18 => CodePage::CP852,
			19 => CodePage::CP858,
			20 => false, // Thai Character Code 42
			21 => CodePage::CP874, // Thai Character Code 11
			22 => false, // Thai Character Code 13
			23 => false, // Thai Character Code 14
			24 => false, // Thai Character Code 16
			25 => false, // Thai Character Code 17
			26 => false, // Thai Character Code 18
			30 => false, // TCVN-3: Vietnamese
			31 => false, // TCVN-3: Vietnamese
			32 => CodePage::CP720,
			33 => CodePage::CP775,
			34 => CodePage::CP855,
			35 => CodePage::CP861,
			36 => CodePage::CP862,
			37 => CodePage::CP864,
			38 => CodePage::CP869,
			39 => CodePage::ISO8859_2,
			40 => CodePage::ISO8859_15,
			41 => CodePage::CP1098, // PC1098: Farsi
			42 => CodePage::CP774,
			43 => CodePage::CP772,
			44 => CodePage::CP1125,
			45 => CodePage::CP1250,
			46 => CodePage::CP1251,
			47 => CodePage::CP1253,
			48 => CodePage::CP1254,
			49 => CodePage::CP1255,
			50 => CodePage::CP1256,
			51 => CodePage::CP1257,
			52 => CodePage::CP1258,
			53 => CodePage::RK1048,
			66 => false, // Devanagari
			67 => false, // Bengali
			68 => false, // Tamil
			69 => false, // Telugu
			70 => false, // Assamese
			71 => false, // Oriya
			72 => false, // Kannada
			73 => false, // Malayalam
			74 => false, // Gujarati
			75 => false, // Punjabi
			82 => false, // Marathi
			254 => false,
			255 => false);
	}

	function getSupportsBarcodeB() {
		return true;
	}
	
	function getSupportsBitImage() {
		return true;
	}

	function getSupportsGraphics() {
		return true;
	}

	function getSupportsStarCommands() {
		return false;
	}

	function getSupportsQrCode() {
		return true;
	}
}
