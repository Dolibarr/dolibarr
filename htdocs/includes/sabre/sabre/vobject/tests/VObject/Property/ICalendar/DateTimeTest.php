<?php

namespace Sabre\VObject\Property\ICalendar;

use Sabre\VObject\Component\VCalendar;

class DateTimeTest extends \PHPUnit_Framework_TestCase {

    protected $vcal;

    function setUp() {

        $this->vcal = new VCalendar();

    }

    function testSetDateTime() {

        $tz = new \DateTimeZone('Europe/Amsterdam');
        $dt = new \DateTime('1985-07-04 01:30:00', $tz);
        $dt->setTimeZone($tz);

        $elem = $this->vcal->createProperty('DTSTART');
        $elem->setDateTime($dt);

        $this->assertEquals('19850704T013000', (string)$elem);
        $this->assertEquals('Europe/Amsterdam', (string)$elem['TZID']);
        $this->assertNull($elem['VALUE']);

        $this->assertTrue($elem->hasTime());

    }

    function testSetDateTimeLOCAL() {

        $tz = new \DateTimeZone('Europe/Amsterdam');
        $dt = new \DateTime('1985-07-04 01:30:00', $tz);
        $dt->setTimeZone($tz);

        $elem = $this->vcal->createProperty('DTSTART');
        $elem->setDateTime($dt, $isFloating = true);

        $this->assertEquals('19850704T013000', (string)$elem);
        $this->assertNull($elem['TZID']);

        $this->assertTrue($elem->hasTime());
    }

    function testSetDateTimeUTC() {

        $tz = new \DateTimeZone('GMT');
        $dt = new \DateTime('1985-07-04 01:30:00', $tz);
        $dt->setTimeZone($tz);

        $elem = $this->vcal->createProperty('DTSTART');
        $elem->setDateTime($dt);

        $this->assertEquals('19850704T013000Z', (string)$elem);
        $this->assertNull($elem['TZID']);

        $this->assertTrue($elem->hasTime());
    }

    function testSetDateTimeFromUnixTimestamp() {

        // When initialized from a Unix timestamp, the timezone is set to "+00:00".
        $dt = new \DateTime('@489288600');

        $elem = $this->vcal->createProperty('DTSTART');
        $elem->setDateTime($dt);

        $this->assertEquals('19850704T013000Z', (string)$elem);
        $this->assertNull($elem['TZID']);

        $this->assertTrue($elem->hasTime());
    }

    function testSetDateTimeLOCALTZ() {

        $tz = new \DateTimeZone('Europe/Amsterdam');
        $dt = new \DateTime('1985-07-04 01:30:00', $tz);
        $dt->setTimeZone($tz);

        $elem = $this->vcal->createProperty('DTSTART');
        $elem->setDateTime($dt);

        $this->assertEquals('19850704T013000', (string)$elem);
        $this->assertEquals('Europe/Amsterdam', (string)$elem['TZID']);

        $this->assertTrue($elem->hasTime());
    }

    function testSetDateTimeDATE() {

        $tz = new \DateTimeZone('Europe/Amsterdam');
        $dt = new \DateTime('1985-07-04 01:30:00', $tz);
        $dt->setTimeZone($tz);

        $elem = $this->vcal->createProperty('DTSTART');
        $elem['VALUE'] = 'DATE';
        $elem->setDateTime($dt);

        $this->assertEquals('19850704', (string)$elem);
        $this->assertNull($elem['TZID']);
        $this->assertEquals('DATE', (string)$elem['VALUE']);

        $this->assertFalse($elem->hasTime());
    }

    function testSetValue() {

        $tz = new \DateTimeZone('Europe/Amsterdam');
        $dt = new \DateTime('1985-07-04 01:30:00', $tz);
        $dt->setTimeZone($tz);

        $elem = $this->vcal->createProperty('DTSTART');
        $elem->setValue($dt);

        $this->assertEquals('19850704T013000', (string)$elem);
        $this->assertEquals('Europe/Amsterdam', (string)$elem['TZID']);
        $this->assertNull($elem['VALUE']);

        $this->assertTrue($elem->hasTime());

    }

