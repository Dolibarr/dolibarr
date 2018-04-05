<?php

namespace Sabre\VObject\Parser;

use Sabre\VObject;

class XmlTest extends \PHPUnit_Framework_TestCase {

    use VObject\PHPUnitAssertions;

    function testRFC6321Example1() {

        $this->assertXMLEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar>
  <properties>
   <calscale>
     <text>GREGORIAN</text>
   </calscale>
   <prodid>
    <text>-//Example Inc.//Example Calendar//EN</text>
   </prodid>
   <version>
     <text>2.0</text>
   </version>
  </properties>
  <components>
   <vevent>
    <properties>
     <dtstamp>
       <date-time>2008-02-05T19:12:24Z</date-time>
     </dtstamp>
     <dtstart>
       <date>2008-10-06</date>
     </dtstart>
     <summary>
      <text>Planning meeting</text>
     </summary>
     <uid>
      <text>4088E990AD89CB3DBB484909</text>
     </uid>
    </properties>
   </vevent>
  </components>
 </vcalendar>
</icalendar>
XML
,
            'BEGIN:VCALENDAR' . "\n" .
            // VERSION comes first because this is required by vCard 4.0.
            'VERSION:2.0' . "\n" .
            'CALSCALE:GREGORIAN' . "\n" .
            'PRODID:-//Example Inc.//Example Calendar//EN' . "\n" .
            'BEGIN:VEVENT' . "\n" .
            'DTSTAMP:20080205T191224Z' . "\n" .
            'DTSTART;VALUE=DATE:20081006' . "\n" .
            'SUMMARY:Planning meeting' . "\n" .
            'UID:4088E990AD89CB3DBB484909' . "\n" .
            'END:VEVENT' . "\n" .
            'END:VCALENDAR' . "\n"
        );

    }

    function testRFC6321Example2() {

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
  <vcalendar>
    <properties>
      <prodid>
        <text>-//Example Inc.//Example Client//EN</text>
      </prodid>
      <version>
        <text>2.0</text>
      </version>
    </properties>
    <components>
      <vtimezone>
        <properties>
          <last-modified>
            <date-time>2004-01-10T03:28:45Z</date-time>
          </last-modified>
          <tzid><text>US/Eastern</text></tzid>
        </properties>
        <components>
          <daylight>
            <properties>
              <dtstart>
                <date-time>2000-04-04T02:00:00</date-time>
              </dtstart>
              <rrule>
                <recur>
                  <freq>YEARLY</freq>
                  <byday>1SU</byday>
                  <bymonth>4</bymonth>
                </recur>
              </rrule>
              <tzname>
                <text>EDT</text>
              </tzname>
              <tzoffsetfrom>
                <utc-offset>-05:00</utc-offset>
              </tzoffsetfrom>
              <tzoffsetto>
                <utc-offset>-04:00</utc-offset>
              </tzoffsetto>
            </properties>
          </daylight>
          <standard>
            <properties>
              <dtstart>
                <date-time>2000-10-26T02:00:00</date-time>
              </dtstart>
              <rrule>
                <recur>
                  <freq>YEARLY</freq>
                  <byday>-1SU</byday>
                  <bymonth>10</bymonth>
                </recur>
              </rrule>
              <tzname>
                <text>EST</text>
              </tzname>
              <tzoffsetfrom>
                <utc-offset>-04:00</utc-offset>
              </tzoffsetfrom>
              <tzoffsetto>
                <utc-offset>-05:00</utc-offset>
              </tzoffsetto>
            </properties>
          </standard>
        </components>
      </vtimezone>
      <vevent>
        <properties>
          <dtstamp>
            <date-time>2006-02-06T00:11:21Z</date-time>
          </dtstamp>
          <dtstart>
            <parameters>
              <tzid><text>US/Eastern</text></tzid>
            </parameters>
            <date-time>2006-01-02T12:00:00</date-time>
          </dtstart>
          <duration>
            <duration>PT1H</duration>
          </duration>
          <rrule>
            <recur>
              <freq>DAILY</freq>
              <count>5</count>
            </recur>
          </rrule>
          <rdate>
            <parameters>
              <tzid><text>US/Eastern</text></tzid>
            </parameters>
            <period>
              <start>2006-01-02T15:00:00</start>
              <duration>PT2H</duration>
            </period>
          </rdate>
          <summary>
            <text>Event #2</text>
          </summary>
          <description>
            <text>We are having a meeting all this week at 12
pm for one hour, with an additional meeting on the first day
2 hours long.&#x0a;Please bring your own lunch for the 12 pm
meetings.</text>
          </description>
          <uid>
            <text>00959BC664CA650E933C892C@example.com</text>
          </uid>
        </properties>
      </vevent>
      <vevent>
        <properties>
          <dtstamp>
            <date-time>2006-02-06T00:11:21Z</date-time>
          </dtstamp>
          <dtstart>
            <parameters>
              <tzid><text>US/Eastern</text></tzid>
            </parameters>
            <date-time>2006-01-04T14:00:00</date-time>
          </dtstart>
          <duration>
            <duration>PT1H</duration>
          </duration>
          <recurrence-id>
            <parameters>
              <tzid><text>US/Eastern</text></tzid>
            </parameters>
            <date-time>2006-01-04T12:00:00</date-time>
          </recurrence-id>
          <summary>
            <text>Event #2 bis</text>
          </summary>
          <uid>
            <text>00959BC664CA650E933C892C@example.com</text>
          </uid>
        </properties>
      </vevent>
    </components>
  </vcalendar>
</icalendar>
XML;

        $component = VObject\Reader::readXML($xml);
        $this->assertVObjectEqualsVObject(
            'BEGIN:VCALENDAR' . "\n" .
            'VERSION:2.0' . "\n" .
            'PRODID:-//Example Inc.//Example Client//EN' . "\n" .
            'BEGIN:VTIMEZONE' . "\n" .
            'LAST-MODIFIED:20040110T032845Z' . "\n" .
            'TZID:US/Eastern' . "\n" .
            'BEGIN:DAYLIGHT' . "\n" .
            'DTSTART:20000404T020000' . "\n" .
            'RRULE:FREQ=YEARLY;BYDAY=1SU;BYMONTH=4' . "\n" .
            'TZNAME:EDT' . "\n" .
            'TZOFFSETFROM:-0500' . "\n" .
            'TZOFFSETTO:-0400' . "\n" .
            'END:DAYLIGHT' . "\n" .
            'BEGIN:STANDARD' . "\n" .
            'DTSTART:20001026T020000' . "\n" .
            'RRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=10' . "\n" .
            'TZNAME:EST' . "\n" .
            'TZOFFSETFROM:-0400' . "\n" .
            'TZOFFSETTO:-0500' . "\n" .
            'END:STANDARD' . "\n" .
            'END:VTIMEZONE' . "\n" .
            'BEGIN:VEVENT' . "\n" .
            'DTSTAMP:20060206T001121Z' . "\n" .
            'DTSTART;TZID=US/Eastern:20060102T120000' . "\n" .
            'DURATION:PT1H' . "\n" .
            'RRULE:FREQ=DAILY;COUNT=5' . "\n" .
            'RDATE;TZID=US/Eastern;VALUE=PERIOD:20060102T150000/PT2H' . "\n" .
            'SUMMARY:Event #2' . "\n" .
            'DESCRIPTION:We are having a meeting all this week at 12\npm for one hour\, ' . "\n" .
            ' with an additional meeting on the first day\n2 hours long.\nPlease bring y' . "\n" .
            ' our own lunch for the 12 pm\nmeetings.' . "\n" .
            'UID:00959BC664CA650E933C892C@example.com' . "\n" .
            'END:VEVENT' . "\n" .
            'BEGIN:VEVENT' . "\n" .
            'DTSTAMP:20060206T001121Z' . "\n" .
            'DTSTART;TZID=US/Eastern:20060104T140000' . "\n" .
            'DURATION:PT1H' . "\n" .
            'RECURRENCE-ID;TZID=US/Eastern:20060104T120000' . "\n" .
            'SUMMARY:Event #2 bis' . "\n" .
            'UID:00959BC664CA650E933C892C@example.com' . "\n" .
            'END:VEVENT' . "\n" .
            'END:VCALENDAR' . "\n",
            VObject\Writer::write($component)
        );

    }

    /**
     * iCalendar Stream.
     */
    function testRFC6321Section3_2() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar/>
