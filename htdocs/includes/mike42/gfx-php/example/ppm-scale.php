<?php
require_once(__DIR__ . "/../vendor/autoload.php");

use Mike42\GfxPhp\Image;

// Write original back
$img = Image::fromFile(dirname(__FILE__). "/resources/colorwheel.ppm");
$img -> write("colorwheel-original.ppm");

// Scale a color image
$img = Image::fromFile(dirname(__FILE__). "/resources/colorwheel.ppm");
$img2 = $img -> scale(20, 20);
$img2 -> write("colorwheel-small.ppm");

$img = Image::fromFile(dirname(__FILE__). "/resources/colorwheel.ppm");
$img3 = $img -> scale(60, 60);
$img3 -> write("colorwheel-large.ppm");
