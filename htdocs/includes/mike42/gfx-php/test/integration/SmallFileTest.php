<?php

use Mike42\GfxPhp\Image;
use PHPUnit\Framework\TestCase;

class SmallFileTest extends TestCase
{
    function loadImage(string $filename) {
        $img = Image::fromFile(__DIR__ . "/../resources/small-files/$filename");
        return $img -> toBlackAndWhite();
    }

    function testWhite() {
        $expected = " \n";
        $width = 1;
        $height = 1;
        foreach(['canvas_white.gif', 'canvas_white.png'] as $fn) { // Also jpg and bmp
            $img = $this -> loadImage($fn);
            $this -> assertEquals($expected, $img -> toString());
            $this -> assertEquals($height, $img -> getHeight());
            $this -> assertEquals($width, $img -> getWidth());
        }
    }

    function testBlack() {
        $expected = "▀\n";
        $width = 1;
        $height = 1;
        foreach(['canvas_black.gif', 'canvas_black.png'] as $fn) { // Also jpg and bmp
            $img = $this -> loadImage($fn);
            $this -> assertEquals($expected, $img -> toString());
            $this -> assertEquals($height, $img -> getHeight());
            $this -> assertEquals($width, $img -> getWidth());
        }
    }

    function testBlackWhite() {
        $expected = "▀▀\n";
        $width = 2;
        $height = 2;
        foreach(['black_white.gif', 'black_white.png'] as $fn) { // Also jpg and bmp
            $img = $this -> loadImage($fn);
            $this -> assertEquals($expected, $img -> toString());
            $this -> assertEquals($height, $img -> getHeight());
            $this -> assertEquals($width, $img -> getWidth());
        }
    }

    function testBlackTransparent() {
        $expected = "▀▀\n";
        $width = 2;
        $height = 2;
        foreach(['black_transparent.gif', 'black_transparent.png'] as $fn) { // Also jpg and bmp
            $img = $this -> loadImage($fn);
            $this -> assertEquals($expected, $img -> toString());
            $this -> assertEquals($height, $img -> getHeight());
            $this -> assertEquals($width, $img -> getWidth());
        }
    }

    function testBlackWhiteTall() {
        $expected = "██\n" .
                    "██\n" .
                    "██\n" .
                    "██\n" .
                    "  \n" .
                    "  \n" .
                    "  \n" .
                    "  \n";
        $width = 2;
        $height = 16;
        $img = $this -> loadImage('black_white_tall.png');
        $this -> assertEquals($expected, $img -> toString());
        $this -> assertEquals($height, $img -> getHeight());
        $this -> assertEquals($width, $img -> getWidth());
    }
}