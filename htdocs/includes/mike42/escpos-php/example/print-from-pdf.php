<?php
require __DIR__ . '/../vendor/autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\ImagickEscposImage;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;

/*
 * This is three examples in one:
 *  1: Print an entire PDF, normal quality.
 *  2: Print at a lower quality for speed increase (CPU & transfer)
 *  3: Cache rendered documents for a speed increase (removes CPU image processing completely on subsequent prints)
 */

/* 1: Print an entire PDF, start-to-finish (shorter form of the example) */
$pdf = 'resources/document.pdf';
$connector = new FilePrintConnector("php://stdout");
$printer = new Printer($connector);
try {
    $pages = ImagickEscposImage::loadPdf($pdf);
    foreach ($pages as $page) {
        $printer -> graphics($page);
    }
    $printer -> cut();
} catch (Exception $e) {
    /*
	 * loadPdf() throws exceptions if files or not found, or you don't have the
	 * imagick extension to read PDF's
	 */
    echo $e -> getMessage() . "\n";
} finally {
    $printer -> close();
}


/*
 * 2: Speed up printing by roughly halving the resolution, and printing double-size.
 * This gives a 75% speed increase at the expense of some quality.
 * 
 * Reduce the page width further if necessary: if it extends past the printing area, your prints will be very slow.
 */
$connector = new FilePrintConnector("php://stdout");
$printer = new Printer($connector);
$pdf = 'resources/document.pdf';
$pages = ImagickEscposImage::loadPdf($pdf, 260);
foreach ($pages as $page) {
    $printer -> graphics($page, Printer::IMG_DOUBLE_HEIGHT | Printer::IMG_DOUBLE_WIDTH);
}
$printer -> cut();
$printer -> close();

/*
 * 3: PDF printing still too slow? If you regularly print the same files, serialize & compress your
 * EscposImage objects (after printing[1]), instead of throwing them away.
 * 
 * (You can also do this to print logos on computers which don't have an
 * image processing library, by preparing a serialized version of your logo on your PC)
 * 
 * [1]After printing, the pixels are loaded and formatted for the print command you used, so even a raspberry pi can print complex PDF's quickly.
 */
$connector = new FilePrintConnector("php://stdout");
$printer = new Printer($connector);
$pdf = 'resources/document.pdf';
$ser = 'resources/document.z';
if (!file_exists($ser)) {
    $pages = ImagickEscposImage::loadPdf($pdf);
} else {
    $pages = unserialize(gzuncompress(file_get_contents($ser)));
}

foreach ($pages as $page) {
    $printer -> graphics($page);
}
$printer -> cut();
$printer -> close();

if (!file_exists($ser)) {
    file_put_contents($ser, gzcompress(serialize($pages)));
}
