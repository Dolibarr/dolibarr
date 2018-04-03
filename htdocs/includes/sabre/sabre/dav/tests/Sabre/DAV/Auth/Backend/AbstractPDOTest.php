<?php

namespace Sabre\DAV\Auth\Backend;

abstract class AbstractPDOTest extends \PHPUnit_Framework_TestCase {

    use \Sabre\DAV\DbTestHelperTrait;

    function setUp() {

        $this->dropTables('users');
        $this->createSchema('users');

        $this->getPDO()->query(
            "INSERT INTO users (username,digesta1) VALUES ('user','hash')"

        );

    }

    function testConstruct() {

        $pdo = $this->getPDO();
        $backend = new PDO($pdo);
        $this->assertTrue($backend instanceof PDO);

    }

    /**
     * @depends testConstruct
     */
    function testUserInfo() {

        $pdo = $this->getPDO();
        $backend = new PDO($pdo);

        $this->assertNull($backend->getDigestHash('realm', 'blabla'));

        $expected = 'hash';

        $this->assertEquals($expected, $backend->getDigestHash('realm', 'user'));

    }

}
