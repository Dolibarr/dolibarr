<?php
/* Demonstration of available options on the pdf417Code() command */
require __DIR__ . '/../autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;

$connector = new FilePrintConnector("php://stdout");
$printer = new Printer($connector);

// Most simple example
title($printer, "PDF417 code demo\n");
$testStr = "Testing 123";
$printer -> pdf417Code($testStr);
$printer -> text("Most simple example\n");
$printer -> feed();

// Demo that alignment is the same as text
$printer -> setJustification(Printer::JUSTIFY_CENTER);
$printer -> pdf417Code($testStr, 3, 3, 2);
$printer -> text("Same content, narrow and centred\n");
$printer -> setJustification();
$printer -> feed();

// Demo of error correction
title($printer, "Error correction\n");
$ec = array(0.1, 0.5, 1.0, 2.0, 4.0);
foreach ($ec as $level) {
    $printer -> pdf417Code($testStr, 3, 3, 0, $level);
    $printer -> text("Error correction ratio $level\n");
    $printer -> feed();
}

// Change size
title($printer, "Pixel size\n");
$sizes = array(
    2 => "(minimum)",
    3 => "(default)",
    4 => "",
    8 => "(maximum)");
foreach ($sizes as $size => $label) {
    $printer -> pdf417Code($testStr, $size);
    $printer -> text("Module width $size dots $label\n");
    $printer -> feed();
}

// Change height
title($printer, "Height multiplier\n");
$sizes = array(
    2 => "(minimum)",
    3 => "(default)",
    4 => "",
    8 => "(maximum)");
foreach ($sizes as $size => $label) {
    $printer -> pdf417Code($testStr, 3, $size);
    $printer -> text("Height multiplier $size $label\n");
    $printer -> feed();
}

// Chage data column count
title($printer, "Data column count\n");
$columnCounts = array(
    0 => "(auto, default)",
    1 => "",
    2 => "",
    3 => "",
    4 => "",
    5 => "",
    30 => "(maximum, doesnt fit!)");
foreach ($columnCounts as $columnCount => $label) {
    $printer -> pdf417Code($testStr, 3, 3, $columnCount);
    $printer -> text("Column count $columnCount $label\n");
    $printer -> feed();
}

// Change options
title($printer, "Options\n");
$models = array(
    Printer::PDF417_STANDARD => "Standard",
    Printer::PDF417_TRUNCATED => "Truncated");
foreach ($models as $model => $name) {
    $printer -> pdf417Code($testStr, 3, 3, 0, 0.10, $model);
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
