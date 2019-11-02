<?php
/*
 * Example of one way you could load a PNG data URI into an EscposImage object
 * without using a file.
 */
require __DIR__ . '/../../autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\ImagickEscposImage;

// Data URI for a PNG image (red dot from https://en.wikipedia.org/wiki/Data_URI_scheme )
$uri = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUA
AAAFCAYAAACNbyblAAAAHElEQVQI12P4//8/w38GIAXDIBKE0DHxgljNBAAO
9TXL0Y4OHwAAAABJRU5ErkJggg==";

// Convert data URI to binary data
$imageBlob = base64_decode(explode(",", $uri)[1]);

// Give Imagick a filename with the correct extension to stop it from attempting
// to identify the format itself (this avoids CVE-2016–3714)
$imagick = new Imagick();
$imagick -> setResourceLimit(6, 1); // Prevent libgomp1 segfaults, grumble grumble.
$imagick -> readImageBlob($imageBlob, "input.png");

// Load Imagick straight into an EscposImage object
$im = new ImagickEscposImage();
$im -> readImageFromImagick($imagick);

// Do a test print to make sure that this EscposImage object has the right data
// (should see a tiny bullet point)
$connector = new FilePrintConnector("php://output");
$printer = new Printer($connector);
$printer -> bitImage($im);
$printer -> cut();
$printer -> close();
