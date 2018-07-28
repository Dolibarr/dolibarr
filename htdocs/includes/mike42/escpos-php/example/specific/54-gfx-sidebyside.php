<?php
/*
 * Example of calling ImageMagick 'convert' to manipulate an image prior to printing.
 * 
 * Written as an example to remind you to do four things-
 * - escape your command-line arguments with escapeshellarg
 * - close the printer
 * - delete any temp files
 * - detect and handle external command failure
 *
 * Note that image operations are slow. You can and should serialise an EscposImage
 * object into some sort of cache if you will re-use the output.
 */
require __DIR__ . '/../../autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\CapabilityProfiles\DefaultCapabilityProfile;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;

// Paths to images to combine
$img1_path = dirname(__FILE__) . "/../resources/tux.png";
$img2_path = dirname(__FILE__) . "/../resources/escpos-php.png";

// Set up temp file with .png extension
$tmpf_path = tempnam(sys_get_temp_dir(), 'escpos-php');
$imgCombined_path = $tmpf_path . ".png";

try {
    // Convert, load image, remove temp files
    $cmd = sprintf(
        "convert %s %s +append %s",
        escapeshellarg($img1_path),
        escapeshellarg($img2_path),
        escapeshellarg($imgCombined_path)
    );
    exec($cmd, $outp, $retval);
    if ($retval != 0) {
        // Detect and handle command failure
        throw new Exception("Command \"$cmd\" returned $retval." . implode("\n", $outp));
    }
    $img = new EscposImage($imgCombined_path);

    // Setup the printer
    $connector = new FilePrintConnector("php://stdout");
    $profile = DefaultCapabilityProfile::getInstance();

    // Run the actual print
    $printer = new Printer($connector, $profile);
    try {
        $printer -> setJustification(Printer::JUSTIFY_CENTER);
        $printer -> graphics($img);
        $printer -> cut();
    } finally {
        // Always close the connection
        $printer -> close();
    }
} catch (Exception $e) {
    // Print out any errors: Eg. printer connection, image loading & external image manipulation.
    echo $e -> getMessage() . "\n";
    echo $e -> getTraceAsString();
} finally {
    unlink($imgCombined_path);
    unlink($tmpf_path);
}
