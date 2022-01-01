<?php

namespace Sabre\VObject\Component;

use DateTimeImmutable;
use DateTimeZone;
use Sabre\VObject;
use Sabre\VObject\Reader;

/**
 * We use `RFCxxx` has a placeholder for the
 * https://tools.ietf.org/html/draft-daboo-calendar-availability-05 name.
 */
class VAvailabilityTest extends \PHPUnit_Framework_TestCase {

    function testVAvailabilityComponent() {

        $vcal = <<<VCAL
BEGIN:VCALENDAR
BEGIN:VAVAILABILITY
END:VAVAILABILITY
END:VCALENDAR
VCAL;
        $document = Reader::read($vcal);

        $this->assertInstanceOf(__NAMESPACE__ . '\VAvailability', $document->VAVAILABILITY);

    }

    function testGetEffectiveStartEnd() {

        $vcal = <<<VCAL
BEGIN:VCALENDAR
BEGIN:VAVAILABILITY
DTSTART:20150717T162200Z
DTEND:20150717T172200Z
END:VAVAILABILITY
END:VCALENDAR
VCAL;

        $document = Reader::read($vcal);
        $tz = new DateTimeZone('UTC');
        $this->assertEquals(
            [
                new DateTimeImmutable('2015-07-17 16:22:00', $tz),
                new DateTimeImmutable('2015-07-17 17:22:00', $tz),
            ],
            $document->VAVAILABILITY->getEffectiveStartEnd()
        );

    }

    function testGetEffectiveStartDuration() {

        $vcal = <<<VCAL
BEGIN:VCALENDAR
BEGIN:VAVAILABILITY
DTSTART:20150717T162200Z
DURATION:PT1H
END:VAVAILABILITY
END:VCALENDAR
VCAL;

        $document = Reader::read($vcal);
        $tz = new DateTimeZone('UTC');
        $this->assertEquals(
            [
                new DateTimeImmutable('2015-07-17 16:22:00', $tz),
                new DateTimeImmutable('2015-07-17 17:22:00', $tz),
            ],
            $document->VAVAILABILITY->getEffectiveStartEnd()
        );

    }

    function testGetEffectiveStartEndUnbound() {

        $vcal = <<<VCAL
BEGIN:VCALENDAR
BEGIN:VAVAILABILITY
END:VAVAILABILITY
END:VCALENDAR
VCAL;

        $document = Reader::read($vcal);
        $this->assertEquals(
            [
                null,
                null,
            ],
            $document->VAVAILABILITY->getEffectiveStartEnd()
        );

    }

    function testIsInTimeRangeUnbound() {

        $vcal = <<<VCAL
BEGIN:VCALENDAR
BEGIN:VAVAILABILITY
END:VAVAILABILITY
END:VCALENDAR
VCAL;

        $document = Reader::read($vcal);
        $this->assertTrue(
            $document->VAVAILABILITY->isInTimeRange(new DateTimeImmutable('2015-07-17'), new DateTimeImmutable('2015-07-18'))
        );

    }

    function testIsInTimeRangeOutside() {

        $vcal = <<<VCAL
BEGIN:VCALENDAR
BEGIN:VAVAILABILITY
DTSTART:20140101T000000Z
DTEND:20140102T000000Z
END:VAVAILABILITY
END:VCALENDAR
VCAL;

        $document = Reader::read($vcal);
        $this->assertFalse(
            $document->VAVAILABILITY->isInTimeRange(new DateTimeImmutable('2015-07-17'), new DateTimeImmutable('2015-07-18'))
        );

    }

