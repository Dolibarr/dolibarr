<?php
/*
 * This is an example of printing chinese text. This is a bit different to other character encodings, because
 * the printer accepts a 2-byte character encoding (GBK), and formatting is handled differently while in this mode.
 *
 * At the time of writing, this is implemented separately as a textChinese() function, until chinese text
 * can be properly detected and printed alongside other encodings.
 */
require __DIR__ . '/../../vendor/autoload.php';
use Mike42\Escpos\CapabilityProfile;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;

$connector = new FilePrintConnector("/dev/usb/lp1");
$profile = CapabilityProfile::load("default");

$printer = new Printer($connector);

// Example text from #37
$printer -> textChinese("艾德蒙 AOC E2450SWH 23.6吋 LED液晶寬螢幕特價$ 19900\n\n");

// Note that on the printer tested (ZJ5890), the font only contained simplified characters.
$printer -> textChinese("示例文本打印机!\n\n");
$printer -> close();
