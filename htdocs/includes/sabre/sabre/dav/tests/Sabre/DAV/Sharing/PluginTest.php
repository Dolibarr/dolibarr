<?php

namespace Sabre\DAV\Sharing;

use Sabre\DAV\Mock;
use Sabre\DAV\Xml\Property;

class PluginTest extends \Sabre\DAVServerTest {

    protected $setupSharing = true;
    protected $setupACL = true;
    protected $autoLogin = 'admin';

    function setUpTree() {

        $this->tree[] = new Mock\SharedNode(
            'shareable',
            Plugin::ACCESS_READWRITE
        );

    }

    function testFeatures() {

        $this->assertEquals(
            ['resource-sharing'],
            $this->sharingPlugin->getFeatures()
        );

    }

    function testProperties() {

        $result = $this->server->getPropertiesForPath(
            'shareable',
            ['{DAV:}share-access']
        );

        $expected = [
            [
                200 => [
                    '{DAV:}share-access' => new Property\ShareAccess(Plugin::ACCESS_READWRITE)
                ],
                404    => [],
                'href' => 'shareable',
            ]
        ];

        $this->assertEquals(
            $expected,
            $result
        );

    }

    function testGetPluginInfo() {

        $result = $this->sharingPlugin->getPluginInfo();
        $this->assertInternalType('array', $result);
        $this->assertEquals('sharing', $result['name']);

    }

    function testHtmlActionsPanel() {

        $node = new \Sabre\DAV\Mock\Collection('foo');
        $html = '';

        $this->assertNull(
            $this->sharingPlugin->htmlActionsPanel($node, $html, 'foo/bar')
        );

        $this->assertEquals(
            '',
            $html
        );

        $node = new \Sabre\DAV\Mock\SharedNode('foo', \Sabre\DAV\Sharing\Plugin::ACCESS_SHAREDOWNER);
        $html = '';

        $this->assertNull(
            $this->sharingPlugin->htmlActionsPanel($node, $html, 'shareable')
        );
        $this->assertContains(
            'Share this resource',
            $html
        );

    }

    function testBrowserPostActionUnknownAction() {

        $this->assertNull($this->sharingPlugin->browserPostAction(
            'shareable',
            'foo',
            []
        ));

    }

    function testBrowserPostActionSuccess() {

        $this->assertFalse($this->sharingPlugin->browserPostAction(
            'shareable',
            'share',
            [
                'access' => 'read',
                'href'   => 'mailto:foo@example.org',
            ]
        ));

        $expected = [
            new \Sabre\DAV\Xml\Element\Sharee([
                'href'         => 'mailto:foo@example.org',
                'access'       => \Sabre\DAV\Sharing\Plugin::ACCESS_READ,
                'inviteStatus' => \Sabre\DAV\Sharing\Plugin::INVITE_NORESPONSE,
            ])
        ];
        $this->assertEquals(
            $expected,
            $this->tree[0]->getInvites()
        );

    }

    /**
     * @expectedException \Sabre\DAV\Exception\BadRequest
     */
    function testBrowserPostActionNoHref() {

        $this->sharingPlugin->browserPostAction(
            'shareable',
            'share',
            [
                'access' => 'read',
            ]
        );

    }

    /**
     * @expectedException \Sabre\DAV\Exception\BadRequest
     */
    function testBrowserPostActionNoAccess() {

        $this->sharingPlugin->browserPostAction(
            'shareable',
            'share',
            [
                'href' => 'mailto:foo@example.org',
            ]
        );

    }


    /**
     * @expectedException \Sabre\DAV\Exception\BadRequest
     */
    function testBrowserPostActionBadAccess() {

        $this->sharingPlugin->browserPostAction(
            'shareable',
            'share',
            [
                'href'   => 'mailto:foo@example.org',
                'access' => 'bleed',
            ]
        );

    }

    /**
     * @expectedException \Sabre\DAV\Exception\Forbidden
     */
    function testBrowserPostActionAccessDenied() {

        $this->aclPlugin->setDefaultAcl([]);
        $this->sharingPlugin->browserPostAction(
            'shareable',
            'share',
            [
                'access' => 'read',
                'href'   => 'mailto:foo@example.org',
            ]
        );

    }

}
