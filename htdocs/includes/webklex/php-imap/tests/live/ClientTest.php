<?php
/*
* File: ClientTest.php
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
use Webklex\PHPIMAP\Connection\Protocols\ProtocolInterface;
use Webklex\PHPIMAP\EncodingAliases;
use Webklex\PHPIMAP\Exceptions\AuthFailedException;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Exceptions\FolderFetchingException;
use Webklex\PHPIMAP\Exceptions\ImapBadRequestException;
use Webklex\PHPIMAP\Exceptions\ImapServerErrorException;
use Webklex\PHPIMAP\Exceptions\MaskNotFoundException;
use Webklex\PHPIMAP\Exceptions\ResponseException;
use Webklex\PHPIMAP\Exceptions\RuntimeException;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Support\Masks\AttachmentMask;
use Webklex\PHPIMAP\Support\Masks\MessageMask;

/**
 * Class ClientTest
 *
 * @package Tests
 */
class ClientTest extends LiveMailboxTestCase {

    /**
     * Test if the connection is working
     *
     * @return void
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws ResponseException
     * @throws RuntimeException
     * @throws MaskNotFoundException
     */
    public function testConnect(): void {
        self::assertNotNull($this->getClient()->connect());
    }

    /**
     * Test if the connection is working
     *
     * @return void
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MaskNotFoundException
     * @throws ResponseException
     * @throws RuntimeException
     */
    public function testIsConnected(): void {
        $client = $this->getClient()->connect();

        self::assertTrue($client->isConnected());
    }

    /**
     * Test if the connection state can be determined
     *
     * @return void
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MaskNotFoundException
     * @throws ResponseException
     * @throws RuntimeException
     */
    public function testDisconnect(): void {
        $client = $this->getClient()->connect();

        self::assertFalse($client->disconnect()->isConnected());
    }

    /**
     * Test to get the default inbox folder
     *
     * @return void
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MaskNotFoundException
     * @throws ResponseException
     * @throws RuntimeException
     * @throws FolderFetchingException
     */
    public function testGetFolder(): void {
        $client = $this->getClient()->connect();

        $folder = $client->getFolder('INBOX');
        self::assertInstanceOf(Folder::class, $folder);
    }

