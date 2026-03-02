<?php
/*
* File: BccTest.php
* Category: -
* Author: M.Goldenbaum
* Created: 09.03.23 02:24
* Updated: -
*
* Description:
*  -
*/

namespace Tests\fixtures;

/**
 * Class BccTest
 *
 * @package Tests\fixtures
 */
class BccTest extends FixtureTestCase {

    /**
     * Test the fixture bcc.eml
     *
     * @return void
     */
    public function testFixture(): void {
        $message = $this->getFixture("bcc.eml");

        self::assertEquals("test", $message->subject);
        self::assertSame([
                             'personal' => '',
                             'mailbox'  => 'return-path',
                             'host'     => 'here.com',
                             'mail'     => 'return-path@here.com',
                             'full'     => 'return-path@here.com',
                         ], $message->return_path->first()->toArray());
        self::assertEquals("1.0", $message->mime_version);
        self::assertEquals("text/plain", $message->content_type);
        self::assertEquals("Hi!", $message->getTextBody());
        self::assertFalse($message->hasHTMLBody());
        self::assertEquals("2017-09-27 10:48:51", $message->date->first()->setTimezone('UTC')->format("Y-m-d H:i:s"));
        self::assertEquals("from@there.com", $message->from);
        self::assertEquals("to@here.com", $message->to);
        self::assertEquals("A_€@{è_Z <bcc@here.com>", $message->bcc);
        self::assertEquals("sender@here.com", $message->sender);
        self::assertEquals("reply-to@here.com", $message->reply_to);
    }
}