</icalendar>
XML
,
            'BEGIN:VCALENDAR' . "\n" .
            'END:VCALENDAR' . "\n"
        );
    }

    /**
     * All components exist.
     */
    function testRFC6321Section3_3() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar>
  <components>
   <vtimezone/>
   <vevent/>
   <vtodo/>
   <vjournal/>
   <vfreebusy/>
   <standard/>
   <daylight/>
   <valarm/>
  </components>
 </vcalendar>
</icalendar>
XML
,
            'BEGIN:VCALENDAR' . "\n" .
            'BEGIN:VTIMEZONE' . "\n" .
            'END:VTIMEZONE' . "\n" .
            'BEGIN:VEVENT' . "\n" .
            'END:VEVENT' . "\n" .
            'BEGIN:VTODO' . "\n" .
            'END:VTODO' . "\n" .
            'BEGIN:VJOURNAL' . "\n" .
            'END:VJOURNAL' . "\n" .
            'BEGIN:VFREEBUSY' . "\n" .
            'END:VFREEBUSY' . "\n" .
            'BEGIN:STANDARD' . "\n" .
            'END:STANDARD' . "\n" .
            'BEGIN:DAYLIGHT' . "\n" .
            'END:DAYLIGHT' . "\n" .
            'BEGIN:VALARM' . "\n" .
            'END:VALARM' . "\n" .
            'END:VCALENDAR' . "\n"
        );

    }

    /**
     * Properties, Special Cases, GEO.
     */
    function testRFC6321Section3_4_1_2() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar>
  <properties>
   <geo>
    <latitude>37.386013</latitude>
    <longitude>-122.082932</longitude>
   </geo>
  </properties>
 </vcalendar>
</icalendar>
XML
,
            'BEGIN:VCALENDAR' . "\n" .
            'GEO:37.386013;-122.082932' . "\n" .
            'END:VCALENDAR' . "\n"
        );

    }

    /**
     * Properties, Special Cases, REQUEST-STATUS.
     */
    function testRFC6321Section3_4_1_3() {

        // Example 1 of RFC5545, Section 3.8.8.3.
        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar>
  <properties>
   <request-status>
    <code>2.0</code>
    <description>Success</description>
   </request-status>
  </properties>
 </vcalendar>
</icalendar>
XML
,
            'BEGIN:VCALENDAR' . "\n" .
            'REQUEST-STATUS:2.0;Success' . "\n" .
            'END:VCALENDAR' . "\n"
        );

        // Example 2 of RFC5545, Section 3.8.8.3.
        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar>
  <properties>
   <request-status>
    <code>3.1</code>
    <description>Invalid property value</description>
    <data>DTSTART:96-Apr-01</data>
   </request-status>
  </properties>
 </vcalendar>
</icalendar>
XML
,
            'BEGIN:VCALENDAR' . "\n" .
            'REQUEST-STATUS:3.1;Invalid property value;DTSTART:96-Apr-01' . "\n" .
            'END:VCALENDAR' . "\n"
        );

        // Example 3 of RFC5545, Section 3.8.8.3.
        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar>
  <properties>
   <request-status>
    <code>2.8</code>
    <description>Success, repeating event ignored. Scheduled as a single event.</description>
    <data>RRULE:FREQ=WEEKLY;INTERVAL=2</data>
   </request-status>
  </properties>
 </vcalendar>
