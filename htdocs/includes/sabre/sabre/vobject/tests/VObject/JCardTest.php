<?php

namespace Sabre\VObject;

class JCardTest extends \PHPUnit_Framework_TestCase {

    function testToJCard() {

        $card = new Component\VCard([
            "VERSION"          => "4.0",
            "UID"              => "foo",
            "BDAY"             => "19850407",
            "REV"              => "19951031T222710Z",
            "LANG"             => "nl",
            "N"                => ["Last", "First", "Middle", "", ""],
            "item1.TEL"        => "+1 555 123456",
            "item1.X-AB-LABEL" => "Walkie Talkie",
            "ADR"              => [
                "",
                "",
                ["My Street", "Left Side", "Second Shack"],
                "Hometown",
                "PA",
                "18252",
                "U.S.A",
            ],
        ]);

        $card->add('BDAY', '1979-12-25', ['VALUE' => 'DATE', 'X-PARAM' => [1, 2]]);
        $card->add('BDAY', '1979-12-25T02:00:00', ['VALUE' => 'DATE-TIME']);


        $card->add('X-TRUNCATED', '--1225', ['VALUE' => 'DATE']);
        $card->add('X-TIME-LOCAL', '123000', ['VALUE' => 'TIME']);
        $card->add('X-TIME-UTC', '12:30:00Z', ['VALUE' => 'TIME']);
        $card->add('X-TIME-OFFSET', '12:30:00-08:00', ['VALUE' => 'TIME']);
        $card->add('X-TIME-REDUCED', '23', ['VALUE' => 'TIME']);
        $card->add('X-TIME-TRUNCATED', '--30', ['VALUE' => 'TIME']);

        $card->add('X-KARMA-POINTS', '42', ['VALUE' => 'INTEGER']);
        $card->add('X-GRADE', '1.3', ['VALUE' => 'FLOAT']);

        $card->add('TZ', '-0500', ['VALUE' => 'UTC-OFFSET']);

        $expected = [
            "vcard",
            [
                [
                    "version",
                    new \StdClass(),
                    "text",
                    "4.0"
                ],
                [
                    "prodid",
                    new \StdClass(),
                    "text",
                    "-//Sabre//Sabre VObject " . Version::VERSION . "//EN",
                ],
                [
                    "uid",
                    new \StdClass(),
                    "text",
                    "foo",
                ],
                [
                    "bday",
                    new \StdClass(),
                    "date-and-or-time",
                    "1985-04-07",
                ],
                [
                    "bday",
                    (object)[
                        'x-param' => [1,2],
                    ],
                    "date",
                    "1979-12-25",
                ],
                [
                    "bday",
                    new \StdClass(),
                    "date-time",
                    "1979-12-25T02:00:00",
                ],
                [
                    "rev",
                    new \StdClass(),
                    "timestamp",
                    "1995-10-31T22:27:10Z",
                ],
                [
                    "lang",
                    new \StdClass(),
                    "language-tag",
                    "nl",
                ],
                [
                    "n",
                    new \StdClass(),
                    "text",
                    ["Last", "First", "Middle", "", ""],
                ],
                [
                    "tel",
                    (object)[
                        "group" => "item1",
                    ],
                    "text",
                    "+1 555 123456",
                ],
                [
                    "x-ab-label",
                    (object)[
                        "group" => "item1",
                    ],
                    "unknown",
                    "Walkie Talkie",
                ],
                [
                    "adr",
                    new \StdClass(),
                    "text",
                        [
                            "",
                            "",
                            ["My Street", "Left Side", "Second Shack"],
                            "Hometown",
                            "PA",
                            "18252",
                            "U.S.A",
                        ],
                ],
                [
                    "x-truncated",
                    new \StdClass(),
                    "date",
                    "--12-25",
                ],
                [
                    "x-time-local",
                    new \StdClass(),
                    "time",
                    "12:30:00"
                ],
                [
                    "x-time-utc",
                    new \StdClass(),
                    "time",
                    "12:30:00Z"
                ],
                [
                    "x-time-offset",
                    new \StdClass(),
                    "time",
                    "12:30:00-08:00"
                ],
                [
                    "x-time-reduced",
                    new \StdClass(),
                    "time",
                    "23"
                ],
                [
                    "x-time-truncated",
                    new \StdClass(),
                    "time",
                    "--30"
                ],
                [
                    "x-karma-points",
                    new \StdClass(),
                    "integer",
                    42
                ],
                [
                    "x-grade",
                    new \StdClass(),
                    "float",
                    1.3
                ],
                [
                    "tz",
                    new \StdClass(),
                    "utc-offset",
                    "-05:00",
                ],
            ],
        ];

        $this->assertEquals($expected, $card->jsonSerialize());

    }

}
