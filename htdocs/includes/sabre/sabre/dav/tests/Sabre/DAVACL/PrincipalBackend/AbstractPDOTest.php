<?php

namespace Sabre\DAVACL\PrincipalBackend;

use Sabre\DAV;
use Sabre\HTTP;

abstract class AbstractPDOTest extends \PHPUnit_Framework_TestCase {

    use DAV\DbTestHelperTrait;

    function setUp() {

        $this->dropTables(['principals', 'groupmembers']);
        $this->createSchema('principals');

        $pdo = $this->getPDO();

        $pdo->query("INSERT INTO principals (uri,email,displayname) VALUES ('principals/user','user@example.org','User')");
        $pdo->query("INSERT INTO principals (uri,email,displayname) VALUES ('principals/group','group@example.org','Group')");

        $pdo->query("INSERT INTO groupmembers (principal_id,member_id) VALUES (5,4)");

    }


    function testConstruct() {

        $pdo = $this->getPDO();
        $backend = new PDO($pdo);
        $this->assertTrue($backend instanceof PDO);

    }

    /**
     * @depends testConstruct
     */
    function testGetPrincipalsByPrefix() {

        $pdo = $this->getPDO();
        $backend = new PDO($pdo);

        $expected = [
            [
                'uri'                                   => 'principals/admin',
                '{http://sabredav.org/ns}email-address' => 'admin@example.org',
                '{DAV:}displayname'                     => 'Administrator',
            ],
            [
                'uri'                                   => 'principals/user',
                '{http://sabredav.org/ns}email-address' => 'user@example.org',
                '{DAV:}displayname'                     => 'User',
            ],
            [
                'uri'                                   => 'principals/group',
                '{http://sabredav.org/ns}email-address' => 'group@example.org',
                '{DAV:}displayname'                     => 'Group',
            ],
        ];

        $this->assertEquals($expected, $backend->getPrincipalsByPrefix('principals'));
        $this->assertEquals([], $backend->getPrincipalsByPrefix('foo'));

    }

    /**
     * @depends testConstruct
     */
    function testGetPrincipalByPath() {

        $pdo = $this->getPDO();
        $backend = new PDO($pdo);

        $expected = [
            'id'                                    => 4,
            'uri'                                   => 'principals/user',
            '{http://sabredav.org/ns}email-address' => 'user@example.org',
            '{DAV:}displayname'                     => 'User',
        ];

        $this->assertEquals($expected, $backend->getPrincipalByPath('principals/user'));
        $this->assertEquals(null, $backend->getPrincipalByPath('foo'));

    }

    function testGetGroupMemberSet() {

        $pdo = $this->getPDO();
        $backend = new PDO($pdo);
        $expected = ['principals/user'];

        $this->assertEquals($expected, $backend->getGroupMemberSet('principals/group'));

    }

    function testGetGroupMembership() {

        $pdo = $this->getPDO();
        $backend = new PDO($pdo);
        $expected = ['principals/group'];

        $this->assertEquals($expected, $backend->getGroupMembership('principals/user'));

    }

    function testSetGroupMemberSet() {

        $pdo = $this->getPDO();

        // Start situation
        $backend = new PDO($pdo);
        $this->assertEquals(['principals/user'], $backend->getGroupMemberSet('principals/group'));

        // Removing all principals
        $backend->setGroupMemberSet('principals/group', []);
        $this->assertEquals([], $backend->getGroupMemberSet('principals/group'));

        // Adding principals again
        $backend->setGroupMemberSet('principals/group', ['principals/user']);
        $this->assertEquals(['principals/user'], $backend->getGroupMemberSet('principals/group'));


    }

    function testSearchPrincipals() {

        $pdo = $this->getPDO();

        $backend = new PDO($pdo);

        $result = $backend->searchPrincipals('principals', ['{DAV:}blabla' => 'foo']);
        $this->assertEquals([], $result);

        $result = $backend->searchPrincipals('principals', ['{DAV:}displayname' => 'ou']);
        $this->assertEquals(['principals/group'], $result);

        $result = $backend->searchPrincipals('principals', ['{DAV:}displayname' => 'UsEr', '{http://sabredav.org/ns}email-address' => 'USER@EXAMPLE']);
        $this->assertEquals(['principals/user'], $result);

        $result = $backend->searchPrincipals('mom', ['{DAV:}displayname' => 'UsEr', '{http://sabredav.org/ns}email-address' => 'USER@EXAMPLE']);
        $this->assertEquals([], $result);

    }

    function testUpdatePrincipal() {

        $pdo = $this->getPDO();
        $backend = new PDO($pdo);

        $propPatch = new DAV\PropPatch([
            '{DAV:}displayname' => 'pietje',
        ]);

        $backend->updatePrincipal('principals/user', $propPatch);
        $result = $propPatch->commit();

        $this->assertTrue($result);

        $this->assertEquals([
            'id'                                    => 4,
            'uri'                                   => 'principals/user',
            '{DAV:}displayname'                     => 'pietje',
            '{http://sabredav.org/ns}email-address' => 'user@example.org',
        ], $backend->getPrincipalByPath('principals/user'));

    }

    function testUpdatePrincipalUnknownField() {

        $pdo = $this->getPDO();
        $backend = new PDO($pdo);

        $propPatch = new DAV\PropPatch([
            '{DAV:}displayname' => 'pietje',
            '{DAV:}unknown'     => 'foo',
        ]);

        $backend->updatePrincipal('principals/user', $propPatch);
        $result = $propPatch->commit();

        $this->assertFalse($result);

        $this->assertEquals([
            '{DAV:}displayname' => 424,
            '{DAV:}unknown'     => 403
        ], $propPatch->getResult());

        $this->assertEquals([
            'id'                                    => '4',
            'uri'                                   => 'principals/user',
            '{DAV:}displayname'                     => 'User',
            '{http://sabredav.org/ns}email-address' => 'user@example.org',
        ], $backend->getPrincipalByPath('principals/user'));

    }

    function testFindByUriUnknownScheme() {

        $pdo = $this->getPDO();
        $backend = new PDO($pdo);
        $this->assertNull($backend->findByUri('http://foo', 'principals'));

    }


    function testFindByUri() {

        $pdo = $this->getPDO();
        $backend = new PDO($pdo);
        $this->assertEquals(
            'principals/user',
            $backend->findByUri('mailto:user@example.org', 'principals')
        );

    }

}
