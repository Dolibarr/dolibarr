<?php
/*
* File: ClientTest.php
* Category: -
* Author: M.Goldenbaum
* Created: 28.12.22 18:11
* Updated: -
*
* Description:
*  -
*/

namespace Tests;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Config;
use Webklex\PHPIMAP\Connection\Protocols\ImapProtocol;
use Webklex\PHPIMAP\Connection\Protocols\Response;
use Webklex\PHPIMAP\Exceptions\AuthFailedException;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Exceptions\ImapBadRequestException;
use Webklex\PHPIMAP\Exceptions\ImapServerErrorException;
use Webklex\PHPIMAP\Exceptions\MaskNotFoundException;
use Webklex\PHPIMAP\Exceptions\RuntimeException;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Support\Masks\AttachmentMask;
use Webklex\PHPIMAP\Support\Masks\MessageMask;

class ClientTest extends TestCase {

    /** @var Client $client */
    protected Client $client;

    /** @var MockObject ImapProtocol mockup */
    protected MockObject $protocol;

    /**
     * Setup the test environment.
     *
     * @return void
     * @throws MaskNotFoundException
     */
    public function setUp(): void {
        $config = Config::make([
            "accounts" => [
                "default" => [
                               'protocol'   => 'imap',
                               'encryption' => 'ssl',
                               'username'   => 'foo@domain.tld',
                               'password'   => 'bar',
                               'proxy'      => [
                                   'socket'          => null,
                                   'request_fulluri' => false,
                                   'username'        => null,
                                   'password'        => null,
                               ],
                           ]]
                             ]);
        $this->client = new Client($config);
    }

    /**
     * Client test
     *
     * @return void
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws RuntimeException
     */
    public function testClient(): void {
        $this->createNewProtocolMockup();

        self::assertInstanceOf(ImapProtocol::class, $this->client->getConnection());
        self::assertSame(true, $this->client->isConnected());
        self::assertSame(false, $this->client->checkConnection());
        self::assertSame(30, $this->client->getTimeout());
        self::assertSame(MessageMask::class, $this->client->getDefaultMessageMask());
        self::assertSame(AttachmentMask::class, $this->client->getDefaultAttachmentMask());
        self::assertArrayHasKey("new", $this->client->getDefaultEvents("message"));
    }

    /**
     * @throws MaskNotFoundException
     */
    public function testClientClone(): void {
        $config = Config::make([
                                   "accounts" => [
                                       "default" => [
                                           'host'  => 'example.com',
                                           'port'  => 993,
                                           'protocol'  => 'imap', //might also use imap, [pop3 or nntp (untested)]
                                           'encryption'    => 'ssl', // Supported: false, 'ssl', 'tls'
                                           'validate_cert' => true,
                                           'username' => 'root@example.com',
                                           'password' => 'foo',
                                           'authentication' => null,
                                           'rfc' => 'RFC822', // If you are using iCloud, you might want to set this to 'BODY'
                                           'proxy' => [
                                               'socket' => null,
                                               'request_fulluri' => false,
                                               'username' => null,
                                               'password' => null,
                                           ],
                                           "timeout" => 30,
                                           "extensions" => []
                                       ]]
                               ]);
        $client = new Client($config);
        $clone = $client->clone();
        self::assertInstanceOf(Client::class, $clone);
        self::assertSame($client->getConfig(), $clone->getConfig());
        self::assertSame($client->getAccountConfig(), $clone->getAccountConfig());
        self::assertSame($client->host, $clone->host);
    }

    public function testClientLogout(): void {
        $this->createNewProtocolMockup();

        $this->protocol->expects($this->any())->method('logout')->willReturn(Response::empty()->setResponse([
                                                                                                                0 => "BYE Logging out\r\n",
                                                                                                                1 => "OK Logout completed (0.001 + 0.000 secs).\r\n",
                                                                                                            ]));
        self::assertInstanceOf(Client::class, $this->client->disconnect());

    }

    public function testClientExpunge(): void {
        $this->createNewProtocolMockup();
        $this->protocol->expects($this->any())->method('expunge')->willReturn(Response::empty()->setResponse([
                                                                                  0 => "OK",
                                                                                  1 => "Expunge",
                                                                                  2 => "completed",
                                                                                  3 => [
                                                                                      0 => "0.001",
                                                                                      1 => "+",
                                                                                      2 => "0.000",
                                                                                      3 => "secs).",
                                                                                  ],
                                                                              ]));
        self::assertNotEmpty($this->client->expunge());

    }

