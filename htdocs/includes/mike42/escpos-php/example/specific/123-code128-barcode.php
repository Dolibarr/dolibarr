<?php
require __DIR__ . '/../../autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;

$a = "{A012323392982";
$b = "{B012323392982";
$c = "{C" . chr(01) . chr(23) . chr(23) . chr(39) . chr(29) . chr(82);

$connector = new FilePrintConnector("php://stdout");
$printer = new Printer($connector);
$printer -> setJustification(Printer::JUSTIFY_CENTER);
$printer -> setBarcodeHeight(48);
$printer->setBarcodeTextPosition(Printer::BARCODE_TEXT_BELOW);
foreach(array($a, $b, $c) as $item)  {
    $printer -> barcode($item, Printer::BARCODE_CODE128);
    $printer -> feed(1);
}
$printer -> cut();
$printer -> close();

