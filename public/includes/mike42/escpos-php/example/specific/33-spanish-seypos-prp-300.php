<?php
/*
 * Example of printing Spanish text on SEYPOS PRP-300 thermal line printer.
 * The characters in Spanish are available in code page 437, so no special
 * code pages are needed in this case (SimpleCapabilityProfile).
 *
 * Use the hardware switch to activate "Two-byte Character Code"
 */
require_once(dirname(__FILE__) . "/../../Escpos.php");
$connector = new FilePrintConnector("php://output");
$profile = SimpleCapabilityProfile::getInstance();
$printer = new Escpos($connector);
$printer -> text("El pingüino Wenceslao hizo kilómetros bajo exhaustiva lluvia y frío, añoraba a su querido cachorro.\n");
$printer -> cut();
$printer -> close();

