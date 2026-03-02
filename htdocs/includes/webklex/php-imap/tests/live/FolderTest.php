<?php
/*
* File: FolderTest.php
* Category: -
* Author: M.Goldenbaum
* Created: 04.03.23 03:52
* Updated: -
*
* Description:
*  -
*/

namespace Tests\live;

use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Exceptions\AuthFailedException;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Exceptions\EventNotFoundException;
use Webklex\PHPIMAP\Exceptions\FolderFetchingException;
use Webklex\PHPIMAP\Exceptions\ImapBadRequestException;
use Webklex\PHPIMAP\Exceptions\ImapServerErrorException;
use Webklex\PHPIMAP\Exceptions\InvalidMessageDateException;
use Webklex\PHPIMAP\Exceptions\MaskNotFoundException;
use Webklex\PHPIMAP\Exceptions\MessageContentFetchingException;
use Webklex\PHPIMAP\Exceptions\MessageFlagException;
use Webklex\PHPIMAP\Exceptions\MessageHeaderFetchingException;
use Webklex\PHPIMAP\Exceptions\MessageNotFoundException;
use Webklex\PHPIMAP\Exceptions\ResponseException;
use Webklex\PHPIMAP\Exceptions\RuntimeException;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Message;
use Webklex\PHPIMAP\Query\WhereQuery;
use Webklex\PHPIMAP\Support\FolderCollection;

/**
 * Class FolderTest
 *
 * @package Tests
 */
class FolderTest extends LiveMailboxTestCase {

    /**
     * Try to create a new query instance
     *
     * @return void
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws FolderFetchingException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MaskNotFoundException
     * @throws ResponseException
     * @throws RuntimeException
     */
    public function testQuery(): void {
        $folder = $this->getFolder('INBOX');
        self::assertInstanceOf(Folder::class, $folder);

        self::assertInstanceOf(WhereQuery::class, $folder->query());
        self::assertInstanceOf(WhereQuery::class, $folder->search());
        self::assertInstanceOf(WhereQuery::class, $folder->messages());
    }

    /**
     * Test Folder::hasChildren()
     *
     * @return void
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws FolderFetchingException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MaskNotFoundException
     * @throws ResponseException
     * @throws RuntimeException
     * @throws EventNotFoundException
     */
    public function testHasChildren(): void {
        $folder = $this->getFolder('INBOX');
        self::assertInstanceOf(Folder::class, $folder);

        $delimiter = $this->getManager()->getConfig()->get("options.delimiter");
        $child_path = implode($delimiter, ['INBOX', 'test']);
        if ($folder->getClient()->getFolder($child_path) === null) {
            $folder->getClient()->createFolder($child_path, false);
            $folder = $this->getFolder('INBOX');
        }

        self::assertTrue($folder->hasChildren());
    }

    /**
     * Test Folder::setChildren()
     *
     * @return void
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws EventNotFoundException
     * @throws FolderFetchingException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MaskNotFoundException
     * @throws ResponseException
     * @throws RuntimeException
     */
    public function testSetChildren(): void {
        $folder = $this->getFolder('INBOX');
        self::assertInstanceOf(Folder::class, $folder);

        $delimiter = $this->getManager()->getConfig()->get("options.delimiter");
        $child_path = implode($delimiter, ['INBOX', 'test']);
        if ($folder->getClient()->getFolder($child_path) === null) {
            $folder->getClient()->createFolder($child_path, false);
            $folder = $this->getFolder('INBOX');
        }
        self::assertTrue($folder->hasChildren());

        $folder->setChildren(new FolderCollection());
        self::assertTrue($folder->getChildren()->isEmpty());
    }

