<?php

namespace Sabre\DAV\Xml\Request;

use Sabre\DAV\Sharing\Plugin;
use Sabre\DAV\Xml\Element\Sharee;
use Sabre\DAV\Xml\XmlTest;

class ShareResourceTest extends XmlTest {

    function testDeserialize() {

        $xml = <<<XML
<?xml version="1.0" encoding="utf-8" ?>
<D:share-resource xmlns:D="DAV:">
     <D:sharee>
       <D:href>mailto:eric@example.com</D:href>
       <D:prop>
         <D:displayname>Eric York</D:displayname>
       </D:prop>
       <D:comment>Shared workspace</D:comment>
       <D:share-access>
         <D:read-write />
       </D:share-access>
     </D:sharee>
     <D:sharee>
       <D:href>mailto:eric@example.com</D:href>
       <D:share-access>
         <D:read />
       </D:share-access>
     </D:sharee>
     <D:sharee>
       <D:href>mailto:wilfredo@example.com</D:href>
       <D:share-access>
         <D:no-access />
       </D:share-access>
     </D:sharee>
</D:share-resource>
XML;

        $result = $this->parse($xml, [
            '{DAV:}share-resource' => 'Sabre\\DAV\\Xml\\Request\\ShareResource'
        ]);

        $this->assertInstanceOf(
            'Sabre\\DAV\\Xml\\Request\\ShareResource',
            $result['value']
        );

        $expected = [
            new Sharee(),
            new Sharee(),
            new Sharee(),
        ];

        $expected[0]->href = 'mailto:eric@example.com';
        $expected[0]->properties['{DAV:}displayname'] = 'Eric York';
        $expected[0]->comment = 'Shared workspace';
        $expected[0]->access = Plugin::ACCESS_READWRITE;

        $expected[1]->href = 'mailto:eric@example.com';
        $expected[1]->access = Plugin::ACCESS_READ;

        $expected[2]->href = 'mailto:wilfredo@example.com';
        $expected[2]->access = Plugin::ACCESS_NOACCESS;

        $this->assertEquals(
            $expected,
            $result['value']->sharees
        );

    }


}
