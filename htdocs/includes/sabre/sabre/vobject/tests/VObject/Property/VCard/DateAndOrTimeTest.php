<?php

namespace Sabre\VObject\Property\VCard;

use Sabre\VObject;
use Sabre\VObject\Reader;

class DateAndOrTimeTest extends \PHPUnit_Framework_TestCase {

    /**
     * @dataProvider dates
     */
    function testGetJsonValue($input, $output) {

        $vcard = new VObject\Component\VCard();
        $prop = $vcard->createProperty('BDAY', $input);

        $this->assertEquals([$output], $prop->getJsonValue());

    }

    function dates() {

        return [
            [
                "19961022T140000",
                "1996-10-22T14:00:00",
            ],
            [
                "--1022T1400",
                "--10-22T14:00",
            ],
            [
                "---22T14",
                "---22T14",
            ],
            [
                "19850412",
                "1985-04-12",
            ],
            [
                "1985-04",
                "1985-04",
            ],
            [
                "1985",
                "1985",
            ],
            [
                "--0412",
                "--04-12",
            ],
            [
                "T102200",
                "T10:22:00",
            ],
            [
                "T1022",
                "T10:22",
            ],
            [
                "T10",
                "T10",
            ],
            [
                "T-2200",
                "T-22:00",
            ],
            [
                "T102200Z",
                "T10:22:00Z",
            ],
            [
                "T102200-0800",
                "T10:22:00-0800",
            ],
            [
                "T--00",
                "T--00",
            ],
        ];

    }

    function testSetParts() {

        $vcard = new VObject\Component\VCard();

        $prop = $vcard->createProperty('BDAY');
        $prop->setParts([
            new \DateTime('2014-04-02 18:37:00')
        ]);

        $this->assertEquals('20140402T183700Z', $prop->getValue());

    }

    function testSetPartsDateTimeImmutable() {

        $vcard = new VObject\Component\VCard();

        $prop = $vcard->createProperty('BDAY');
        $prop->setParts([
            new \DateTimeImmutable('2014-04-02 18:37:00')
        ]);

        $this->assertEquals('20140402T183700Z', $prop->getValue());

    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testSetPartsTooMany() {

        $vcard = new VObject\Component\VCard();

        $prop = $vcard->createProperty('BDAY');
        $prop->setParts([
            1,
            2
        ]);

    }

    function testSetPartsString() {

        $vcard = new VObject\Component\VCard();

        $prop = $vcard->createProperty('BDAY');
        $prop->setParts([
            "20140402T183700Z"
        ]);

        $this->assertEquals('20140402T183700Z', $prop->getValue());

    }

    function testSetValueDateTime() {

        $vcard = new VObject\Component\VCard();

        $prop = $vcard->createProperty('BDAY');
        $prop->setValue(
            new \DateTime('2014-04-02 18:37:00')
        );

        $this->assertEquals('20140402T183700Z', $prop->getValue());

    }

    function testSetValueDateTimeImmutable() {

        $vcard = new VObject\Component\VCard();

        $prop = $vcard->createProperty('BDAY');
        $prop->setValue(
            new \DateTimeImmutable('2014-04-02 18:37:00')
        );

        $this->assertEquals('20140402T183700Z', $prop->getValue());

    }

    function testSetDateTimeOffset() {

        $vcard = new VObject\Component\VCard();

        $prop = $vcard->createProperty('BDAY');
        $prop->setValue(
            new \DateTime('2014-04-02 18:37:00', new \DateTimeZone('America/Toronto'))
        );

        $this->assertEquals('20140402T183700-0400', $prop->getValue());

    }

    function testGetDateTime() {

        $datetime = new \DateTime('2014-04-02 18:37:00', new \DateTimeZone('America/Toronto'));

        $vcard = new VObject\Component\VCard();
        $prop = $vcard->createProperty('BDAY', $datetime);

        $dt = $prop->getDateTime();
        $this->assertEquals('2014-04-02T18:37:00-04:00', $dt->format('c'), "For some reason this one failed. Current default timezone is: " . date_default_timezone_get());

    }

    function testGetDate() {

        $datetime = new \DateTime('2014-04-02');

        $vcard = new VObject\Component\VCard();
        $prop = $vcard->createProperty('BDAY', $datetime, null, 'DATE');

        $this->assertEquals('DATE', $prop->getValueType());
        $this->assertEquals('BDAY:20140402', rtrim($prop->serialize()));

    }

    function testGetDateIncomplete() {

        $datetime = '--0407';

        $vcard = new VObject\Component\VCard();
        $prop = $vcard->add('BDAY', $datetime);

        $dt = $prop->getDateTime();
        // Note: if the year changes between the last line and the next line of
        // code, this test may fail.
        //
        // If that happens, head outside and have a drink.
        $current = new \DateTime('now');
        $year = $current->format('Y');

        $this->assertEquals($year . '0407', $dt->format('Ymd'));

    }

    function testGetDateIncompleteFromVCard() {

        $vcard = <<<VCF
BEGIN:VCARD
VERSION:4.0
BDAY:--0407
END:VCARD
VCF;
        $vcard = Reader::read($vcard);
        $prop = $vcard->BDAY;

        $dt = $prop->getDateTime();
        // Note: if the year changes between the last line and the next line of
        // code, this test may fail.
        //
        // If that happens, head outside and have a drink.
        $current = new \DateTime('now');
        $year = $current->format('Y');

        $this->assertEquals($year . '0407', $dt->format('Ymd'));

    }

    function testValidate() {

        $datetime = '--0407';

        $vcard = new VObject\Component\VCard();
        $prop = $vcard->add('BDAY', $datetime);

        $this->assertEquals([], $prop->validate());

    }

    function testValidateBroken() {

        $datetime = '123';

        $vcard = new VObject\Component\VCard();
        $prop = $vcard->add('BDAY', $datetime);

        $this->assertEquals([[
            'level'   => 3,
            'message' => 'The supplied value (123) is not a correct DATE-AND-OR-TIME property',
            'node'    => $prop,
        ]], $prop->validate());

    }
}
