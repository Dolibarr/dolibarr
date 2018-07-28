<?php
/**
 * This print-out shows how large the available font sizes are. It is included
 * separately due to the amount of text it prints.
 *
 * @author Michael Billington <michael.billington@gmail.com>
 */
require __DIR__ . '/../autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;

$connector = new FilePrintConnector("php://stdout");
$printer = new Printer($connector);

/* Initialize */
$printer -> initialize();

/* Text of various (in-proportion) sizes */
title($printer, "Change height & width\n");
for ($i = 1; $i <= 8; $i++) {
    $printer -> setTextSize($i, $i);
    $printer -> text($i);
}
$printer -> text("\n");

/* Width changing only */
title($printer, "Change width only (height=4):\n");
for ($i = 1; $i <= 8; $i++) {
    $printer -> setTextSize($i, 4);
    $printer -> text($i);
}
$printer -> text("\n");

/* Height changing only */
title($printer, "Change height only (width=4):\n");
for ($i = 1; $i <= 8; $i++) {
    $printer -> setTextSize(4, $i);
    $printer -> text($i);
}
$printer -> text("\n");

/* Very narrow text */
title($printer, "Very narrow text:\n");
$printer -> setTextSize(1, 8);
$printer -> text("The quick brown fox jumps over the lazy dog.\n");

/* Very flat text */
title($printer, "Very wide text:\n");
$printer -> setTextSize(4, 1);
$printer -> text("Hello world!\n");

/* Very large text */
title($printer, "Largest possible text:\n");
$printer -> setTextSize(8, 8);
$printer -> text("Hello\nworld!\n");

$printer -> cut();
$printer -> close();

function title(Printer $printer, $text)
{
    $printer -> selectPrintMode(Printer::MODE_EMPHASIZED);
    $printer -> text("\n" . $text);
    $printer -> selectPrintMode(); // Reset
}
