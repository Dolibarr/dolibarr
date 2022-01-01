<?php

namespace Sabre\CalDAV\Xml\Property;

use Sabre\CalDAV;
use Sabre\DAV;
use Sabre\DAV\Sharing\Plugin as SP;
use Sabre\DAV\Xml\Element\Sharee;

class InviteTest extends DAV\Xml\XmlTest {

    function setUp() {

        $this->namespaceMap[CalDAV\Plugin::NS_CALDAV] = 'cal';
        $this->namespaceMap[CalDAV\Plugin::NS_CALENDARSERVER] = 'cs';


    }

    function testSimple() {

        $invite = new Invite([]);
        $this->assertInstanceOf('Sabre\CalDAV\Xml\Property\Invite', $invite);
        $this->assertEquals([], $invite->getValue());

    }

    /**
     * @depends testSimple
     */
    function testSerialize() {

        $property = new Invite([
            new Sharee([
                'href'         => 'mailto:thedoctor@example.org',
                'properties'   => ['{DAV:}displayname' => 'The Doctor'],
                'inviteStatus' => SP::INVITE_ACCEPTED,
                'access'       => SP::ACCESS_SHAREDOWNER,
            ]),
            new Sharee([
                'href'         => 'mailto:user1@example.org',
                'inviteStatus' => SP::INVITE_ACCEPTED,
                'access'       => SP::ACCESS_READWRITE,
            ]),
            new Sharee([
                'href'         => 'mailto:user2@example.org',
                'properties'   => ['{DAV:}displayname' => 'John Doe'],
                'inviteStatus' => SP::INVITE_DECLINED,
                'access'       => SP::ACCESS_READ,
            ]),
            new Sharee([
                'href'         => 'mailto:user3@example.org',
                'properties'   => ['{DAV:}displayname' => 'Joe Shmoe'],
                'inviteStatus' => SP::INVITE_NORESPONSE,
                'access'       => SP::ACCESS_READ,
                'comment'      => 'Something, something',
            ]),
            new Sharee([
                'href'         => 'mailto:user4@example.org',
                'properties'   => ['{DAV:}displayname' => 'Hoe Boe'],
                'inviteStatus' => SP::INVITE_INVALID,
                'access'       => SP::ACCESS_READ,
            ]),
        ]);

        $xml = $this->write(['{DAV:}root' => $property]);

        $this->assertXmlStringEqualsXmlString(
'<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:cal="' . CalDAV\Plugin::NS_CALDAV . '" xmlns:cs="' . CalDAV\Plugin::NS_CALENDARSERVER . '">
  <cs:organizer>
    <d:href>mailto:thedoctor@example.org</d:href>
    <cs:common-name>The Doctor</cs:common-name>
  </cs:organizer>
  <cs:user>
    <cs:invite-accepted/>
    <cs:access>
      <cs:read-write/>
    </cs:access>
    <d:href>mailto:user1@example.org</d:href>
  </cs:user>
  <cs:user>
    <cs:invite-declined/>
    <cs:access>
      <cs:read/>
    </cs:access>
    <d:href>mailto:user2@example.org</d:href>
    <cs:common-name>John Doe</cs:common-name>
  </cs:user>
  <cs:user>
    <cs:invite-noresponse/>
    <cs:access>
      <cs:read/>
    </cs:access>
    <d:href>mailto:user3@example.org</d:href>
    <cs:common-name>Joe Shmoe</cs:common-name>
    <cs:summary>Something, something</cs:summary>
  </cs:user>
  <cs:user>
    <cs:invite-invalid/>
    <cs:access>
      <cs:read/>
    </cs:access>
    <d:href>mailto:user4@example.org</d:href>
    <cs:common-name>Hoe Boe</cs:common-name>
  </cs:user>
</d:root>
', $xml);

    }

}
