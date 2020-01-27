<?php

namespace Sabre\VObject;

class VCardConverterTest extends \PHPUnit_Framework_TestCase {

    use \Sabre\VObject\PHPUnitAssertions;

    function testConvert30to40() {

        $input = <<<IN
BEGIN:VCARD
VERSION:3.0
PRODID:foo
FN;CHARSET=UTF-8:Steve
TEL;TYPE=PREF,HOME:+1 555 666 777
ITEM1.TEL:+1 444 555 666
ITEM1.X-ABLABEL:CustomLabel
PHOTO;ENCODING=b;TYPE=JPEG,HOME:Zm9v
PHOTO;ENCODING=b;TYPE=GIF:Zm9v
PHOTO;X-PARAM=FOO;ENCODING=b;TYPE=PNG:Zm9v
PHOTO;VALUE=URI:http://example.org/foo.png
X-ABShowAs:COMPANY
END:VCARD
IN;

        $output = <<<OUT
BEGIN:VCARD
VERSION:4.0
FN:Steve
TEL;PREF=1;TYPE=HOME:+1 555 666 777
ITEM1.TEL:+1 444 555 666
ITEM1.X-ABLABEL:CustomLabel
PHOTO;TYPE=HOME:data:image/jpeg;base64,Zm9v
PHOTO:data:image/gif;base64,Zm9v
PHOTO;X-PARAM=FOO:data:image/png;base64,Zm9v
PHOTO:http://example.org/foo.png
KIND:ORG
END:VCARD
OUT;

        $vcard = Reader::read($input);
        $vcard = $vcard->convert(Document::VCARD40);

        $this->assertVObjectEqualsVObject(
            $output,
            $vcard
        );

    }

    function testConvert40to40() {

        $input = <<<IN
BEGIN:VCARD
VERSION:4.0
FN:Steve
TEL;PREF=1;TYPE=HOME:+1 555 666 777
PHOTO:data:image/jpeg;base64,Zm9v
PHOTO:data:image/gif;base64,Zm9v
PHOTO;X-PARAM=FOO:data:image/png;base64,Zm9v
PHOTO:http://example.org/foo.png
END:VCARD

IN;

        $output = <<<OUT
BEGIN:VCARD
VERSION:4.0
FN:Steve
TEL;PREF=1;TYPE=HOME:+1 555 666 777
PHOTO:data:image/jpeg;base64,Zm9v
PHOTO:data:image/gif;base64,Zm9v
PHOTO;X-PARAM=FOO:data:image/png;base64,Zm9v
PHOTO:http://example.org/foo.png
END:VCARD

OUT;

        $vcard = Reader::read($input);
        $vcard = $vcard->convert(Document::VCARD40);

        $this->assertVObjectEqualsVObject(
            $output,
            $vcard
        );

    }

    function testConvert21to40() {

        $input = <<<IN
BEGIN:VCARD
VERSION:2.1
N:Family;Johnson
FN:Johnson Family
TEL;HOME;VOICE:555-12345-345
ADR;HOME:;;100 Street Lane;Saubel Beach;ON;H0H0H0
LABEL;HOME;ENCODING=QUOTED-PRINTABLE:100 Street Lane=0D=0ASaubel Beach,
 ON H0H0H0
REV:20110731T040251Z
UID:12345678
END:VCARD
IN;

        $output = <<<OUT
BEGIN:VCARD
VERSION:4.0
N:Family;Johnson;;;
FN:Johnson Family
TEL;TYPE=HOME,VOICE:555-12345-345
ADR;TYPE=HOME:;;100 Street Lane;Saubel Beach;ON;H0H0H0;
REV:20110731T040251Z
UID:12345678
END:VCARD

OUT;

        $vcard = Reader::read($input);
        $vcard = $vcard->convert(Document::VCARD40);

        $this->assertVObjectEqualsVObject(
            $output,
            $vcard
        );

    }

    function testConvert30to30() {

        $input = <<<IN
BEGIN:VCARD
VERSION:3.0
PRODID:foo
FN;CHARSET=UTF-8:Steve
TEL;TYPE=PREF,HOME:+1 555 666 777
PHOTO;ENCODING=b;TYPE=JPEG:Zm9v
PHOTO;ENCODING=b;TYPE=GIF:Zm9v
PHOTO;X-PARAM=FOO;ENCODING=b;TYPE=PNG:Zm9v
PHOTO;VALUE=URI:http://example.org/foo.png
END:VCARD

IN;

        $output = <<<OUT
BEGIN:VCARD
VERSION:3.0
PRODID:foo
FN;CHARSET=UTF-8:Steve
TEL;TYPE=PREF,HOME:+1 555 666 777
PHOTO;ENCODING=b;TYPE=JPEG:Zm9v
PHOTO;ENCODING=b;TYPE=GIF:Zm9v
PHOTO;X-PARAM=FOO;ENCODING=b;TYPE=PNG:Zm9v
PHOTO;VALUE=URI:http://example.org/foo.png
END:VCARD

OUT;

        $vcard = Reader::read($input);
        $vcard = $vcard->convert(Document::VCARD30);

        $this->assertVObjectEqualsVObject(
            $output,
            $vcard
        );

    }

