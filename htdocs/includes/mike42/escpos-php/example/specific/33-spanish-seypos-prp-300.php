<?php
/*
 * Example of printing Spanish text on SEYPOS PRP-300 thermal line printer.
 * The characters in Spanish are available in code page 437, so no special
 * code pages are needed in this case (SimpleCapabilityProfile).
 *
 * Use the hardware switch to activate "Two-byte Character Code"
 */
require __DIR__ . '/../../vendor/autoload.php';
use Mike42\Escpos\CapabilityProfile;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;

$connector = new FilePrintConnector("php://output");
$profile = CapabilityProfile::load("simple"); // Works for Epson printers
$printer = new Printer($connector);
$printer -> text("El pingüino Wenceslao hizo kilómetros bajo exhaustiva lluvia y frío, añoraba a su querido cachorro.\n");
$printer -> cut();
$printer -> close();