    /**
     * Test Folder::getChildren()
     *
     * @return void
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws EventNotFoundException
     * @throws FolderFetchingException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MaskNotFoundException
     * @throws ResponseException
     * @throws RuntimeException
     */
    public function testGetChildren(): void {
        $folder = $this->getFolder('INBOX');
        self::assertInstanceOf(Folder::class, $folder);

        $delimiter = $this->getManager()->getConfig()->get("options.delimiter");
        $child_path = implode($delimiter, ['INBOX', 'test']);
        if ($folder->getClient()->getFolder($child_path) === null) {
            $folder->getClient()->createFolder($child_path, false);
        }

        $folder = $folder->getClient()->getFolders()->where('name', 'INBOX')->first();
        self::assertInstanceOf(Folder::class, $folder);

        self::assertTrue($folder->hasChildren());
        self::assertFalse($folder->getChildren()->isEmpty());
    }

    /**
     * Test Folder::move()
     *
     * @return void
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws EventNotFoundException
     * @throws FolderFetchingException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MaskNotFoundException
     * @throws ResponseException
     * @throws RuntimeException
     */
    public function testMove(): void {
        $client = $this->getClient();

        $delimiter = $this->getManager()->getConfig()->get("options.delimiter");
        $folder_path = implode($delimiter, ['INBOX', 'test']);

        $folder = $client->getFolder($folder_path);
        if ($folder === null) {
            $folder = $client->createFolder($folder_path, false);
        }
        $new_folder_path = implode($delimiter, ['INBOX', 'other']);
        $new_folder = $client->getFolder($new_folder_path);
        $new_folder?->delete(false);

        $status = $folder->move($new_folder_path, false);
        self::assertIsArray($status);
        self::assertTrue(str_starts_with($status[0], 'OK'));

        $new_folder = $client->getFolder($new_folder_path);
        self::assertEquals($new_folder_path, $new_folder->path);
        self::assertEquals('other', $new_folder->name);

        if ($this->deleteFolder($new_folder) === false) {
            $this->fail("Could not delete folder: " . $new_folder->path);
        }
    }

    /**
     * Test Folder::delete()
     *
     * @return void
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws EventNotFoundException
     * @throws FolderFetchingException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MaskNotFoundException
     * @throws ResponseException
     * @throws RuntimeException
     */
    public function testDelete(): void {
        $client = $this->getClient();

        $delimiter = $this->getManager()->getConfig()->get("options.delimiter");
        $folder_path = implode($delimiter, ['INBOX', 'test']);

        $folder = $client->getFolder($folder_path);
        if ($folder === null) {
            $folder = $client->createFolder($folder_path, false);
        }
        self::assertInstanceOf(Folder::class, $folder);

        if ($this->deleteFolder($folder) === false) {
            $this->fail("Could not delete folder: " . $folder->path);
        }
    }

    /**
     * Test Folder::overview()
     *
     * @return void
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws EventNotFoundException
     * @throws FolderFetchingException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MaskNotFoundException
     * @throws ResponseException
     * @throws RuntimeException
     * @throws InvalidMessageDateException
     * @throws MessageContentFetchingException
     * @throws MessageFlagException
     * @throws MessageHeaderFetchingException
     * @throws MessageNotFoundException
     */
    public function testOverview(): void {
        $folder = $this->getFolder('INBOX');
        self::assertInstanceOf(Folder::class, $folder);

        $folder->select();

        // Test empty overview
        $overview = $folder->overview();
        self::assertIsArray($overview);
        self::assertCount(0, $overview);

        $message = $this->appendMessageTemplate($folder, "plain.eml");

        $overview = $folder->overview();

        self::assertIsArray($overview);
        self::assertCount(1, $overview);

        self::assertEquals($message->from->first()->full, end($overview)["from"]->toString());

        self::assertTrue($message->delete());
    }

    /**
     * Test Folder::appendMessage()
     *
     * @return void
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws EventNotFoundException
     * @throws FolderFetchingException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws InvalidMessageDateException
     * @throws MaskNotFoundException
     * @throws MessageContentFetchingException
     * @throws MessageFlagException
     * @throws MessageHeaderFetchingException
     * @throws MessageNotFoundException
     * @throws ResponseException
     * @throws RuntimeException
     */
    public function testAppendMessage(): void {
        $folder = $this->getFolder('INBOX');
        self::assertInstanceOf(Folder::class, $folder);

        $message = $this->appendMessageTemplate($folder, "plain.eml");
        self::assertInstanceOf(Message::class, $message);

        self::assertEquals("Example", $message->subject);
        self::assertEquals("to@someone-else.com", $message->to);
        self::assertEquals("from@someone.com", $message->from);

        // Clean up
        $this->assertTrue($message->delete(true));
    }

