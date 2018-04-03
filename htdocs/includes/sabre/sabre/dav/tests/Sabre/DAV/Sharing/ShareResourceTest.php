<?php

namespace Sabre\DAV\Sharing;

use Sabre\DAV\Mock;
use Sabre\DAV\Xml\Element\Sharee;
use Sabre\HTTP\Request;

class ShareResourceTest extends \Sabre\DAVServerTest {

    protected $setupSharing = true;
    protected $sharingNodeMock;

    function setUpTree() {

        $this->tree[] = $this->sharingNodeMock = new Mock\SharedNode(
            'shareable',
            Plugin::ACCESS_SHAREDOWNER
        );

    }

    function testShareResource() {

        $body = <<<XML
<?xml version="1.0" encoding="utf-8" ?>
<D:share-resource xmlns:D="DAV:">
 <D:sharee>
   <D:href>mailto:eric@example.com</D:href>
   <D:prop>
     <D:displayname>Eric York</D:displayname>
   </D:prop>
   <D:comment>Shared workspace</D:comment>
   <D:share-access>
     <D:read-write />
   </D:share-access>
 </D:sharee>
</D:share-resource>
XML;
        $request = new Request('POST', '/shareable', ['Content-Type' => 'application/davsharing+xml; charset="utf-8"'], $body);

        $response = $this->request($request);
        $this->assertEquals(200, $response->getStatus(), (string)$response->getBodyAsString());

        $expected = [
            new Sharee([
                'href'       => 'mailto:eric@example.com',
                'properties' => [
                    '{DAV:}displayname' => 'Eric York',
                ],
                'access'       => Plugin::ACCESS_READWRITE,
                'comment'      => 'Shared workspace',
                'inviteStatus' => \Sabre\DAV\Sharing\Plugin::INVITE_NORESPONSE,
            ])
        ];

        $this->assertEquals(
            $expected,
            $this->sharingNodeMock->getInvites()
        );

    }

    /**
     * @depends testShareResource
     */
    function testShareResourceRemoveAccess() {

        // First we just want to execute all the actions from the first
        // test.
        $this->testShareResource();

        $body = <<<XML
<?xml version="1.0" encoding="utf-8" ?>
<D:share-resource xmlns:D="DAV:">
 <D:sharee>
   <D:href>mailto:eric@example.com</D:href>
   <D:share-access>
     <D:no-access />
   </D:share-access>
 </D:sharee>
</D:share-resource>
XML;
        $request = new Request('POST', '/shareable', ['Content-Type' => 'application/davsharing+xml; charset="utf-8"'], $body);

        $response = $this->request($request);
        $this->assertEquals(200, $response->getStatus(), (string)$response->getBodyAsString());

        $expected = [];

        $this->assertEquals(
            $expected,
            $this->sharingNodeMock->getInvites()
        );


    }

    /**
     * @depends testShareResource
     */
    function testShareResourceInviteProperty() {

        // First we just want to execute all the actions from the first
        // test.
        $this->testShareResource();

        $body = <<<XML
<?xml version="1.0" encoding="utf-8" ?>
<D:propfind xmlns:D="DAV:">
  <D:prop>
    <D:invite />
    <D:share-access />
    <D:share-resource-uri />
  </D:prop>
</D:propfind>
XML;
        $request = new Request('PROPFIND', '/shareable', ['Content-Type' => 'application/xml'], $body);
        $response = $this->request($request);

        $this->assertEquals(207, $response->getStatus());

        $expected = <<<XML
<?xml version="1.0" encoding="utf-8" ?>
<d:multistatus xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">
  <d:response>
    <d:href>/shareable</d:href>
    <d:propstat>
      <d:prop>
        <d:invite>
          <d:sharee>
            <d:href>mailto:eric@example.com</d:href>
            <d:prop>
              <d:displayname>Eric York</d:displayname>
            </d:prop>
            <d:share-access><d:read-write /></d:share-access>
            <d:invite-noresponse />
          </d:sharee>
        </d:invite>
        <d:share-access><d:shared-owner /></d:share-access>
        <d:share-resource-uri><d:href>urn:example:bar</d:href></d:share-resource-uri>
      </d:prop>
      <d:status>HTTP/1.1 200 OK</d:status>
    </d:propstat>
  </d:response>
</d:multistatus>
XML;

        $this->assertXmlStringEqualsXmlString($expected, $response->getBodyAsString());

    }

    function testShareResourceNotFound() {

        $body = <<<XML
<?xml version="1.0" encoding="utf-8" ?>
<D:share-resource xmlns:D="DAV:">
 <D:sharee>
   <D:href>mailto:eric@example.com</D:href>
   <D:prop>
     <D:displayname>Eric York</D:displayname>
   </D:prop>
   <D:comment>Shared workspace</D:comment>
   <D:share-access>
     <D:read-write />
   </D:share-access>
 </D:sharee>
</D:share-resource>
XML;
        $request = new Request('POST', '/not-found', ['Content-Type' => 'application/davsharing+xml; charset="utf-8"'], $body);

        $response = $this->request($request, 404);

    }

    function testShareResourceNotISharedNode() {

        $body = <<<XML
<?xml version="1.0" encoding="utf-8" ?>
<D:share-resource xmlns:D="DAV:">
 <D:sharee>
   <D:href>mailto:eric@example.com</D:href>
   <D:prop>
     <D:displayname>Eric York</D:displayname>
   </D:prop>
   <D:comment>Shared workspace</D:comment>
   <D:share-access>
     <D:read-write />
   </D:share-access>
 </D:sharee>
</D:share-resource>
XML;
        $request = new Request('POST', '/', ['Content-Type' => 'application/davsharing+xml; charset="utf-8"'], $body);

        $response = $this->request($request, 403);

    }

    function testShareResourceUnknownDoc() {

        $body = <<<XML
<?xml version="1.0" encoding="utf-8" ?>
<D:blablabla xmlns:D="DAV:" />
XML;
        $request = new Request('POST', '/shareable', ['Content-Type' => 'application/davsharing+xml; charset="utf-8"'], $body);
        $response = $this->request($request, 400);

    }

}
