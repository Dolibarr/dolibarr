<?php
/*
* File: EmailAddressTest.php
* Category: -
* Author: M.Goldenbaum
* Created: 09.03.23 02:24
* Updated: -
*
* Description:
*  -
*/

namespace Tests\fixtures;

use Webklex\PHPIMAP\Exceptions\AuthFailedException;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Exceptions\ImapBadRequestException;
use Webklex\PHPIMAP\Exceptions\ImapServerErrorException;
use Webklex\PHPIMAP\Exceptions\InvalidMessageDateException;
use Webklex\PHPIMAP\Exceptions\MaskNotFoundException;
use Webklex\PHPIMAP\Exceptions\MessageContentFetchingException;
use Webklex\PHPIMAP\Exceptions\ResponseException;
use Webklex\PHPIMAP\Exceptions\RuntimeException;

/**
 * Class EmailAddressTest
 *
 * @package Tests\fixtures
 */
class EmailAddressTest extends FixtureTestCase {

    /**
     * Test the fixture email_address.eml
     *
     * @return void
     * @throws \ReflectionException
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws InvalidMessageDateException
     * @throws MaskNotFoundException
     * @throws MessageContentFetchingException
     * @throws ResponseException
     * @throws RuntimeException
     */
    public function testFixture() : void {
        $message = $this->getFixture("email_address.eml");

        self::assertEquals("", $message->subject);
        self::assertEquals("123@example.com", $message->message_id);
        self::assertEquals("Hi\r\nHow are you?", $message->getTextBody());
        self::assertFalse($message->hasHTMLBody());
        self::assertFalse($message->date->first());
        self::assertEquals("no_host", (string)$message->from);
        self::assertEquals("", $message->to);
        self::assertEquals("This one: is \"right\" <ding@dong.com>, No-address", (string)$message->cc);
    }
}