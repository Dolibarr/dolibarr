<?php

namespace Sabre\DAV\Xml\Element;

use Sabre\DAV;

class ResponseTest extends DAV\Xml\XmlTest {

    function testSimple() {

        $innerProps = [
            200 => [
                '{DAV:}displayname' => 'my file',
            ],
            404 => [
                '{DAV:}owner' => null,
            ]
        ];

        $property = new Response('uri', $innerProps);

        $this->assertEquals('uri', $property->getHref());
        $this->assertEquals($innerProps, $property->getResponseProperties());


    }

    /**
     * @depends testSimple
     */
    function testSerialize() {

        $innerProps = [
            200 => [
                '{DAV:}displayname' => 'my file',
            ],
            404 => [
                '{DAV:}owner' => null,
            ]
        ];

        $property = new Response('uri', $innerProps);

        $xml = $this->write(['{DAV:}root' => ['{DAV:}response' => $property]]);

        $this->assertXmlStringEqualsXmlString(
'<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:response>
    <d:href>/uri</d:href>
    <d:propstat>
      <d:prop>
        <d:displayname>my file</d:displayname>
      </d:prop>
      <d:status>HTTP/1.1 200 OK</d:status>
    </d:propstat>
    <d:propstat>
      <d:prop>
        <d:owner/>
      </d:prop>
      <d:status>HTTP/1.1 404 Not Found</d:status>
    </d:propstat>
  </d:response>
</d:root>
', $xml);

    }

    /**
     * This one is specifically for testing properties with no namespaces, which is legal xml
     *
     * @depends testSerialize
     */
    function testSerializeEmptyNamespace() {

        $innerProps = [
            200 => [
                '{}propertyname' => 'value',
            ],
        ];

        $property = new Response('uri', $innerProps);

        $xml = $this->write(['{DAV:}root' => ['{DAV:}response' => $property]]);

        $this->assertEquals(
'<d:root xmlns:d="DAV:">
 <d:response>
  <d:href>/uri</d:href>
  <d:propstat>
   <d:prop>
    <propertyname xmlns="">value</propertyname>
   </d:prop>
   <d:status>HTTP/1.1 200 OK</d:status>
  </d:propstat>
 </d:response>
</d:root>
', $xml);

    }

    /**
     * This one is specifically for testing properties with no namespaces, which is legal xml
     *
     * @depends testSerialize
     */
    function testSerializeCustomNamespace() {

        $innerProps = [
            200 => [
                '{http://sabredav.org/NS/example}propertyname' => 'value',
            ],
        ];

        $property = new Response('uri', $innerProps);
        $xml = $this->write(['{DAV:}root' => ['{DAV:}response' => $property]]);

        $this->assertXmlStringEqualsXmlString(
'<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:response>
      <d:href>/uri</d:href>
      <d:propstat>
        <d:prop>
          <x1:propertyname xmlns:x1="http://sabredav.org/NS/example">value</x1:propertyname>
        </d:prop>
      <d:status>HTTP/1.1 200 OK</d:status>
      </d:propstat>
  </d:response>
</d:root>', $xml);

    }

    /**
     * @depends testSerialize
     */
    function testSerializeComplexProperty() {

        $innerProps = [
            200 => [
                '{DAV:}link' => new DAV\Xml\Property\Href('http://sabredav.org/', false)
            ],
        ];

        $property = new Response('uri', $innerProps);
        $xml = $this->write(['{DAV:}root' => ['{DAV:}response' => $property]]);

        $this->assertXmlStringEqualsXmlString(
'<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:response>
      <d:href>/uri</d:href>
      <d:propstat>
        <d:prop>
          <d:link><d:href>http://sabredav.org/</d:href></d:link>
        </d:prop>
        <d:status>HTTP/1.1 200 OK</d:status>
      </d:propstat>
  </d:response>
</d:root>
', $xml);

    }

    /**
     * @depends testSerialize
     * @expectedException \InvalidArgumentException
     */
    function testSerializeBreak() {

        $innerProps = [
            200 => [
                '{DAV:}link' => new \STDClass()
            ],
        ];

        $property = new Response('uri', $innerProps);
        $this->write(['{DAV:}root' => ['{DAV:}response' => $property]]);

    }

    function testDeserializeComplexProperty() {

        $xml = '<?xml version="1.0"?>
<d:response xmlns:d="DAV:">
  <d:href>/uri</d:href>
  <d:propstat>
    <d:prop>
      <d:foo>hello</d:foo>
    </d:prop>
    <d:status>HTTP/1.1 200 OK</d:status>
  </d:propstat>
</d:response>
';

        $result = $this->parse($xml, [
            '{DAV:}response' => 'Sabre\DAV\Xml\Element\Response',
            '{DAV:}foo'      => function($reader) {

                $reader->next();
                return 'world';
            },
        ]);
        $this->assertEquals(
            new Response('/uri', [
                '200' => [
                    '{DAV:}foo' => 'world',
                ]
            ]),
            $result['value']
        );

    }

    /**
     * @depends testSimple
     */
    function testSerializeUrlencoding() {

        $innerProps = [
            200 => [
                '{DAV:}displayname' => 'my file',
            ],
        ];

        $property = new Response('space here', $innerProps);

        $xml = $this->write(['{DAV:}root' => ['{DAV:}response' => $property]]);

        $this->assertXmlStringEqualsXmlString(
'<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:response>
    <d:href>/space%20here</d:href>
    <d:propstat>
      <d:prop>
        <d:displayname>my file</d:displayname>
      </d:prop>
      <d:status>HTTP/1.1 200 OK</d:status>
    </d:propstat>
  </d:response>
</d:root>
', $xml);

    }

    /**
     * @depends testSerialize
     *
     * The WebDAV spec _requires_ at least one DAV:propstat to appear for
     * every DAV:response. In some circumstances however, there are no
     * properties to encode.
     *
     * In those cases we MUST specify at least one DAV:propstat anyway, with
     * no properties.
     */
    function testSerializeNoProperties() {

        $innerProps = [];

        $property = new Response('uri', $innerProps);
        $xml = $this->write(['{DAV:}root' => ['{DAV:}response' => $property]]);

        $this->assertXmlStringEqualsXmlString(
'<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
  <d:response>
      <d:href>/uri</d:href>
      <d:propstat>
        <d:prop />
        <d:status>HTTP/1.1 418 I\'m a teapot</d:status>
      </d:propstat>
  </d:response>
</d:root>
', $xml);

    }

    /**
     * In the case of {DAV:}prop, a deserializer should never get called, if
     * the property element is empty.
     */
    function testDeserializeComplexPropertyEmpty() {

        $xml = '<?xml version="1.0"?>
<d:response xmlns:d="DAV:">
  <d:href>/uri</d:href>
  <d:propstat>
    <d:prop>
      <d:foo />
    </d:prop>
    <d:status>HTTP/1.1 404 Not Found</d:status>
  </d:propstat>
</d:response>
';

        $result = $this->parse($xml, [
            '{DAV:}response' => 'Sabre\DAV\Xml\Element\Response',
            '{DAV:}foo'      => function($reader) {
                throw new \LogicException('This should never happen');
            },
        ]);
        $this->assertEquals(
            new Response('/uri', [
                '404' => [
                    '{DAV:}foo' => null
                ]
            ]),
            $result['value']
        );

    }
}
