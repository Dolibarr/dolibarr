<?php

use Mike42\GfxPhp\Image;
use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase
{
    public function testBadFilename() {
        $this -> expectException(Exception::class);
        Image::fromFile("not a real file");
    }
}