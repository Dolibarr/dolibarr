<?php

namespace Sabre\VObject;

class JCalTest extends \PHPUnit_Framework_TestCase {

    function testToJCal() {

        $cal = new Component\VCalendar();

        $event = $cal->add('VEVENT', [
            "UID"        => "foo",
            "DTSTART"    => new \DateTime("2013-05-26 18:10:00Z"),
            "DURATION"   => "P1D",
            "CATEGORIES" => ['home', 'testing'],
            "CREATED"    => new \DateTime("2013-05-26 18:10:00Z"),

            "ATTENDEE"     => "mailto:armin@example.org",
            "GEO"          => [51.96668, 7.61876],
            "SEQUENCE"     => 5,
            "FREEBUSY"     => ["20130526T210213Z/PT1H", "20130626T120000Z/20130626T130000Z"],
            "URL"          => "http://example.org/",
            "TZOFFSETFROM" => "+0500",
            "RRULE"        => ['FREQ' => 'WEEKLY', 'BYDAY' => ['MO', 'TU']],
        ], false);

        // Modifying DTSTART to be a date-only.
        $event->dtstart['VALUE'] = 'DATE';
        $event->add("X-BOOL", true, ['VALUE' => 'BOOLEAN']);
        $event->add("X-TIME", "08:00:00", ['VALUE' => 'TIME']);
        $event->add("ATTACH", "attachment", ['VALUE' => 'BINARY']);
        $event->add("ATTENDEE", "mailto:dominik@example.org", ["CN" => "Dominik", "PARTSTAT" => "DECLINED"]);

        $event->add('REQUEST-STATUS', ["2.0", "Success"]);
        $event->add('REQUEST-STATUS', ["3.7", "Invalid Calendar User", "ATTENDEE:mailto:jsmith@example.org"]);

        $event->add('DTEND', '20150108T133000');

        $expected = [
            "vcalendar",
            [
                [
                    "version",
                    new \StdClass(),
                    "text",
                    "2.0"
                ],
                [
                    "prodid",
                    new \StdClass(),
                    "text",
                    "-//Sabre//Sabre VObject " . Version::VERSION . "//EN",
                ],
                [
                    "calscale",
                    new \StdClass(),
                    "text",
                    "GREGORIAN"
                ],
            ],
            [
                ["vevent",
                    [
                        [
                            "uid", new \StdClass(), "text", "foo",
                        ],
                        [
                            "dtstart", new \StdClass(), "date", "2013-05-26",
                        ],
                        [
                            "duration", new \StdClass(), "duration", "P1D",
                        ],
                        [
                            "categories", new \StdClass(), "text", "home", "testing",
                        ],
                        [
                            "created", new \StdClass(), "date-time", "2013-05-26T18:10:00Z",
                        ],
                        [
                            "attendee", new \StdClass(), "cal-address", "mailto:armin@example.org",
                        ],
                        [
                            "attendee",
                            (object)[
                                "cn"       => "Dominik",
                                "partstat" => "DECLINED",
                            ],
                            "cal-address",
                            "mailto:dominik@example.org"
                        ],
                        [
                            "geo", new \StdClass(), "float", [51.96668, 7.61876],
                        ],
                        [
                            "sequence", new \StdClass(), "integer", 5
                        ],
                        [
                            "freebusy", new \StdClass(), "period",  ["2013-05-26T21:02:13", "PT1H"], ["2013-06-26T12:00:00", "2013-06-26T13:00:00"],
                        ],
                        [
                            "url", new \StdClass(), "uri", "http://example.org/",
                        ],
                        [
                            "tzoffsetfrom", new \StdClass(), "utc-offset", "+05:00",
                        ],
                        [
                            "rrule", new \StdClass(), "recur", [
                                'freq'  => 'WEEKLY',
                                'byday' => ['MO', 'TU'],
                            ],
                        ],
                        [
                            "x-bool", new \StdClass(), "boolean", true
                        ],
                        [
                            "x-time", new \StdClass(), "time", "08:00:00",
                        ],
                        [
                            "attach", new \StdClass(), "binary", base64_encode('attachment')
                        ],
                        [
                            "request-status",
                            new \StdClass(),
                            "text",
                            ["2.0", "Success"],
                        ],
                        [
                            "request-status",
                            new \StdClass(),
                            "text",
                            ["3.7", "Invalid Calendar User", "ATTENDEE:mailto:jsmith@example.org"],
                        ],
                        [
                            'dtend',
                            new \StdClass(),
                            "date-time",
                            "2015-01-08T13:30:00",
                        ],
                    ],
                    [],
                ]
            ],
        ];

        $this->assertEquals($expected, $cal->jsonSerialize());

    }

}
