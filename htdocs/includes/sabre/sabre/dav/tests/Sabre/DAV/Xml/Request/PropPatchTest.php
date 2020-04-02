<?php

namespace Sabre\DAV\Xml\Request;

use Sabre\DAV\Xml\Property\Href;
use Sabre\DAV\Xml\XmlTest;

class PropPatchTest extends XmlTest {

    function testSerialize() {

        $propPatch = new PropPatch();
        $propPatch->properties = [
            '{DAV:}displayname' => 'Hello!',
            '{DAV:}delete-me'   => null,
            '{DAV:}some-url'    => new Href('foo/bar')
        ];

        $result = $this->write(
            ['{DAV:}propertyupdate' => $propPatch]
        );

        $expected = <<<XML
<?xml version="1.0"?>
<d:propertyupdate xmlns:d="DAV:">
    <d:set>
        <d:prop>
            <d:displayname>Hello!</d:displayname>
        </d:prop>
    </d:set>
    <d:remove>
        <d:prop>
          <d:delete-me />
        </d:prop>
    </d:remove>
    <d:set>
        <d:prop>
            <d:some-url>
                <d:href>/foo/bar</d:href>
            </d:some-url>
        </d:prop>
    </d:set>
</d:propertyupdate>
XML;

        $this->assertXmlStringEqualsXmlString(
            $expected,
            $result
        );

    }

}
