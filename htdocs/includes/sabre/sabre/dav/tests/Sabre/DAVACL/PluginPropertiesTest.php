<?php

namespace Sabre\DAVACL;

use Sabre\DAV;
use Sabre\HTTP;

class PluginPropertiesTest extends \PHPUnit_Framework_TestCase {

    function testPrincipalCollectionSet() {

        $plugin = new Plugin();
        $plugin->allowUnauthenticatedAccess = false;
        $plugin->setDefaultACL([
            [
                'principal' => '{DAV:}all',
                'privilege' => '{DAV:}all',
            ],
        ]);
        //Anyone can do anything
        $plugin->principalCollectionSet = [
            'principals1',
            'principals2',
        ];

        $requestedProperties = [
            '{DAV:}principal-collection-set',
        ];

        $server = new DAV\Server(new DAV\SimpleCollection('root'));
        $server->addPlugin($plugin);

        $result = $server->getPropertiesForPath('', $requestedProperties);
        $result = $result[0];

        $this->assertEquals(1, count($result[200]));
        $this->assertArrayHasKey('{DAV:}principal-collection-set', $result[200]);
        $this->assertInstanceOf('Sabre\\DAV\\Xml\\Property\\Href', $result[200]['{DAV:}principal-collection-set']);

        $expected = [
            'principals1/',
            'principals2/',
        ];


        $this->assertEquals($expected, $result[200]['{DAV:}principal-collection-set']->getHrefs());


    }

    function testCurrentUserPrincipal() {

        $fakeServer = new DAV\Server();
        $plugin = new DAV\Auth\Plugin(new DAV\Auth\Backend\Mock());
        $fakeServer->addPlugin($plugin);
        $plugin = new Plugin();
        $plugin->setDefaultACL([
            [
                'principal' => '{DAV:}all',
                'privilege' => '{DAV:}all',
            ],
        ]);
        $fakeServer->addPlugin($plugin);


        $requestedProperties = [
            '{DAV:}current-user-principal',
        ];

        $result = $fakeServer->getPropertiesForPath('', $requestedProperties);
        $result = $result[0];

        $this->assertEquals(1, count($result[200]));
        $this->assertArrayHasKey('{DAV:}current-user-principal', $result[200]);
        $this->assertInstanceOf('Sabre\DAVACL\Xml\Property\Principal', $result[200]['{DAV:}current-user-principal']);
        $this->assertEquals(Xml\Property\Principal::UNAUTHENTICATED, $result[200]['{DAV:}current-user-principal']->getType());

        // This will force the login
        $fakeServer->emit('beforeMethod', [$fakeServer->httpRequest, $fakeServer->httpResponse]);

        $result = $fakeServer->getPropertiesForPath('', $requestedProperties);
        $result = $result[0];

        $this->assertEquals(1, count($result[200]));
        $this->assertArrayHasKey('{DAV:}current-user-principal', $result[200]);
        $this->assertInstanceOf('Sabre\DAVACL\Xml\Property\Principal', $result[200]['{DAV:}current-user-principal']);
        $this->assertEquals(Xml\Property\Principal::HREF, $result[200]['{DAV:}current-user-principal']->getType());
        $this->assertEquals('principals/admin/', $result[200]['{DAV:}current-user-principal']->getHref());

    }

    function testSupportedPrivilegeSet() {

        $plugin = new Plugin();
        $plugin->allowUnauthenticatedAccess = false;
        $plugin->setDefaultACL([
            [
                'principal' => '{DAV:}all',
                'privilege' => '{DAV:}all',
            ],
        ]);
        $server = new DAV\Server();
        $server->addPlugin($plugin);

        $requestedProperties = [
            '{DAV:}supported-privilege-set',
        ];

        $result = $server->getPropertiesForPath('', $requestedProperties);
        $result = $result[0];

        $this->assertEquals(1, count($result[200]));
        $this->assertArrayHasKey('{DAV:}supported-privilege-set', $result[200]);
        $this->assertInstanceOf('Sabre\\DAVACL\\Xml\\Property\\SupportedPrivilegeSet', $result[200]['{DAV:}supported-privilege-set']);

        $server = new DAV\Server();

        $prop = $result[200]['{DAV:}supported-privilege-set'];
        $result = $server->xml->write('{DAV:}root', $prop);

        $xpaths = [
            '/d:root'                                                                                                                 => 1,
            '/d:root/d:supported-privilege'                                                                                           => 1,
            '/d:root/d:supported-privilege/d:privilege'                                                                               => 1,
            '/d:root/d:supported-privilege/d:privilege/d:all'                                                                         => 1,
            '/d:root/d:supported-privilege/d:abstract'                                                                                => 0,
            '/d:root/d:supported-privilege/d:supported-privilege'                                                                     => 2,
            '/d:root/d:supported-privilege/d:supported-privilege/d:privilege'                                                         => 2,
            '/d:root/d:supported-privilege/d:supported-privilege/d:privilege/d:read'                                                  => 1,
            '/d:root/d:supported-privilege/d:supported-privilege/d:privilege/d:write'                                                 => 1,
            '/d:root/d:supported-privilege/d:supported-privilege/d:supported-privilege'                                               => 7,
            '/d:root/d:supported-privilege/d:supported-privilege/d:supported-privilege/d:privilege'                                   => 7,
            '/d:root/d:supported-privilege/d:supported-privilege/d:supported-privilege/d:privilege/d:read-acl'                        => 1,
            '/d:root/d:supported-privilege/d:supported-privilege/d:supported-privilege/d:privilege/d:read-current-user-privilege-set' => 1,
            '/d:root/d:supported-privilege/d:supported-privilege/d:supported-privilege/d:privilege/d:write-content'                   => 1,
            '/d:root/d:supported-privilege/d:supported-privilege/d:supported-privilege/d:privilege/d:write-properties'                => 1,
            '/d:root/d:supported-privilege/d:supported-privilege/d:supported-privilege/d:privilege/d:bind'                            => 1,
            '/d:root/d:supported-privilege/d:supported-privilege/d:supported-privilege/d:privilege/d:unbind'                          => 1,
            '/d:root/d:supported-privilege/d:supported-privilege/d:supported-privilege/d:privilege/d:unlock'                          => 1,
            '/d:root/d:supported-privilege/d:supported-privilege/d:supported-privilege/d:abstract'                                    => 0,
        ];


        // reloading because php dom sucks
        $dom2 = new \DOMDocument('1.0', 'utf-8');
        $dom2->loadXML($result);

        $dxpath = new \DOMXPath($dom2);
        $dxpath->registerNamespace('d', 'DAV:');
        foreach ($xpaths as $xpath => $count) {

            $this->assertEquals($count, $dxpath->query($xpath)->length, 'Looking for : ' . $xpath . ', we could only find ' . $dxpath->query($xpath)->length . ' elements, while we expected ' . $count . ' Full XML: ' . $result);

        }

    }