</icalendar>
XML
,
            'BEGIN:VCALENDAR' . "\n" .
            'REQUEST-STATUS:2.8;Success\, repeating event ignored. Scheduled as a single' . "\n" .
            '  event.;RRULE:FREQ=WEEKLY\;INTERVAL=2' . "\n" .
            'END:VCALENDAR' . "\n"
        );

        // Example 4 of RFC5545, Section 3.8.8.3.
        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar>
  <properties>
   <request-status>
    <code>4.1</code>
    <description>Event conflict.  Date-time is busy.</description>
   </request-status>
  </properties>
 </vcalendar>
</icalendar>
XML
,
            'BEGIN:VCALENDAR' . "\n" .
            'REQUEST-STATUS:4.1;Event conflict.  Date-time is busy.' . "\n" .
            'END:VCALENDAR' . "\n"
        );

        // Example 5 of RFC5545, Section 3.8.8.3.
        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar>
  <properties>
   <request-status>
    <code>3.7</code>
    <description>Invalid calendar user</description>
    <data>ATTENDEE:mailto:jsmith@example.com</data>
   </request-status>
  </properties>
 </vcalendar>
</icalendar>
XML
,
            'BEGIN:VCALENDAR' . "\n" .
            'REQUEST-STATUS:3.7;Invalid calendar user;ATTENDEE:mailto:jsmith@example.com' . "\n" .
            'END:VCALENDAR' . "\n"
        );

    }

    /**
     * Values, Binary.
     */
    function testRFC6321Section3_6_1() {

        $this->assertXMLEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar>
  <properties>
   <attach>
    <binary>SGVsbG8gV29ybGQh</binary>
   </attach>
  </properties>
 </vcalendar>
</icalendar>
XML
,
            'BEGIN:VCALENDAR' . "\n" .
            'ATTACH:SGVsbG8gV29ybGQh' . "\n" .
            'END:VCALENDAR' . "\n"
        );

        // In vCard 4, BINARY no longer exists and is replaced by URI.
        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar>
  <properties>
   <attach>
    <uri>SGVsbG8gV29ybGQh</uri>
   </attach>
  </properties>
 </vcalendar>
</icalendar>
XML
,
            'BEGIN:VCALENDAR' . "\n" .
            'ATTACH:SGVsbG8gV29ybGQh' . "\n" .
            'END:VCALENDAR' . "\n"
        );

    }

    /**
     * Values, Boolean.
     */
    function testRFC6321Section3_6_2() {

        $this->assertXMLEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar>
  <properties>
   <attendee>
    <parameters>
     <rsvp><boolean>true</boolean></rsvp>
    </parameters>
    <cal-address>mailto:cyrus@example.com</cal-address>
   </attendee>
  </properties>
 </vcalendar>
</icalendar>
XML
,
            'BEGIN:VCALENDAR' . "\n" .
            'ATTENDEE;RSVP=true:mailto:cyrus@example.com' . "\n" .
            'END:VCALENDAR' . "\n"
        );

    }

    /**
     * Values, Calendar User Address.
     */
    function testRFC6321Section3_6_3() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar>
  <properties>
   <attendee>
    <cal-address>mailto:cyrus@example.com</cal-address>
   </attendee>
  </properties>
 </vcalendar>
</icalendar>
XML
,
            'BEGIN:VCALENDAR' . "\n" .
            'ATTENDEE:mailto:cyrus@example.com' . "\n" .
            'END:VCALENDAR' . "\n"
        );

    }

    /**
     * Values, Date.
     */
    function testRFC6321Section3_6_4() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar>
  <properties>
   <dtstart>
    <date>2011-05-17</date>
   </dtstart>
  </properties>
 </vcalendar>
</icalendar>
XML
,
            'BEGIN:VCALENDAR' . "\n" .
            'DTSTART;VALUE=DATE:20110517' . "\n" .
            'END:VCALENDAR' . "\n"
        );

    }

    /**
     * Values, Date-Time.
     */
    function testRFC6321Section3_6_5() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar>
  <properties>
   <dtstart>
    <date-time>2011-05-17T12:00:00</date-time>
   </dtstart>
  </properties>
 </vcalendar>
</icalendar>
XML
,
            'BEGIN:VCALENDAR' . "\n" .
            'DTSTART:20110517T120000' . "\n" .
            'END:VCALENDAR' . "\n"
        );

    }

    /**
     * Values, Duration.
     */
    function testRFC6321Section3_6_6() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar>
  <properties>
   <duration>
    <duration>P1D</duration>
   </duration>
  </properties>
 </vcalendar>
</icalendar>
XML
,
            'BEGIN:VCALENDAR' . "\n" .
            'DURATION:P1D' . "\n" .
            'END:VCALENDAR' . "\n"
        );

    }

    /**
     * Values, Float.
     */
    function testRFC6321Section3_6_7() {

        // GEO uses <float /> with a positive and a non-negative numbers.
        $this->testRFC6321Section3_4_1_2();

    }

    /**
     * Values, Integer.
     */
    function testRFC6321Section3_6_8() {

        $this->assertXMLEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar>
  <properties>
   <foo>
    <integer>42</integer>
   </foo>
  </properties>
 </vcalendar>
</icalendar>
XML
,
            'BEGIN:VCALENDAR' . "\n" .
            'FOO:42' . "\n" .
            'END:VCALENDAR' . "\n"
        );

        $this->assertXMLEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar>
  <properties>
   <foo>
    <integer>-42</integer>
   </foo>
  </properties>
 </vcalendar>
