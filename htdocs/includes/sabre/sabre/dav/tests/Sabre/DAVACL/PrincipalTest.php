<?php

namespace Sabre\DAVACL;

use Sabre\DAV;
use Sabre\HTTP;

class PrincipalTest extends \PHPUnit_Framework_TestCase {

    function testConstruct() {

        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);
        $this->assertTrue($principal instanceof Principal);

    }

    /**
     * @expectedException Sabre\DAV\Exception
     */
    function testConstructNoUri() {

        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, []);

    }

    function testGetName() {

        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);
        $this->assertEquals('admin', $principal->getName());

    }

    function testGetDisplayName() {

        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);
        $this->assertEquals('admin', $principal->getDisplayname());

        $principal = new Principal($principalBackend, [
            'uri'               => 'principals/admin',
            '{DAV:}displayname' => 'Mr. Admin'
        ]);
        $this->assertEquals('Mr. Admin', $principal->getDisplayname());

    }

    function testGetProperties() {

        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, [
            'uri'                                   => 'principals/admin',
            '{DAV:}displayname'                     => 'Mr. Admin',
            '{http://www.example.org/custom}custom' => 'Custom',
            '{http://sabredav.org/ns}email-address' => 'admin@example.org',
        ]);

        $keys = [
            '{DAV:}displayname',
            '{http://www.example.org/custom}custom',
            '{http://sabredav.org/ns}email-address',
        ];
        $props = $principal->getProperties($keys);

        foreach ($keys as $key) $this->assertArrayHasKey($key, $props);

        $this->assertEquals('Mr. Admin', $props['{DAV:}displayname']);

        $this->assertEquals('admin@example.org', $props['{http://sabredav.org/ns}email-address']);
    }

    function testUpdateProperties() {

        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);

        $propPatch = new DAV\PropPatch(['{DAV:}yourmom' => 'test']);

        $result = $principal->propPatch($propPatch);
        $result = $propPatch->commit();
        $this->assertTrue($result);

    }

    function testGetPrincipalUrl() {

        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);
        $this->assertEquals('principals/admin', $principal->getPrincipalUrl());

    }

    function testGetAlternateUriSet() {

        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, [
            'uri'                                   => 'principals/admin',
            '{DAV:}displayname'                     => 'Mr. Admin',
            '{http://www.example.org/custom}custom' => 'Custom',
            '{http://sabredav.org/ns}email-address' => 'admin@example.org',
            '{DAV:}alternate-URI-set'               => [
                'mailto:admin+1@example.org',
                'mailto:admin+2@example.org',
                'mailto:admin@example.org',
            ],
        ]);

        $expected = [
            'mailto:admin+1@example.org',
            'mailto:admin+2@example.org',
            'mailto:admin@example.org',
        ];

        $this->assertEquals($expected, $principal->getAlternateUriSet());

    }
    function testGetAlternateUriSetEmpty() {

        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, [
            'uri' => 'principals/admin',
        ]);

        $expected = [];

        $this->assertEquals($expected, $principal->getAlternateUriSet());

    }

    function testGetGroupMemberSet() {

        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);
        $this->assertEquals([], $principal->getGroupMemberSet());

    }
    function testGetGroupMembership() {

        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);
        $this->assertEquals([], $principal->getGroupMembership());

    }

    function testSetGroupMemberSet() {

        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);
        $principal->setGroupMemberSet(['principals/foo']);

        $this->assertEquals([
            'principals/admin' => ['principals/foo'],
        ], $principalBackend->groupMembers);

    }

    function testGetOwner() {

        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);
        $this->assertEquals('principals/admin', $principal->getOwner());

    }

    function testGetGroup() {

        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);
        $this->assertNull($principal->getGroup());

    }

    function testGetACl() {

        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);
        $this->assertEquals([
            [
                'privilege' => '{DAV:}all',
                'principal' => '{DAV:}owner',
                'protected' => true,
            ]
        ], $principal->getACL());

    }

    /**
     * @expectedException \Sabre\DAV\Exception\Forbidden
     */
    function testSetACl() {

        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);
        $principal->setACL([]);

    }

    function testGetSupportedPrivilegeSet() {

        $principalBackend = new PrincipalBackend\Mock();
        $principal = new Principal($principalBackend, ['uri' => 'principals/admin']);
        $this->assertNull($principal->getSupportedPrivilegeSet());

    }

}
