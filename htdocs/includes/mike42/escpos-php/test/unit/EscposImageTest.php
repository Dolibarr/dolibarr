<?php
use Mike42\Escpos\EscposImage;

class EscposImageTest extends PHPUnit_Framework_TestCase
{
    public function testImageMissingException()
    {
        $this -> setExpectedException('Exception');
        $img = EscposImage::load('not-a-real-file.png');
    }
    public function testImageNotSupportedException()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $img = EscposImage::load('/dev/null', false, array());
    }
}