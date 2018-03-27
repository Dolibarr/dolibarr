<?php

namespace Sabre\CalDAV\Xml\Request;

use Sabre\DAV\Xml\Element\Sharee;
use Sabre\DAV\Xml\XmlTest;

class ShareTest extends XmlTest {

    protected $elementMap = [
        '{http://calendarserver.org/ns/}share' => 'Sabre\\CalDAV\\Xml\\Request\\Share',
    ];

    function testDeserialize() {

        $xml = <<<XML
<?xml version="1.0" encoding="utf-8" ?>
   <CS:share xmlns:D="DAV:"
                 xmlns:CS="http://calendarserver.org/ns/">
     <CS:set>
       <D:href>mailto:eric@example.com</D:href>
       <CS:common-name>Eric York</CS:common-name>
       <CS:summary>Shared workspace</CS:summary>
       <CS:read-write />
     </CS:set>
     <CS:remove>
       <D:href>mailto:foo@bar</D:href>
     </CS:remove>
   </CS:share>
XML;

        $result = $this->parse($xml);
        $share = new Share([
            new Sharee([
                'href'       => 'mailto:eric@example.com',
                'access'     => \Sabre\DAV\Sharing\Plugin::ACCESS_READWRITE,
                'properties' => [
                    '{DAV:}displayname' => 'Eric York',
                ],
                'comment' => 'Shared workspace',
            ]),
            new Sharee([
                'href'   => 'mailto:foo@bar',
                'access' => \Sabre\DAV\Sharing\Plugin::ACCESS_NOACCESS,
            ]),
        ]);

        $this->assertEquals(
            $share,
            $result['value']
        );

    }

    function testDeserializeMinimal() {

        $xml = <<<XML
<?xml version="1.0" encoding="utf-8" ?>
   <CS:share xmlns:D="DAV:"
                 xmlns:CS="http://calendarserver.org/ns/">
     <CS:set>
       <D:href>mailto:eric@example.com</D:href>
        <CS:read />
     </CS:set>
   </CS:share>
XML;

        $result = $this->parse($xml);
        $share = new Share([
            new Sharee([
                'href'   => 'mailto:eric@example.com',
                'access' => \Sabre\DAV\Sharing\Plugin::ACCESS_READ,
            ]),
        ]);

        $this->assertEquals(
            $share,
            $result['value']
        );

    }

}
