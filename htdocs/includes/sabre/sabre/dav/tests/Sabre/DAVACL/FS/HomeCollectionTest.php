<?php

namespace Sabre\DAVACL\FS;

use Sabre\DAVACL\PrincipalBackend\Mock as PrincipalBackend;

class HomeCollectionTest extends \PHPUnit_Framework_TestCase {

    /**
     * System under test
     *
     * @var HomeCollection
     */
    protected $sut;

    protected $path;
    protected $name = 'thuis';

    function setUp() {

        $principalBackend = new PrincipalBackend();

        $this->path = SABRE_TEMPDIR . '/home';

        $this->sut = new HomeCollection($principalBackend, $this->path);
        $this->sut->collectionName = $this->name;


    }

    function tearDown() {

        \Sabre\TestUtil::clearTempDir();

    }

    function testGetName() {

        $this->assertEquals(
            $this->name,
            $this->sut->getName()
        );

    }

    function testGetChild() {

        $child = $this->sut->getChild('user1');
        $this->assertInstanceOf('Sabre\\DAVACL\\FS\\Collection', $child);
        $this->assertEquals('user1', $child->getName());

        $owner = 'principals/user1';
        $acl = [
            [
                'privilege' => '{DAV:}all',
                'principal' => '{DAV:}owner',
                'protected' => true,
            ],
        ];

        $this->assertEquals($acl, $child->getACL());
        $this->assertEquals($owner, $child->getOwner());

    }

    function testGetOwner() {

        $this->assertNull(
            $this->sut->getOwner()
        );

    }

    function testGetGroup() {

        $this->assertNull(
            $this->sut->getGroup()
        );

    }

    function testGetACL() {

        $acl = [
            [
                'principal' => '{DAV:}authenticated',
                'privilege' => '{DAV:}read',
                'protected' => true,
            ]
        ];

        $this->assertEquals(
            $acl,
            $this->sut->getACL()
        );

    }

    /**
     * @expectedException \Sabre\DAV\Exception\Forbidden
     */
    function testSetAcl() {

        $this->sut->setACL([]);

    }

    function testGetSupportedPrivilegeSet() {

        $this->assertNull(
            $this->sut->getSupportedPrivilegeSet()
        );

    }

}
