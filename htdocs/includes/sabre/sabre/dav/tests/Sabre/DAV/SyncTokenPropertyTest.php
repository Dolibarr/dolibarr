<?php

namespace Sabre\DAV;

class SyncTokenPropertyTest extends \Sabre\DAVServerTest {

    /**
     * The assumption in these tests is that a PROPFIND is going on, and to
     * fetch the sync-token, the event handler is just able to use the existing
     * result.
     *
     * @param string $name
     * @param mixed $value
     *
     * @dataProvider data
     */
    function testAlreadyThere1($name, $value) {

        $propFind = new PropFind('foo', [
            '{http://calendarserver.org/ns/}getctag',
            $name,
        ]);

        $propFind->set($name, $value);
        $corePlugin = new CorePlugin();
        $corePlugin->propFindLate($propFind, new SimpleCollection('hi'));

        $this->assertEquals("hello", $propFind->get('{http://calendarserver.org/ns/}getctag'));

    }

    /**
     * In these test-cases, the plugin is forced to do a local propfind to
     * fetch the items.
     *
     * @param string $name
     * @param mixed $value
     *
     * @dataProvider data
     */
    function testRefetch($name, $value) {

        $this->server->tree = new Tree(
            new SimpleCollection('root', [
                new Mock\PropertiesCollection(
                    'foo',
                    [],
                    [$name => $value]
                )
            ])
        );
        $propFind = new PropFind('foo', [
            '{http://calendarserver.org/ns/}getctag',
            $name,
        ]);

        $corePlugin = $this->server->getPlugin('core');
        $corePlugin->propFindLate($propFind, new SimpleCollection('hi'));

        $this->assertEquals("hello", $propFind->get('{http://calendarserver.org/ns/}getctag'));

    }

    function testNoData() {

        $this->server->tree = new Tree(
            new SimpleCollection('root', [
                new Mock\PropertiesCollection(
                    'foo',
                    [],
                    []
                )
            ])
        );

        $propFind = new PropFind('foo', [
            '{http://calendarserver.org/ns/}getctag',
        ]);

        $corePlugin = $this->server->getPlugin('core');
        $corePlugin->propFindLate($propFind, new SimpleCollection('hi'));

        $this->assertNull($propFind->get('{http://calendarserver.org/ns/}getctag'));

    }

    function data() {

        return [
            [
                '{http://sabredav.org/ns}sync-token',
                "hello"
            ],
            [
                '{DAV:}sync-token',
                "hello"
            ],
            [
                '{DAV:}sync-token',
                new Xml\Property\Href(Sync\Plugin::SYNCTOKEN_PREFIX . "hello", false)
            ]
        ];

    }

}
