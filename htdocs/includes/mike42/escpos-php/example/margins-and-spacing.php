<?php
/* Left margin & page width demo. */
require __DIR__ . '/../vendor/autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;

$connector = new FilePrintConnector("php://stdout"); // Add connector for your printer here.
$printer = new Printer($connector);

/* Line spacing */
/*
$printer -> setEmphasis(true);
$printer -> text("Line spacing\n");
$printer -> setEmphasis(false);
foreach(array(16, 32, 64, 128, 255) as $spacing) {
    $printer -> setLineSpacing($spacing);
    $printer -> text("Spacing $spacing: The quick brown fox jumps over the lazy dog. The quick brown fox jumps over the lazy dog.\n");
}
$printer -> setLineSpacing(); // Back to default
*/

/* Stuff around with left margin */
$printer -> setEmphasis(true);
$printer -> text("Left margin\n");
$printer -> setEmphasis(false);
$printer -> text("Default left\n");
foreach(array(1, 2, 4, 8, 16, 32, 64, 128, 256, 512) as $margin) {
    $printer -> setPrintLeftMargin($margin);
    $printer -> text("left margin $margin\n");
}
/* Reset left */
$printer -> setPrintLeftMargin(0);

/* Stuff around with page width */
$printer -> setEmphasis(true);
$printer -> text("Page width\n");
$printer -> setEmphasis(false);
$printer -> setJustification(Printer::JUSTIFY_RIGHT);
$printer -> text("Default width\n");
foreach(array(512, 256, 128, 64) as $width) {
    $printer -> setPrintWidth($width);
    $printer -> text("page width $width\n");
}

/* Printer shutdown */
$printer -> cut();
$printer -> close();

