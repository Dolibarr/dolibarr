<?php

namespace Sabre\DAV;

use Sabre\HTTP;

require_once 'Sabre/HTTP/ResponseMock.php';
require_once 'Sabre/DAV/AbstractServer.php';

class GetIfConditionsTest extends AbstractServer {

    function testNoConditions() {

        $request = new HTTP\Request();

        $conditions = $this->server->getIfConditions($request);
        $this->assertEquals([], $conditions);

    }

    function testLockToken() {

        $request = new HTTP\Request('GET', '/path/', ['If' => '(<opaquelocktoken:token1>)']);
        $conditions = $this->server->getIfConditions($request);

        $compare = [

            [
                'uri'    => 'path',
                'tokens' => [
                    [
                        'negate' => false,
                        'token'  => 'opaquelocktoken:token1',
                        'etag'   => '',
                    ],
                ],

            ],

        ];

        $this->assertEquals($compare, $conditions);

    }

    function testNotLockToken() {

        $serverVars = [
            'HTTP_IF'     => '(Not <opaquelocktoken:token1>)',
            'REQUEST_URI' => '/bla'
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $conditions = $this->server->getIfConditions($request);

        $compare = [

            [
                'uri'    => 'bla',
                'tokens' => [
                    [
                        'negate' => true,
                        'token'  => 'opaquelocktoken:token1',
                        'etag'   => '',
                    ],
                ],

            ],

        ];
        $this->assertEquals($compare, $conditions);

    }

    function testLockTokenUrl() {

        $serverVars = [
            'HTTP_IF' => '<http://www.example.com/> (<opaquelocktoken:token1>)',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $conditions = $this->server->getIfConditions($request);

        $compare = [

            [
                'uri'    => '',
                'tokens' => [
                    [
                        'negate' => false,
                        'token'  => 'opaquelocktoken:token1',
                        'etag'   => '',
                    ],
                ],

            ],

        ];
        $this->assertEquals($compare, $conditions);

    }

    function test2LockTokens() {

        $serverVars = [
            'HTTP_IF'     => '(<opaquelocktoken:token1>) (Not <opaquelocktoken:token2>)',
            'REQUEST_URI' => '/bla',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $conditions = $this->server->getIfConditions($request);

        $compare = [

            [
                'uri'    => 'bla',
                'tokens' => [
                    [
                        'negate' => false,
                        'token'  => 'opaquelocktoken:token1',
                        'etag'   => '',
                    ],
                    [
                        'negate' => true,
                        'token'  => 'opaquelocktoken:token2',
                        'etag'   => '',
                    ],
                ],

            ],

        ];
        $this->assertEquals($compare, $conditions);

    }

    function test2UriLockTokens() {

        $serverVars = [
            'HTTP_IF' => '<http://www.example.org/node1> (<opaquelocktoken:token1>) <http://www.example.org/node2> (Not <opaquelocktoken:token2>)',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $conditions = $this->server->getIfConditions($request);

        $compare = [

            [
                'uri'    => 'node1',
                'tokens' => [
                    [
                        'negate' => false,
                        'token'  => 'opaquelocktoken:token1',
                        'etag'   => '',
                    ],
                 ],
            ],
            [
                'uri'    => 'node2',
                'tokens' => [
                    [
                        'negate' => true,
                        'token'  => 'opaquelocktoken:token2',
                        'etag'   => '',
                    ],
                ],

            ],

        ];
        $this->assertEquals($compare, $conditions);

    }

    function test2UriMultiLockTokens() {

        $serverVars = [
            'HTTP_IF' => '<http://www.example.org/node1> (<opaquelocktoken:token1>) (<opaquelocktoken:token2>) <http://www.example.org/node2> (Not <opaquelocktoken:token3>)',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $conditions = $this->server->getIfConditions($request);

        $compare = [

            [
                'uri'    => 'node1',
                'tokens' => [
                    [
                        'negate' => false,
                        'token'  => 'opaquelocktoken:token1',
                        'etag'   => '',
                    ],
                    [
                        'negate' => false,
                        'token'  => 'opaquelocktoken:token2',
                        'etag'   => '',
                    ],
                 ],
            ],
            [
                'uri'    => 'node2',
                'tokens' => [
                    [
                        'negate' => true,
                        'token'  => 'opaquelocktoken:token3',
                        'etag'   => '',
                    ],
                ],

            ],

        ];
        $this->assertEquals($compare, $conditions);

    }

    function testEtag() {

        $serverVars = [
            'HTTP_IF'     => '(["etag1"])',
            'REQUEST_URI' => '/foo',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $conditions = $this->server->getIfConditions($request);

        $compare = [

            [
                'uri'    => 'foo',
                'tokens' => [
                    [
                        'negate' => false,
                        'token'  => '',
                        'etag'   => '"etag1"',
                    ],
                 ],
            ],

        ];
        $this->assertEquals($compare, $conditions);

    }

    function test2Etags() {

        $serverVars = [
            'HTTP_IF' => '<http://www.example.org/> (["etag1"]) (["etag2"])',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $conditions = $this->server->getIfConditions($request);

        $compare = [

            [
                'uri'    => '',
                'tokens' => [
                    [
                        'negate' => false,
                        'token'  => '',
                        'etag'   => '"etag1"',
                    ],
                    [
                        'negate' => false,
                        'token'  => '',
                        'etag'   => '"etag2"',
                    ],
                 ],
            ],

        ];
        $this->assertEquals($compare, $conditions);

    }

    function testComplexIf() {

        $serverVars = [
            'HTTP_IF' => '<http://www.example.org/node1> (<opaquelocktoken:token1> ["etag1"]) ' .
                         '(Not <opaquelocktoken:token2>) (["etag2"]) <http://www.example.org/node2> ' .
                         '(<opaquelocktoken:token3>) (Not <opaquelocktoken:token4>) (["etag3"])',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $conditions = $this->server->getIfConditions($request);

        $compare = [

            [
                'uri'    => 'node1',
                'tokens' => [
                    [
                        'negate' => false,
                        'token'  => 'opaquelocktoken:token1',
                        'etag'   => '"etag1"',
                    ],
                    [
                        'negate' => true,
                        'token'  => 'opaquelocktoken:token2',
                        'etag'   => '',
                    ],
                    [
                        'negate' => false,
                        'token'  => '',
                        'etag'   => '"etag2"',
                    ],
                 ],
            ],
            [
                'uri'    => 'node2',
                'tokens' => [
                    [
                        'negate' => false,
                        'token'  => 'opaquelocktoken:token3',
                        'etag'   => '',
                    ],
                    [
                        'negate' => true,
                        'token'  => 'opaquelocktoken:token4',
                        'etag'   => '',
                    ],
                    [
                        'negate' => false,
                        'token'  => '',
                        'etag'   => '"etag3"',
                    ],
                 ],
            ],

        ];
        $this->assertEquals($compare, $conditions);

    }

}