    public function testClientFolders(): void {
        $this->createNewProtocolMockup();
        $this->protocol->expects($this->any())->method('expunge')->willReturn(Response::empty()->setResponse([
                                                                                                                 0 => "OK",
                                                                                                                 1 => "Expunge",
                                                                                                                 2 => "completed",
                                                                                                                 3 => [
                                                                                                                     0 => "0.001",
                                                                                                                     1 => "+",
                                                                                                                     2 => "0.000",
                                                                                                                     3 => "secs).",
                                                                                                                 ],
                                                                                                             ]));

        $this->protocol->expects($this->any())->method('selectFolder')->willReturn(Response::empty()->setResponse([
                                                                                       "flags"       => [
                                                                                           0 => [
                                                                                               0 => "\Answered",
                                                                                               1 => "\Flagged",
                                                                                               2 => "\Deleted",
                                                                                               3 => "\Seen",
                                                                                               4 => "\Draft",
                                                                                               5 => "NonJunk",
                                                                                               6 => "unknown-1",
                                                                                           ],
                                                                                       ],
                                                                                       "exists"      => 139,
                                                                                       "recent"      => 0,
                                                                                       "unseen"      => 94,
                                                                                       "uidvalidity" => 1488899637,
                                                                                       "uidnext"     => 278,
                                                                                   ]));
        self::assertNotEmpty($this->client->openFolder("INBOX"));
        self::assertSame("INBOX", $this->client->getFolderPath());

        $this->protocol->expects($this->any())->method('examineFolder')->willReturn(Response::empty()->setResponse([
                                                                                        "flags"       => [
                                                                                            0 => [
                                                                                                0 => "\Answered",
                                                                                                1 => "\Flagged",
                                                                                                2 => "\Deleted",
                                                                                                3 => "\Seen",
                                                                                                4 => "\Draft",
                                                                                                5 => "NonJunk",
                                                                                                6 => "unknown-1",
                                                                                            ],
                                                                                        ],
                                                                                        "exists"      => 139,
                                                                                        "recent"      => 0,
                                                                                        "unseen"      => 94,
                                                                                        "uidvalidity" => 1488899637,
                                                                                        "uidnext"     => 278,
                                                                                    ]));
        self::assertNotEmpty($this->client->checkFolder("INBOX"));

        $this->protocol->expects($this->any())->method('folders')->with($this->identicalTo(""), $this->identicalTo("*"))->willReturn(Response::empty()->setResponse([
                                                                                                                   "INBOX"                  => [
                                                                                                                       "delimiter" => ".",
                                                                                                                       "flags"     => [
                                                                                                                           0 => "\HasChildren",
                                                                                                                       ],
                                                                                                                   ],
                                                                                                                   "INBOX.new"              => [
                                                                                                                       "delimiter" => ".",
                                                                                                                       "flags"     => [
                                                                                                                           0 => "\HasNoChildren",
                                                                                                                       ],
                                                                                                                   ],
                                                                                                                   "INBOX.9AL56dEMTTgUKOAz" => [
                                                                                                                       "delimiter" => ".",
                                                                                                                       "flags"     => [
                                                                                                                           0 => "\HasNoChildren",
                                                                                                                       ],
                                                                                                                   ],
                                                                                                                   "INBOX.U9PsHCvXxAffYvie" => [
                                                                                                                       "delimiter" => ".",
                                                                                                                       "flags"     => [
                                                                                                                           0 => "\HasNoChildren",
                                                                                                                       ],
                                                                                                                   ],
                                                                                                                   "INBOX.Trash"            => [
                                                                                                                       "delimiter" => ".",
                                                                                                                       "flags"     => [
                                                                                                                           0 => "\HasNoChildren",
                                                                                                                           1 => "\Trash",
                                                                                                                       ],
                                                                                                                   ],
                                                                                                                   "INBOX.processing"       => [
                                                                                                                       "delimiter" => ".",
                                                                                                                       "flags"     => [
                                                                                                                           0 => "\HasNoChildren",
                                                                                                                       ],
                                                                                                                   ],
                                                                                                                   "INBOX.Sent"             => [
                                                                                                                       "delimiter" => ".",
                                                                                                                       "flags"     => [
                                                                                                                           0 => "\HasNoChildren",
                                                                                                                           1 => "\Sent",
                                                                                                                       ],
                                                                                                                   ],
                                                                                                                   "INBOX.OzDWCXKV3t241koc" => [
                                                                                                                       "delimiter" => ".",
                                                                                                                       "flags"     => [
                                                                                                                           0 => "\HasNoChildren",
                                                                                                                       ],
                                                                                                                   ],
                                                                                                                   "INBOX.5F3bIVTtBcJEqIVe" => [
                                                                                                                       "delimiter" => ".",
                                                                                                                       "flags"     => [
                                                                                                                           0 => "\HasNoChildren",
                                                                                                                       ],
                                                                                                                   ],
                                                                                                                   "INBOX.8J3rll6eOBWnTxIU" => [
                                                                                                                       "delimiter" => ".",
                                                                                                                       "flags"     => [
                                                                                                                           0 => "\HasNoChildren",
                                                                                                                       ],
                                                                                                                   ],
                                                                                                                   "INBOX.Junk"             => [
                                                                                                                       "delimiter" => ".",
                                                                                                                       "flags"     => [
                                                                                                                           0 => "\HasNoChildren",
                                                                                                                           1 => "\Junk",
                                                                                                                       ],
                                                                                                                   ],
                                                                                                                   "INBOX.Drafts"           => [
                                                                                                                       "delimiter" => ".",
                                                                                                                       "flags"     => [
                                                                                                                           0 => "\HasNoChildren",
                                                                                                                           1 => "\Drafts",
                                                                                                                       ],
                                                                                                                   ],
                                                                                                                   "INBOX.test"             => [
                                                                                                                       "delimiter" => ".",
                                                                                                                       "flags"     => [
                                                                                                                           0 => "\HasNoChildren",
                                                                                                                       ],
                                                                                                                   ],
                                                                                                               ]));

        $this->protocol->expects($this->any())->method('createFolder')->willReturn(Response::empty()->setResponse([
                                                                                       0 => "OK Create completed (0.004 + 0.000 + 0.003 secs).\r\n",
                                                                                   ]));
        self::assertNotEmpty($this->client->createFolder("INBOX.new"));

        $this->protocol->expects($this->any())->method('deleteFolder')->willReturn(Response::empty()->setResponse([
                                                                                       0 => "OK Delete completed (0.007 + 0.000 + 0.006 secs).\r\n",
                                                                                   ]));
        self::assertNotEmpty($this->client->deleteFolder("INBOX.new"));

        self::assertInstanceOf(Folder::class, $this->client->getFolderByPath("INBOX.new"));
        self::assertInstanceOf(Folder::class, $this->client->getFolderByName("new"));
        self::assertInstanceOf(Folder::class, $this->client->getFolder("INBOX.new", "."));
        self::assertInstanceOf(Folder::class, $this->client->getFolder("new"));
    }

