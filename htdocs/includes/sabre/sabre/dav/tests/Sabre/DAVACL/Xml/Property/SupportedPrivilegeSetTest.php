<?php

namespace Sabre\DAVACL\Xml\Property;

use Sabre\DAV;
use Sabre\DAV\Browser\HtmlOutputHelper;
use Sabre\HTTP;

class SupportedPrivilegeSetTest extends \PHPUnit_Framework_TestCase {

    function testSimple() {

        $prop = new SupportedPrivilegeSet([
            'privilege' => '{DAV:}all',
        ]);
        $this->assertInstanceOf('Sabre\DAVACL\Xml\Property\SupportedPrivilegeSet', $prop);

    }


    /**
     * @depends testSimple
     */
    function testSerializeSimple() {

        $prop = new SupportedPrivilegeSet([]);

        $xml = (new DAV\Server())->xml->write('{DAV:}supported-privilege-set', $prop);

        $this->assertXmlStringEqualsXmlString('
<d:supported-privilege-set xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">
  <d:supported-privilege>
    <d:privilege>
      <d:all/>
    </d:privilege>
  </d:supported-privilege>
</d:supported-privilege-set>', $xml);

    }

    /**
     * @depends testSimple
     */
    function testSerializeAggregate() {

        $prop = new SupportedPrivilegeSet([
            '{DAV:}read'  => [],
            '{DAV:}write' => [
                'description' => 'booh',
            ]
        ]);

        $xml = (new DAV\Server())->xml->write('{DAV:}supported-privilege-set', $prop);

        $this->assertXmlStringEqualsXmlString('
<d:supported-privilege-set xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">
 <d:supported-privilege>
  <d:privilege>
   <d:all/>
  </d:privilege>
  <d:supported-privilege>
   <d:privilege>
    <d:read/>
   </d:privilege>
  </d:supported-privilege>
  <d:supported-privilege>
   <d:privilege>
    <d:write/>
   </d:privilege>
  <d:description>booh</d:description>
  </d:supported-privilege>
 </d:supported-privilege>
</d:supported-privilege-set>', $xml);

    }

    function testToHtml() {

        $prop = new SupportedPrivilegeSet([
            '{DAV:}read'  => [],
            '{DAV:}write' => [
                'description' => 'booh',
            ],
        ]);
        $html = new HtmlOutputHelper(
            '/base/',
            ['DAV:' => 'd']
        );

        $expected = <<<HTML
<ul class="tree"><li><span title="{DAV:}all">d:all</span>
<ul>
<li><span title="{DAV:}read">d:read</span></li>
<li><span title="{DAV:}write">d:write</span> booh</li>
</ul></li>
</ul>

HTML;

        $this->assertEquals($expected, $prop->toHtml($html));

    }
}
