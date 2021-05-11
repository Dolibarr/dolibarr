<?php

namespace Sabre\VObject;

/**
 * Tests the cli.
 *
 * Warning: these tests are very rudimentary.
 */
class CliTest extends \PHPUnit_Framework_TestCase {

    function setUp() {

        $this->cli = new CliMock();
        $this->cli->stderr = fopen('php://memory', 'r+');
        $this->cli->stdout = fopen('php://memory', 'r+');

    }

    function testInvalidArg() {

        $this->assertEquals(
            1,
            $this->cli->main(['vobject', '--hi'])
        );
        rewind($this->cli->stderr);
        $this->assertTrue(strlen(stream_get_contents($this->cli->stderr)) > 100);

    }

    function testQuiet() {

        $this->assertEquals(
            1,
            $this->cli->main(['vobject', '-q'])
        );
        $this->assertTrue($this->cli->quiet);

        rewind($this->cli->stderr);
        $this->assertEquals(0, strlen(stream_get_contents($this->cli->stderr)));

    }

    function testHelp() {

        $this->assertEquals(
            0,
            $this->cli->main(['vobject', '-h'])
        );
        rewind($this->cli->stderr);
        $this->assertTrue(strlen(stream_get_contents($this->cli->stderr)) > 100);

    }

    function testFormat() {

        $this->assertEquals(
            1,
            $this->cli->main(['vobject', '--format=jcard'])
        );

        rewind($this->cli->stderr);
        $this->assertTrue(strlen(stream_get_contents($this->cli->stderr)) > 100);

        $this->assertEquals('jcard', $this->cli->format);

    }

    function testFormatInvalid() {

        $this->assertEquals(
            1,
            $this->cli->main(['vobject', '--format=foo'])
        );

        rewind($this->cli->stderr);
        $this->assertTrue(strlen(stream_get_contents($this->cli->stderr)) > 100);

        $this->assertNull($this->cli->format);

    }

    function testInputFormatInvalid() {

        $this->assertEquals(
            1,
            $this->cli->main(['vobject', '--inputformat=foo'])
        );

        rewind($this->cli->stderr);
        $this->assertTrue(strlen(stream_get_contents($this->cli->stderr)) > 100);

        $this->assertNull($this->cli->format);

    }


    function testNoInputFile() {

        $this->assertEquals(
            1,
            $this->cli->main(['vobject', 'color'])
        );

        rewind($this->cli->stderr);
        $this->assertTrue(strlen(stream_get_contents($this->cli->stderr)) > 100);

    }

    function testTooManyArgs() {

        $this->assertEquals(
            1,
            $this->cli->main(['vobject', 'color', 'a', 'b', 'c'])
        );

    }

    function testUnknownCommand() {

        $this->assertEquals(
            1,
            $this->cli->main(['vobject', 'foo', '-'])
        );

    }

    function testConvertJson() {

        $inputStream = fopen('php://memory', 'r+');

        fwrite($inputStream, <<<ICS
BEGIN:VCARD
VERSION:3.0
FN:Cowboy Henk
END:VCARD
ICS
    );
        rewind($inputStream);
        $this->cli->stdin = $inputStream;

        $this->assertEquals(
            0,
            $this->cli->main(['vobject', 'convert', '--format=json', '-'])
        );

        rewind($this->cli->stdout);
        $version = Version::VERSION;
        $this->assertEquals(
            '["vcard",[["version",{},"text","4.0"],["prodid",{},"text","-\/\/Sabre\/\/Sabre VObject ' . $version . '\/\/EN"],["fn",{},"text","Cowboy Henk"]]]',
            stream_get_contents($this->cli->stdout)
        );

    }

