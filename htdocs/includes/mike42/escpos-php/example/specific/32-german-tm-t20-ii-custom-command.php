<?php
require __DIR__ . '/../../vendor/autoload.php';
use Mike42\Escpos\CapabilityProfile;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;

/*
 * This example shows how tok send a custom command to the printer-
 * The use case here is an Epson TM-T20II and German text.
 * 
 * Background: Not all ESC/POS features are available in the driver, so sometimes
 * you might want to send a custom commnad. This is useful for testing
 * new features.
 * 
 * The Escpos::text() function removes non-printable characters as a precaution,
 * so that commands cannot be injected into user input. To send raw data to
 * the printer, you need to write bytes to the underlying PrintConnector.
 * 
 * If you get a new command working, please file an issue on GitHub with a code
 * snippet so that it can be incorporated into escpos-php.
 */

/* Set up profile & connector */
$connector = new FilePrintConnector("php://output");
$profile = CapabilityProfile::load("default"); // Works for Epson printers

$printer = new Printer($connector, $profile);
$cmd = Printer::ESC . "V" . chr(1); // Try out 90-degree rotation.
$printer -> getPrintConnector() -> write($cmd);
$printer -> text("Beispieltext in Deutsch\n");
$printer -> cut();
$printer -> close();
/*
 * Hex-dump of output confirms that ESC V 1 being sent:
 *
 * 0000000 033   @ 033   V 001   B   e   i   s   p   i   e   l   t   e   x
 * 0000010   t       i   n       D   e   u   t   s   c   h  \n 035   V   A
 * 0000020 003
 */
