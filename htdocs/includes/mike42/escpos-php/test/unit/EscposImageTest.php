<?php
use Mike42\Escpos\EscposImage;

class EscposImageTest extends PHPUnit\Framework\TestCase
{
    public function testImageMissingException()
    {
        $this -> expectException(Exception::class);
        $img = EscposImage::load('not-a-real-file.png');
    }
    public function testImageNotSupportedException()
    {
        $this -> expectException(InvalidArgumentException::class);
        $img = EscposImage::load('/dev/null', false, array());
    }
}