<?php

namespace Sabre\DAVACL;

use Sabre\HTTP\Request;

class AclPrincipalPropSetReportTest extends \Sabre\DAVServerTest {

    public $setupACL = true;
    public $autoLogin = 'admin';

    function testReport() {

        $xml = <<<XML
<?xml version="1.0"?>
<acl-principal-prop-set xmlns="DAV:">
    <prop>
        <principal-URL />
        <displayname />
    </prop>
</acl-principal-prop-set>
XML;

        $request = new Request('REPORT', '/principals/user1', ['Content-Type' => 'application/xml', 'Depth' => 0]);
        $request->setBody($xml);

        $response = $this->request($request, 207);

        $expected = <<<XML
<?xml version="1.0"?>
<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">
    <d:response>
        <d:href>/principals/admin/</d:href>
        <d:propstat>
            <d:prop>
                <d:principal-URL><d:href>/principals/admin/</d:href></d:principal-URL>
                <d:displayname>Admin</d:displayname>
            </d:prop>
            <d:status>HTTP/1.1 200 OK</d:status>
        </d:propstat>
    </d:response>
</d:multistatus>
XML;

        $this->assertXmlStringEqualsXmlString(
            $expected,
            $response->getBodyAsString()
        );

    }

    function testReportDepth1() {

        $xml = <<<XML
<?xml version="1.0"?>
<acl-principal-prop-set xmlns="DAV:">
    <principal-URL />
    <displayname />
</acl-principal-prop-set>
XML;

        $request = new Request('REPORT', '/principals/user1', ['Content-Type' => 'application/xml', 'Depth' => 1]);
        $request->setBody($xml);

        $this->request($request, 400);

    }

}