    function testSetValueArray() {

        $tz = new \DateTimeZone('Europe/Amsterdam');
        $dt1 = new \DateTime('1985-07-04 01:30:00', $tz);
        $dt2 = new \DateTime('1985-07-04 02:30:00', $tz);
        $dt1->setTimeZone($tz);
        $dt2->setTimeZone($tz);

        $elem = $this->vcal->createProperty('DTSTART');
        $elem->setValue([$dt1, $dt2]);

        $this->assertEquals('19850704T013000,19850704T023000', (string)$elem);
        $this->assertEquals('Europe/Amsterdam', (string)$elem['TZID']);
        $this->assertNull($elem['VALUE']);

        $this->assertTrue($elem->hasTime());

    }

    function testSetParts() {

        $tz = new \DateTimeZone('Europe/Amsterdam');
        $dt1 = new \DateTime('1985-07-04 01:30:00', $tz);
        $dt2 = new \DateTime('1985-07-04 02:30:00', $tz);
        $dt1->setTimeZone($tz);
        $dt2->setTimeZone($tz);

        $elem = $this->vcal->createProperty('DTSTART');
        $elem->setParts([$dt1, $dt2]);

        $this->assertEquals('19850704T013000,19850704T023000', (string)$elem);
        $this->assertEquals('Europe/Amsterdam', (string)$elem['TZID']);
        $this->assertNull($elem['VALUE']);

        $this->assertTrue($elem->hasTime());

    }
    function testSetPartsStrings() {

        $dt1 = '19850704T013000Z';
        $dt2 = '19850704T023000Z';

        $elem = $this->vcal->createProperty('DTSTART');
        $elem->setParts([$dt1, $dt2]);

        $this->assertEquals('19850704T013000Z,19850704T023000Z', (string)$elem);
        $this->assertNull($elem['VALUE']);

        $this->assertTrue($elem->hasTime());

    }


    function testGetDateTimeCached() {

        $tz = new \DateTimeZone('Europe/Amsterdam');
        $dt = new \DateTimeImmutable('1985-07-04 01:30:00', $tz);
        $dt->setTimeZone($tz);

        $elem = $this->vcal->createProperty('DTSTART');
        $elem->setDateTime($dt);

        $this->assertEquals($elem->getDateTime(), $dt);

    }

    function testGetDateTimeDateNULL() {

        $elem = $this->vcal->createProperty('DTSTART');
        $dt = $elem->getDateTime();

        $this->assertNull($dt);

    }

    function testGetDateTimeDateDATE() {

        $elem = $this->vcal->createProperty('DTSTART', '19850704');
        $dt = $elem->getDateTime();

        $this->assertInstanceOf('DateTimeImmutable', $dt);
        $this->assertEquals('1985-07-04 00:00:00', $dt->format('Y-m-d H:i:s'));

    }

    function testGetDateTimeDateDATEReferenceTimeZone() {

        $elem = $this->vcal->createProperty('DTSTART', '19850704');

        $tz = new \DateTimeZone('America/Toronto');
        $dt = $elem->getDateTime($tz);
        $dt = $dt->setTimeZone(new \DateTimeZone('UTC'));

        $this->assertInstanceOf('DateTimeImmutable', $dt);
        $this->assertEquals('1985-07-04 04:00:00', $dt->format('Y-m-d H:i:s'));

    }

    function testGetDateTimeDateFloating() {

        $elem = $this->vcal->createProperty('DTSTART', '19850704T013000');
        $dt = $elem->getDateTime();

        $this->assertInstanceOf('DateTimeImmutable', $dt);
        $this->assertEquals('1985-07-04 01:30:00', $dt->format('Y-m-d H:i:s'));

    }

    function testGetDateTimeDateFloatingReferenceTimeZone() {

        $elem = $this->vcal->createProperty('DTSTART', '19850704T013000');

        $tz = new \DateTimeZone('America/Toronto');
        $dt = $elem->getDateTime($tz);
        $dt = $dt->setTimeZone(new \DateTimeZone('UTC'));

        $this->assertInstanceOf('DateTimeInterface', $dt);
        $this->assertEquals('1985-07-04 05:30:00', $dt->format('Y-m-d H:i:s'));

    }

