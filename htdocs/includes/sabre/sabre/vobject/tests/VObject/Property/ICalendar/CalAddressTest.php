<?php

namespace Sabre\VObject\Property\ICalendar;

class CalAddressTest extends \PHPUnit_Framework_TestCase {

    /**
     * @dataProvider values
     */
    function testGetNormalizedValue($expected, $input) {

        $vobj = new \Sabre\VObject\Component\VCalendar();
        $property = $vobj->add('ATTENDEE', $input);

        $this->assertEquals(
            $expected,
            $property->getNormalizedValue()
        );

    }

    function values() {

        return [
            ['mailto:a@b.com', 'mailto:a@b.com'],
            ['mailto:a@b.com', 'MAILTO:a@b.com'],
            ['/foo/bar', '/foo/bar'],
        ];

    }

}
