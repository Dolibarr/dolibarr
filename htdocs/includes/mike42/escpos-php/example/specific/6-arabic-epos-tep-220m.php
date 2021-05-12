<?php
/*
 * This example shows Arabic image-based output on the EPOS TEP 220m.
 *
 * Because escpos-php is not yet able to render Arabic correctly
 * on thermal line printers, small images are generated and sent
 * instead. This is a bit slower, and only limited formatting
 * is currently available in this mode.
 * 
 * Requirements are:
 *  - imagick extension (For the ImagePrintBuffer, which does not
 *      support gd at the time of writing)
 *  - Ar-PHP library, available from sourceforge, for the first
 *      part of this example. Drop it in the folder listed below:
 */
require_once(dirname(__FILE__) . "/../../Escpos.php");
require_once(dirname(__FILE__) . "/../../vendor/I18N/Arabic.php");

/*
 * First, convert the text into LTR byte order with joined letters,
 * Using the Ar-PHP library.
 * 
 * The Ar-PHP library uses the default internal encoding, and can print
 * a lot of errors depending on the input, so be prepared to debug
 * the next four lines.
 * 
 * Note that this output shows that numerals are converted to placeholder
 * characters, indicating that western numerals (123) have to be used instead.
 */
mb_internal_encoding("UTF-8");
$Arabic = new I18N_Arabic('Glyphs');
$text = "صِف خَلقَ خَودِ كَمِثلِ الشَمسِ إِذ بَزَغَت — يَحظى الضَجيعُ بِها نَجلاءَ مِعطارِ";
$text = $Arabic -> utf8Glyphs($text);

/*
 * Set up and use the printer
 */
$buffer = new ImagePrintBuffer();
$profile = EposTepCapabilityProfile::getInstance();
$connector = new FilePrintConnector("php://output");
		// = WindowsPrintConnector("LPT2");
		// Windows LPT2 was used in the bug tracker

$printer = new Escpos($connector, $profile);
$printer -> setPrintBuffer($buffer);
$printer -> text($text . "\n");
$printer -> close();
