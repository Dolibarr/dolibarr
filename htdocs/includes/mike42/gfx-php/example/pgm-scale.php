<?php
require_once(__DIR__ . "/../vendor/autoload.php");

use Mike42\GfxPhp\Image;

// Write original back
$img = Image::fromFile(dirname(__FILE__). "/resources/gradient.pgm");
$img -> write("gradient-original.pgm");

// Scale a gray image
$img = Image::fromFile(dirname(__FILE__). "/resources/gradient.pgm");
$img2 = $img -> scale(20, 20);
$img2 -> write("gradient-small.pgm");

$img = Image::fromFile(dirname(__FILE__). "/resources/gradient.pgm");
$img3 = $img -> scale(60, 60);
$img3 -> write("gradient-large.pgm");
