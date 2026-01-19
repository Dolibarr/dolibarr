<?php
/*
* File: PlainTextAttachmentTest.php
* Category: -
* Author: M.Goldenbaum
* Created: 09.03.23 02:24
* Updated: -
*
* Description:
*  -
*/

namespace Tests\fixtures;

use Webklex\PHPIMAP\Attachment;

/**
 * Class PlainTextAttachmentTest
 *
 * @package Tests\fixtures
 */
class PlainTextAttachmentTest extends FixtureTestCase {

    /**
     * Test the fixture plain_text_attachment.eml
     *
     * @return void
     */
    public function testFixture() : void {
        $message = $this->getFixture("plain_text_attachment.eml");

        self::assertEquals("Plain text attachment", $message->subject);
        self::assertEquals("Test", $message->getTextBody());
        self::assertFalse($message->hasHTMLBody());

        self::assertEquals("2018-08-21 07:05:14", $message->date->first()->setTimezone('UTC')->format("Y-m-d H:i:s"));
        self::assertEquals("from@there.com", $message->from->first()->mail);
        self::assertEquals("to@here.com", $message->to->first()->mail);

        self::assertCount(1, $message->attachments());

        $attachment = $message->attachments()->first();
        self::assertInstanceOf(Attachment::class, $attachment);
        self::assertEquals("a.txt", $attachment->name);
        self::assertEquals('txt', $attachment->getExtension());
        self::assertEquals('text', $attachment->type);
        self::assertNull($attachment->content_type);
        self::assertEquals("Hi!", $attachment->content);
        self::assertEquals(4, $attachment->size);
        self::assertEquals(2, $attachment->part_number);
        self::assertEquals("attachment", $attachment->disposition);
        self::assertNotEmpty($attachment->id);
    }
}