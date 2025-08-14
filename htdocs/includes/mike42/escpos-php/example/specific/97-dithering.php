<?php
/*
 * Example of dithering used in EscposImage by default, if you have Imagick loaded.
 */
require __DIR__ . '/../../vendor/autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;

$connector = new FilePrintConnector("/dev/usb/lp0");
$printer = new Printer($connector);
try {
    /*  Load with optimisations enabled. If you have Imagick, this will get you
        a nicely dithered image, which prints very quickly
    */
    $img1 = EscposImage::load(__DIR__ . '/../resources/tulips.png');
    $printer -> bitImage($img1);
    
    /*  Load with optimisations disabled, forcing the use of PHP to convert the
        pixels, which uses a threshold and is much slower.
    */
    $img2 = EscposImage::load(__DIR__ . '/../resources/tulips.png', false);
    $printer -> bitImage($img2);
    $printer -> cut();
} finally {
    /* Always close the printer! */
    $printer -> close();
}