    /**
     * Test Folder::subscribe()
     *
     * @return void
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws FolderFetchingException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MaskNotFoundException
     * @throws ResponseException
     * @throws RuntimeException
     */
    public function testSubscribe(): void {
        $folder = $this->getFolder('INBOX');
        self::assertInstanceOf(Folder::class, $folder);

        $status = $folder->subscribe();
        self::assertIsArray($status);
        self::assertTrue(str_starts_with($status[0], 'OK'));

        // Clean up
        $folder->unsubscribe();
    }

    /**
     * Test Folder::unsubscribe()
     *
     * @return void
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws FolderFetchingException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MaskNotFoundException
     * @throws ResponseException
     * @throws RuntimeException
     */
    public function testUnsubscribe(): void {
        $folder = $this->getFolder('INBOX');
        self::assertInstanceOf(Folder::class, $folder);

        $folder->subscribe();

        $status = $folder->subscribe();
        self::assertIsArray($status);
        self::assertTrue(str_starts_with($status[0], 'OK'));
    }

    /**
     * Test Folder::status()
     *
     * @return void
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws FolderFetchingException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MaskNotFoundException
     * @throws ResponseException
     * @throws RuntimeException
     */
    public function testStatus(): void {
        $folder = $this->getFolder('INBOX');
        self::assertInstanceOf(Folder::class, $folder);

        $status = $folder->status();
        self::assertEquals(0, $status['messages']);
        self::assertEquals(0, $status['recent']);
        self::assertEquals(0, $status['unseen']);
        self::assertGreaterThan(0, $status['uidnext']);
        self::assertGreaterThan(0, $status['uidvalidity']);
    }

    /**
     * Test Folder::examine()
     *
     * @return void
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws FolderFetchingException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MaskNotFoundException
     * @throws ResponseException
     * @throws RuntimeException
     */
    public function testExamine(): void {
        $folder = $this->getFolder('INBOX');
        self::assertInstanceOf(Folder::class, $folder);

        $status = $folder->examine();
        self::assertTrue(isset($status["flags"]) && count($status["flags"]) > 0);
        self::assertTrue(($status["uidnext"] ?? 0) > 0);
        self::assertTrue(($status["uidvalidity"] ?? 0) > 0);
        self::assertTrue(($status["recent"] ?? -1) >= 0);
        self::assertTrue(($status["exists"] ?? -1) >= 0);
    }

    /**
     * Test Folder::getClient()
     *
     * @return void
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws FolderFetchingException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MaskNotFoundException
     * @throws ResponseException
     * @throws RuntimeException
     */
    public function testGetClient(): void {
        $folder = $this->getFolder('INBOX');
        self::assertInstanceOf(Folder::class, $folder);
        self::assertInstanceOf(Client::class, $folder->getClient());
    }

    /**
     * Test Folder::setDelimiter()
     *
     * @return void
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws FolderFetchingException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MaskNotFoundException
     * @throws ResponseException
     * @throws RuntimeException
     */
    public function testSetDelimiter(): void {
        $folder = $this->getFolder('INBOX');
        self::assertInstanceOf(Folder::class, $folder);

        $folder->setDelimiter("/");
        self::assertEquals("/", $folder->delimiter);

        $folder->setDelimiter(".");
        self::assertEquals(".", $folder->delimiter);

        $default_delimiter = $this->getManager()->getConfig()->get("options.delimiter", "/");
        $folder->setDelimiter(null);
        self::assertEquals($default_delimiter, $folder->delimiter);
    }

}