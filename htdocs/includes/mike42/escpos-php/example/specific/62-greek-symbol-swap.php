<?php
require __DIR__ . '/../../autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\CapabilityProfile;

$connector = new FilePrintConnector("php://stdout");
$profile = CapabilityProfile::load("default");
$printer = new Printer($connector, $profile);

$printer -> text("Μιχάλης Νίκος\n");
$printer -> cut();
$printer -> close();

?>
