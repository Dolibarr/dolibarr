<?php
/*
 * Example showing how to return binary data back to the user.
 * 
 * This is intended for the "Star TSP650IIcloudPRNT" printer.
 */
require __DIR__ . '/../../vendor/autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\DummyPrintConnector;
use Mike42\Escpos\CapabilityProfile;

// Make sure you load a Star print connector or you may get gibberish.
$connector = new DummyPrintConnector();
$profile = CapabilityProfile::load("TSP600");
$printer = new Printer($connector);
$printer -> text("Hello world!\n");
$printer -> cut();

// Get the data out as a string
$data = $connector -> getData();

// Return it, check the manual for specifics.
header('Content-type: application/octet-stream');
header('Content-Length: '.strlen($data));
echo $data;

// Close the printer when done.
$printer -> close();
