<?php
/*
* File: ClientManagerTest.php
* Category: -
* Author: M.Goldenbaum
* Created: 28.12.22 18:11
* Updated: -
*
* Description:
*  -
*/

namespace Tests;

use PHPUnit\Framework\TestCase;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Config;
use Webklex\PHPIMAP\Exceptions\MaskNotFoundException;
use Webklex\PHPIMAP\IMAP;

class ClientManagerTest extends TestCase {

    /** @var ClientManager $cm */
    protected ClientManager $cm;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp(): void {
        $this->cm = new ClientManager();
    }

    /**
     * Test if the config can be accessed
     *
     * @return void
     */
    public function testConfigAccessorAccount(): void {
        $config = $this->cm->getConfig();
        self::assertInstanceOf(Config::class, $config);
        self::assertSame("default", $config->get("default"));
        self::assertSame("d-M-Y", $config->get("date_format"));
        self::assertSame(IMAP::FT_PEEK, $config->get("options.fetch"));
        self::assertSame([], $config->get("options.open"));
    }

    /**
     * Test creating a client instance
     *
     * @throws MaskNotFoundException
     */
    public function testMakeClient(): void {
        self::assertInstanceOf(Client::class, $this->cm->make([]));
    }

    /**
     * Test accessing accounts
     *
     * @throws MaskNotFoundException
     */
    public function testAccountAccessor(): void {
        self::assertSame("default", $this->cm->getConfig()->getDefaultAccount());
        self::assertNotEmpty($this->cm->account("default"));

        $this->cm->getConfig()->setDefaultAccount("foo");
        self::assertSame("foo", $this->cm->getConfig()->getDefaultAccount());
        $this->cm->getConfig()->setDefaultAccount("default");
    }

    /**
     * Test setting a config
     *
     * @throws MaskNotFoundException
     */
    public function testSetConfig(): void {
        $config = [
            "default" => "foo",
            "options" => [
                "fetch" => IMAP::ST_MSGN,
                "open"  => "foo"
            ]
        ];
        $cm = new ClientManager($config);

        self::assertSame("foo", $cm->getConfig()->getDefaultAccount());
        self::assertInstanceOf(Client::class, $cm->account("foo"));
        self::assertSame(IMAP::ST_MSGN, $cm->getConfig()->get("options.fetch"));
        self::assertSame(false, is_array($cm->getConfig()->get("options.open")));

    }
}