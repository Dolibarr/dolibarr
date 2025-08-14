<?php
use Mike42\Escpos\CodePage;

class CodePageTest extends PHPUnit\Framework\TestCase
{
    public function testDataGenerated()
    {
        // Set up CP437
        $cp = new CodePage("CP437", array(
            "name" => "CP437",
            "iconv" => "CP437"
        ));
        $dataArray = $cp->getDataArray();
        $this->assertEquals(128, count($dataArray));
        $expected = "ÇüéâäàåçêëèïîìÄÅÉæÆôöòûùÿÖÜ¢£¥₧ƒáíóúñÑªº¿⌐¬½¼¡«»░▒▓│┤╡╢╖╕╣║╗╝╜╛┐└┴┬├─┼╞╟╚╔╩╦╠═╬╧╨╤╥╙╘╒╓╫╪┘┌█▄▌▐▀αßΓπΣσμτΦΘΩδ∞φε∩≡±≥≤⌠⌡÷≈°∙·√ⁿ²■ ";
        $this->assertEquals($expected, self::dataArrayToString($dataArray));
    }

    public function testDataGenerateFailed()
    {
        // No errors raised, you just get an empty list of supported characters if you try to compute a fake code page
        $cp = new CodePage("foo", array(
            "name" => "foo",
            "iconv" => "foo"
        ));
        $this->assertTrue($cp->isEncodable());
        $this->assertEquals($cp->getIconv(), "foo");
        $this->assertEquals($cp->getName(), "foo");
        $this->assertEquals($cp->getId(), "foo");
        $this->assertEquals($cp->getNotes(), null);
        $dataArray = $cp->getDataArray();
        $expected = str_repeat(" ", 128);
        $this->assertEquals($expected, self::dataArrayToString($dataArray));
        // Do this twice (caching behaviour)
        $dataArray = $cp->getDataArray();
        $this->assertEquals($expected, self::dataArrayToString($dataArray));
    }

    public function testDataDefined()
    {
        // A made up code page called "baz", which is the same as CP437 but with some unmapped values at the start.
        $cp = new CodePage("baz", array(
            "name" => "baz",
            "iconv" => "baz",
            "data" => [
                "   âäàåçêëèïîìÄÅ",
                "ÉæÆôöòûùÿÖÜ¢£¥₧ƒ",
                "áíóúñÑªº¿⌐¬½¼¡«»",
                "░▒▓│┤╡╢╖╕╣║╗╝╜╛┐",
                "└┴┬├─┼╞╟╚╔╩╦╠═╬╧",
                "╨╤╥╙╘╒╓╫╪┘┌█▄▌▐▀",
                "αßΓπΣσμτΦΘΩδ∞φε∩",
                "≡±≥≤⌠⌡÷≈°∙·√ⁿ²■ "]
        ));
        $dataArray = $cp->getDataArray();
        $this->assertEquals(128, count($dataArray));
        $expected = "   âäàåçêëèïîìÄÅÉæÆôöòûùÿÖÜ¢£¥₧ƒáíóúñÑªº¿⌐¬½¼¡«»░▒▓│┤╡╢╖╕╣║╗╝╜╛┐└┴┬├─┼╞╟╚╔╩╦╠═╬╧╨╤╥╙╘╒╓╫╪┘┌█▄▌▐▀αßΓπΣσμτΦΘΩδ∞φε∩≡±≥≤⌠⌡÷≈°∙·√ⁿ²■ ";
        $this->assertEquals($expected, self::dataArrayToString($dataArray));
    }

    public function testDataCannotEncode()
    {
        $this->expectException(InvalidArgumentException::class);
        $cp = new CodePage("foo", array(
            "name" => "foo"
        ));
        $this->assertFalse($cp->isEncodable());
        $cp->getDataArray();
    }

    private static function dataArrayToString(array $codePoints) : string
    {
        // Assemble into character string so that the assertion is more compact
        return implode(array_map("IntlChar::chr", $codePoints));
    }
}
