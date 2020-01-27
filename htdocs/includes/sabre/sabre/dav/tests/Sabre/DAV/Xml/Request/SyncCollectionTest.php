<?php

namespace Sabre\DAV\Xml\Request;

use Sabre\DAV\Xml\XmlTest;

class SyncCollectionTest extends XmlTest {

    function testDeserializeProp() {

        $xml = '<?xml version="1.0"?>
<d:sync-collection xmlns:d="DAV:">
    <d:sync-token />
    <d:sync-level>1</d:sync-level>
    <d:prop>
        <d:foo />
    </d:prop>
</d:sync-collection>
';

        $result = $this->parse($xml, ['{DAV:}sync-collection' => 'Sabre\\DAV\\Xml\\Request\\SyncCollectionReport']);

        $elem = new SyncCollectionReport();
        $elem->syncLevel = 1;
        $elem->properties = ['{DAV:}foo'];

        $this->assertEquals($elem, $result['value']);

    }


    function testDeserializeLimit() {

        $xml = '<?xml version="1.0"?>
<d:sync-collection xmlns:d="DAV:">
    <d:sync-token />
    <d:sync-level>1</d:sync-level>
    <d:prop>
        <d:foo />
    </d:prop>
    <d:limit><d:nresults>5</d:nresults></d:limit>
</d:sync-collection>
';

        $result = $this->parse($xml, ['{DAV:}sync-collection' => 'Sabre\\DAV\\Xml\\Request\\SyncCollectionReport']);

        $elem = new SyncCollectionReport();
        $elem->syncLevel = 1;
        $elem->properties = ['{DAV:}foo'];
        $elem->limit = 5;

        $this->assertEquals($elem, $result['value']);

    }


    function testDeserializeInfinity() {

        $xml = '<?xml version="1.0"?>
<d:sync-collection xmlns:d="DAV:">
    <d:sync-token />
    <d:sync-level>infinity</d:sync-level>
    <d:prop>
        <d:foo />
    </d:prop>
</d:sync-collection>
';

        $result = $this->parse($xml, ['{DAV:}sync-collection' => 'Sabre\\DAV\\Xml\\Request\\SyncCollectionReport']);

        $elem = new SyncCollectionReport();
        $elem->syncLevel = \Sabre\DAV\Server::DEPTH_INFINITY;
        $elem->properties = ['{DAV:}foo'];

        $this->assertEquals($elem, $result['value']);

    }

    /**
     * @expectedException \Sabre\DAV\Exception\BadRequest
     */
    function testDeserializeMissingElem() {

        $xml = '<?xml version="1.0"?>
<d:sync-collection xmlns:d="DAV:">
    <d:sync-token />
</d:sync-collection>
';

        $result = $this->parse($xml, ['{DAV:}sync-collection' => 'Sabre\\DAV\\Xml\\Request\\SyncCollectionReport']);

    }

}
