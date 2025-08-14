<?php
/**
 * This is a demo script for the functions of the PHP ESC/POS print driver,
 * Escpos.php.
 *
 * Most printers implement only a subset of the functionality of the driver, so
 * will not render this output correctly in all cases.
 *
 * @author Michael Billington <michael.billington@gmail.com>
 */
require __DIR__ . '/../vendor/autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\EscposImage;

$connector = new FilePrintConnector("php://stdout");
$printer = new Printer($connector);

/* Initialize */
$printer -> initialize();

/* Text */
$printer -> text("Hello world\n");
$printer -> cut();

/* Line feeds */
$printer -> text("ABC");
$printer -> feed(7);
$printer -> text("DEF");
$printer -> feedReverse(3);
$printer -> text("GHI");
$printer -> feed();
$printer -> cut();

/* Font modes */
$modes = array(
    Printer::MODE_FONT_B,
    Printer::MODE_EMPHASIZED,
    Printer::MODE_DOUBLE_HEIGHT,
    Printer::MODE_DOUBLE_WIDTH,
    Printer::MODE_UNDERLINE);
for ($i = 0; $i < pow(2, count($modes)); $i++) {
    $bits = str_pad(decbin($i), count($modes), "0", STR_PAD_LEFT);
    $mode = 0;
    for ($j = 0; $j < strlen($bits); $j++) {
        if (substr($bits, $j, 1) == "1") {
            $mode |= $modes[$j];
        }
    }
    $printer -> selectPrintMode($mode);
    $printer -> text("ABCDEFGHIJabcdefghijk\n");
}
$printer -> selectPrintMode(); // Reset
$printer -> cut();

/* Underline */
for ($i = 0; $i < 3; $i++) {
    $printer -> setUnderline($i);
    $printer -> text("The quick brown fox jumps over the lazy dog\n");
}
$printer -> setUnderline(0); // Reset
$printer -> cut();

/* Cuts */
$printer -> text("Partial cut\n(not available on all printers)\n");
$printer -> cut(Printer::CUT_PARTIAL);
$printer -> text("Full cut\n");
$printer -> cut(Printer::CUT_FULL);

/* Emphasis */
for ($i = 0; $i < 2; $i++) {
    $printer -> setEmphasis($i == 1);
    $printer -> text("The quick brown fox jumps over the lazy dog\n");
}
$printer -> setEmphasis(false); // Reset
$printer -> cut();

/* Double-strike (looks basically the same as emphasis) */
for ($i = 0; $i < 2; $i++) {
    $printer -> setDoubleStrike($i == 1);
    $printer -> text("The quick brown fox jumps over the lazy dog\n");
}
$printer -> setDoubleStrike(false);
$printer -> cut();

/* Fonts (many printers do not have a 'Font C') */
$fonts = array(
    Printer::FONT_A,
    Printer::FONT_B,
    Printer::FONT_C);
for ($i = 0; $i < count($fonts); $i++) {
    $printer -> setFont($fonts[$i]);
    $printer -> text("The quick brown fox jumps over the lazy dog\n");
}
$printer -> setFont(); // Reset
$printer -> cut();

/* Justification */
$justification = array(
    Printer::JUSTIFY_LEFT,
    Printer::JUSTIFY_CENTER,
    Printer::JUSTIFY_RIGHT);
for ($i = 0; $i < count($justification); $i++) {
    $printer -> setJustification($justification[$i]);
    $printer -> text("A man a plan a canal panama\n");
}
$printer -> setJustification(); // Reset
$printer -> cut();

/* Barcodes - see barcode.php for more detail */
$printer -> setBarcodeHeight(80);
$printer->setBarcodeTextPosition(Printer::BARCODE_TEXT_BELOW);
$printer -> barcode("9876");
$printer -> feed();
$printer -> cut();

/* Graphics - this demo will not work on some non-Epson printers */
try {
    $logo = EscposImage::load("resources/escpos-php.png", false);
    $imgModes = array(
        Printer::IMG_DEFAULT,
        Printer::IMG_DOUBLE_WIDTH,
        Printer::IMG_DOUBLE_HEIGHT,
        Printer::IMG_DOUBLE_WIDTH | Printer::IMG_DOUBLE_HEIGHT
    );
    foreach ($imgModes as $mode) {
        $printer -> graphics($logo, $mode);
    }
} catch (Exception $e) {
    /* Images not supported on your PHP, or image file not found */
    $printer -> text($e -> getMessage() . "\n");
}
$printer -> cut();

/* Bit image */
try {
    $logo = EscposImage::load("resources/escpos-php.png", false);
    $imgModes = array(
        Printer::IMG_DEFAULT,
        Printer::IMG_DOUBLE_WIDTH,
        Printer::IMG_DOUBLE_HEIGHT,
        Printer::IMG_DOUBLE_WIDTH | Printer::IMG_DOUBLE_HEIGHT
    );
    foreach ($imgModes as $mode) {
        $printer -> bitImage($logo, $mode);
    }
} catch (Exception $e) {
    /* Images not supported on your PHP, or image file not found */
    $printer -> text($e -> getMessage() . "\n");
}
$printer -> cut();

/* QR Code - see also the more in-depth demo at qr-code.php */
$testStr = "Testing 123";
$models = array(
    Printer::QR_MODEL_1 => "QR Model 1",
    Printer::QR_MODEL_2 => "QR Model 2 (default)",
    Printer::QR_MICRO => "Micro QR code\n(not supported on all printers)");
foreach ($models as $model => $name) {
    $printer -> qrCode($testStr, Printer::QR_ECLEVEL_L, 3, $model);
    $printer -> text("$name\n");
    $printer -> feed();
}
$printer -> cut();

/* Pulse */
$printer -> pulse();

/* Always close the printer! On some PrintConnectors, no actual
 * data is sent until the printer is closed. */
$printer -> close();
