<?php
/* Demonstration of available options on the qrCode() command */
require __DIR__ . '/../autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;

$connector = new FilePrintConnector("php://stdout");
$printer = new Printer($connector);

// Most simple example
title($printer, "QR code demo\n");
$testStr = "Testing 123";
$printer -> qrCode($testStr);
$printer -> text("Most simple example\n");
$printer -> feed();

// Demo that alignment is the same as text
$printer -> setJustification(Printer::JUSTIFY_CENTER);
$printer -> qrCode($testStr);
$printer -> text("Same example, centred\n");
$printer -> setJustification();
$printer -> feed();
    
// Demo of numeric data being packed more densly
title($printer, "Data encoding\n");
$test = array(
    "Numeric"      => "0123456789012345678901234567890123456789",
    "Alphanumeric" => "abcdefghijklmnopqrstuvwxyzabcdefghijklmn",
    "Binary"       => str_repeat("\0", 40));
foreach ($test as $type => $data) {
    $printer -> qrCode($data);
    $printer -> text("$type\n");
    $printer -> feed();
}

// Demo of error correction
title($printer, "Error correction\n");
$ec = array(
    Printer::QR_ECLEVEL_L => "L",
    Printer::QR_ECLEVEL_M => "M",
    Printer::QR_ECLEVEL_Q => "Q",
    Printer::QR_ECLEVEL_H => "H");
foreach ($ec as $level => $name) {
    $printer -> qrCode($testStr, $level);
    $printer -> text("Error correction $name\n");
    $printer -> feed();
}

// Change size
title($printer, "Pixel size\n");
$sizes = array(
    1 => "(minimum)",
    2 => "",
    3 => "(default)",
    4 => "",
    5 => "",
    10 => "",
    16 => "(maximum)");
foreach ($sizes as $size => $label) {
    $printer -> qrCode($testStr, Printer::QR_ECLEVEL_L, $size);
    $printer -> text("Pixel size $size $label\n");
    $printer -> feed();
}

// Change model
title($printer, "QR model\n");
$models = array(
    Printer::QR_MODEL_1 => "QR Model 1",
    Printer::QR_MODEL_2 => "QR Model 2 (default)",
    Printer::QR_MICRO => "Micro QR code\n(not supported on all printers)");
foreach ($models as $model => $name) {
    $printer -> qrCode($testStr, Printer::QR_ECLEVEL_L, 3, $model);
    $printer -> text("$name\n");
    $printer -> feed();
}

// Cut & close
$printer -> cut();
$printer -> close();

function title(Printer $printer, $str)
{
    $printer -> selectPrintMode(Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_DOUBLE_WIDTH);
    $printer -> text($str);
    $printer -> selectPrintMode();
}
