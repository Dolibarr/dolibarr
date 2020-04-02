<?php

namespace Sabre\DAV;

class PropFindTest extends \PHPUnit_Framework_TestCase {

    function testHandle() {

        $propFind = new PropFind('foo', ['{DAV:}displayname']);
        $propFind->handle('{DAV:}displayname', 'foobar');

        $this->assertEquals([
            200 => ['{DAV:}displayname' => 'foobar'],
            404 => [],
        ], $propFind->getResultForMultiStatus());

    }

    function testHandleCallBack() {

        $propFind = new PropFind('foo', ['{DAV:}displayname']);
        $propFind->handle('{DAV:}displayname', function() { return 'foobar'; });

        $this->assertEquals([
            200 => ['{DAV:}displayname' => 'foobar'],
            404 => [],
        ], $propFind->getResultForMultiStatus());

    }

    function testAllPropDefaults() {

        $propFind = new PropFind('foo', ['{DAV:}displayname'], 0, PropFind::ALLPROPS);

        $this->assertEquals([
            200 => [],
        ], $propFind->getResultForMultiStatus());

    }

    function testSet() {

        $propFind = new PropFind('foo', ['{DAV:}displayname']);
        $propFind->set('{DAV:}displayname', 'bar');

        $this->assertEquals([
            200 => ['{DAV:}displayname' => 'bar'],
            404 => [],
        ], $propFind->getResultForMultiStatus());

    }

    function testSetAllpropCustom() {

        $propFind = new PropFind('foo', ['{DAV:}displayname'], 0, PropFind::ALLPROPS);
        $propFind->set('{DAV:}customproperty', 'bar');

        $this->assertEquals([
            200 => ['{DAV:}customproperty' => 'bar'],
        ], $propFind->getResultForMultiStatus());

    }

    function testSetUnset() {

        $propFind = new PropFind('foo', ['{DAV:}displayname']);
        $propFind->set('{DAV:}displayname', 'bar');
        $propFind->set('{DAV:}displayname', null);

        $this->assertEquals([
            200 => [],
            404 => ['{DAV:}displayname' => null],
        ], $propFind->getResultForMultiStatus());

    }
}
