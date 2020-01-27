<?php

namespace Sabre\DAV\Xml\Request;

use Sabre\DAV\Xml\XmlTest;

class PropFindTest extends XmlTest {

    function testDeserializeProp() {

        $xml = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
    <d:prop>
        <d:hello />
    </d:prop>
</d:root>
';

        $result = $this->parse($xml, ['{DAV:}root' => 'Sabre\\DAV\\Xml\\Request\PropFind']);

        $propFind = new PropFind();
        $propFind->properties = ['{DAV:}hello'];

        $this->assertEquals($propFind, $result['value']);


    }

    function testDeserializeAllProp() {

        $xml = '<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
    <d:allprop />
</d:root>
';

        $result = $this->parse($xml, ['{DAV:}root' => 'Sabre\\DAV\\Xml\\Request\PropFind']);

        $propFind = new PropFind();
        $propFind->allProp = true;

        $this->assertEquals($propFind, $result['value']);


    }


}