    function testConvert40to30() {

        $input = <<<IN
BEGIN:VCARD
VERSION:4.0
PRODID:foo
FN:Steve
TEL;PREF=1;TYPE=HOME:+1 555 666 777
PHOTO:data:image/jpeg;base64,Zm9v
PHOTO:data:image/gif,foo
PHOTO;X-PARAM=FOO:data:image/png;base64,Zm9v
PHOTO:http://example.org/foo.png
KIND:ORG
END:VCARD

IN;

        $output = <<<OUT
BEGIN:VCARD
VERSION:3.0
FN:Steve
TEL;TYPE=PREF,HOME:+1 555 666 777
PHOTO;ENCODING=b;TYPE=JPEG:Zm9v
PHOTO;ENCODING=b;TYPE=GIF:Zm9v
PHOTO;ENCODING=b;TYPE=PNG;X-PARAM=FOO:Zm9v
PHOTO;VALUE=URI:http://example.org/foo.png
X-ABSHOWAS:COMPANY
END:VCARD

OUT;

        $vcard = Reader::read($input);
        $vcard = $vcard->convert(Document::VCARD30);

        $this->assertVObjectEqualsVObject(
            $output,
            $vcard
        );

    }

    function testConvertGroupCard() {

        $input = <<<IN
BEGIN:VCARD
VERSION:3.0
PRODID:foo
X-ADDRESSBOOKSERVER-KIND:GROUP
END:VCARD

IN;

        $output = <<<OUT
BEGIN:VCARD
VERSION:4.0
KIND:GROUP
END:VCARD

OUT;

        $vcard = Reader::read($input);
        $vcard = $vcard->convert(Document::VCARD40);

        $this->assertVObjectEqualsVObject(
            $output,
            $vcard
        );

        $input = $output;
        $output = <<<OUT
BEGIN:VCARD
VERSION:3.0
X-ADDRESSBOOKSERVER-KIND:GROUP
END:VCARD

OUT;

        $vcard = Reader::read($input);
        $vcard = $vcard->convert(Document::VCARD30);

        $this->assertVObjectEqualsVObject(
            $output,
            $vcard
        );

    }

