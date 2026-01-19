<?php
/*
* File: AddressTest.php
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
use Webklex\PHPIMAP\Address;

class AddressTest extends TestCase {

    /**
     * Test data
     *
     * @var array|string[] $data
     */
    protected array $data = [
        "personal" => "Username",
        "mailbox" => "info",
        "host" => "domain.tld",
        "mail" => "info@domain.tld",
        "full" => "Username <info@domain.tld>",
    ];

    /**
     * Address test
     *
     * @return void
     */
    public function testAddress(): void {
        $address = new Address((object)$this->data);

        self::assertSame("Username", $address->personal);
        self::assertSame("info", $address->mailbox);
        self::assertSame("domain.tld", $address->host);
        self::assertSame("info@domain.tld", $address->mail);
        self::assertSame("Username <info@domain.tld>", $address->full);
    }

    /**
     * Test Address to string conversion
     *
     * @return void
     */
    public function testAddressToStringConversion(): void {
        $address = new Address((object)$this->data);

        self::assertSame("Username <info@domain.tld>", (string)$address);
    }

    /**
     * Test Address serialization
     *
     * @return void
     */
    public function testAddressSerialization(): void {
        $address = new Address((object)$this->data);

        foreach($address as $key => $value) {
            self::assertSame($this->data[$key], $value);
        }

    }
}