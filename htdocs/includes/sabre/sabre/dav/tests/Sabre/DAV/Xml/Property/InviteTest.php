<?php

namespace Sabre\DAV\Xml\Property;

use Sabre\DAV\Sharing\Plugin;
use Sabre\DAV\Xml\Element\Sharee;
use Sabre\DAV\Xml\XmlTest;

class InviteTest extends XmlTest {

    function testSerialize() {

        $sharees = [
            new Sharee(),
            new Sharee(),
            new Sharee(),
            new Sharee()
        ];
        $sharees[0]->href = 'mailto:foo@example.org';
        $sharees[0]->properties['{DAV:}displayname'] = 'Foo Bar';
        $sharees[0]->access = Plugin::ACCESS_SHAREDOWNER;
        $sharees[0]->inviteStatus = Plugin::INVITE_ACCEPTED;

        $sharees[1]->href = 'mailto:bar@example.org';
        $sharees[1]->access = Plugin::ACCESS_READ;
        $sharees[1]->inviteStatus = Plugin::INVITE_DECLINED;

        $sharees[2]->href = 'mailto:baz@example.org';
        $sharees[2]->access = Plugin::ACCESS_READWRITE;
        $sharees[2]->inviteStatus = Plugin::INVITE_NORESPONSE;

        $sharees[3]->href = 'mailto:zim@example.org';
        $sharees[3]->access = Plugin::ACCESS_READWRITE;
        $sharees[3]->inviteStatus = Plugin::INVITE_INVALID;

        $invite = new Invite($sharees);

        $xml = $this->write(['{DAV:}root' => $invite]);

        $expected = <<<XML
<?xml version="1.0"?>
<d:root xmlns:d="DAV:">
<d:sharee>
  <d:href>mailto:foo@example.org</d:href>
  <d:prop>
    <d:displayname>Foo Bar</d:displayname>
  </d:prop>
  <d:share-access><d:shared-owner /></d:share-access>
  <d:invite-accepted/>
</d:sharee>
<d:sharee>
  <d:href>mailto:bar@example.org</d:href>
  <d:prop />
  <d:share-access><d:read /></d:share-access>
  <d:invite-declined/>
</d:sharee>
<d:sharee>
  <d:href>mailto:baz@example.org</d:href>
  <d:prop />
  <d:share-access><d:read-write /></d:share-access>
  <d:invite-noresponse/>
</d:sharee>
<d:sharee>
  <d:href>mailto:zim@example.org</d:href>
  <d:prop />
  <d:share-access><d:read-write /></d:share-access>
  <d:invite-invalid/>
</d:sharee>
</d:root>
XML;

        $this->assertXmlStringEqualsXmlString($expected, $xml);

    }

}
