<?php
// Read an image file,
require_once("../../vendor/autoload.php");
use Mike42\GfxPhp\Image;

if(!isset($argv[1])) {
    die("Usage " . $argv[0] . " IMAGE\n");
}
$filename = $argv[1];
$outp = basename($filename, '.png');
echo "Opening file $filename\n";
$img = Image::fromFile($filename);
$img -> write('out/' . $outp . ".ppm");
echo $img -> toBlackAndWhite() -> toString() . "\n";

