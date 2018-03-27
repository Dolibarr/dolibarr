<?php

namespace Sabre\DAVACL\Xml\Property;

use Sabre\DAV;
use Sabre\DAV\Browser\HtmlOutputHelper;
use Sabre\HTTP;
use Sabre\Xml\Reader;

class CurrentUserPrivilegeSetTest extends \PHPUnit_Framework_TestCase {

    function testSerialize() {

        $privileges = [
            '{DAV:}read',
            '{DAV:}write',
        ];
        $prop = new CurrentUserPrivilegeSet($privileges);
        $xml = (new DAV\Server())->xml->write('{DAV:}root', $prop);

        $expected = <<<XML
<d:root xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">
    <d:privilege>
        <d:read />
    </d:privilege>
    <d:privilege>
        <d:write />
    </d:privilege>
</d:root>
XML;


        $this->assertXmlStringEqualsXmlString($expected, $xml);

    }

    function testUnserialize() {

        $source = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
    <d:privilege>
        <d:write-properties />
    </d:privilege>
    <d:ignoreme />
    <d:privilege>
        <d:read />
    </d:privilege>
</d:root>
';

        $result = $this->parse($source);
        $this->assertTrue($result->has('{DAV:}read'));
        $this->assertTrue($result->has('{DAV:}write-properties'));
        $this->assertFalse($result->has('{DAV:}bind'));

    }

    function parse($xml) {

        $reader = new Reader();
        $reader->elementMap['{DAV:}root'] = 'Sabre\\DAVACL\\Xml\\Property\\CurrentUserPrivilegeSet';
        $reader->xml($xml);
        $result = $reader->parse();
        return $result['value'];

    }

    function testToHtml() {

        $privileges = ['{DAV:}read', '{DAV:}write'];

        $prop = new CurrentUserPrivilegeSet($privileges);
        $html = new HtmlOutputHelper(
            '/base/',
            ['DAV:' => 'd']
        );

        $expected =
            '<span title="{DAV:}read">d:read</span>, ' .
            '<span title="{DAV:}write">d:write</span>';

        $this->assertEquals($expected, $prop->toHtml($html));

    }

}
