<?php
require_once(__DIR__ . "/../vendor/autoload.php");

use Mike42\GfxPhp\Image;

// Write colorwheel.ppm out as each supported format
$img = Image::fromFile(dirname(__FILE__). "/resources/colorwheel.ppm");
$img -> write("colorwheel.bmp");
$img -> write("colorwheel.gif");
$img -> write("colorwheel.pbm");
$img -> write("colorwheel.pgm");
$img -> write("colorwheel.png");
$img -> write("colorwheel.ppm");
$img -> write("colorwheel.wbmp");

// Write gradient.pgm out as each supported format
$img = Image::fromFile(dirname(__FILE__). "/resources/gradient.pgm");
$img -> write("gradient.bmp");
$img -> write("gradient.gif");
$img -> write("gradient.pbm");
$img -> write("gradient.pgm");
$img -> write("gradient.png");
$img -> write("gradient.ppm");
$img -> write("gradient.wbmp");

// Write 5x7hex.pbm out as each supported format
$img = Image::fromFile(dirname(__FILE__). "/resources/5x7hex.pbm");
$img -> write("font.bmp");
$img -> write("font.gif");
$img -> write("font.pbm");
$img -> write("font.pgm");
$img -> write("font.png");
$img -> write("font.ppm");
$img -> write("font.wbmp");

// Write abc.png out as each supported format
$img = Image::fromFile(dirname(__FILE__). "/resources/abc.png");
$img -> write("abc.bmp");
$img -> write("abc.gif");
$img -> write("abc.pbm");
$img -> write("abc.pgm");
$img -> write("abc.png");
$img -> write("abc.ppm");
$img -> write("abc.wbmp");

// Write bricks.wbmp out as each supported format
$img = Image::fromFile(dirname(__FILE__). "/resources/bricks.wbmp");
$img -> write("bricks.bmp");
$img -> write("bricks.gif");
$img -> write("bricks.pbm");
$img -> write("bricks.pgm");
$img -> write("bricks.png");
$img -> write("bricks.ppm");
$img -> write("bricks.wbmp");
