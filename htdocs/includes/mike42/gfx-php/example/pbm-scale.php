<?php
require_once(__DIR__ . "/../vendor/autoload.php");

use Mike42\GfxPhp\Image;

// Write original back
$img = Image::fromFile(dirname(__FILE__). "/resources/5x7hex.pbm");
$img -> write("font-original.pbm");

// Scale a black and white image
$img = Image::fromFile(dirname(__FILE__). "/resources/5x7hex.pbm");
$img2 = $img -> scale(40, 4);
$img2 -> write("font-small.pbm");

$img = Image::fromFile(dirname(__FILE__). "/resources/5x7hex.pbm");
$img3 = $img -> scale(160, 14);
$img3 -> write("font-large.pbm");
