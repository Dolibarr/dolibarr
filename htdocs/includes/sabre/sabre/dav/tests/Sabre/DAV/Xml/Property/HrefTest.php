<?php

namespace Sabre\DAV\Xml\Property;

use Sabre\DAV;
use Sabre\DAV\Browser\HtmlOutputHelper;
use Sabre\DAV\Xml\XmlTest;

class HrefTest extends XmlTest {

    function testConstruct() {

        $href = new Href('path');
        $this->assertEquals('path', $href->getHref());

    }

    function testSerialize() {

        $href = new Href('path');
        $this->assertEquals('path', $href->getHref());

        $this->contextUri = '/bla/';

        $xml = $this->write(['{DAV:}anything' => $href]);

        $this->assertXmlStringEqualsXmlString(
'<?xml version="1.0"?>
<d:anything xmlns:d="DAV:"><d:href>/bla/path</d:href></d:anything>
', $xml);

    }

    function testUnserialize() {

        $xml = '<?xml version="1.0"?>
<d:anything xmlns:d="DAV:"><d:href>/bla/path</d:href></d:anything>
';

        $result = $this->parse($xml, ['{DAV:}anything' => 'Sabre\\DAV\\Xml\\Property\\Href']);

        $href = $result['value'];

        $this->assertInstanceOf('Sabre\\DAV\\Xml\\Property\\Href', $href);

        $this->assertEquals('/bla/path', $href->getHref());

    }

    function testUnserializeIncompatible() {

        $xml = '<?xml version="1.0"?>
<d:anything xmlns:d="DAV:"><d:href2>/bla/path</d:href2></d:anything>
';
        $result = $this->parse($xml, ['{DAV:}anything' => 'Sabre\\DAV\\Xml\\Property\\Href']);
        $href = $result['value'];
        $this->assertNull($href);

    }
    function testUnserializeEmpty() {

        $xml = '<?xml version="1.0"?>
<d:anything xmlns:d="DAV:"></d:anything>
';
        $result = $this->parse($xml, ['{DAV:}anything' => 'Sabre\\DAV\\Xml\\Property\\Href']);
        $href = $result['value'];
        $this->assertNull($href);

    }

    /**
     * This method tests if hrefs containing & are correctly encoded.
     */
    function testSerializeEntity() {

        $href = new Href('http://example.org/?a&b', false);
        $this->assertEquals('http://example.org/?a&b', $href->getHref());

        $xml = $this->write(['{DAV:}anything' => $href]);

        $this->assertXmlStringEqualsXmlString(
'<?xml version="1.0"?>
<d:anything xmlns:d="DAV:"><d:href>http://example.org/?a&amp;b</d:href></d:anything>
', $xml);

    }

    function testToHtml() {

        $href = new Href([
            '/foo/bar',
            'foo/bar',
            'http://example.org/bar'
        ]);

        $html = new HtmlOutputHelper(
            '/base/',
            []
        );

        $expected =
            '<a href="/foo/bar">/foo/bar</a><br />' .
            '<a href="/base/foo/bar">/base/foo/bar</a><br />' .
            '<a href="http://example.org/bar">http://example.org/bar</a>';
        $this->assertEquals($expected, $href->toHtml($html));

    }

}
