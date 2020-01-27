<?php

namespace Sabre\CalDAV\Subscriptions;

use Sabre\CalDAV;
use Sabre\HTTP\Request;

class CreateSubscriptionTest extends \Sabre\DAVServerTest {

    protected $setupCalDAV = true;
    protected $setupCalDAVSubscriptions = true;

    /**
     * OS X 10.7 - 10.9.1
     */
    function testMKCOL() {

        $body = <<<XML
<A:mkcol xmlns:A="DAV:">
    <A:set>
        <A:prop>
            <B:subscribed-strip-attachments xmlns:B="http://calendarserver.org/ns/" />
            <B:subscribed-strip-todos xmlns:B="http://calendarserver.org/ns/" />
            <A:resourcetype>
                <A:collection />
                <B:subscribed xmlns:B="http://calendarserver.org/ns/" />
            </A:resourcetype>
            <E:calendar-color xmlns:E="http://apple.com/ns/ical/">#1C4587FF</E:calendar-color>
            <A:displayname>Jewish holidays</A:displayname>
            <C:calendar-description xmlns:C="urn:ietf:params:xml:ns:caldav">Foo</C:calendar-description>
            <E:calendar-order xmlns:E="http://apple.com/ns/ical/">19</E:calendar-order>
            <B:source xmlns:B="http://calendarserver.org/ns/">
                <A:href>webcal://www.example.org/</A:href>
            </B:source>
            <E:refreshrate xmlns:E="http://apple.com/ns/ical/">P1W</E:refreshrate>
            <B:subscribed-strip-alarms xmlns:B="http://calendarserver.org/ns/" />
        </A:prop>
    </A:set>
</A:mkcol>
XML;

        $headers = [
            'Content-Type' => 'application/xml',
        ];
        $request = new Request('MKCOL', '/calendars/user1/subscription1', $headers, $body);

        $response = $this->request($request);
        $this->assertEquals(201, $response->getStatus());
        $subscriptions = $this->caldavBackend->getSubscriptionsForUser('principals/user1');
        $this->assertSubscription($subscriptions[0]);


    }
    /**
     * OS X 10.9.2 and up
     */
    function testMKCALENDAR() {

        $body = <<<XML
<B:mkcalendar xmlns:B="urn:ietf:params:xml:ns:caldav">
    <A:set xmlns:A="DAV:">
        <A:prop>
            <B:supported-calendar-component-set>
                <B:comp name="VEVENT" />
            </B:supported-calendar-component-set>
            <C:subscribed-strip-alarms xmlns:C="http://calendarserver.org/ns/" />
            <C:subscribed-strip-attachments xmlns:C="http://calendarserver.org/ns/" />
            <A:resourcetype>
                <A:collection />
                <C:subscribed xmlns:C="http://calendarserver.org/ns/" />
            </A:resourcetype>
            <D:refreshrate xmlns:D="http://apple.com/ns/ical/">P1W</D:refreshrate>
            <C:source xmlns:C="http://calendarserver.org/ns/">
                <A:href>webcal://www.example.org/</A:href>
            </C:source>
            <D:calendar-color xmlns:D="http://apple.com/ns/ical/">#1C4587FF</D:calendar-color>
            <D:calendar-order xmlns:D="http://apple.com/ns/ical/">19</D:calendar-order>
            <B:calendar-description>Foo</B:calendar-description>
            <C:subscribed-strip-todos xmlns:C="http://calendarserver.org/ns/" />
            <A:displayname>Jewish holidays</A:displayname>
        </A:prop>
    </A:set>
</B:mkcalendar>
XML;

        $headers = [
            'Content-Type' => 'application/xml',
        ];
        $request = new Request('MKCALENDAR', '/calendars/user1/subscription1', $headers, $body);

        $response = $this->request($request);
        $this->assertEquals(201, $response->getStatus());
        $subscriptions = $this->caldavBackend->getSubscriptionsForUser('principals/user1');
        $this->assertSubscription($subscriptions[0]);

        // Also seeing if it works when calling this as a PROPFIND.
        $this->assertEquals([
                '{http://calendarserver.org/ns/}subscribed-strip-alarms' => '',
            ],
            $this->server->getProperties('calendars/user1/subscription1', ['{http://calendarserver.org/ns/}subscribed-strip-alarms'])
        );


    }

    function assertSubscription($subscription) {

        $this->assertEquals('', $subscription['{http://calendarserver.org/ns/}subscribed-strip-attachments']);
        $this->assertEquals('', $subscription['{http://calendarserver.org/ns/}subscribed-strip-todos']);
        $this->assertEquals('#1C4587FF', $subscription['{http://apple.com/ns/ical/}calendar-color']);
        $this->assertEquals('Jewish holidays', $subscription['{DAV:}displayname']);
        $this->assertEquals('Foo', $subscription['{urn:ietf:params:xml:ns:caldav}calendar-description']);
        $this->assertEquals('19', $subscription['{http://apple.com/ns/ical/}calendar-order']);
        $this->assertEquals('webcal://www.example.org/', $subscription['{http://calendarserver.org/ns/}source']->getHref());
        $this->assertEquals('P1W', $subscription['{http://apple.com/ns/ical/}refreshrate']);
        $this->assertEquals('subscription1', $subscription['uri']);
        $this->assertEquals('principals/user1', $subscription['principaluri']);
        $this->assertEquals('webcal://www.example.org/', $subscription['source']);
        $this->assertEquals(['principals/user1', 1], $subscription['id']);

    }

}
