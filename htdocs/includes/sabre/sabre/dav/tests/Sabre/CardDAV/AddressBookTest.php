<?php

namespace Sabre\CardDAV;

use Sabre\DAV\PropPatch;

class AddressBookTest extends \PHPUnit_Framework_TestCase {

    use \Sabre\DAV\DbTestHelperTrait;

    /**
     * @var Sabre\CardDAV\AddressBook
     */
    protected $ab;
    protected $backend;

    function setUp() {

        $this->backend = new Backend\Mock();
        $this->ab = new AddressBook(
            $this->backend,
            [
                'uri'               => 'book1',
                'id'                => 'foo',
                '{DAV:}displayname' => 'd-name',
                'principaluri'      => 'principals/user1',
            ]
        );

    }

    function testGetName() {

        $this->assertEquals('book1', $this->ab->getName());

    }

    function testGetChild() {

        $card = $this->ab->getChild('card1');
        $this->assertInstanceOf('Sabre\\CardDAV\\Card', $card);
        $this->assertEquals('card1', $card->getName());

    }

    /**
     * @expectedException Sabre\DAV\Exception\NotFound
     */
    function testGetChildNotFound() {

        $card = $this->ab->getChild('card3');

    }

    function testGetChildren() {

        $cards = $this->ab->getChildren();
        $this->assertEquals(2, count($cards));

        $this->assertEquals('card1', $cards[0]->getName());
        $this->assertEquals('card2', $cards[1]->getName());

    }

    /**
     * @expectedException Sabre\DAV\Exception\MethodNotAllowed
     */
    function testCreateDirectory() {

        $this->ab->createDirectory('name');

    }

    function testCreateFile() {

        $file = fopen('php://memory', 'r+');
        fwrite($file, 'foo');
        rewind($file);
        $this->ab->createFile('card2', $file);

        $this->assertEquals('foo', $this->backend->cards['foo']['card2']);

    }

    function testDelete() {

        $this->ab->delete();
        $this->assertEquals(1, count($this->backend->addressBooks));

    }

    /**
     * @expectedException Sabre\DAV\Exception\MethodNotAllowed
     */
    function testSetName() {

        $this->ab->setName('foo');

    }

    function testGetLastModified() {

        $this->assertNull($this->ab->getLastModified());

    }

    function testUpdateProperties() {

        $propPatch = new PropPatch([
            '{DAV:}displayname' => 'barrr',
        ]);
        $this->ab->propPatch($propPatch);
        $this->assertTrue($propPatch->commit());

        $this->assertEquals('barrr', $this->backend->addressBooks[0]['{DAV:}displayname']);

    }

    function testGetProperties() {

        $props = $this->ab->getProperties(['{DAV:}displayname']);
        $this->assertEquals([
            '{DAV:}displayname' => 'd-name',
        ], $props);

    }

    function testACLMethods() {

        $this->assertEquals('principals/user1', $this->ab->getOwner());
        $this->assertNull($this->ab->getGroup());
        $this->assertEquals([
            [
                'privilege' => '{DAV:}all',
                'principal' => '{DAV:}owner',
                'protected' => true,
            ],
        ], $this->ab->getACL());

    }

    /**
     * @expectedException Sabre\DAV\Exception\Forbidden
     */
    function testSetACL() {

       $this->ab->setACL([]);

    }

    function testGetSupportedPrivilegeSet() {

        $this->assertNull(
            $this->ab->getSupportedPrivilegeSet()
        );

    }

    function testGetSyncTokenNoSyncSupport() {

        $this->assertNull($this->ab->getSyncToken());

    }
    function testGetChangesNoSyncSupport() {

        $this->assertNull($this->ab->getChanges(1, null));

    }

    function testGetSyncToken() {

        $this->driver = 'sqlite';
        $this->dropTables(['addressbooks', 'cards', 'addressbookchanges']);
        $this->createSchema('addressbooks');
        $backend = new Backend\PDO(
            $this->getPDO()
        );
        $ab = new AddressBook($backend, ['id' => 1, '{DAV:}sync-token' => 2]);
        $this->assertEquals(2, $ab->getSyncToken());
    }

    function testGetSyncToken2() {

        $this->driver = 'sqlite';
        $this->dropTables(['addressbooks', 'cards', 'addressbookchanges']);
        $this->createSchema('addressbooks');
        $backend = new Backend\PDO(
            $this->getPDO()
        );
        $ab = new AddressBook($backend, ['id' => 1, '{http://sabredav.org/ns}sync-token' => 2]);
        $this->assertEquals(2, $ab->getSyncToken());
    }

}
