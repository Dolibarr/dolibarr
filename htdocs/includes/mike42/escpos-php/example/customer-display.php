<?php
/**
 * This demo interacts with an Aures OCD-300 customer display,
 * showing its support for ESC/POS text encodings.
 */

require __DIR__ . '/../vendor/autoload.php';

use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\CapabilityProfile;
use Mike42\Escpos\Printer;
use Mike42\Escpos\Devices\AuresCustomerDisplay;

/*
 * Device appears as a serial port.
 * 
 *   stat /dev/ttyACM0
 *   sudo usermod -a -G dialout [username]
 */
$connector = new FilePrintConnector("/dev/ttyACM0");

// Profile and display
$profile = CapabilityProfile::load("OCD-300");
$display = new AuresCustomerDisplay($connector, $profile);


// Make a really long test string
include(__DIR__ . "/resources/character-encoding-test-strings.inc");
$input = "";
foreach ($inputsOk as $str) {
    $input .= $str;
}

// Wrap at a fixed width (as ASCII...), and show the user
// what's about to be sent to the printer
$wrapped = wordwrap($input, 20);
echo($wrapped);

// Roll out each line with 0.5s delay
foreach (explode("\n", $wrapped) as $line) {
    $display -> feed();
    $display -> text($line);
    usleep(500000);
}

// Finish by showing "Hello World"
$display -> clear();
$display -> text("Hello World\n");

// Dont forget to close the device
$display -> close();
