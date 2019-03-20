<?php

namespace Sabre\CalDAV\Principal;

use Sabre\DAVACL;

class UserTest extends \PHPUnit_Framework_TestCase {

    function getInstance() {

        $backend = new DAVACL\PrincipalBackend\Mock();
        $backend->addPrincipal([
            'uri' => 'principals/user/calendar-proxy-read',
        ]);
        $backend->addPrincipal([
            'uri' => 'principals/user/calendar-proxy-write',
        ]);
        $backend->addPrincipal([
            'uri' => 'principals/user/random',
        ]);
        return new User($backend, [
            'uri' => 'principals/user',
        ]);

    }

    /**
     * @expectedException Sabre\DAV\Exception\Forbidden
     */
    function testCreateFile() {

        $u = $this->getInstance();
        $u->createFile('test');

    }

    /**
     * @expectedException Sabre\DAV\Exception\Forbidden
     */
    function testCreateDirectory() {

        $u = $this->getInstance();
        $u->createDirectory('test');

    }

    function testGetChildProxyRead() {

        $u = $this->getInstance();
        $child = $u->getChild('calendar-proxy-read');
        $this->assertInstanceOf('Sabre\\CalDAV\\Principal\\ProxyRead', $child);

    }

    function testGetChildProxyWrite() {

        $u = $this->getInstance();
        $child = $u->getChild('calendar-proxy-write');
        $this->assertInstanceOf('Sabre\\CalDAV\\Principal\\ProxyWrite', $child);

    }

    /**
     * @expectedException Sabre\DAV\Exception\NotFound
     */
    function testGetChildNotFound() {

        $u = $this->getInstance();
        $child = $u->getChild('foo');

    }

    /**
     * @expectedException Sabre\DAV\Exception\NotFound
     */
    function testGetChildNotFound2() {

        $u = $this->getInstance();
        $child = $u->getChild('random');

    }

    function testGetChildren() {

        $u = $this->getInstance();
        $children = $u->getChildren();
        $this->assertEquals(2, count($children));
        $this->assertInstanceOf('Sabre\\CalDAV\\Principal\\ProxyRead', $children[0]);
        $this->assertInstanceOf('Sabre\\CalDAV\\Principal\\ProxyWrite', $children[1]);

    }

    function testChildExist() {

        $u = $this->getInstance();
        $this->assertTrue($u->childExists('calendar-proxy-read'));
        $this->assertTrue($u->childExists('calendar-proxy-write'));
        $this->assertFalse($u->childExists('foo'));

    }

    function testGetACL() {

        $expected = [
            [
                'privilege' => '{DAV:}all',
                'principal' => '{DAV:}owner',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user/calendar-proxy-read',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user/calendar-proxy-write',
                'protected' => true,
            ],
        ];

        $u = $this->getInstance();
        $this->assertEquals($expected, $u->getACL());

    }

}