    /**
     * Test to get the default inbox folder by name
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
    public function testGetFolderByName(): void {
        $client = $this->getClient()->connect();

        $folder = $client->getFolderByName('INBOX');
        self::assertInstanceOf(Folder::class, $folder);
    }

    /**
     * Test to get the default inbox folder by path
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
    public function testGetFolderByPath(): void {
        $client = $this->getClient()->connect();

        $folder = $client->getFolderByPath('INBOX');
        self::assertInstanceOf(Folder::class, $folder);
    }

    /**
     * Test to get all folders
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
    public function testGetFolders(): void {
        $client = $this->getClient()->connect();

        $folders = $client->getFolders(false);
        self::assertTrue($folders->count() > 0);
    }

    public function testGetFoldersWithStatus(): void {
        $client = $this->getClient()->connect();

        $folders = $client->getFoldersWithStatus(false);
        self::assertTrue($folders->count() > 0);
    }

    public function testOpenFolder(): void {
        $client = $this->getClient()->connect();

        $status = $client->openFolder("INBOX");
        self::assertTrue(isset($status["flags"]) && count($status["flags"]) > 0);
        self::assertTrue(($status["uidnext"] ?? 0) > 0);
        self::assertTrue(($status["uidvalidity"] ?? 0) > 0);
        self::assertTrue(($status["recent"] ?? -1) >= 0);
        self::assertTrue(($status["exists"] ?? -1) >= 0);
    }

    public function testCreateFolder(): void {
        $client = $this->getClient()->connect();

        $delimiter = $this->getManager()->getConfig()->get("options.delimiter");
        $folder_path = implode($delimiter, ['INBOX', $this->getSpecialChars()]);

        $folder = $client->getFolder($folder_path);

        $this->deleteFolder($folder);

        $folder = $client->createFolder($folder_path, false);
        self::assertInstanceOf(Folder::class, $folder);

        $folder = $this->getFolder($folder_path);
        self::assertInstanceOf(Folder::class, $folder);

        $this->assertEquals($this->getSpecialChars(), $folder->name);
        $this->assertEquals($folder_path, $folder->full_name);

        $folder_path = implode($delimiter, ['INBOX', EncodingAliases::convert($this->getSpecialChars(), "utf-8", "utf7-imap")]);
        $this->assertEquals($folder_path, $folder->path);

        // Clean up
        if ($this->deleteFolder($folder) === false) {
            $this->fail("Could not delete folder: " . $folder->path);
        }
    }

    public function testCheckFolder(): void {
        $client = $this->getClient()->connect();

        $status = $client->checkFolder("INBOX");
        self::assertTrue(isset($status["flags"]) && count($status["flags"]) > 0);
        self::assertTrue(($status["uidnext"] ?? 0) > 0);
        self::assertTrue(($status["uidvalidity"] ?? 0) > 0);
        self::assertTrue(($status["recent"] ?? -1) >= 0);
        self::assertTrue(($status["exists"] ?? -1) >= 0);
    }

    public function testGetFolderPath(): void {
        $client = $this->getClient()->connect();

        self::assertIsArray($client->openFolder("INBOX"));
        self::assertEquals("INBOX", $client->getFolderPath());
    }

    public function testId(): void {
        $client = $this->getClient()->connect();

        $info = $client->Id();
        self::assertIsArray($info);
        $valid = false;
        foreach ($info as $value) {
            if (str_starts_with($value, "OK")) {
                $valid = true;
                break;
            }
        }
        self::assertTrue($valid);
    }

    public function testGetQuotaRoot(): void {
        if (!getenv("LIVE_MAILBOX_QUOTA_SUPPORT")) {
            $this->markTestSkipped("Quota support is not enabled");
        }

        $client = $this->getClient()->connect();

        $quota = $client->getQuotaRoot("INBOX");
        self::assertIsArray($quota);
        self::assertTrue(count($quota) > 1);
        self::assertIsArray($quota[0]);
        self::assertEquals("INBOX", $quota[0][1]);
        self::assertIsArray($quota[1]);
        self::assertIsArray($quota[1][2]);
        self::assertTrue($quota[1][2][2] > 0);
    }

    public function testSetTimeout(): void {
        $client = $this->getClient()->connect();

        self::assertInstanceOf(ProtocolInterface::class, $client->setTimeout(57));
        self::assertEquals(57, $client->getTimeout());
    }

    public function testExpunge(): void {
        $client = $this->getClient()->connect();

        $client->openFolder("INBOX");
        $status = $client->expunge();

        self::assertIsArray($status);
        self::assertIsArray($status[0]);
        self::assertEquals("OK", $status[0][0]);
    }

    public function testGetDefaultMessageMask(): void {
        $client = $this->getClient();

        self::assertEquals(MessageMask::class, $client->getDefaultMessageMask());
    }

    public function testGetDefaultEvents(): void {
        $client = $this->getClient();

        self::assertIsArray($client->getDefaultEvents("message"));
    }

    public function testSetDefaultMessageMask(): void {
        $client = $this->getClient();

        self::assertInstanceOf(Client::class, $client->setDefaultMessageMask(AttachmentMask::class));
        self::assertEquals(AttachmentMask::class, $client->getDefaultMessageMask());

        $client->setDefaultMessageMask(MessageMask::class);
    }

    public function testGetDefaultAttachmentMask(): void {
        $client = $this->getClient();

        self::assertEquals(AttachmentMask::class, $client->getDefaultAttachmentMask());
    }

    public function testSetDefaultAttachmentMask(): void {
        $client = $this->getClient();

        self::assertInstanceOf(Client::class, $client->setDefaultAttachmentMask(MessageMask::class));
        self::assertEquals(MessageMask::class, $client->getDefaultAttachmentMask());

        $client->setDefaultAttachmentMask(AttachmentMask::class);
    }
}