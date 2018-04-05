<?php

namespace Sabre\DAV\PropertyStorage\Backend;

use Sabre\DAV\PropFind;
use Sabre\DAV\PropPatch;
use Sabre\DAV\Xml\Property\Complex;
use Sabre\DAV\Xml\Property\Href;

abstract class AbstractPDOTest extends \PHPUnit_Framework_TestCase {

    use \Sabre\DAV\DbTestHelperTrait;

    function getBackend() {

        $this->dropTables('propertystorage');
        $this->createSchema('propertystorage');

        $pdo = $this->getPDO();

        $pdo->exec("INSERT INTO propertystorage (path, name, valuetype, value) VALUES ('dir', '{DAV:}displayname', 1, 'Directory')");

        return new PDO($this->getPDO());

    }

    function testPropFind() {

        $backend = $this->getBackend();

        $propFind = new PropFind('dir', ['{DAV:}displayname']);
        $backend->propFind('dir', $propFind);

        $this->assertEquals('Directory', $propFind->get('{DAV:}displayname'));

    }

    function testPropFindNothingToDo() {

        $backend = $this->getBackend();

        $propFind = new PropFind('dir', ['{DAV:}displayname']);
        $propFind->set('{DAV:}displayname', 'foo');
        $backend->propFind('dir', $propFind);

        $this->assertEquals('foo', $propFind->get('{DAV:}displayname'));

    }

    /**
     * @depends testPropFind
     */
    function testPropPatchUpdate() {

        $backend = $this->getBackend();

        $propPatch = new PropPatch(['{DAV:}displayname' => 'bar']);
        $backend->propPatch('dir', $propPatch);
        $propPatch->commit();

        $propFind = new PropFind('dir', ['{DAV:}displayname']);
        $backend->propFind('dir', $propFind);

        $this->assertEquals('bar', $propFind->get('{DAV:}displayname'));

    }

    /**
     * @depends testPropPatchUpdate
     */
    function testPropPatchComplex() {

        $backend = $this->getBackend();

        $complex = new Complex('<foo xmlns="DAV:">somevalue</foo>');

        $propPatch = new PropPatch(['{DAV:}complex' => $complex]);
        $backend->propPatch('dir', $propPatch);
        $propPatch->commit();

        $propFind = new PropFind('dir', ['{DAV:}complex']);
        $backend->propFind('dir', $propFind);

        $this->assertEquals($complex, $propFind->get('{DAV:}complex'));

    }


    /**
     * @depends testPropPatchComplex
     */
    function testPropPatchCustom() {

        $backend = $this->getBackend();

        $custom = new Href('/foo/bar/');

        $propPatch = new PropPatch(['{DAV:}custom' => $custom]);
        $backend->propPatch('dir', $propPatch);
        $propPatch->commit();

        $propFind = new PropFind('dir', ['{DAV:}custom']);
        $backend->propFind('dir', $propFind);

        $this->assertEquals($custom, $propFind->get('{DAV:}custom'));

    }

    /**
     * @depends testPropFind
     */
    function testPropPatchRemove() {

        $backend = $this->getBackend();

        $propPatch = new PropPatch(['{DAV:}displayname' => null]);
        $backend->propPatch('dir', $propPatch);
        $propPatch->commit();

        $propFind = new PropFind('dir', ['{DAV:}displayname']);
        $backend->propFind('dir', $propFind);

        $this->assertEquals(null, $propFind->get('{DAV:}displayname'));

    }

    /**
     * @depends testPropFind
     */
    function testDelete() {

        $backend = $this->getBackend();
        $backend->delete('dir');

        $propFind = new PropFind('dir', ['{DAV:}displayname']);
        $backend->propFind('dir', $propFind);

        $this->assertEquals(null, $propFind->get('{DAV:}displayname'));

    }

    /**
     * @depends testPropFind
     */
    function testMove() {

        $backend = $this->getBackend();
        // Creating a new child property.
        $propPatch = new PropPatch(['{DAV:}displayname' => 'child']);
        $backend->propPatch('dir/child', $propPatch);
        $propPatch->commit();

        $backend->move('dir', 'dir2');

        // Old 'dir'
        $propFind = new PropFind('dir', ['{DAV:}displayname']);
        $backend->propFind('dir', $propFind);
        $this->assertEquals(null, $propFind->get('{DAV:}displayname'));

        // Old 'dir/child'
        $propFind = new PropFind('dir/child', ['{DAV:}displayname']);
        $backend->propFind('dir/child', $propFind);
        $this->assertEquals(null, $propFind->get('{DAV:}displayname'));

        // New 'dir2'
        $propFind = new PropFind('dir2', ['{DAV:}displayname']);
        $backend->propFind('dir2', $propFind);
        $this->assertEquals('Directory', $propFind->get('{DAV:}displayname'));

        // New 'dir2/child'
        $propFind = new PropFind('dir2/child', ['{DAV:}displayname']);
        $backend->propFind('dir2/child', $propFind);
        $this->assertEquals('child', $propFind->get('{DAV:}displayname'));
    }

    /**
     * @depends testPropFind
     */
    function testDeepDelete() {

        $backend = $this->getBackend();
        $propPatch = new PropPatch(['{DAV:}displayname' => 'child']);
        $backend->propPatch('dir/child', $propPatch);
        $propPatch->commit();
        $backend->delete('dir');

        $propFind = new PropFind('dir/child', ['{DAV:}displayname']);
        $backend->propFind('dir/child', $propFind);

        $this->assertEquals(null, $propFind->get('{DAV:}displayname'));

    }
}
