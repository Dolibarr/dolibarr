<?php
/*
* File: UndefinedCharsetHeaderTest.php
* Category: -
* Author: M.Goldenbaum
* Created: 09.03.23 02:24
* Updated: -
*
* Description:
*  -
*/

namespace Tests\fixtures;

use Webklex\PHPIMAP\Address;

/**
 * Class UndefinedCharsetHeaderTest
 *
 * @package Tests\fixtures
 */
class UndefinedCharsetHeaderTest extends FixtureTestCase {

    /**
     * Test the fixture undefined_charset_header.eml
     *
     * @return void
     */
    public function testFixture(): void {
        $message = $this->getFixture("undefined_charset_header.eml");

        self::assertEquals("<monitor@bla.bla>", $message->get("x-real-to"));
        self::assertEquals("1.0", $message->get("mime-version"));
        self::assertEquals("Mon, 27 Feb 2017 13:21:44 +0930", $message->get("Resent-Date"));
        self::assertEquals("<postmaster@bla.bla>", $message->get("Resent-From"));
        self::assertEquals("BlaBla", $message->get("X-Stored-In"));
        self::assertSame([
                             'personal' => '',
                             'mailbox'  => 'info',
                             'host'     => 'bla.bla',
                             'mail'     => 'info@bla.bla',
                             'full'     => 'info@bla.bla',
                         ], $message->get("Return-Path")->first()->toArray());
        self::assertEquals([
                               'from <postmaster@bla.bla>  by bla.bla (CommuniGate Pro RULE 6.1.13)  with RULE id 14057804; Mon, 27 Feb 2017 13:21:44 +0930',
                           ], $message->get("Received")->all());
        self::assertEquals(")", $message->getHTMLBody());
        self::assertFalse($message->hasTextBody());
        self::assertEquals("2017-02-27 03:51:29", $message->date->first()->setTimezone('UTC')->format("Y-m-d H:i:s"));

        $from = $message->from->first();
        self::assertInstanceOf(Address::class, $from);

        self::assertEquals("myGov", $from->personal);
        self::assertEquals("info", $from->mailbox);
        self::assertEquals("bla.bla", $from->host);
        self::assertEquals("info@bla.bla", $from->mail);
        self::assertEquals("myGov <info@bla.bla>", $from->full);

        self::assertEquals("sales@bla.bla", $message->to->first()->mail);
        self::assertEquals("Submit your tax refund | Australian Taxation Office.", $message->subject);
        self::assertEquals("201702270351.BGF77614@bla.bla", $message->message_id);
    }
}