    function testRFCxxxSection3_1_availabilityprop_required() {

        // UID and DTSTAMP are present.
        $this->assertIsValid(Reader::read(
<<<VCAL
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//id
BEGIN:VAVAILABILITY
UID:foo@test
DTSTAMP:20111005T133225Z
END:VAVAILABILITY
END:VCALENDAR
VCAL
        ));

        // UID and DTSTAMP are missing.
        $this->assertIsNotValid(Reader::read(
<<<VCAL
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//id
BEGIN:VAVAILABILITY
END:VAVAILABILITY
END:VCALENDAR
VCAL
        ));

        // DTSTAMP is missing.
        $this->assertIsNotValid(Reader::read(
<<<VCAL
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//id
BEGIN:VAVAILABILITY
UID:foo@test
END:VAVAILABILITY
END:VCALENDAR
VCAL
        ));

        // UID is missing.
        $this->assertIsNotValid(Reader::read(
<<<VCAL
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//id
BEGIN:VAVAILABILITY
DTSTAMP:20111005T133225Z
END:VAVAILABILITY
END:VCALENDAR
VCAL
        ));

    }

    function testRFCxxxSection3_1_availabilityprop_optional_once() {

        $properties = [
            'BUSYTYPE:BUSY',
            'CLASS:PUBLIC',
            'CREATED:20111005T135125Z',
            'DESCRIPTION:Long bla bla',
            'DTSTART:20111005T020000',
            'LAST-MODIFIED:20111005T135325Z',
            'ORGANIZER:mailto:foo@example.com',
            'PRIORITY:1',
            'SEQUENCE:0',
            'SUMMARY:Bla bla',
            'URL:http://example.org/'
        ];

        // They are all present, only once.
        $this->assertIsValid(Reader::read($this->template($properties)));

        // We duplicate each one to see if it fails.
        foreach ($properties as $property) {
            $this->assertIsNotValid(Reader::read($this->template([
                $property,
                $property
            ])));
        }

    }

    function testRFCxxxSection3_1_availabilityprop_dtend_duration() {

        // Only DTEND.
        $this->assertIsValid(Reader::read($this->template([
            'DTEND:21111005T133225Z'
        ])));

        // Only DURATION.
        $this->assertIsValid(Reader::read($this->template([
            'DURATION:PT1H'
        ])));

        // Both (not allowed).
        $this->assertIsNotValid(Reader::read($this->template([
            'DTEND:21111005T133225Z',
            'DURATION:PT1H'
        ])));
    }

    function testAvailableSubComponent() {

        $vcal = <<<VCAL
BEGIN:VCALENDAR
BEGIN:VAVAILABILITY
BEGIN:AVAILABLE
END:AVAILABLE
END:VAVAILABILITY
END:VCALENDAR
VCAL;
        $document = Reader::read($vcal);

        $this->assertInstanceOf(__NAMESPACE__, $document->VAVAILABILITY->AVAILABLE);

    }

    function testRFCxxxSection3_1_availableprop_required() {

        // UID, DTSTAMP and DTSTART are present.
        $this->assertIsValid(Reader::read(
<<<VCAL
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//id
BEGIN:VAVAILABILITY
UID:foo@test
DTSTAMP:20111005T133225Z
BEGIN:AVAILABLE
UID:foo@test
DTSTAMP:20111005T133225Z
DTSTART:20111005T133225Z
END:AVAILABLE
END:VAVAILABILITY
END:VCALENDAR
VCAL
        ));

        // UID, DTSTAMP and DTSTART are missing.
        $this->assertIsNotValid(Reader::read(
<<<VCAL
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//id
BEGIN:VAVAILABILITY
UID:foo@test
DTSTAMP:20111005T133225Z
BEGIN:AVAILABLE
END:AVAILABLE
END:VAVAILABILITY
END:VCALENDAR
VCAL
        ));

        // UID is missing.
        $this->assertIsNotValid(Reader::read(
<<<VCAL
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//id
BEGIN:VAVAILABILITY
UID:foo@test
DTSTAMP:20111005T133225Z
BEGIN:AVAILABLE
DTSTAMP:20111005T133225Z
DTSTART:20111005T133225Z
END:AVAILABLE
END:VAVAILABILITY
END:VCALENDAR
VCAL
        ));

        // DTSTAMP is missing.
        $this->assertIsNotValid(Reader::read(
<<<VCAL
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//id
BEGIN:VAVAILABILITY
UID:foo@test
DTSTAMP:20111005T133225Z
BEGIN:AVAILABLE
UID:foo@test
DTSTART:20111005T133225Z
END:AVAILABLE
END:VAVAILABILITY
END:VCALENDAR
VCAL
        ));

        // DTSTART is missing.
        $this->assertIsNotValid(Reader::read(
<<<VCAL
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//id
BEGIN:VAVAILABILITY
UID:foo@test
DTSTAMP:20111005T133225Z
BEGIN:AVAILABLE
UID:foo@test
DTSTAMP:20111005T133225Z
END:AVAILABLE
END:VAVAILABILITY
END:VCALENDAR
VCAL
        ));

    }

