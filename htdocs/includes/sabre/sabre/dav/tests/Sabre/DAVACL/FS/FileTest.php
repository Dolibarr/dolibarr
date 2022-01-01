<?php

namespace Sabre\DAVACL\FS;

class FileTest extends \PHPUnit_Framework_TestCase {

    /**
     * System under test
     *
     * @var File
     */
    protected $sut;

    protected $path = 'foo';
    protected $acl = [
        [
            'privilege' => '{DAV:}read',
            'principal' => '{DAV:}authenticated',
        ]
    ];

    protected $owner = 'principals/evert';

    function setUp() {

        $this->sut = new File($this->path, $this->acl, $this->owner);

    }

    function testGetOwner() {

        $this->assertEquals(
            $this->owner,
            $this->sut->getOwner()
        );

    }

    function testGetGroup() {

        $this->assertNull(
            $this->sut->getGroup()
        );

    }

    function testGetACL() {

        $this->assertEquals(
            $this->acl,
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
