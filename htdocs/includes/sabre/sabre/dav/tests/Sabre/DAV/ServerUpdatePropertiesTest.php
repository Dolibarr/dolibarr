<?php

namespace Sabre\DAV;

class ServerUpdatePropertiesTest extends \PHPUnit_Framework_TestCase {

    function testUpdatePropertiesFail() {

        $tree = [
            new SimpleCollection('foo'),
        ];
        $server = new Server($tree);

        $result = $server->updateProperties('foo', [
            '{DAV:}foo' => 'bar'
        ]);

        $expected = [
            '{DAV:}foo' => 403,
        ];
        $this->assertEquals($expected, $result);

    }

    function testUpdatePropertiesProtected() {

        $tree = [
            new SimpleCollection('foo'),
        ];
        $server = new Server($tree);

        $server->on('propPatch', function($path, PropPatch $propPatch) {
            $propPatch->handleRemaining(function() { return true; });
        });
        $result = $server->updateProperties('foo', [
            '{DAV:}getetag' => 'bla',
            '{DAV:}foo'     => 'bar'
        ]);

        $expected = [
            '{DAV:}getetag' => 403,
            '{DAV:}foo'     => 424,
        ];
        $this->assertEquals($expected, $result);

    }

    function testUpdatePropertiesEventFail() {

        $tree = [
            new SimpleCollection('foo'),
        ];
        $server = new Server($tree);
        $server->on('propPatch', function($path, PropPatch $propPatch) {
            $propPatch->setResultCode('{DAV:}foo', 404);
            $propPatch->handleRemaining(function() { return true; });
        });

        $result = $server->updateProperties('foo', [
            '{DAV:}foo'  => 'bar',
            '{DAV:}foo2' => 'bla',
        ]);

        $expected = [
            '{DAV:}foo'  => 404,
            '{DAV:}foo2' => 424,
        ];
        $this->assertEquals($expected, $result);

    }

    function testUpdatePropertiesEventSuccess() {

        $tree = [
            new SimpleCollection('foo'),
        ];
        $server = new Server($tree);
        $server->on('propPatch', function($path, PropPatch $propPatch) {

            $propPatch->handle(['{DAV:}foo', '{DAV:}foo2'], function() {
                return [
                    '{DAV:}foo'  => 200,
                    '{DAV:}foo2' => 201,
                ];
            });

        });

        $result = $server->updateProperties('foo', [
            '{DAV:}foo'  => 'bar',
            '{DAV:}foo2' => 'bla',
        ]);

        $expected = [
            '{DAV:}foo'  => 200,
            '{DAV:}foo2' => 201,
        ];
        $this->assertEquals($expected, $result);

    }

}
