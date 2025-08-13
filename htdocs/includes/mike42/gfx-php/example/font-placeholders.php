<?php
require_once(__DIR__ . "/../vendor/autoload.php");

use Mike42\GfxPhp\Image;

// Inputs
$outFile = "out.pbm";
$font = Image::fromFile(dirname(__FILE__). "/resources/5x7hex.pbm");
$codePoint = str_split("0A2F");
$charWidth = 5;
$charHeight = 7;

// Create small image for each character
$chars = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "A", "B", "C", "D", "E", "F"];
$subImages = [];
for ($i = 0; $i < count($codePoint); $i++) {
    $id = array_search($codePoint[$i], $chars);
    if ($id === false) {
        throw new Exception("Don't know how to encode " . $codePoint[$i]);
    }
    $subImages[] = $font -> subImage($id * $charWidth, 0, $charWidth, $charHeight);
}

// Place four images in a box
$out = Image::create(18, 17, Image::IMAGE_BLACK_WHITE);
$out -> rect(0, 0, 18, 17);
$out -> rect(3, 0, 12, 17, true, 0, 0);
$out -> compose($subImages[0], 0, 0, 4, 1, $charWidth, $charHeight);
$out -> compose($subImages[1], 0, 0, 10, 1, $charWidth, $charHeight);
$out -> compose($subImages[2], 0, 0, 4, 9, $charWidth, $charHeight);
$out -> compose($subImages[3], 0, 0, 10, 9, $charWidth, $charHeight);

# Print output for debugging ;)
echo $out -> toString();
$out -> write($outFile);
