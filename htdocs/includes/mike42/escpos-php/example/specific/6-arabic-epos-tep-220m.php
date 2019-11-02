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
 *  - ArPHP 4.0 (release date: Jan 8, 2016), available from SourceForge, for
 *      handling the layout for this example.
 */
require __DIR__ . '/../../autoload.php';
use Mike42\Escpos\CapabilityProfile;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\PrintBuffers\ImagePrintBuffer;

/*
 * Drop Ar-php into the folder listed below:
 */
require_once(dirname(__FILE__) . "/../../I18N/Arabic.php");
$fontPath = dirname(__FILE__) . "/../../I18N/Arabic/Examples/GD/ae_AlHor.ttf";

/*
 * Inputs are some text, line wrapping options, and a font size. 
 */
$textUtf8 = "صِف خَلقَ خَودِ كَمِثلِ الشَمسِ إِذ بَزَغَت — يَحظى الضَجيعُ بِها نَجلاءَ مِعطارِ";
$maxChars = 50;
$fontSize = 28;

/*
 * First, convert the text into LTR byte order with line wrapping,
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
$textLtr = $Arabic -> utf8Glyphs($textUtf8, $maxChars);
$textLine = explode("\n", $textLtr);

/*
 * Set up and use an image print buffer with a suitable font
 */
$buffer = new ImagePrintBuffer();
$buffer -> setFont($fontPath);
$buffer -> setFontSize($fontSize);

$profile = CapabilityProfile::load("TEP-200M");
$connector = new FilePrintConnector("php://output");
        // = new WindowsPrintConnector("LPT2");
        // Windows LPT2 was used in the bug tracker

$printer = new Printer($connector, $profile);
$printer -> setPrintBuffer($buffer);

$printer -> setJustification(Printer::JUSTIFY_RIGHT);
foreach($textLine as $text) {
    // Print each line separately. We need to do this since Imagick thinks
    // text is left-to-right
    $printer -> text($text . "\n");
}

$printer -> cut();
$printer -> close();