</icalendar>
XML
,
            'BEGIN:VCALENDAR' . "\n" .
            'FOO:-42' . "\n" .
            'END:VCALENDAR' . "\n"
        );

    }

    /**
     * Values, Period of Time.
     */
    function testRFC6321Section3_6_9() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar>
  <properties>
   <freebusy>
    <period>
     <start>2011-05-17T12:00:00</start>
     <duration>P1H</duration>
    </period>
   </freebusy>
  </properties>
 </vcalendar>
</icalendar>
XML
,
            'BEGIN:VCALENDAR' . "\n" .
            'FREEBUSY:20110517T120000/P1H' . "\n" .
            'END:VCALENDAR' . "\n"
        );

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar>
  <properties>
   <freebusy>
    <period>
     <start>2011-05-17T12:00:00</start>
     <end>2012-05-17T12:00:00</end>
    </period>
   </freebusy>
  </properties>
 </vcalendar>
</icalendar>
XML
,
            'BEGIN:VCALENDAR' . "\n" .
            'FREEBUSY:20110517T120000/20120517T120000' . "\n" .
            'END:VCALENDAR' . "\n"
        );

    }

    /**
     * Values, Recurrence Rule.
     */
    function testRFC6321Section3_6_10() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar>
  <properties>
   <rrule>
    <recur>
     <freq>YEARLY</freq>
     <count>5</count>
     <byday>-1SU</byday>
     <bymonth>10</bymonth>
    </recur>
   </rrule>
  </properties>
 </vcalendar>
</icalendar>
XML
,
            'BEGIN:VCALENDAR' . "\n" .
            'RRULE:FREQ=YEARLY;COUNT=5;BYDAY=-1SU;BYMONTH=10' . "\n" .
            'END:VCALENDAR' . "\n"
        );

    }

    /**
     * Values, Text.
     */
    function testRFC6321Section3_6_11() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar>
  <properties>
   <calscale>
    <text>GREGORIAN</text>
   </calscale>
  </properties>
 </vcalendar>
</icalendar>
XML
,
            'BEGIN:VCALENDAR' . "\n" .
            'CALSCALE:GREGORIAN' . "\n" .
            'END:VCALENDAR' . "\n"
        );

    }

    /**
     * Values, Time.
     */
    function testRFC6321Section3_6_12() {

        $this->assertXMLEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar>
  <properties>
   <foo>
    <time>12:00:00</time>
   </foo>
  </properties>
 </vcalendar>
</icalendar>
XML
,
            'BEGIN:VCALENDAR' . "\n" .
            'FOO:120000' . "\n" .
            'END:VCALENDAR' . "\n"
        );

    }

    /**
     * Values, URI.
     */
    function testRFC6321Section3_6_13() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar>
  <properties>
   <attach>
    <uri>http://calendar.example.com</uri>
   </attach>
  </properties>
 </vcalendar>
</icalendar>
XML
,
            'BEGIN:VCALENDAR' . "\n" .
            'ATTACH:http://calendar.example.com' . "\n" .
            'END:VCALENDAR' . "\n"
        );

    }

    /**
     * Values, UTC Offset.
     */
    function testRFC6321Section3_6_14() {

        // Example 1 of RFC5545, Section 3.3.14.
        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar>
  <properties>
   <tzoffsetfrom>
    <utc-offset>-05:00</utc-offset>
   </tzoffsetfrom>
  </properties>
 </vcalendar>
</icalendar>
XML
,
            'BEGIN:VCALENDAR' . "\n" .
            'TZOFFSETFROM:-0500' . "\n" .
            'END:VCALENDAR' . "\n"
        );

        // Example 2 of RFC5545, Section 3.3.14.
        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar>
  <properties>
   <tzoffsetfrom>
    <utc-offset>+01:00</utc-offset>
   </tzoffsetfrom>
  </properties>
 </vcalendar>
</icalendar>
XML
,
            'BEGIN:VCALENDAR' . "\n" .
            'TZOFFSETFROM:+0100' . "\n" .
            'END:VCALENDAR' . "\n"
        );

    }

    /**
     * Handling Unrecognized Properties or Parameters.
     */
    function testRFC6321Section5() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar>
  <properties>
   <x-property>
    <unknown>20110512T120000Z</unknown>
   </x-property>
  </properties>
 </vcalendar>
</icalendar>
XML
,
            'BEGIN:VCALENDAR' . "\n" .
            'X-PROPERTY:20110512T120000Z' . "\n" .
            'END:VCALENDAR' . "\n"
        );

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar>
  <properties>
   <dtstart>
    <parameters>
     <x-param>
      <text>PT30M</text>
     </x-param>
    </parameters>
    <date-time>2011-05-12T13:00:00Z</date-time>
   </dtstart>
  </properties>
 </vcalendar>
</icalendar>
XML
,
            'BEGIN:VCALENDAR' . "\n" .
            'DTSTART;X-PARAM=PT30M:20110512T130000Z' . "\n" .
            'END:VCALENDAR' . "\n"
        );

    }

    function testRDateWithDateTime() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar>
  <properties>
   <rdate>
    <date-time>2008-02-05T19:12:24Z</date-time>
   </rdate>
  </properties>
 </vcalendar>
</icalendar>
XML
,
            'BEGIN:VCALENDAR' . "\n" .
            'RDATE:20080205T191224Z' . "\n" .
            'END:VCALENDAR' . "\n"
        );

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar>
  <properties>
   <rdate>
    <date-time>2008-02-05T19:12:24Z</date-time>
    <date-time>2009-02-05T19:12:24Z</date-time>
   </rdate>
  </properties>
 </vcalendar>
