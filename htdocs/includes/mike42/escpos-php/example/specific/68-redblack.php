<?php
/*
 * Example of two-color printing, tested on an epson TM-U220 with two-color ribbon installed.
 */
require __DIR__ . '/../../autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;

$connector = new FilePrintConnector("/dev/usb/lp0");
$printer = new Printer($connector);
try {
    $printer -> text("Hello World!\n");
    $printer -> setColor(Escpos::COLOR_2);
    $printer -> text("Red?!\n");
    $printer -> setColor(Escpos::COLOR_1);
    $printer -> text("Default color again?!\n");
    $printer -> cut();
} finally {
    /* Always close the printer! */
    $printer -> close();
}