    function testBDAYConversion() {

        $input = <<<IN
BEGIN:VCARD
VERSION:3.0
PRODID:foo
BDAY;X-APPLE-OMIT-YEAR=1604:1604-04-16
END:VCARD

IN;

        $output = <<<OUT
BEGIN:VCARD
VERSION:4.0
BDAY:--04-16
END:VCARD

OUT;

        $vcard = Reader::read($input);
        $vcard = $vcard->convert(Document::VCARD40);

        $this->assertVObjectEqualsVObject(
            $output,
            $vcard
        );

        $input = $output;
        $output = <<<OUT
BEGIN:VCARD
VERSION:3.0
BDAY;X-APPLE-OMIT-YEAR=1604:1604-04-16
END:VCARD

OUT;

        $vcard = Reader::read($input);
        $vcard = $vcard->convert(Document::VCARD30);

        $this->assertVObjectEqualsVObject(
            $output,
            $vcard
        );

    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testUnknownSourceVCardVersion() {

        $input = <<<IN
BEGIN:VCARD
VERSION:4.2
PRODID:foo
FN;CHARSET=UTF-8:Steve
TEL;TYPE=PREF,HOME:+1 555 666 777
ITEM1.TEL:+1 444 555 666
ITEM1.X-ABLABEL:CustomLabel
PHOTO;ENCODING=b;TYPE=JPEG,HOME:Zm9v
PHOTO;ENCODING=b;TYPE=GIF:Zm9v
PHOTO;X-PARAM=FOO;ENCODING=b;TYPE=PNG:Zm9v
PHOTO;VALUE=URI:http://example.org/foo.png
X-ABShowAs:COMPANY
END:VCARD

IN;

        $vcard = Reader::read($input);
        $vcard->convert(Document::VCARD40);

    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testUnknownTargetVCardVersion() {

        $input = <<<IN
BEGIN:VCARD
VERSION:3.0
PRODID:foo
END:VCARD

IN;

        $vcard = Reader::read($input);
        $vcard->convert(Document::VCARD21);

    }

    function testConvertIndividualCard() {

        $input = <<<IN
BEGIN:VCARD
VERSION:4.0
PRODID:foo
KIND:INDIVIDUAL
END:VCARD

IN;

        $output = <<<OUT
BEGIN:VCARD
VERSION:3.0
END:VCARD

OUT;

        $vcard = Reader::read($input);
        $vcard = $vcard->convert(Document::VCARD30);

        $this->assertVObjectEqualsVObject(
            $output,
            $vcard
        );

        $input = $output;
        $output = <<<OUT
BEGIN:VCARD
VERSION:4.0
END:VCARD

OUT;

        $vcard = Reader::read($input);
        $vcard = $vcard->convert(Document::VCARD40);

        $this->assertVObjectEqualsVObject(
            $output,
            $vcard
        );

    }

    function testAnniversary() {

        $input = <<<IN
BEGIN:VCARD
VERSION:4.0
ITEM1.ANNIVERSARY:20081210
END:VCARD

IN;

        $output = <<<'OUT'
BEGIN:VCARD
VERSION:3.0
ITEM1.X-ABDATE;VALUE=DATE-AND-OR-TIME:20081210
ITEM1.X-ABLABEL:_$!<Anniversary>!$_
ITEM1.X-ANNIVERSARY;VALUE=DATE-AND-OR-TIME:20081210
END:VCARD

OUT;

        $vcard = Reader::read($input);
        $vcard = $vcard->convert(Document::VCARD30);

        $this->assertVObjectEqualsVObject(
            $output,
            $vcard
        );

        // Swapping input and output
        list(
            $input,
            $output
        ) = [
            $output,
            $input
        ];

        $vcard = Reader::read($input);
        $vcard = $vcard->convert(Document::VCARD40);

        $this->assertVObjectEqualsVObject(
            $output,
            $vcard
        );

    }

    function testMultipleAnniversaries() {

        $input = <<<IN
BEGIN:VCARD
VERSION:4.0
ITEM1.ANNIVERSARY:20081210
ITEM2.ANNIVERSARY:20091210
ITEM3.ANNIVERSARY:20101210
END:VCARD

IN;

        $output = <<<'OUT'
BEGIN:VCARD
VERSION:3.0
ITEM1.X-ABDATE;VALUE=DATE-AND-OR-TIME:20081210
ITEM1.X-ABLABEL:_$!<Anniversary>!$_
ITEM1.X-ANNIVERSARY;VALUE=DATE-AND-OR-TIME:20081210
ITEM2.X-ABDATE;VALUE=DATE-AND-OR-TIME:20091210
ITEM2.X-ABLABEL:_$!<Anniversary>!$_
ITEM2.X-ANNIVERSARY;VALUE=DATE-AND-OR-TIME:20091210
ITEM3.X-ABDATE;VALUE=DATE-AND-OR-TIME:20101210
ITEM3.X-ABLABEL:_$!<Anniversary>!$_
ITEM3.X-ANNIVERSARY;VALUE=DATE-AND-OR-TIME:20101210
END:VCARD

OUT;

        $vcard = Reader::read($input);
        $vcard = $vcard->convert(Document::VCARD30);

        $this->assertVObjectEqualsVObject(
            $output,
            $vcard
        );

        // Swapping input and output
        list(
            $input,
            $output
        ) = [
            $output,
            $input
        ];

        $vcard = Reader::read($input);
        $vcard = $vcard->convert(Document::VCARD40);

        $this->assertVObjectEqualsVObject(
            $output,
            $vcard
        );

    }

    function testNoLabel() {

      $input = <<<VCF
BEGIN:VCARD
VERSION:3.0
UID:foo
N:Doe;John;;;
FN:John Doe
item1.X-ABDATE;type=pref:2008-12-11
END:VCARD

VCF;

      $vcard = Reader::read($input);

      $this->assertInstanceOf('Sabre\\VObject\\Component\\VCard', $vcard);
      $vcard = $vcard->convert(Document::VCARD40);
      $vcard = $vcard->serialize();

      $converted = Reader::read($vcard);
      $converted->validate();

      $version = Version::VERSION;

      $expected = <<<VCF
BEGIN:VCARD
VERSION:4.0
PRODID:-//Sabre//Sabre VObject $version//EN
UID:foo
N:Doe;John;;;
FN:John Doe
ITEM1.X-ABDATE;PREF=1:2008-12-11
END:VCARD

VCF;

      $this->assertEquals($expected, str_replace("\r", "", $vcard));

    }

}
