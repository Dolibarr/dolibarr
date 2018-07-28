<?php
require __DIR__ . '/../../autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\CapabilityProfiles\DefaultCapabilityProfile;

$connector = new FilePrintConnector("php://stdout");
$profile = DefaultCapabilityProfile::getInstance();
$printer = new Printer($connector, $profile);

$printer -> text("Μιχάλης Νίκος\n");
$printer -> cut();
$printer -> close();

?>
