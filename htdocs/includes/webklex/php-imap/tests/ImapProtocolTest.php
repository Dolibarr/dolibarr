<?php
/*
* File: ImapProtocolTest.php
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
use Webklex\PHPIMAP\Config;
use Webklex\PHPIMAP\Connection\Protocols\ImapProtocol;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;

class ImapProtocolTest extends TestCase {

    /** @var Config $config */
    protected Config $config;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp(): void {
        $this->config = Config::make();
    }


    /**
     * ImapProtocol test
     *
     * @return void
     */
    public function testImapProtocol(): void {

        $protocol = new ImapProtocol($this->config, false);
        self::assertSame(false, $protocol->getCertValidation());
        self::assertSame("", $protocol->getEncryption());

        $protocol->setCertValidation(true);
        $protocol->setEncryption("ssl");

        self::assertSame(true, $protocol->getCertValidation());
        self::assertSame("ssl", $protocol->getEncryption());

        $protocol->setSslOptions([
            'verify_peer' => true,
            'cafile' => '/dummy/path/for/testing',
            'peer_fingerprint' => ['md5' => 40],
        ]);

        self::assertSame([
            'verify_peer' => true,
            'cafile' => '/dummy/path/for/testing',
            'peer_fingerprint' => ['md5' => 40],
        ], $protocol->getSslOptions());
    }
}