    public function testClientId(): void {
        $this->createNewProtocolMockup();
        $this->protocol->expects($this->any())->method('ID')->willReturn(Response::empty()->setResponse([
                                                                             0 => "ID (\"name\" \"Dovecot\")\r\n",
                                                                             1 => "OK ID completed (0.001 + 0.000 secs).\r\n"

                                                                         ]));
        self::assertSame("ID (\"name\" \"Dovecot\")\r\n", $this->client->Id()[0]);

    }

    public function testClientConfig(): void {
        $config = $this->client->getConfig()->get("accounts.".$this->client->getConfig()->getDefaultAccount());
        self::assertSame("foo@domain.tld", $config["username"]);
        self::assertSame("bar", $config["password"]);
        self::assertSame("localhost", $config["host"]);
        self::assertSame(true, $config["validate_cert"]);
        self::assertSame(993, $config["port"]);

        $this->client->getConfig()->set("accounts.".$this->client->getConfig()->getDefaultAccount(), [
            "host"     => "domain.tld",
            'password' => 'bar',
        ]);
        $config = $this->client->getConfig()->get("accounts.".$this->client->getConfig()->getDefaultAccount());

        self::assertSame("bar", $config["password"]);
        self::assertSame("domain.tld", $config["host"]);
        self::assertSame(true, $config["validate_cert"]);
    }

    protected function createNewProtocolMockup() {
        $this->protocol = $this->createMock(ImapProtocol::class);

        $this->protocol->expects($this->any())->method('connected')->willReturn(true);
        $this->protocol->expects($this->any())->method('getConnectionTimeout')->willReturn(30);

        $this->protocol
            ->expects($this->any())
            ->method('createStream')
            //->will($this->onConsecutiveCalls(true));
            ->willReturn(true);

        $this->client->connection = $this->protocol;
    }
}