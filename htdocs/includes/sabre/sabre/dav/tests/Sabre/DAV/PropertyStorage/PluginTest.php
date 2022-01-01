<?php

namespace Sabre\DAV\PropertyStorage;

class PluginTest extends \Sabre\DAVServerTest {

    protected $backend;
    protected $plugin;

    protected $setupFiles = true;

    function setUp() {

        parent::setUp();
        $this->backend = new Backend\Mock();
        $this->plugin = new Plugin(
            $this->backend
        );

        $this->server->addPlugin($this->plugin);

    }

    function testGetInfo() {

        $this->assertArrayHasKey(
            'name',
            $this->plugin->getPluginInfo()
        );

    }

    function testSetProperty() {

        $this->server->updateProperties('', ['{DAV:}displayname' => 'hi']);
        $this->assertEquals([
            '' => [
                '{DAV:}displayname' => 'hi',
            ]
        ], $this->backend->data);

    }

    /**
     * @depends testSetProperty
     */
    function testGetProperty() {

        $this->testSetProperty();
        $result = $this->server->getProperties('', ['{DAV:}displayname']);

        $this->assertEquals([
            '{DAV:}displayname' => 'hi',
        ], $result);

    }

    /**
     * @depends testSetProperty
     */
    function testDeleteProperty() {

        $this->testSetProperty();
        $this->server->emit('afterUnbind', ['']);
        $this->assertEquals([], $this->backend->data);

    }

    function testMove() {

        $this->server->tree->getNodeForPath('files')->createFile('source');
        $this->server->updateProperties('files/source', ['{DAV:}displayname' => 'hi']);

        $request = new \Sabre\HTTP\Request('MOVE', '/files/source', ['Destination' => '/files/dest']);
        $this->assertHTTPStatus(201, $request);

        $result = $this->server->getProperties('/files/dest', ['{DAV:}displayname']);

        $this->assertEquals([
            '{DAV:}displayname' => 'hi',
        ], $result);

        $this->server->tree->getNodeForPath('files')->createFile('source');
        $result = $this->server->getProperties('/files/source', ['{DAV:}displayname']);

        $this->assertEquals([], $result);

    }

    /**
     * @depends testDeleteProperty
     */
    function testSetPropertyInFilteredPath() {

        $this->plugin->pathFilter = function($path) {

            return false;

        };

        $this->server->updateProperties('', ['{DAV:}displayname' => 'hi']);
        $this->assertEquals([], $this->backend->data);

    }

    /**
     * @depends testSetPropertyInFilteredPath
     */
    function testGetPropertyInFilteredPath() {

        $this->testSetPropertyInFilteredPath();
        $result = $this->server->getProperties('', ['{DAV:}displayname']);

        $this->assertEquals([], $result);
    }

}
