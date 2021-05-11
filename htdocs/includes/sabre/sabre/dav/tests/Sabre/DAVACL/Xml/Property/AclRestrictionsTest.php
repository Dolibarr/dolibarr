<?php

namespace Sabre\DAVACL\Xml\Property;

use Sabre\DAV;
use Sabre\HTTP;

class AclRestrictionsTest extends \PHPUnit_Framework_TestCase {

    function testConstruct() {

        $prop = new AclRestrictions();
        $this->assertInstanceOf('Sabre\DAVACL\Xml\Property\AclRestrictions', $prop);

    }

    function testSerialize() {

        $prop = new AclRestrictions();
        $xml = (new DAV\Server())->xml->write('{DAV:}root', $prop);

        $expected = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns"><d:grant-only/><d:no-invert/></d:root>';

        $this->assertXmlStringEqualsXmlString($expected, $xml);

    }


}
