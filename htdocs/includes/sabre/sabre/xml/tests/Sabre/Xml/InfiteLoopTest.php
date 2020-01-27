<?php

namespace Sabre\Xml;

class InfiteLoopTest extends \PHPUnit_Framework_TestCase {

    /**
     * This particular xml body caused the parser to go into an infinite loop.
     * Need to know why.
     */
    function testDeserialize() {

        $body = '<?xml version="1.0"?>
<d:propertyupdate xmlns:d="DAV:" xmlns:s="http://sabredav.org/NS/test">
  <d:set><d:prop></d:prop></d:set>
  <d:set><d:prop></d:prop></d:set>
</d:propertyupdate>';

        $reader = new Reader();
        $reader->elementMap = [
            '{DAV:}set' => 'Sabre\\Xml\\Element\\KeyValue',
        ];
        $reader->xml($body);

        $output = $reader->parse();

        $this->assertEquals([
            'name'  => '{DAV:}propertyupdate',
            'value' => [
                [
                    'name'  => '{DAV:}set',
                    'value' => [
                        '{DAV:}prop' => null,
                    ],
                    'attributes' => [],
                ],
                [
                    'name'  => '{DAV:}set',
                    'value' => [
                        '{DAV:}prop' => null,
                    ],
                    'attributes' => [],
                ],
            ],
            'attributes' => [],
        ], $output);

    }

}