    function testGetDateTimeDateUTC() {

        $elem = $this->vcal->createProperty('DTSTART', '19850704T013000Z');
        $dt = $elem->getDateTime();

        $this->assertInstanceOf('DateTimeImmutable', $dt);
        $this->assertEquals('1985-07-04 01:30:00', $dt->format('Y-m-d H:i:s'));
        $this->assertEquals('UTC', $dt->getTimeZone()->getName());

    }

    function testGetDateTimeDateLOCALTZ() {

        $elem = $this->vcal->createProperty('DTSTART', '19850704T013000');
        $elem['TZID'] = 'Europe/Amsterdam';

        $dt = $elem->getDateTime();

        $this->assertInstanceOf('DateTimeImmutable', $dt);
        $this->assertEquals('1985-07-04 01:30:00', $dt->format('Y-m-d H:i:s'));
        $this->assertEquals('Europe/Amsterdam', $dt->getTimeZone()->getName());

    }

    /**
     * @expectedException \Sabre\VObject\InvalidDataException
     */
    function testGetDateTimeDateInvalid() {

        $elem = $this->vcal->createProperty('DTSTART', 'bla');
        $dt = $elem->getDateTime();

    }

    function testGetDateTimeWeirdTZ() {

        $elem = $this->vcal->createProperty('DTSTART', '19850704T013000');
        $elem['TZID'] = '/freeassociation.sourceforge.net/Tzfile/Europe/Amsterdam';


        $event = $this->vcal->createComponent('VEVENT');
        $event->add($elem);

        $timezone = $this->vcal->createComponent('VTIMEZONE');
        $timezone->TZID = '/freeassociation.sourceforge.net/Tzfile/Europe/Amsterdam';
        $timezone->{'X-LIC-LOCATION'} = 'Europe/Amsterdam';

        $this->vcal->add($event);
        $this->vcal->add($timezone);

        $dt = $elem->getDateTime();

        $this->assertInstanceOf('DateTimeImmutable', $dt);
        $this->assertEquals('1985-07-04 01:30:00', $dt->format('Y-m-d H:i:s'));
        $this->assertEquals('Europe/Amsterdam', $dt->getTimeZone()->getName());

    }

    function testGetDateTimeBadTimeZone() {

        $default = date_default_timezone_get();
        date_default_timezone_set('Canada/Eastern');

        $elem = $this->vcal->createProperty('DTSTART', '19850704T013000');
        $elem['TZID'] = 'Moon';


        $event = $this->vcal->createComponent('VEVENT');
        $event->add($elem);

        $timezone = $this->vcal->createComponent('VTIMEZONE');
        $timezone->TZID = 'Moon';
        $timezone->{'X-LIC-LOCATION'} = 'Moon';


        $this->vcal->add($event);
        $this->vcal->add($timezone);

        $dt = $elem->getDateTime();

        $this->assertInstanceOf('DateTimeImmutable', $dt);
        $this->assertEquals('1985-07-04 01:30:00', $dt->format('Y-m-d H:i:s'));
        $this->assertEquals('Canada/Eastern', $dt->getTimeZone()->getName());
        date_default_timezone_set($default);

    }

    function testUpdateValueParameter() {

        $dtStart = $this->vcal->createProperty('DTSTART', new \DateTime('2013-06-07 15:05:00'));
        $dtStart['VALUE'] = 'DATE';

        $this->assertEquals("DTSTART;VALUE=DATE:20130607\r\n", $dtStart->serialize());

    }

    function testValidate() {

        $exDate = $this->vcal->createProperty('EXDATE', '-00011130T143000Z');
        $messages = $exDate->validate();
        $this->assertEquals(1, count($messages));
        $this->assertEquals(3, $messages[0]['level']);

    }

    /**
     * This issue was discovered on the sabredav mailing list.
     */
    function testCreateDatePropertyThroughAdd() {

        $vcal = new VCalendar();
        $vevent = $vcal->add('VEVENT');

        $dtstart = $vevent->add(
            'DTSTART',
            new \DateTime('2014-03-07'),
            ['VALUE' => 'DATE']
        );

        $this->assertEquals("DTSTART;VALUE=DATE:20140307\r\n", $dtstart->serialize());

    }

}