</icalendar>
XML
,
            'BEGIN:VCALENDAR' . "\n" .
            'RDATE:20080205T191224Z,20090205T191224Z' . "\n" .
            'END:VCALENDAR' . "\n"
        );

    }

    function testRDateWithDate() {

        $this->assertXMLEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar>
  <properties>
   <rdate>
    <date>2008-10-06</date>
   </rdate>
  </properties>
 </vcalendar>
</icalendar>
XML
,
            'BEGIN:VCALENDAR' . "\n" .
            'RDATE:20081006' . "\n" .
            'END:VCALENDAR' . "\n"
        );

        $this->assertXMLEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar>
  <properties>
   <rdate>
    <date>2008-10-06</date>
    <date>2009-10-06</date>
    <date>2010-10-06</date>
   </rdate>
  </properties>
 </vcalendar>
</icalendar>
XML
,
            'BEGIN:VCALENDAR' . "\n" .
            'RDATE:20081006,20091006,20101006' . "\n" .
            'END:VCALENDAR' . "\n"
        );

    }

    function testRDateWithPeriod() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar>
  <properties>
   <rdate>
    <parameters>
     <tzid>
      <text>US/Eastern</text>
     </tzid>
    </parameters>
    <period>
     <start>2006-01-02T15:00:00</start>
     <duration>PT2H</duration>
    </period>
   </rdate>
  </properties>
 </vcalendar>
</icalendar>
XML
,
            'BEGIN:VCALENDAR' . "\n" .
            'RDATE;TZID=US/Eastern;VALUE=PERIOD:20060102T150000/PT2H' . "\n" .
            'END:VCALENDAR' . "\n"
        );

        $this->assertXMLEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0">
 <vcalendar>
  <properties>
   <rdate>
    <parameters>
     <tzid>
      <text>US/Eastern</text>
     </tzid>
    </parameters>
    <period>
     <start>2006-01-02T15:00:00</start>
     <duration>PT2H</duration>
    </period>
    <period>
     <start>2008-01-02T15:00:00</start>
     <duration>PT1H</duration>
    </period>
   </rdate>
  </properties>
 </vcalendar>
