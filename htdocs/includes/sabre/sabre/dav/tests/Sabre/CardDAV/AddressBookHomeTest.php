<?php

namespace Sabre\CardDAV;

use Sabre\DAV\MkCol;

class AddressBookHomeTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Sabre\CardDAV\AddressBookHome
     */
    protected $s;
    protected $backend;

    function setUp() {

        $this->backend = new Backend\Mock();
        $this->s = new AddressBookHome(
            $this->backend,
            'principals/user1'
        );

    }

    function testGetName() {

        $this->assertEquals('user1', $this->s->getName());

    }

    /**
     * @expectedException Sabre\DAV\Exception\MethodNotAllowed
     */
    function testSetName() {

        $this->s->setName('user2');

    }

    /**
     * @expectedException Sabre\DAV\Exception\MethodNotAllowed
     */
    function testDelete() {

        $this->s->delete();

    }

    function testGetLastModified() {

        $this->assertNull($this->s->getLastModified());

    }

    /**
     * @expectedException Sabre\DAV\Exception\MethodNotAllowed
     */
    function testCreateFile() {

        $this->s->createFile('bla');

    }

    /**
     * @expectedException Sabre\DAV\Exception\MethodNotAllowed
     */
    function testCreateDirectory() {

        $this->s->createDirectory('bla');

    }

    function testGetChild() {

        $child = $this->s->getChild('book1');
        $this->assertInstanceOf('Sabre\\CardDAV\\AddressBook', $child);
        $this->assertEquals('book1', $child->getName());

    }

    /**
     * @expectedException Sabre\DAV\Exception\NotFound
     */
    function testGetChild404() {

        $this->s->getChild('book2');

    }

    function testGetChildren() {

        $children = $this->s->getChildren();
        $this->assertEquals(2, count($children));
        $this->assertInstanceOf('Sabre\\CardDAV\\AddressBook', $children[0]);
        $this->assertEquals('book1', $children[0]->getName());

    }

    function testCreateExtendedCollection() {

        $resourceType = [
            '{' . Plugin::NS_CARDDAV . '}addressbook',
            '{DAV:}collection',
        ];
        $this->s->createExtendedCollection('book2', new MkCol($resourceType, ['{DAV:}displayname' => 'a-book 2']));

        $this->assertEquals([
            'id'                => 'book2',
            'uri'               => 'book2',
            '{DAV:}displayname' => 'a-book 2',
            'principaluri'      => 'principals/user1',
        ], $this->backend->addressBooks[2]);

    }

    /**
     * @expectedException Sabre\DAV\Exception\InvalidResourceType
     */
    function testCreateExtendedCollectionInvalid() {

        $resourceType = [
            '{DAV:}collection',
        ];
        $this->s->createExtendedCollection('book2', new MkCol($resourceType, ['{DAV:}displayname' => 'a-book 2']));

    }


    function testACLMethods() {

        $this->assertEquals('principals/user1', $this->s->getOwner());
        $this->assertNull($this->s->getGroup());
        $this->assertEquals([
            [
                'privilege' => '{DAV:}all',
                'principal' => '{DAV:}owner',
                'protected' => true,
            ],
        ], $this->s->getACL());

    }

    /**
     * @expectedException Sabre\DAV\Exception\Forbidden
     */
    function testSetACL() {

       $this->s->setACL([]);

    }

    function testGetSupportedPrivilegeSet() {

        $this->assertNull(
            $this->s->getSupportedPrivilegeSet()
        );

    }
}
