<?php
use Mike42\Escpos\CodePage;

class CodePageTest extends PHPUnit_Framework_TestCase
{

    protected function requiresIconv()
    {
        if (! extension_loaded('iconv')) {
            $this->markTestSkipped("Requires iconv");
        }
    }

    public function testDataIconv()
    {
        // Set up CP437
        $this->requiresIconv();
        $cp = new CodePage("CP437", array(
            "name" => "CP437",
            "iconv" => "CP437"
        ));
        $this->assertTrue($cp->isEncodable());
        $this->assertEquals($cp->getIconv(), "CP437");
        $this->assertEquals($cp->getName(), "CP437");
        $this->assertEquals($cp->getId(), "CP437");
        $this->assertEquals($cp->getNotes(), null);
        // Get data and see if it's right
        $data = $cp->getData();
        $expected = "ÇüéâäàåçêëèïîìÄÅÉæÆôöòûùÿÖÜ¢£¥₧ƒáíóúñÑªº¿⌐¬½¼¡«»░▒▓│┤╡╢╖╕╣║╗╝╜╛┐└┴┬├─┼╞╟╚╔╩╦╠═╬╧╨╤╥╙╘╒╓╫╪┘┌█▄▌▐▀αßΓπΣσµτΦΘΩδ∞φε∩≡±≥≤⌠⌡÷≈°∙·√ⁿ²■ ";
        $this->assertEquals($expected, $data);
    }

    public function testDataIconvBogus()
    {
        // No errors raised, you just get an empty list of supported characters if you try to compute a fake code page
        $this->requiresIconv();
        $cp = new CodePage("foo", array(
            "name" => "foo",
            "iconv" => "foo"
        ));
        $this->assertTrue($cp->isEncodable());
        $this->assertEquals($cp->getIconv(), "foo");
        $this->assertEquals($cp->getName(), "foo");
        $this->assertEquals($cp->getId(), "foo");
        $this->assertEquals($cp->getNotes(), null);
        $data = $cp->getData();
        $expected = str_repeat(" ", 128);
        $this->assertEquals($expected, $data);
        // Do this twice (caching behaviour)
        $data = $cp->getData();
        $this->assertEquals($expected, $data);
    }

    public function testDataCannotEncode()
    {
        $this->setExpectedException('\InvalidArgumentException');
        $cp = new CodePage("foo", array(
            "name" => "foo"
        ));
        $this->assertFalse($cp->isEncodable());
        $cp->getData();
    }
}