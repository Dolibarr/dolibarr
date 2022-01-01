<?php

namespace Sabre\HTTP;

class UtilTest extends \PHPUnit_Framework_TestCase {

    function testParseHTTPDate() {

        $times = [
            'Wed, 13 Oct 2010 10:26:00 GMT',
            'Wednesday, 13-Oct-10 10:26:00 GMT',
            'Wed Oct 13 10:26:00 2010',
        ];

        $expected = 1286965560;

        foreach ($times as $time) {
            $result = Util::parseHTTPDate($time);
            $this->assertEquals($expected, $result->format('U'));
        }

        $result = Util::parseHTTPDate('Wed Oct  6 10:26:00 2010');
        $this->assertEquals(1286360760, $result->format('U'));

    }

    function testParseHTTPDateFail() {

        $times = [
            //random string
            'NOW',
            // not-GMT timezone
            'Wednesday, 13-Oct-10 10:26:00 UTC',
            // No space before the 6
            'Wed Oct 6 10:26:00 2010',
            // Invalid day
            'Wed Oct  0 10:26:00 2010',
            'Wed Oct 32 10:26:00 2010',
            'Wed, 0 Oct 2010 10:26:00 GMT',
            'Wed, 32 Oct 2010 10:26:00 GMT',
            'Wednesday, 32-Oct-10 10:26:00 GMT',
            // Invalid hour
            'Wed, 13 Oct 2010 24:26:00 GMT',
            'Wednesday, 13-Oct-10 24:26:00 GMT',
            'Wed Oct 13 24:26:00 2010',
        ];

        foreach ($times as $time) {
            $this->assertFalse(Util::parseHTTPDate($time), 'We used the string: ' . $time);
        }

    }

    function testTimezones() {

        $default = date_default_timezone_get();
        date_default_timezone_set('Europe/Amsterdam');

        $this->testParseHTTPDate();

        date_default_timezone_set($default);

    }

    function testToHTTPDate() {

        $dt = new \DateTime('2011-12-10 12:00:00 +0200');

        $this->assertEquals(
            'Sat, 10 Dec 2011 10:00:00 GMT',
            Util::toHTTPDate($dt)
        );

    }

    /**
     * @dataProvider negotiateData
     */
    function testNegotiate($acceptHeader, $available, $expected) {

        $this->assertEquals(
            $expected,
            Util::negotiate($acceptHeader, $available)
        );

    }

    function negotiateData() {

        return [
            [ // simple
                'application/xml',
                ['application/xml'],
                'application/xml',
            ],
            [ // no header
                null,
                ['application/xml'],
                'application/xml',
            ],
            [ // 2 options
                'application/json',
                ['application/xml', 'application/json'],
                'application/json',
            ],
            [ // 2 choices
                'application/json, application/xml',
                ['application/xml'],
                'application/xml',
            ],
            [ // quality
                'application/xml;q=0.2, application/json',
                ['application/xml', 'application/json'],
                'application/json',
            ],
            [ // wildcard
                'image/jpeg, image/png, */*',
                ['application/xml', 'application/json'],
                'application/xml',
            ],
            [ // wildcard + quality
                'image/jpeg, image/png; q=0.5, */*',
                ['application/xml', 'application/json', 'image/png'],
                'application/xml',
            ],
            [ // no match
                'image/jpeg',
                ['application/xml'],
                null,
            ],
            [ // This is used in sabre/dav
                'text/vcard; version=4.0',
                [
                    // Most often used mime-type. Version 3
                    'text/x-vcard',
                    // The correct standard mime-type. Defaults to version 3 as
                    // well.
                    'text/vcard',
                    // vCard 4
                    'text/vcard; version=4.0',
                    // vCard 3
                    'text/vcard; version=3.0',
                    // jCard
                    'application/vcard+json',
                ],
                'text/vcard; version=4.0',

            ],
            [ // rfc7231 example 1
                'audio/*; q=0.2, audio/basic',
                [
                    'audio/pcm',
                    'audio/basic',
                ],
                'audio/basic',
            ],
            [ // Lower quality after
                'audio/pcm; q=0.2, audio/basic; q=0.1',
                [
                    'audio/pcm',
                    'audio/basic',
                ],
                'audio/pcm',
            ],
            [ // Random parameter, should be ignored
                'audio/pcm; hello; q=0.2, audio/basic; q=0.1',
                [
                    'audio/pcm',
                    'audio/basic',
                ],
                'audio/pcm',
            ],
            [ // No whitepace after type, should pick the one that is the most specific.
                'text/vcard;version=3.0, text/vcard',
                [
                    'text/vcard',
                    'text/vcard; version=3.0'
                ],
                'text/vcard; version=3.0',
            ],
            [ // Same as last one, but order is different
                'text/vcard, text/vcard;version=3.0',
                [
                    'text/vcard; version=3.0',
                    'text/vcard',
                ],
                'text/vcard; version=3.0',
            ],
            [ // Charset should be ignored here.
                'text/vcard; charset=utf-8; version=3.0, text/vcard',
                [
                    'text/vcard',
                    'text/vcard; version=3.0'
                ],
                'text/vcard; version=3.0',
            ],
            [ // Undefined offset issue.
                'text/html, image/gif, image/jpeg, *; q=.2, */*; q=.2',
                ['application/xml', 'application/json', 'image/png'],
                'application/xml',
            ],

        ];

    }
}
