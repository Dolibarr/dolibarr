<?php 
require_once(dirname(__FILE__) . "/../../Escpos.php");
/* This example shows the printing of Latvian text on the Star TUP 592 printer */
$profile = StarCapabilityProfile::getInstance();

/* Option 1: Native character encoding */
$connector = new FilePrintConnector("php://stdout");
$printer = new Escpos($connector, $profile);
$printer -> text("Glāžšķūņa rūķīši dzērumā čiepj Baha koncertflīģeļu vākus\n");
$printer -> cut();
$printer -> close();

/* Option 2: Image-based output (formatting not available using this output) */
$buffer = new ImagePrintBuffer();
$connector = new FilePrintConnector("php://stdout");
$printer = new Escpos($connector, $profile);
$printer -> setPrintBuffer($buffer);
$printer -> text("Glāžšķūņa rūķīši dzērumā čiepj Baha koncertflīģeļu vākus\n");
$printer -> cut();
$printer -> close();
?>