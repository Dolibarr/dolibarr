<?php
/* Example of Greek text on the P-822D */
require __DIR__ . '/../../autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\CapabilityProfiles\P822DCapabilityProfile;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;

// Setup the printer
$connector = new FilePrintConnector("php://stdout");
$profile = P822DCapabilityProfile::getInstance();
$printer = new Printer($connector, $profile);

// Print a Greek pangram
$text = "Ξεσκεπάζω την ψυχοφθόρα βδελυγμία";
$printer -> text($text . "\n");
$printer -> cut();

// Close the connection
$printer -> close();
