<?php
/* Example of printing the GBP pound symbol on a STAR TSP650
 * 
 * In this example, it's shown how to check that your PHP files are actually being
 * saved in unicode. Sections B) and C) are identical in UTF-8, but different
 * if you are saving to a retro format like Windows-1252.
 */

// Adjust these to your environment
require __DIR__ . '/../../autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\CapabilityProfiles\SimpleCapabilityProfile;

$connector = new FilePrintConnector("php://stdout");

// Start printer
$profile = SimpleCapabilityProfile::getInstance();
$printer = new Printer($connector, $profile);

// A) Raw pound symbol
// This is the most likely thing to work, and bypasses all the fancy stuff.
$printer -> textRaw("\x9C"); // based on position in CP437
$printer -> text(" 1.95\n");

// B) Manually encoded UTF8 pound symbol. Tests that the driver correctly
//		encodes this as CP437.
$printer -> text(base64_decode("wqM=") . " 2.95\n");

// C) Pasted in file. Tests that your files are being saved as UTF-8, which
// 		escpos-php is able to convert automatically to a mix of code pages.
$printer -> text("£ 3.95\n");

$printer -> cut();
$printer -> close();
