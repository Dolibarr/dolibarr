<?php

namespace Sabre\CardDAV;

class CardTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Sabre\CardDAV\Card
     */
    protected $card;
    /**
     * @var Sabre\CardDAV\MockBackend
     */
    protected $backend;

    function setUp() {

        $this->backend = new Backend\Mock();
        $this->card = new Card(
            $this->backend,
            [
                'uri'          => 'book1',
                'id'           => 'foo',
                'principaluri' => 'principals/user1',
            ],
            [
                'uri'           => 'card1',
                'addressbookid' => 'foo',
                'carddata'      => 'card',
            ]
        );

    }

    function testGet() {

        $result = $this->card->get();
        $this->assertEquals('card', $result);

    }
    function testGet2() {

        $this->card = new Card(
            $this->backend,
            [
                'uri'          => 'book1',
                'id'           => 'foo',
                'principaluri' => 'principals/user1',
            ],
            [
                'uri'           => 'card1',
                'addressbookid' => 'foo',
            ]
        );
        $result = $this->card->get();
        $this->assertEquals("BEGIN:VCARD\nVERSION:3.0\nUID:12345\nEND:VCARD", $result);

    }


    /**
     * @depends testGet
     */
    function testPut() {

        $file = fopen('php://memory', 'r+');
        fwrite($file, 'newdata');
        rewind($file);
        $this->card->put($file);
        $result = $this->card->get();
        $this->assertEquals('newdata', $result);

    }


    function testDelete() {

        $this->card->delete();
        $this->assertEquals(1, count($this->backend->cards['foo']));

    }

    function testGetContentType() {

        $this->assertEquals('text/vcard; charset=utf-8', $this->card->getContentType());

    }

    function testGetETag() {

        $this->assertEquals('"' . md5('card') . '"', $this->card->getETag());

    }

    function testGetETag2() {

        $card = new Card(
            $this->backend,
            [
                'uri'          => 'book1',
                'id'           => 'foo',
                'principaluri' => 'principals/user1',
            ],
            [
                'uri'           => 'card1',
                'addressbookid' => 'foo',
                'carddata'      => 'card',
                'etag'          => '"blabla"',
            ]
        );
        $this->assertEquals('"blabla"', $card->getETag());

    }

    function testGetLastModified() {

        $this->assertEquals(null, $this->card->getLastModified());

    }

    function testGetSize() {

        $this->assertEquals(4, $this->card->getSize());
        $this->assertEquals(4, $this->card->getSize());

    }

    function testGetSize2() {

        $card = new Card(
            $this->backend,
            [
                'uri'          => 'book1',
                'id'           => 'foo',
                'principaluri' => 'principals/user1',
            ],
            [
                'uri'           => 'card1',
                'addressbookid' => 'foo',
                'etag'          => '"blabla"',
                'size'          => 4,
            ]
        );
        $this->assertEquals(4, $card->getSize());

    }

    function testACLMethods() {

        $this->assertEquals('principals/user1', $this->card->getOwner());
        $this->assertNull($this->card->getGroup());
        $this->assertEquals([
            [
                'privilege' => '{DAV:}all',
                'principal' => 'principals/user1',
                'protected' => true,
            ],
        ], $this->card->getACL());

    }
    function testOverrideACL() {

        $card = new Card(
            $this->backend,
            [
                'uri'          => 'book1',
                'id'           => 'foo',
                'principaluri' => 'principals/user1',
            ],
            [
                'uri'           => 'card1',
                'addressbookid' => 'foo',
                'carddata'      => 'card',
                'acl'           => [
                    [
                        'privilege' => '{DAV:}read',
                        'principal' => 'principals/user1',
                        'protected' => true,
                    ],
                ],
            ]
        );
        $this->assertEquals([
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/user1',
                'protected' => true,
            ],
        ], $card->getACL());

    }

    /**
     * @expectedException Sabre\DAV\Exception\Forbidden
     */
    function testSetACL() {

       $this->card->setACL([]);

    }

    function testGetSupportedPrivilegeSet() {

        $this->assertNull(
            $this->card->getSupportedPrivilegeSet()
        );

    }

}
