<?php

namespace Sabre\VObject;

class ReaderTest extends \PHPUnit_Framework_TestCase {

    function testReadComponent() {

        $data = "BEGIN:VCALENDAR\r\nEND:VCALENDAR";

        $result = Reader::read($data);

        $this->assertInstanceOf('Sabre\\VObject\\Component', $result);
        $this->assertEquals('VCALENDAR', $result->name);
        $this->assertEquals(0, count($result->children()));

    }

    function testReadStream() {

        $data = "BEGIN:VCALENDAR\r\nEND:VCALENDAR";

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $data);
        rewind($stream);

        $result = Reader::read($stream);

        $this->assertInstanceOf('Sabre\\VObject\\Component', $result);
        $this->assertEquals('VCALENDAR', $result->name);
        $this->assertEquals(0, count($result->children()));

    }

    function testReadComponentUnixNewLine() {

        $data = "BEGIN:VCALENDAR\nEND:VCALENDAR";

        $result = Reader::read($data);

        $this->assertInstanceOf('Sabre\\VObject\\Component', $result);
        $this->assertEquals('VCALENDAR', $result->name);
        $this->assertEquals(0, count($result->children()));

    }

    function testReadComponentLineFold() {

        $data = "BEGIN:\r\n\tVCALENDAR\r\nE\r\n ND:VCALENDAR";

        $result = Reader::read($data);

        $this->assertInstanceOf('Sabre\\VObject\\Component', $result);
        $this->assertEquals('VCALENDAR', $result->name);
        $this->assertEquals(0, count($result->children()));

    }

    /**
     * @expectedException Sabre\VObject\ParseException
     */
    function testReadCorruptComponent() {

        $data = "BEGIN:VCALENDAR\r\nEND:FOO";

        $result = Reader::read($data);

    }

    /**
     * @expectedException Sabre\VObject\ParseException
     */
    function testReadCorruptSubComponent() {

        $data = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nEND:FOO\r\nEND:VCALENDAR";

        $result = Reader::read($data);

    }

    function testReadProperty() {

        $data = "BEGIN:VCALENDAR\r\nSUMMARY:propValue\r\nEND:VCALENDAR";
        $result = Reader::read($data);

        $result = $result->SUMMARY;
        $this->assertInstanceOf('Sabre\\VObject\\Property', $result);
        $this->assertEquals('SUMMARY', $result->name);
        $this->assertEquals('propValue', $result->getValue());

    }

    function testReadPropertyWithNewLine() {

        $data = "BEGIN:VCALENDAR\r\nSUMMARY:Line1\\nLine2\\NLine3\\\\Not the 4th line!\r\nEND:VCALENDAR";
        $result = Reader::read($data);

        $result = $result->SUMMARY;
        $this->assertInstanceOf('Sabre\\VObject\\Property', $result);
        $this->assertEquals('SUMMARY', $result->name);
        $this->assertEquals("Line1\nLine2\nLine3\\Not the 4th line!", $result->getValue());

    }

    function testReadMappedProperty() {

        $data = "BEGIN:VCALENDAR\r\nDTSTART:20110529\r\nEND:VCALENDAR";
        $result = Reader::read($data);

        $result = $result->DTSTART;
        $this->assertInstanceOf('Sabre\\VObject\\Property\\ICalendar\\DateTime', $result);
        $this->assertEquals('DTSTART', $result->name);
        $this->assertEquals('20110529', $result->getValue());

    }

    function testReadMappedPropertyGrouped() {

        $data = "BEGIN:VCALENDAR\r\nfoo.DTSTART:20110529\r\nEND:VCALENDAR";
        $result = Reader::read($data);

        $result = $result->DTSTART;
        $this->assertInstanceOf('Sabre\\VObject\\Property\\ICalendar\\DateTime', $result);
        $this->assertEquals('DTSTART', $result->name);
        $this->assertEquals('20110529', $result->getValue());

    }

    /**
     * @expectedException Sabre\VObject\ParseException
     */
    function testReadBrokenLine() {

        $data = "BEGIN:VCALENDAR\r\nPROPNAME;propValue";
        $result = Reader::read($data);

    }

    function testReadPropertyInComponent() {

        $data = [
            "BEGIN:VCALENDAR",
            "PROPNAME:propValue",
            "END:VCALENDAR"
        ];

        $result = Reader::read(implode("\r\n", $data));

        $this->assertInstanceOf('Sabre\\VObject\\Component', $result);
        $this->assertEquals('VCALENDAR', $result->name);
        $this->assertEquals(1, count($result->children()));
        $this->assertInstanceOf('Sabre\\VObject\\Property', $result->children()[0]);
        $this->assertEquals('PROPNAME', $result->children()[0]->name);
        $this->assertEquals('propValue', $result->children()[0]->getValue());

    }

    function testReadNestedComponent() {

        $data = [
            "BEGIN:VCALENDAR",
            "BEGIN:VTIMEZONE",
            "BEGIN:DAYLIGHT",
            "END:DAYLIGHT",
            "END:VTIMEZONE",
            "END:VCALENDAR"
        ];

        $result = Reader::read(implode("\r\n", $data));

        $this->assertInstanceOf('Sabre\\VObject\\Component', $result);
        $this->assertEquals('VCALENDAR', $result->name);
        $this->assertEquals(1, count($result->children()));
        $this->assertInstanceOf('Sabre\\VObject\\Component', $result->children()[0]);
        $this->assertEquals('VTIMEZONE', $result->children()[0]->name);
        $this->assertEquals(1, count($result->children()[0]->children()));
        $this->assertInstanceOf('Sabre\\VObject\\Component', $result->children()[0]->children()[0]);
        $this->assertEquals('DAYLIGHT', $result->children()[0]->children()[0]->name);


    }

    function testReadPropertyParameter() {

        $data = "BEGIN:VCALENDAR\r\nPROPNAME;PARAMNAME=paramvalue:propValue\r\nEND:VCALENDAR";
        $result = Reader::read($data);

        $result = $result->PROPNAME;

        $this->assertInstanceOf('Sabre\\VObject\\Property', $result);
        $this->assertEquals('PROPNAME', $result->name);
        $this->assertEquals('propValue', $result->getValue());
        $this->assertEquals(1, count($result->parameters()));
        $this->assertEquals('PARAMNAME', $result->parameters['PARAMNAME']->name);
        $this->assertEquals('paramvalue', $result->parameters['PARAMNAME']->getValue());

    }

    function testReadPropertyRepeatingParameter() {

        $data = "BEGIN:VCALENDAR\r\nPROPNAME;N=1;N=2;N=3,4;N=\"5\",6;N=\"7,8\";N=9,10;N=^'11^':propValue\r\nEND:VCALENDAR";
        $result = Reader::read($data);

        $result = $result->PROPNAME;

        $this->assertInstanceOf('Sabre\\VObject\\Property', $result);
        $this->assertEquals('PROPNAME', $result->name);
        $this->assertEquals('propValue', $result->getValue());
        $this->assertEquals(1, count($result->parameters()));
        $this->assertEquals('N', $result->parameters['N']->name);
        $this->assertEquals('1,2,3,4,5,6,7,8,9,10,"11"', $result->parameters['N']->getValue());
        $this->assertEquals([1, 2, 3, 4, 5, 6, "7,8", 9, 10, '"11"'], $result->parameters['N']->getParts());

    }

    function testReadPropertyRepeatingNamelessGuessedParameter() {

        $data = "BEGIN:VCALENDAR\r\nPROPNAME;WORK;VOICE;PREF:propValue\r\nEND:VCALENDAR";
        $result = Reader::read($data);

        $result = $result->PROPNAME;

        $this->assertInstanceOf('Sabre\\VObject\\Property', $result);
        $this->assertEquals('PROPNAME', $result->name);
        $this->assertEquals('propValue', $result->getValue());
        $this->assertEquals(1, count($result->parameters()));
        $this->assertEquals('TYPE', $result->parameters['TYPE']->name);
        $this->assertEquals('WORK,VOICE,PREF', $result->parameters['TYPE']->getValue());
        $this->assertEquals(['WORK', 'VOICE', 'PREF'], $result->parameters['TYPE']->getParts());

    }

    function testReadPropertyNoName() {

        $data = "BEGIN:VCALENDAR\r\nPROPNAME;PRODIGY:propValue\r\nEND:VCALENDAR";
        $result = Reader::read($data);

        $result = $result->PROPNAME;

        $this->assertInstanceOf('Sabre\\VObject\\Property', $result);
        $this->assertEquals('PROPNAME', $result->name);
        $this->assertEquals('propValue', $result->getValue());
        $this->assertEquals(1, count($result->parameters()));
        $this->assertEquals('TYPE', $result->parameters['TYPE']->name);
        $this->assertTrue($result->parameters['TYPE']->noName);
        $this->assertEquals('PRODIGY', $result->parameters['TYPE']);

    }

    function testReadPropertyParameterExtraColon() {

        $data = "BEGIN:VCALENDAR\r\nPROPNAME;PARAMNAME=paramvalue:propValue:anotherrandomstring\r\nEND:VCALENDAR";
        $result = Reader::read($data);

        $result = $result->PROPNAME;

        $this->assertInstanceOf('Sabre\\VObject\\Property', $result);
        $this->assertEquals('PROPNAME', $result->name);
        $this->assertEquals('propValue:anotherrandomstring', $result->getValue());
        $this->assertEquals(1, count($result->parameters()));
        $this->assertEquals('PARAMNAME', $result->parameters['PARAMNAME']->name);
        $this->assertEquals('paramvalue', $result->parameters['PARAMNAME']->getValue());

    }

    function testReadProperty2Parameters() {

        $data = "BEGIN:VCALENDAR\r\nPROPNAME;PARAMNAME=paramvalue;PARAMNAME2=paramvalue2:propValue\r\nEND:VCALENDAR";
        $result = Reader::read($data);

        $result = $result->PROPNAME;

        $this->assertInstanceOf('Sabre\\VObject\\Property', $result);
        $this->assertEquals('PROPNAME', $result->name);
        $this->assertEquals('propValue', $result->getValue());
        $this->assertEquals(2, count($result->parameters()));
        $this->assertEquals('PARAMNAME', $result->parameters['PARAMNAME']->name);
        $this->assertEquals('paramvalue', $result->parameters['PARAMNAME']->getValue());
        $this->assertEquals('PARAMNAME2', $result->parameters['PARAMNAME2']->name);
        $this->assertEquals('paramvalue2', $result->parameters['PARAMNAME2']->getValue());

    }

    function testReadPropertyParameterQuoted() {

        $data = "BEGIN:VCALENDAR\r\nPROPNAME;PARAMNAME=\"paramvalue\":propValue\r\nEND:VCALENDAR";
        $result = Reader::read($data);

        $result = $result->PROPNAME;

        $this->assertInstanceOf('Sabre\\VObject\\Property', $result);
        $this->assertEquals('PROPNAME', $result->name);
        $this->assertEquals('propValue', $result->getValue());
        $this->assertEquals(1, count($result->parameters()));
        $this->assertEquals('PARAMNAME', $result->parameters['PARAMNAME']->name);
        $this->assertEquals('paramvalue', $result->parameters['PARAMNAME']->getValue());

    }

    function testReadPropertyParameterNewLines() {

        $data = "BEGIN:VCALENDAR\r\nPROPNAME;PARAMNAME=paramvalue1^nvalue2^^nvalue3:propValue\r\nEND:VCALENDAR";
        $result = Reader::read($data);

        $result = $result->PROPNAME;

        $this->assertInstanceOf('Sabre\\VObject\\Property', $result);
        $this->assertEquals('PROPNAME', $result->name);
        $this->assertEquals('propValue', $result->getValue());

        $this->assertEquals(1, count($result->parameters()));
        $this->assertEquals('PARAMNAME', $result->parameters['PARAMNAME']->name);
        $this->assertEquals("paramvalue1\nvalue2^nvalue3", $result->parameters['PARAMNAME']->getValue());

    }

    function testReadPropertyParameterQuotedColon() {

        $data = "BEGIN:VCALENDAR\r\nPROPNAME;PARAMNAME=\"param:value\":propValue\r\nEND:VCALENDAR";
        $result = Reader::read($data);
        $result = $result->PROPNAME;

        $this->assertInstanceOf('Sabre\\VObject\\Property', $result);
        $this->assertEquals('PROPNAME', $result->name);
        $this->assertEquals('propValue', $result->getValue());
        $this->assertEquals(1, count($result->parameters()));
        $this->assertEquals('PARAMNAME', $result->parameters['PARAMNAME']->name);
        $this->assertEquals('param:value', $result->parameters['PARAMNAME']->getValue());

    }

    function testReadForgiving() {

        $data = [
            "BEGIN:VCALENDAR",
            "X_PROP:propValue",
            "END:VCALENDAR"
        ];

        $caught = false;
        try {
            $result = Reader::read(implode("\r\n", $data));
        } catch (ParseException $e) {
            $caught = true;
        }

        $this->assertEquals(true, $caught);

        $result = Reader::read(implode("\r\n", $data), Reader::OPTION_FORGIVING);

        $expected = implode("\r\n", [
            "BEGIN:VCALENDAR",
            "X_PROP:propValue",
            "END:VCALENDAR",
            ""
        ]);

        $this->assertEquals($expected, $result->serialize());

    }

    function testReadWithInvalidLine() {

        $data = [
            "BEGIN:VCALENDAR",
            "DESCRIPTION:propValue",
            "Yes, we've actually seen a file with non-idented property values on multiple lines",
            "END:VCALENDAR"
        ];

        $caught = false;
        try {
            $result = Reader::read(implode("\r\n", $data));
        } catch (ParseException $e) {
            $caught = true;
        }

        $this->assertEquals(true, $caught);

        $result = Reader::read(implode("\r\n", $data), Reader::OPTION_IGNORE_INVALID_LINES);

        $expected = implode("\r\n", [
            "BEGIN:VCALENDAR",
            "DESCRIPTION:propValue",
            "END:VCALENDAR",
            ""
        ]);

        $this->assertEquals($expected, $result->serialize());

    }

    /**
     * Reported as Issue 32.
     *
     * @expectedException \Sabre\VObject\ParseException
     */
    function testReadIncompleteFile() {

        $input = <<<ICS
BEGIN:VCALENDAR
VERSION:1.0
BEGIN:VEVENT
X-FUNAMBOL-FOLDER:DEFAULT_FOLDER
X-FUNAMBOL-ALLDAY:0
DTSTART:20111017T110000Z
DTEND:20111017T123000Z
X-MICROSOFT-CDO-BUSYSTATUS:BUSY
CATEGORIES:
LOCATION;ENCODING=QUOTED-PRINTABLE;CHARSET=UTF-8:Netviewer Meeting
PRIORITY:1
STATUS:3
X-MICROSOFT-CDO-REPLYTIME:20111017T064200Z
SUMMARY;ENCODING=QUOTED-PRINTABLE;CHARSET=UTF-8:Kopieren: test
CLASS:PUBLIC
AALARM:
RRULE:
X-FUNAMBOL-BILLINGINFO:
X-FUNAMBOL-COMPANIES:
X-FUNAMBOL-MILEAGE:
X-FUNAMBOL-NOAGING:0
ATTENDEE;STATUS=NEEDS ACTION;ENCODING=QUOTED-PRINTABLE;CHARSET=UTF-8:'Heino' heino@test.com
ATTENDEE;STATUS=NEEDS ACTION;ENCODING=QUOTED-PRINTABLE;CHARSET=UTF-8:'Markus' test@test.com
ATTENDEE;STATUS=NEEDS AC
ICS;

        Reader::read($input);

    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function testReadBrokenInput() {

        Reader::read(false);

    }

    function testReadBOM() {

        $data = chr(0xef) . chr(0xbb) . chr(0xbf) . "BEGIN:VCALENDAR\r\nEND:VCALENDAR";
        $result = Reader::read($data);

        $this->assertInstanceOf('Sabre\\VObject\\Component', $result);
        $this->assertEquals('VCALENDAR', $result->name);
        $this->assertEquals(0, count($result->children()));

    }

    function testReadXMLComponent() {

        $data = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar>
 </vcalendar>
</icalendar>
XML;

        $result = Reader::readXML($data);

        $this->assertInstanceOf('Sabre\\VObject\\Component', $result);
        $this->assertEquals('VCALENDAR', $result->name);
        $this->assertEquals(0, count($result->children()));

    }

    function testReadXMLStream() {

        $data = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar>
 </vcalendar>
</icalendar>
XML;

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $data);
        rewind($stream);

        $result = Reader::readXML($stream);

        $this->assertInstanceOf('Sabre\\VObject\\Component', $result);
        $this->assertEquals('VCALENDAR', $result->name);
        $this->assertEquals(0, count($result->children()));

    }

}
