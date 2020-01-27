<?php

namespace Sabre\DAV\Xml\Property;

use DateTime;
use DateTimeZone;
use Sabre\DAV\Xml\XmlTest;

class LastModifiedTest extends XmlTest {

    function testSerializeDateTime() {

        $dt = new DateTime('2015-03-24 11:47:00', new DateTimeZone('America/Vancouver'));
        $val = ['{DAV:}getlastmodified' => new GetLastModified($dt)];

        $result = $this->write($val);
        $expected = <<<XML
<?xml version="1.0"?>
<d:getlastmodified xmlns:d="DAV:">Tue, 24 Mar 2015 18:47:00 GMT</d:getlastmodified>
XML;

        $this->assertXmlStringEqualsXmlString($expected, $result);

    }

    function testSerializeTimeStamp() {

        $dt = new DateTime('2015-03-24 11:47:00', new DateTimeZone('America/Vancouver'));
        $dt = $dt->getTimeStamp();
        $val = ['{DAV:}getlastmodified' => new GetLastModified($dt)];

        $result = $this->write($val);
        $expected = <<<XML
<?xml version="1.0"?>
<d:getlastmodified xmlns:d="DAV:">Tue, 24 Mar 2015 18:47:00 GMT</d:getlastmodified>
XML;

        $this->assertXmlStringEqualsXmlString($expected, $result);

    }

    function testDeserialize() {

        $input = <<<XML
<?xml version="1.0"?>
<d:getlastmodified xmlns:d="DAV:">Tue, 24 Mar 2015 18:47:00 GMT</d:getlastmodified>
XML;

        $elementMap = ['{DAV:}getlastmodified' => 'Sabre\DAV\Xml\Property\GetLastModified'];
        $result = $this->parse($input, $elementMap);

        $this->assertEquals(
            new DateTime('2015-03-24 18:47:00', new DateTimeZone('UTC')),
            $result['value']->getTime()
        );

    }

}
