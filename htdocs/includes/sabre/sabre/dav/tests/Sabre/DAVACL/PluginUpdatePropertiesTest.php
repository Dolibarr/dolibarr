<?php

namespace Sabre\DAVACL;

use Sabre\DAV;

class PluginUpdatePropertiesTest extends \PHPUnit_Framework_TestCase {

    function testUpdatePropertiesPassthrough() {

        $tree = [
            new DAV\SimpleCollection('foo'),
        ];
        $server = new DAV\Server($tree);
        $server->addPlugin(new DAV\Auth\Plugin());
        $server->addPlugin(new Plugin());

        $result = $server->updateProperties('foo', [
            '{DAV:}foo' => 'bar',
        ]);

        $expected = [
            '{DAV:}foo' => 403,
        ];

        $this->assertEquals($expected, $result);

    }

    function testRemoveGroupMembers() {

        $tree = [
            new MockPrincipal('foo', 'foo'),
        ];
        $server = new DAV\Server($tree);
        $plugin = new Plugin();
        $plugin->allowUnauthenticatedAccess = false;
        $server->addPlugin($plugin);

        $result = $server->updateProperties('foo', [
            '{DAV:}group-member-set' => null,
        ]);

        $expected = [
            '{DAV:}group-member-set' => 204
        ];

        $this->assertEquals($expected, $result);
        $this->assertEquals([], $tree[0]->getGroupMemberSet());

    }

    function testSetGroupMembers() {

        $tree = [
            new MockPrincipal('foo', 'foo'),
        ];
        $server = new DAV\Server($tree);
        $plugin = new Plugin();
        $plugin->allowUnauthenticatedAccess = false;
        $server->addPlugin($plugin);

        $result = $server->updateProperties('foo', [
            '{DAV:}group-member-set' => new DAV\Xml\Property\Href(['/bar', '/baz'], true),
        ]);

        $expected = [
            '{DAV:}group-member-set' => 200
        ];

        $this->assertEquals($expected, $result);
        $this->assertEquals(['bar', 'baz'], $tree[0]->getGroupMemberSet());

    }

    /**
     * @expectedException Sabre\DAV\Exception
     */
    function testSetBadValue() {

        $tree = [
            new MockPrincipal('foo', 'foo'),
        ];
        $server = new DAV\Server($tree);
        $plugin = new Plugin();
        $plugin->allowUnauthenticatedAccess = false;
        $server->addPlugin($plugin);

        $result = $server->updateProperties('foo', [
            '{DAV:}group-member-set' => new \StdClass(),
        ]);

    }

    function testSetBadNode() {

        $tree = [
            new DAV\SimpleCollection('foo'),
        ];
        $server = new DAV\Server($tree);
        $plugin = new Plugin();
        $plugin->allowUnauthenticatedAccess = false;
        $server->addPlugin($plugin);

        $result = $server->updateProperties('foo', [
            '{DAV:}group-member-set' => new DAV\Xml\Property\Href(['/bar', '/baz'], false),
        ]);

        $expected = [
            '{DAV:}group-member-set' => 403,
        ];

        $this->assertEquals($expected, $result);

    }
}