    function testACL() {

        $plugin = new Plugin();
        $plugin->allowUnauthenticatedAccess = false;
        $plugin->setDefaultACL([
            [
                'principal' => '{DAV:}all',
                'privilege' => '{DAV:}all',
            ],
        ]);

        $nodes = [
            new MockACLNode('foo', [
                [
                    'principal' => 'principals/admin',
                    'privilege' => '{DAV:}read',
                ]
            ]),
            new DAV\SimpleCollection('principals', [
                $principal = new MockPrincipal('admin', 'principals/admin'),
            ]),

        ];

        $server = new DAV\Server($nodes);
        $server->addPlugin($plugin);
        $authPlugin = new DAV\Auth\Plugin(new DAV\Auth\Backend\Mock());
        $server->addPlugin($authPlugin);

        // Force login
        $authPlugin->beforeMethod(new HTTP\Request(), new HTTP\Response());

        $requestedProperties = [
            '{DAV:}acl',
        ];

        $result = $server->getPropertiesForPath('foo', $requestedProperties);
        $result = $result[0];

        $this->assertEquals(1, count($result[200]), 'The {DAV:}acl property did not return from the list. Full list: ' . print_r($result, true));
        $this->assertArrayHasKey('{DAV:}acl', $result[200]);
        $this->assertInstanceOf('Sabre\\DAVACL\\Xml\Property\\Acl', $result[200]['{DAV:}acl']);

    }

    function testACLRestrictions() {

        $plugin = new Plugin();
        $plugin->allowUnauthenticatedAccess = false;

        $nodes = [
            new MockACLNode('foo', [
                [
                    'principal' => 'principals/admin',
                    'privilege' => '{DAV:}read',
                ]
            ]),
            new DAV\SimpleCollection('principals', [
                $principal = new MockPrincipal('admin', 'principals/admin'),
            ]),

        ];

        $server = new DAV\Server($nodes);
        $server->addPlugin($plugin);
        $authPlugin = new DAV\Auth\Plugin(new DAV\Auth\Backend\Mock());
        $server->addPlugin($authPlugin);

        // Force login
        $authPlugin->beforeMethod(new HTTP\Request(), new HTTP\Response());

        $requestedProperties = [
            '{DAV:}acl-restrictions',
        ];

        $result = $server->getPropertiesForPath('foo', $requestedProperties);
        $result = $result[0];

        $this->assertEquals(1, count($result[200]), 'The {DAV:}acl-restrictions property did not return from the list. Full list: ' . print_r($result, true));
        $this->assertArrayHasKey('{DAV:}acl-restrictions', $result[200]);
        $this->assertInstanceOf('Sabre\\DAVACL\\Xml\\Property\\AclRestrictions', $result[200]['{DAV:}acl-restrictions']);

    }

