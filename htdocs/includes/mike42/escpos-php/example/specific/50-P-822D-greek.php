<?php
/* Example of Greek text on the P-822D */
require_once(dirname(__FILE__) . "/../../Escpos.php");

// Setup the printer
$connector = new FilePrintConnector("php://stdout");
$profile = P822DCapabilityProfile::getInstance();
$printer = new Escpos($connector, $profile);

// Print a Greek pangram
$text = "Ξεσκεπάζω την ψυχοφθόρα βδελυγμία";
$printer -> text($text . "\n");
$printer -> cut();

// Close the connection
$printer -> close();
