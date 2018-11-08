<?php

namespace Sabre\Xml\Serializer;

use Sabre\Xml\Service;

class RepeatingElementsTest extends \PHPUnit_Framework_TestCase {

    function testSerialize() {

        $service = new Service();
        $service->namespaceMap['urn:test'] = null;
        $xml = $service->write('{urn:test}collection', function($writer) {
            repeatingElements($writer, [
                'foo',
                'bar',
            ], '{urn:test}item');
        });

        $expected = <<<XML
<?xml version="1.0"?>
<collection xmlns="urn:test">
   <item>foo</item>
   <item>bar</item>
</collection>
XML;


        $this->assertXmlStringEqualsXmlString($expected, $xml);


    }


}
