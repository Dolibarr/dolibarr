<?php

namespace Sabre\Xml\Deserializer;

use Sabre\Xml\Service;

class EnumTest extends \PHPUnit_Framework_TestCase {

    function testDeserialize() {

        $service = new Service();
        $service->elementMap['{urn:test}root'] = 'Sabre\Xml\Deserializer\enum';

        $xml = <<<XML
<?xml version="1.0"?>
<root xmlns="urn:test">
   <foo1/>
   <foo2/>
</root>
XML;

        $result = $service->parse($xml);

        $expected = [
            '{urn:test}foo1',
            '{urn:test}foo2',
        ];


        $this->assertEquals($expected, $result);


    }

    function testDeserializeDefaultNamespace() {

        $service = new Service();
        $service->elementMap['{urn:test}root'] = function($reader) {
            return enum($reader, 'urn:test');
        };

        $xml = <<<XML
<?xml version="1.0"?>
<root xmlns="urn:test">
   <foo1/>
   <foo2/>
</root>
XML;

        $result = $service->parse($xml);

        $expected = [
            'foo1',
            'foo2',
        ];


        $this->assertEquals($expected, $result);

    }

}