    function testConvertJCardPretty() {

        if (version_compare(PHP_VERSION, '5.4.0') < 0) {
            $this->markTestSkipped('This test required PHP 5.4.0');
        }

        $inputStream = fopen('php://memory', 'r+');

        fwrite($inputStream, <<<ICS
BEGIN:VCARD
VERSION:3.0
FN:Cowboy Henk
END:VCARD
ICS
    );
        rewind($inputStream);
        $this->cli->stdin = $inputStream;

        $this->assertEquals(
            0,
            $this->cli->main(['vobject', 'convert', '--format=jcard', '--pretty', '-'])
        );

        rewind($this->cli->stdout);

        // PHP 5.5.12 changed the output

        $expected = <<<JCARD
[
    "vcard",
    [
        [
            "versi
JCARD;

          $this->assertStringStartsWith(
            $expected,
            stream_get_contents($this->cli->stdout)
        );

    }

    function testConvertJCalFail() {

        $inputStream = fopen('php://memory', 'r+');

        fwrite($inputStream, <<<ICS
BEGIN:VCARD
VERSION:3.0
FN:Cowboy Henk
END:VCARD
ICS
    );
        rewind($inputStream);
        $this->cli->stdin = $inputStream;

        $this->assertEquals(
            2,
            $this->cli->main(['vobject', 'convert', '--format=jcal', '--inputformat=mimedir', '-'])
        );

    }

    function testConvertMimeDir() {

        $inputStream = fopen('php://memory', 'r+');

        fwrite($inputStream, <<<JCARD
[
    "vcard",
    [
        [
            "version",
            {

            },
            "text",
            "4.0"
        ],
        [
            "prodid",
            {

            },
            "text",
            "-\/\/Sabre\/\/Sabre VObject 3.1.0\/\/EN"
        ],
        [
            "fn",
            {

            },
            "text",
            "Cowboy Henk"
        ]
    ]
]
JCARD
    );
        rewind($inputStream);
        $this->cli->stdin = $inputStream;

        $this->assertEquals(
            0,
            $this->cli->main(['vobject', 'convert', '--format=mimedir', '--inputformat=json', '--pretty', '-'])
        );

        rewind($this->cli->stdout);
        $expected = <<<VCF
BEGIN:VCARD
VERSION:4.0
PRODID:-//Sabre//Sabre VObject 3.1.0//EN
FN:Cowboy Henk
END:VCARD

VCF;

          $this->assertEquals(
            strtr($expected, ["\n" => "\r\n"]),
            stream_get_contents($this->cli->stdout)
        );

    }

    function testConvertDefaultFormats() {

        $outputFile = SABRE_TEMPDIR . 'bar.json';

        $this->assertEquals(
            2,
            $this->cli->main(['vobject', 'convert', 'foo.json', $outputFile])
        );

        $this->assertEquals('json', $this->cli->inputFormat);
        $this->assertEquals('json', $this->cli->format);

    }

    function testConvertDefaultFormats2() {

        $outputFile = SABRE_TEMPDIR . 'bar.ics';

        $this->assertEquals(
            2,
            $this->cli->main(['vobject', 'convert', 'foo.ics', $outputFile])
        );

        $this->assertEquals('mimedir', $this->cli->inputFormat);
        $this->assertEquals('mimedir', $this->cli->format);

    }

    function testVCard3040() {

        $inputStream = fopen('php://memory', 'r+');

        fwrite($inputStream, <<<VCARD
BEGIN:VCARD
VERSION:3.0
PRODID:-//Sabre//Sabre VObject 3.1.0//EN
FN:Cowboy Henk
END:VCARD

VCARD
    );
        rewind($inputStream);
        $this->cli->stdin = $inputStream;

        $this->assertEquals(
            0,
            $this->cli->main(['vobject', 'convert', '--format=vcard40', '--pretty', '-'])
        );

        rewind($this->cli->stdout);

        $version = Version::VERSION;
        $expected = <<<VCF
BEGIN:VCARD
VERSION:4.0
PRODID:-//Sabre//Sabre VObject $version//EN
FN:Cowboy Henk
END:VCARD

VCF;

          $this->assertEquals(
            strtr($expected, ["\n" => "\r\n"]),
            stream_get_contents($this->cli->stdout)
        );

    }

    function testVCard4030() {

        $inputStream = fopen('php://memory', 'r+');

        fwrite($inputStream, <<<VCARD
BEGIN:VCARD
VERSION:4.0
PRODID:-//Sabre//Sabre VObject 3.1.0//EN
FN:Cowboy Henk
END:VCARD

VCARD
    );
        rewind($inputStream);
        $this->cli->stdin = $inputStream;

        $this->assertEquals(
            0,
            $this->cli->main(['vobject', 'convert', '--format=vcard30', '--pretty', '-'])
        );

        $version = Version::VERSION;

        rewind($this->cli->stdout);
        $expected = <<<VCF
BEGIN:VCARD
VERSION:3.0
PRODID:-//Sabre//Sabre VObject $version//EN
FN:Cowboy Henk
END:VCARD

VCF;

          $this->assertEquals(
            strtr($expected, ["\n" => "\r\n"]),
            stream_get_contents($this->cli->stdout)
        );

    }

    function testVCard4021() {

        $inputStream = fopen('php://memory', 'r+');

        fwrite($inputStream, <<<VCARD
BEGIN:VCARD
VERSION:4.0
PRODID:-//Sabre//Sabre VObject 3.1.0//EN
FN:Cowboy Henk
END:VCARD

VCARD
    );
        rewind($inputStream);
        $this->cli->stdin = $inputStream;

        $this->assertEquals(
            2,
            $this->cli->main(['vobject', 'convert', '--format=vcard21', '--pretty', '-'])
        );

    }

    function testValidate() {

        $inputStream = fopen('php://memory', 'r+');

        fwrite($inputStream, <<<VCARD
BEGIN:VCARD
VERSION:4.0
PRODID:-//Sabre//Sabre VObject 3.1.0//EN
UID:foo
FN:Cowboy Henk
END:VCARD

VCARD
    );
        rewind($inputStream);
        $this->cli->stdin = $inputStream;
        $result = $this->cli->main(['vobject', 'validate', '-']);

        $this->assertEquals(
            0,
            $result
        );

    }

    function testValidateFail() {

        $inputStream = fopen('php://memory', 'r+');

        fwrite($inputStream, <<<VCARD
BEGIN:VCALENDAR
VERSION:2.0
END:VCARD

VCARD
    );
        rewind($inputStream);
        $this->cli->stdin = $inputStream;
        // vCard 2.0 is not supported yet, so this returns a failure.
        $this->assertEquals(
            2,
            $this->cli->main(['vobject', 'validate', '-'])
        );

    }

    function testValidateFail2() {

        $inputStream = fopen('php://memory', 'r+');

        fwrite($inputStream, <<<VCARD
BEGIN:VCALENDAR
VERSION:5.0
END:VCALENDAR

VCARD
    );
        rewind($inputStream);
        $this->cli->stdin = $inputStream;

        $this->assertEquals(
            2,
            $this->cli->main(['vobject', 'validate', '-'])
        );

    }

    function testRepair() {

        $inputStream = fopen('php://memory', 'r+');

        fwrite($inputStream, <<<VCARD
BEGIN:VCARD
VERSION:5.0
END:VCARD

VCARD
    );
        rewind($inputStream);
        $this->cli->stdin = $inputStream;

        $this->assertEquals(
            2,
            $this->cli->main(['vobject', 'repair', '-'])
        );

        rewind($this->cli->stdout);
        $this->assertRegExp("/^BEGIN:VCARD\r\nVERSION:2.1\r\nUID:[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\r\nEND:VCARD\r\n$/", stream_get_contents($this->cli->stdout));
    }

    function testRepairNothing() {

        $inputStream = fopen('php://memory', 'r+');

        fwrite($inputStream, <<<VCARD
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject 3.1.0//EN
BEGIN:VEVENT
UID:foo
DTSTAMP:20140122T233226Z
DTSTART:20140101T120000Z
END:VEVENT
END:VCALENDAR

VCARD
    );
        rewind($inputStream);
        $this->cli->stdin = $inputStream;

        $result = $this->cli->main(['vobject', 'repair', '-']);

        rewind($this->cli->stderr);
        $error = stream_get_contents($this->cli->stderr);

        $this->assertEquals(
            0,
            $result,
            "This should have been error free. stderr output:\n" . $error
        );

    }

    /**
     * Note: this is a very shallow test, doesn't dig into the actual output,
     * but just makes sure there's no errors thrown.
     *
     * The colorizer is not a critical component, it's mostly a debugging tool.
     */
    function testColorCalendar() {

        $inputStream = fopen('php://memory', 'r+');

        $version = Version::VERSION;

        /**
         * This object is not valid, but it's designed to hit every part of the
         * colorizer source.
         */
        fwrite($inputStream, <<<VCARD
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject {$version}//EN
BEGIN:VTIMEZONE
END:VTIMEZONE
BEGIN:VEVENT
ATTENDEE;RSVP=TRUE:mailto:foo@example.org
REQUEST-STATUS:5;foo
ATTACH:blabla
END:VEVENT
END:VCALENDAR

VCARD
    );
        rewind($inputStream);
        $this->cli->stdin = $inputStream;

        $result = $this->cli->main(['vobject', 'color', '-']);

        rewind($this->cli->stderr);
        $error = stream_get_contents($this->cli->stderr);

        $this->assertEquals(
            0,
            $result,
            "This should have been error free. stderr output:\n" . $error
        );

    }

    /**
     * Note: this is a very shallow test, doesn't dig into the actual output,
     * but just makes sure there's no errors thrown.
     *
     * The colorizer is not a critical component, it's mostly a debugging tool.
     */
    function testColorVCard() {

        $inputStream = fopen('php://memory', 'r+');

        $version = Version::VERSION;

        /**
         * This object is not valid, but it's designed to hit every part of the
         * colorizer source.
         */
        fwrite($inputStream, <<<VCARD
BEGIN:VCARD
VERSION:4.0
PRODID:-//Sabre//Sabre VObject {$version}//EN
ADR:1;2;3;4a,4b;5;6
group.TEL:123454768
END:VCARD

VCARD
    );
        rewind($inputStream);
        $this->cli->stdin = $inputStream;

        $result = $this->cli->main(['vobject', 'color', '-']);

        rewind($this->cli->stderr);
        $error = stream_get_contents($this->cli->stderr);

        $this->assertEquals(
            0,
            $result,
            "This should have been error free. stderr output:\n" . $error
        );

    }
}

class CliMock extends Cli {

    public $quiet = false;

    public $format;

    public $pretty;

    public $stdin;

    public $stdout;

    public $stderr;

    public $inputFormat;

    public $outputFormat;

}