</icalendar>
XML
,
            'BEGIN:VCALENDAR' . "\n" .
            'RDATE;TZID=US/Eastern;VALUE=PERIOD:20060102T150000/PT2H,20080102T150000/PT1' . "\n" .
            ' H' . "\n" .
            'END:VCALENDAR' . "\n"
        );

    }

    /**
     * Basic example.
     */
    function testRFC6351Basic() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <fn>
   <text>J. Doe</text>
  </fn>
  <n>
   <surname>Doe</surname>
   <given>J.</given>
   <additional/>
   <prefix/>
   <suffix/>
  </n>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'FN:J. Doe' . "\n" .
            'N:Doe;J.;;;' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Example 1.
     */
    function testRFC6351Example1() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <fn>
   <text>J. Doe</text>
  </fn>
  <n>
   <surname>Doe</surname>
   <given>J.</given>
   <additional/>
   <prefix/>
   <suffix/>
  </n>
  <x-file>
   <parameters>
    <mediatype>
     <text>image/jpeg</text>
    </mediatype>
   </parameters>
   <unknown>alien.jpg</unknown>
  </x-file>
  <x1:a href="http://www.example.com" xmlns:x1="http://www.w3.org/1999/xhtml">My web page!</x1:a>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'FN:J. Doe' . "\n" .
            'N:Doe;J.;;;' . "\n" .
            'X-FILE;MEDIATYPE=image/jpeg:alien.jpg' . "\n" .
            'XML:<a xmlns="http://www.w3.org/1999/xhtml" href="http://www.example.com">M' . "\n" .
            ' y web page!</a>' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Design Considerations.
     */
    function testRFC6351Section5() {

        $this->assertXMLEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <tel>
   <parameters>
    <type>
     <text>voice</text>
     <text>video</text>
    </type>
   </parameters>
   <uri>tel:+1-555-555-555</uri>
  </tel>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'TEL;TYPE="voice,video":tel:+1-555-555-555' . "\n" .
            'END:VCARD' . "\n"
        );

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <tel>
   <parameters>
    <type>
     <text>voice</text>
     <text>video</text>
    </type>
   </parameters>
   <text>tel:+1-555-555-555</text>
  </tel>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'TEL;TYPE="voice,video":tel:+1-555-555-555' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Design Considerations.
     */
    function testRFC6351Section5Group() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <tel>
   <text>tel:+1-555-555-556</text>
  </tel>
  <group name="contact">
   <tel>
    <text>tel:+1-555-555-555</text>
   </tel>
   <fn>
    <text>Gordon</text>
   </fn>
  </group>
  <group name="media">
   <fn>
    <text>Gordon</text>
   </fn>
  </group>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'TEL:tel:+1-555-555-556' . "\n" .
            'contact.TEL:tel:+1-555-555-555' . "\n" .
            'contact.FN:Gordon' . "\n" .
            'media.FN:Gordon' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Extensibility.
     */
    function testRFC6351Section5_1_NoNamespace() {

        $this->assertXMLEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <x-my-prop>
   <parameters>
    <pref>
     <integer>1</integer>
    </pref>
   </parameters>
   <text>value goes here</text>
  </x-my-prop>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'X-MY-PROP;PREF=1:value goes here' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Section 4.3.1 of Relax NG Schema: value-date.
     */
    function testRFC6351ValueDateWithYearMonthDay() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <bday>
   <date-and-or-time>20150128</date-and-or-time>
  </bday>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'BDAY:20150128' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Section 4.3.1 of Relax NG Schema: value-date.
     */
    function testRFC6351ValueDateWithYearMonth() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <bday>
   <date-and-or-time>2015-01</date-and-or-time>
  </bday>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'BDAY:2015-01' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Section 4.3.1 of Relax NG Schema: value-date.
     */
    function testRFC6351ValueDateWithMonth() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <bday>
   <date-and-or-time>--01</date-and-or-time>
  </bday>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'BDAY:--01' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Section 4.3.1 of Relax NG Schema: value-date.
     */
    function testRFC6351ValueDateWithMonthDay() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <bday>
   <date-and-or-time>--0128</date-and-or-time>
  </bday>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'BDAY:--0128' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Section 4.3.1 of Relax NG Schema: value-date.
     */
    function testRFC6351ValueDateWithDay() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <bday>
   <date-and-or-time>---28</date-and-or-time>
  </bday>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'BDAY:---28' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Section 4.3.2 of Relax NG Schema: value-time.
     */
    function testRFC6351ValueTimeWithHour() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <bday>
   <date-and-or-time>13</date-and-or-time>
  </bday>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'BDAY:13' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Section 4.3.2 of Relax NG Schema: value-time.
     */
    function testRFC6351ValueTimeWithHourMinute() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <bday>
   <date-and-or-time>1353</date-and-or-time>
  </bday>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'BDAY:1353' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Section 4.3.2 of Relax NG Schema: value-time.
     */
    function testRFC6351ValueTimeWithHourMinuteSecond() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <bday>
   <date-and-or-time>135301</date-and-or-time>
  </bday>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'BDAY:135301' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Section 4.3.2 of Relax NG Schema: value-time.
     */
    function testRFC6351ValueTimeWithMinute() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <bday>
   <date-and-or-time>-53</date-and-or-time>
  </bday>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'BDAY:-53' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Section 4.3.2 of Relax NG Schema: value-time.
     */
    function testRFC6351ValueTimeWithMinuteSecond() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <bday>
   <date-and-or-time>-5301</date-and-or-time>
  </bday>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'BDAY:-5301' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Section 4.3.2 of Relax NG Schema: value-time.
     */
    function testRFC6351ValueTimeWithSecond() {

        $this->assertTrue(true);

        /*
         * According to the Relax NG Schema, there is a conflict between
         * value-date and value-time. The --01 syntax can only match a
         * value-date because of the higher priority set in
         * value-date-and-or-time. So we basically skip this test.
         *
        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <bday>
   <date-and-or-time>--01</date-and-or-time>
  </bday>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'BDAY:--01' . "\n" .
            'END:VCARD' . "\n"
        );
        */

    }

    /**
     * Section 4.3.2 of Relax NG Schema: value-time.
     */
    function testRFC6351ValueTimeWithSecondZ() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <bday>
   <date-and-or-time>--01Z</date-and-or-time>
  </bday>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'BDAY:--01Z' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Section 4.3.2 of Relax NG Schema: value-time.
     */
    function testRFC6351ValueTimeWithSecondTZ() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <bday>
   <date-and-or-time>--01+1234</date-and-or-time>
  </bday>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'BDAY:--01+1234' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Section 4.3.3 of Relax NG Schema: value-date-time.
     */
    function testRFC6351ValueDateTimeWithYearMonthDayHour() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <bday>
   <date-and-or-time>20150128T13</date-and-or-time>
  </bday>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'BDAY:20150128T13' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Section 4.3.3 of Relax NG Schema: value-date-time.
     */
    function testRFC6351ValueDateTimeWithMonthDayHour() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <bday>
   <date-and-or-time>--0128T13</date-and-or-time>
  </bday>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'BDAY:--0128T13' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Section 4.3.3 of Relax NG Schema: value-date-time.
     */
    function testRFC6351ValueDateTimeWithDayHour() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <bday>
   <date-and-or-time>---28T13</date-and-or-time>
  </bday>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'BDAY:---28T13' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Section 4.3.3 of Relax NG Schema: value-date-time.
     */
    function testRFC6351ValueDateTimeWithDayHourMinute() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <bday>
   <date-and-or-time>---28T1353</date-and-or-time>
  </bday>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'BDAY:---28T1353' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Section 4.3.3 of Relax NG Schema: value-date-time.
     */
    function testRFC6351ValueDateTimeWithDayHourMinuteSecond() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <bday>
   <date-and-or-time>---28T135301</date-and-or-time>
  </bday>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'BDAY:---28T135301' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Section 4.3.3 of Relax NG Schema: value-date-time.
     */
    function testRFC6351ValueDateTimeWithDayHourZ() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <bday>
   <date-and-or-time>---28T13Z</date-and-or-time>
  </bday>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'BDAY:---28T13Z' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Section 4.3.3 of Relax NG Schema: value-date-time.
     */
    function testRFC6351ValueDateTimeWithDayHourTZ() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <bday>
   <date-and-or-time>---28T13+1234</date-and-or-time>
  </bday>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'BDAY:---28T13+1234' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Property: SOURCE.
     */
    function testRFC6350Section6_1_3() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <source>
   <uri>ldap://ldap.example.com/cn=Babs%20Jensen,%20o=Babsco,%20c=US</uri>
  </source>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'SOURCE:ldap://ldap.example.com/cn=Babs%20Jensen\,%20o=Babsco\,%20c=US' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Property: KIND.
     */
    function testRFC6350Section6_1_4() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <kind>
   <text>individual</text>
  </kind>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'KIND:individual' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Property: FN.
     */
    function testRFC6350Section6_2_1() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <fn>
   <text>Mr. John Q. Public, Esq.</text>
  </fn>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'FN:Mr. John Q. Public\, Esq.' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Property: N.
     */
    function testRFC6350Section6_2_2() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <n>
   <surname>Stevenson</surname>
   <given>John</given>
   <additional>Philip,Paul</additional>
   <prefix>Dr.</prefix>
   <suffix>Jr.,M.D.,A.C.P.</suffix>
  </n>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'N:Stevenson;John;Philip\,Paul;Dr.;Jr.\,M.D.\,A.C.P.' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Property: NICKNAME.
     */
    function testRFC6350Section6_2_3() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <nickname>
   <text>Jim</text>
   <text>Jimmie</text>
  </nickname>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'NICKNAME:Jim,Jimmie' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Property: PHOTO.
     */
    function testRFC6350Section6_2_4() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <photo>
   <uri>http://www.example.com/pub/photos/jqpublic.gif</uri>
  </photo>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'PHOTO:http://www.example.com/pub/photos/jqpublic.gif' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    function testRFC6350Section6_2_5() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <bday>
   <date-and-or-time>19531015T231000Z</date-and-or-time>
  </bday>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'BDAY:19531015T231000Z' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    function testRFC6350Section6_2_6() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <anniversary>
   <date-and-or-time>19960415</date-and-or-time>
  </anniversary>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'ANNIVERSARY:19960415' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Property: GENDER.
     */
    function testRFC6350Section6_2_7() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <gender>
   <sex>Jim</sex>
   <text>Jimmie</text>
  </gender>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'GENDER:Jim;Jimmie' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Property: ADR.
     */
    function testRFC6350Section6_3_1() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <adr>
   <pobox/>
   <ext/>
   <street>123 Main Street</street>
   <locality>Any Town</locality>
   <region>CA</region>
   <code>91921-1234</code>
   <country>U.S.A.</country>
  </adr>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'ADR:;;123 Main Street;Any Town;CA;91921-1234;U.S.A.' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Property: TEL.
     */
    function testRFC6350Section6_4_1() {

        /**
         * Quoting RFC:
         * > Value type:  By default, it is a single free-form text value (for
         * > backward compatibility with vCard 3), but it SHOULD be reset to a
         * > URI value.  It is expected that the URI scheme will be "tel", as
         * > specified in [RFC3966], but other schemes MAY be used.
         *
         * So first, we test xCard/URI to vCard/URI.
         * Then, we test xCard/TEXT to vCard/TEXT to xCard/TEXT.
         */
        $this->assertXMLEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <tel>
   <parameters>
    <type>
     <text>home</text>
    </type>
   </parameters>
   <uri>tel:+33-01-23-45-67</uri>
  </tel>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'TEL;TYPE=home:tel:+33-01-23-45-67' . "\n" .
            'END:VCARD' . "\n"
        );

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <tel>
   <parameters>
    <type>
     <text>home</text>
    </type>
   </parameters>
   <text>tel:+33-01-23-45-67</text>
  </tel>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'TEL;TYPE=home:tel:+33-01-23-45-67' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Property: EMAIL.
     */
    function testRFC6350Section6_4_2() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <email>
   <parameters>
    <type>
     <text>work</text>
    </type>
   </parameters>
   <text>jqpublic@xyz.example.com</text>
  </email>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'EMAIL;TYPE=work:jqpublic@xyz.example.com' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Property: IMPP.
     */
    function testRFC6350Section6_4_3() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <impp>
   <parameters>
    <pref>
     <text>1</text>
    </pref>
   </parameters>
   <uri>xmpp:alice@example.com</uri>
  </impp>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'IMPP;PREF=1:xmpp:alice@example.com' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Property: LANG.
     */
    function testRFC6350Section6_4_4() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <lang>
   <parameters>
    <type>
     <text>work</text>
    </type>
    <pref>
     <text>2</text>
    </pref>
   </parameters>
   <language-tag>en</language-tag>
  </lang>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'LANG;TYPE=work;PREF=2:en' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Property: TZ.
     */
    function testRFC6350Section6_5_1() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <tz>
   <text>Raleigh/North America</text>
  </tz>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'TZ:Raleigh/North America' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Property: GEO.
     */
    function testRFC6350Section6_5_2() {

        $this->assertXMLEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <geo>
   <uri>geo:37.386013,-122.082932</uri>
  </geo>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'GEO:geo:37.386013\,-122.082932' . "\n" .
            'END:VCARD' . "\n"
        );

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <geo>
   <text>geo:37.386013,-122.082932</text>
  </geo>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'GEO:geo:37.386013\,-122.082932' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Property: TITLE.
     */
    function testRFC6350Section6_6_1() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <title>
   <text>Research Scientist</text>
  </title>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'TITLE:Research Scientist' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Property: ROLE.
     */
    function testRFC6350Section6_6_2() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <role>
   <text>Project Leader</text>
  </role>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'ROLE:Project Leader' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Property: LOGO.
     */
    function testRFC6350Section6_6_3() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <logo>
   <uri>http://www.example.com/pub/logos/abccorp.jpg</uri>
  </logo>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'LOGO:http://www.example.com/pub/logos/abccorp.jpg' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Property: ORG.
     */
    function testRFC6350Section6_6_4() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <org>
   <text>ABC, Inc.</text>
   <text>North American Division</text>
   <text>Marketing</text>
  </org>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'ORG:ABC\, Inc.;North American Division;Marketing' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Property: MEMBER.
     */
    function testRFC6350Section6_6_5() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <member>
   <uri>urn:uuid:03a0e51f-d1aa-4385-8a53-e29025acd8af</uri>
  </member>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'MEMBER:urn:uuid:03a0e51f-d1aa-4385-8a53-e29025acd8af' . "\n" .
            'END:VCARD' . "\n"
        );

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <member>
   <uri>mailto:subscriber1@example.com</uri>
  </member>
  <member>
   <uri>xmpp:subscriber2@example.com</uri>
  </member>
  <member>
   <uri>sip:subscriber3@example.com</uri>
  </member>
  <member>
   <uri>tel:+1-418-555-5555</uri>
  </member>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'MEMBER:mailto:subscriber1@example.com' . "\n" .
            'MEMBER:xmpp:subscriber2@example.com' . "\n" .
            'MEMBER:sip:subscriber3@example.com' . "\n" .
            'MEMBER:tel:+1-418-555-5555' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Property: RELATED.
     */
    function testRFC6350Section6_6_6() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <related>
   <parameters>
    <type>
     <text>friend</text>
    </type>
   </parameters>
   <uri>urn:uuid:f81d4fae-7dec-11d0-a765-00a0c91e6bf6</uri>
  </related>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'RELATED;TYPE=friend:urn:uuid:f81d4fae-7dec-11d0-a765-00a0c91e6bf6' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Property: CATEGORIES.
     */
    function testRFC6350Section6_7_1() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <categories>
   <text>INTERNET</text>
   <text>IETF</text>
   <text>INDUSTRY</text>
   <text>INFORMATION TECHNOLOGY</text>
  </categories>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'CATEGORIES:INTERNET,IETF,INDUSTRY,INFORMATION TECHNOLOGY' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Property: NOTE.
     */
    function testRFC6350Section6_7_2() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <note>
   <text>Foo, bar</text>
  </note>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'NOTE:Foo\, bar' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Property: PRODID.
     */
    function testRFC6350Section6_7_3() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <prodid>
   <text>-//ONLINE DIRECTORY//NONSGML Version 1//EN</text>
  </prodid>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'PRODID:-//ONLINE DIRECTORY//NONSGML Version 1//EN' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    function testRFC6350Section6_7_4() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <rev>
   <timestamp>19951031T222710Z</timestamp>
  </rev>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'REV:19951031T222710Z' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Property: SOUND.
     */
    function testRFC6350Section6_7_5() {

        $this->assertXMLEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <sound>
   <uri>CID:JOHNQPUBLIC.part8.19960229T080000.xyzMail@example.com</uri>
  </sound>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'SOUND:CID:JOHNQPUBLIC.part8.19960229T080000.xyzMail@example.com' . "\n" .
            'END:VCARD' . "\n"
        );

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <sound>
   <text>CID:JOHNQPUBLIC.part8.19960229T080000.xyzMail@example.com</text>
  </sound>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'SOUND:CID:JOHNQPUBLIC.part8.19960229T080000.xyzMail@example.com' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Property: UID.
     */
    function testRFC6350Section6_7_6() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <uid>
   <text>urn:uuid:f81d4fae-7dec-11d0-a765-00a0c91e6bf6</text>
  </uid>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'UID:urn:uuid:f81d4fae-7dec-11d0-a765-00a0c91e6bf6' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Property: CLIENTPIDMAP.
     */
    function testRFC6350Section6_7_7() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <clientpidmap>
   <sourceid>1</sourceid>
   <uri>urn:uuid:3df403f4-5924-4bb7-b077-3c711d9eb34b</uri>
  </clientpidmap>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'CLIENTPIDMAP:1;urn:uuid:3df403f4-5924-4bb7-b077-3c711d9eb34b' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Property: URL.
     */
    function testRFC6350Section6_7_8() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <url>
   <uri>http://example.org/restaurant.french/~chezchic.html</uri>
  </url>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'URL:http://example.org/restaurant.french/~chezchic.html' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Property: VERSION.
     */
    function testRFC6350Section6_7_9() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard/>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Property: KEY.
     */
    function testRFC6350Section6_8_1() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <key>
   <parameters>
    <mediatype>
     <text>application/pgp-keys</text>
    </mediatype>
   </parameters>
   <text>ftp://example.com/keys/jdoe</text>
  </key>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'KEY;MEDIATYPE=application/pgp-keys:ftp://example.com/keys/jdoe' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Property: FBURL.
     */
    function testRFC6350Section6_9_1() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <fburl>
   <parameters>
    <pref>
     <text>1</text>
    </pref>
   </parameters>
   <uri>http://www.example.com/busy/janedoe</uri>
  </fburl>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'FBURL;PREF=1:http://www.example.com/busy/janedoe' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Property: CALADRURI.
     */
    function testRFC6350Section6_9_2() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <caladruri>
   <uri>http://example.com/calendar/jdoe</uri>
  </caladruri>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'CALADRURI:http://example.com/calendar/jdoe' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Property: CALURI.
     */
    function testRFC6350Section6_9_3() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <caluri>
   <parameters>
    <pref>
     <text>1</text>
    </pref>
   </parameters>
   <uri>http://cal.example.com/calA</uri>
  </caluri>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'CALURI;PREF=1:http://cal.example.com/calA' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Property: CAPURI.
     */
    function testRFC6350SectionA_3() {

        $this->assertXMLReflexivelyEqualsToMimeDir(
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<vcards xmlns="urn:ietf:params:xml:ns:vcard-4.0">
 <vcard>
  <capuri>
   <uri>http://cap.example.com/capA</uri>
  </capuri>
 </vcard>
</vcards>
XML
,
            'BEGIN:VCARD' . "\n" .
            'VERSION:4.0' . "\n" .
            'CAPURI:http://cap.example.com/capA' . "\n" .
            'END:VCARD' . "\n"
        );

    }

    /**
     * Check this equality:
     *     XML -> object model -> MIME Dir.
     */
    protected function assertXMLEqualsToMimeDir($xml, $mimedir) {

        $component = VObject\Reader::readXML($xml);
        $this->assertVObjectEqualsVObject($mimedir, $component);

    }

    /**
     * Check this (reflexive) equality:
     *     XML -> object model -> MIME Dir -> object model -> XML.
     */
    protected function assertXMLReflexivelyEqualsToMimeDir($xml, $mimedir) {

        $this->assertXMLEqualsToMimeDir($xml, $mimedir);

        $component = VObject\Reader::read($mimedir);
        $this->assertXmlStringEqualsXmlString($xml, VObject\Writer::writeXML($component));

    }
}