    function testAlternateUriSet() {

        $tree = [
            new DAV\SimpleCollection('principals', [
                $principal = new MockPrincipal('user', 'principals/user'),
            ])
        ];

        $fakeServer = new DAV\Server($tree);
        //$plugin = new DAV\Auth\Plugin(new DAV\Auth\MockBackend())
        //$fakeServer->addPlugin($plugin);
        $plugin = new Plugin();
        $plugin->allowUnauthenticatedAccess = false;
        $plugin->setDefaultACL([
            [
                'principal' => '{DAV:}all',
                'privilege' => '{DAV:}all',
            ],
        ]);
        $fakeServer->addPlugin($plugin);

        $requestedProperties = [
            '{DAV:}alternate-URI-set',
        ];
        $result = $fakeServer->getPropertiesForPath('principals/user', $requestedProperties);
        $result = $result[0];

        $this->assertTrue(isset($result[200]));
        $this->assertTrue(isset($result[200]['{DAV:}alternate-URI-set']));
        $this->assertInstanceOf('Sabre\\DAV\\Xml\\Property\\Href', $result[200]['{DAV:}alternate-URI-set']);

        $this->assertEquals([], $result[200]['{DAV:}alternate-URI-set']->getHrefs());

    }

    function testPrincipalURL() {

        $tree = [
            new DAV\SimpleCollection('principals', [
                $principal = new MockPrincipal('user', 'principals/user'),
            ]),
        ];

        $fakeServer = new DAV\Server($tree);
        //$plugin = new DAV\Auth\Plugin(new DAV\Auth\MockBackend());
        //$fakeServer->addPlugin($plugin);
        $plugin = new Plugin();
        $plugin->allowUnauthenticatedAccess = false;
        $plugin->setDefaultACL([
            [
                'principal' => '{DAV:}all',
                'privilege' => '{DAV:}all',
            ],
        ]);
        $fakeServer->addPlugin($plugin);

        $requestedProperties = [
            '{DAV:}principal-URL',
        ];

        $result = $fakeServer->getPropertiesForPath('principals/user', $requestedProperties);
        $result = $result[0];

        $this->assertTrue(isset($result[200]));
        $this->assertTrue(isset($result[200]['{DAV:}principal-URL']));
        $this->assertInstanceOf('Sabre\\DAV\\Xml\\Property\\Href', $result[200]['{DAV:}principal-URL']);

        $this->assertEquals('principals/user/', $result[200]['{DAV:}principal-URL']->getHref());

    }

    function testGroupMemberSet() {

        $tree = [
            new DAV\SimpleCollection('principals', [
                $principal = new MockPrincipal('user', 'principals/user'),
            ]),
        ];

        $fakeServer = new DAV\Server($tree);
        //$plugin = new DAV\Auth\Plugin(new DAV\Auth\MockBackend());
        //$fakeServer->addPlugin($plugin);
        $plugin = new Plugin();
        $plugin->allowUnauthenticatedAccess = false;
        $plugin->setDefaultACL([
            [
                'principal' => '{DAV:}all',
                'privilege' => '{DAV:}all',
            ],
        ]);
        $fakeServer->addPlugin($plugin);

        $requestedProperties = [
            '{DAV:}group-member-set',
        ];

        $result = $fakeServer->getPropertiesForPath('principals/user', $requestedProperties);
        $result = $result[0];

        $this->assertTrue(isset($result[200]));
        $this->assertTrue(isset($result[200]['{DAV:}group-member-set']));
        $this->assertInstanceOf('Sabre\\DAV\\Xml\\Property\\Href', $result[200]['{DAV:}group-member-set']);

        $this->assertEquals([], $result[200]['{DAV:}group-member-set']->getHrefs());

    }

    function testGroupMemberShip() {

        $tree = [
            new DAV\SimpleCollection('principals', [
                $principal = new MockPrincipal('user', 'principals/user'),
            ]),
        ];

        $fakeServer = new DAV\Server($tree);
        $plugin = new Plugin();
        $plugin->allowUnauthenticatedAccess = false;
        $fakeServer->addPlugin($plugin);
        $plugin->setDefaultACL([
            [
                'principal' => '{DAV:}all',
                'privilege' => '{DAV:}all',
            ],
        ]);

        $requestedProperties = [
            '{DAV:}group-membership',
        ];

        $result = $fakeServer->getPropertiesForPath('principals/user', $requestedProperties);
        $result = $result[0];

        $this->assertTrue(isset($result[200]));
        $this->assertTrue(isset($result[200]['{DAV:}group-membership']));
        $this->assertInstanceOf('Sabre\\DAV\\Xml\\Property\\Href', $result[200]['{DAV:}group-membership']);

        $this->assertEquals([], $result[200]['{DAV:}group-membership']->getHrefs());

    }

    function testGetDisplayName() {

        $tree = [
            new DAV\SimpleCollection('principals', [
                $principal = new MockPrincipal('user', 'principals/user'),
            ]),
        ];

        $fakeServer = new DAV\Server($tree);
        $plugin = new Plugin();
        $plugin->allowUnauthenticatedAccess = false;
        $fakeServer->addPlugin($plugin);
        $plugin->setDefaultACL([
            [
                'principal' => '{DAV:}all',
                'privilege' => '{DAV:}all',
            ],
        ]);

        $requestedProperties = [
            '{DAV:}displayname',
        ];

        $result = $fakeServer->getPropertiesForPath('principals/user', $requestedProperties);
        $result = $result[0];

        $this->assertTrue(isset($result[200]));
        $this->assertTrue(isset($result[200]['{DAV:}displayname']));

        $this->assertEquals('user', $result[200]['{DAV:}displayname']);

    }
}