    function testRFCxxxSection3_1_available_dtend_duration() {

        // Only DTEND.
        $this->assertIsValid(Reader::read($this->templateAvailable([
            'DTEND:21111005T133225Z'
        ])));

        // Only DURATION.
        $this->assertIsValid(Reader::read($this->templateAvailable([
            'DURATION:PT1H'
        ])));

        // Both (not allowed).
        $this->assertIsNotValid(Reader::read($this->templateAvailable([
            'DTEND:21111005T133225Z',
            'DURATION:PT1H'
        ])));
    }

    function testRFCxxxSection3_1_available_optional_once() {

        $properties = [
            'CREATED:20111005T135125Z',
            'DESCRIPTION:Long bla bla',
            'LAST-MODIFIED:20111005T135325Z',
            'RECURRENCE-ID;RANGE=THISANDFUTURE:19980401T133000Z',
            'RRULE:FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR',
            'SUMMARY:Bla bla'
        ];

        // They are all present, only once.
        $this->assertIsValid(Reader::read($this->templateAvailable($properties)));

        // We duplicate each one to see if it fails.
        foreach ($properties as $property) {
            $this->assertIsNotValid(Reader::read($this->templateAvailable([
                $property,
                $property
            ])));
        }

    }
    function testRFCxxxSection3_2() {

        $this->assertEquals(
            'BUSY',
            Reader::read($this->templateAvailable([
                'BUSYTYPE:BUSY'
            ]))
                ->VAVAILABILITY
                ->AVAILABLE
                ->BUSYTYPE
                ->getValue()
        );

        $this->assertEquals(
            'BUSY-UNAVAILABLE',
            Reader::read($this->templateAvailable([
                'BUSYTYPE:BUSY-UNAVAILABLE'
            ]))
                ->VAVAILABILITY
                ->AVAILABLE
                ->BUSYTYPE
                ->getValue()
        );

        $this->assertEquals(
            'BUSY-TENTATIVE',
            Reader::read($this->templateAvailable([
                'BUSYTYPE:BUSY-TENTATIVE'
            ]))
                ->VAVAILABILITY
                ->AVAILABLE
                ->BUSYTYPE
                ->getValue()
        );

    }

    protected function assertIsValid(VObject\Document $document) {

        $validationResult = $document->validate();
        if ($validationResult) {
            $messages = array_map(function($item) { return $item['message']; }, $validationResult);
            $this->fail('Failed to assert that the supplied document is a valid document. Validation messages: ' . implode(', ', $messages));
        }
        $this->assertEmpty($document->validate());

    }

    protected function assertIsNotValid(VObject\Document $document) {

        $this->assertNotEmpty($document->validate());

    }

    protected function template(array $properties) {

        return $this->_template(
            <<<VCAL
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//id
BEGIN:VAVAILABILITY
UID:foo@test
DTSTAMP:20111005T133225Z
…
END:VAVAILABILITY
END:VCALENDAR
VCAL
,
            $properties
        );

    }

    protected function templateAvailable(array $properties) {

        return $this->_template(
            <<<VCAL
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//id
BEGIN:VAVAILABILITY
UID:foo@test
DTSTAMP:20111005T133225Z
BEGIN:AVAILABLE
UID:foo@test
DTSTAMP:20111005T133225Z
DTSTART:20111005T133225Z
…
END:AVAILABLE
END:VAVAILABILITY
END:VCALENDAR
VCAL
,
            $properties
        );

    }

    protected function _template($template, array $properties) {

        return str_replace('…', implode("\r\n", $properties), $template);

    }

}
