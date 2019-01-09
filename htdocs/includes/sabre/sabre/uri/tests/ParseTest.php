<?php

namespace Sabre\Uri;

class ParseTest extends \PHPUnit_Framework_TestCase{

    /**
     * @dataProvider parseData
     */
    function testParse($in, $out) {

        $this->assertEquals(
            $out,
            parse($in)
        );

    }

    /**
     * @dataProvider parseData
     */
    function testParseFallback($in, $out) {

        $result = _parse_fallback($in);
        $result = $result + [
            'scheme'   => null,
            'host'     => null,
            'path'     => null,
            'port'     => null,
            'user'     => null,
            'query'    => null,
            'fragment' => null,
        ];

        $this->assertEquals(
            $out,
            $result
        );

    }

    function parseData() {

        return [
            [
                'http://example.org/hello?foo=bar#test',
                [
                    'scheme'   => 'http',
                    'host'     => 'example.org',
                    'path'     => '/hello',
                    'port'     => null,
                    'user'     => null,
                    'query'    => 'foo=bar',
                    'fragment' => 'test'
                ]
            ],
            // See issue #6. parse_url corrupts strings like this, but only on
            // macs.
            [
                'http://example.org/有词法别名.zh',
                [
                    'scheme'   => 'http',
                    'host'     => 'example.org',
                    'path'     => '/%E6%9C%89%E8%AF%8D%E6%B3%95%E5%88%AB%E5%90%8D.zh',
                    'port'     => null,
                    'user'     => null,
                    'query'    => null,
                    'fragment' => null
                ]
            ],
            [
                'ftp://user:password@ftp.example.org/',
                [
                    'scheme'   => 'ftp',
                    'host'     => 'ftp.example.org',
                    'path'     => '/',
                    'port'     => null,
                    'user'     => 'user',
                    'pass'     => 'password',
                    'query'    => null,
                    'fragment' => null,
                ]
            ],
            // See issue #9, parse_url doesn't like colons followed by numbers even
            // though they are allowed since RFC 3986
            [
                'http://example.org/hello:12?foo=bar#test',
                [
                    'scheme'   => 'http',
                    'host'     => 'example.org',
                    'path'     => '/hello:12',
                    'port'     => null,
                    'user'     => null,
                    'query'    => 'foo=bar',
                    'fragment' => 'test'
                ]
            ],
            [
                '/path/to/colon:34',
                [
                    'scheme'   => null,
                    'host'     => null,
                    'path'     => '/path/to/colon:34',
                    'port'     => null,
                    'user'     => null,
                    'query'    => null,
                    'fragment' => null,
                ]
            ],
            // File scheme
            [
                'file:///foo/bar',
                [
                    'scheme'   => 'file',
                    'host'     => '',
                    'path'     => '/foo/bar',
                    'port'     => null,
                    'user'     => null,
                    'query'    => null,
                    'fragment' => null,
                ]
            ],
            // Weird scheme with triple-slash. See Issue #11.
            [
                'vfs:///somefile',
                [
                    'scheme'   => 'vfs',
                    'host'     => '',
                    'path'     => '/somefile',
                    'port'     => null,
                    'user'     => null,
                    'query'    => null,
                    'fragment' => null,
                ]
            ],
            // Examples from RFC3986
            [
                'ldap://[2001:db8::7]/c=GB?objectClass?one',
                [
                    'scheme'   => 'ldap',
                    'host'     => '[2001:db8::7]',
                    'path'     => '/c=GB',
                    'port'     => null,
                    'user'     => null,
                    'query'    => 'objectClass?one',
                    'fragment' => null,
                ]
            ],
            [
                'news:comp.infosystems.www.servers.unix',
                [
                    'scheme'   => 'news',
                    'host'     => null,
                    'path'     => 'comp.infosystems.www.servers.unix',
                    'port'     => null,
                    'user'     => null,
                    'query'    => null,
                    'fragment' => null,
                ]
            ],
            // Port
            [
                'http://example.org:8080/',
                [
                    'scheme'   => 'http',
                    'host'     => 'example.org',
                    'path'     => '/',
                    'port'     => 8080,
                    'user'     => null,
                    'query'    => null,
                    'fragment' => null,
                ]
            ],

        ];

    }

}
