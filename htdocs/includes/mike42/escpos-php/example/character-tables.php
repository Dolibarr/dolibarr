<?php
/**
 * This demo prints out supported code pages on your printer. This is intended
 * for debugging character-encoding issues: If your printer does not work with
 * a built-in capability profile, you need to check its documentation for
 * supported code pages.
 *
 * These are then loaded into a capability profile, which maps code page
 * numbers to iconv encoding names on your particular printer. This script
 * will print all configured code pages, so that you can check that the chosen
 * iconv encoding name matches the actual code page contents.
 *
 * If this is correctly set up for your printer, then the driver will try its
 * best to map UTF-8 text into these code pages for you, allowing you to accept
 * arbitrary input from a database, without worrying about encoding it for the printer.
 */
require __DIR__ . '/../vendor/autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\CapabilityProfile;

// Enter connector and capability profile (to match your printer)
$connector = new FilePrintConnector("php://stdout");
$profile = CapabilityProfile::load("default");
$verbose = false; // Skip tables which iconv wont convert to (ie, only print characters available with UTF-8 input)

/* Print a series of receipts containing i18n example strings - Code below shouldn't need changing */
$printer = new Mike42\Escpos\Printer($connector, $profile);
$codePages = $profile -> getCodePages();
$first = true; // Print larger table for first code-page.
foreach ($codePages as $table => $page) {
    /* Change printer code page */
    $printer -> selectCharacterTable(255);
    $printer -> selectCharacterTable($table);
    /* Select & print a label for it */
    $label = $page -> getId();
    if (!$page -> isEncodable()) {
        $label= " (not supported)";
    }
    $printer -> setEmphasis(true);
    $printer -> textRaw("Table $table: $label\n");
    $printer -> setEmphasis(false);
    if (!$page -> isEncodable() && !$verbose) {
        continue; // Skip non-recognised
    }
    /* Print a table of available characters (first table is larger than subsequent ones */
    if ($first) {
        $first = false;
        compactCharTable($printer, 1, true);
    } else {
        compactCharTable($printer);
    }
}
$printer -> cut();
$printer -> close();

function compactCharTable($printer, $start = 4, $header = false)
{
    /* Output a compact character table for the current encoding */
    $chars = str_repeat(' ', 256);
    for ($i = 0; $i < 255; $i++) {
        $chars[$i] = ($i > 32 && $i != 127) ? chr($i) : ' ';
    }
    if ($header) {
        $printer -> setEmphasis(true);
        $printer -> textRaw("  0123456789ABCDEF0123456789ABCDEF\n");
        $printer -> setEmphasis(false);
    }
    for ($y = $start; $y < 8; $y++) {
        $printer -> setEmphasis(true);
        $printer -> textRaw(strtoupper(dechex($y * 2)) . " ");
        $printer -> setEmphasis(false);
        $printer -> textRaw(substr($chars, $y * 32, 32) . "\n");
    }
}
