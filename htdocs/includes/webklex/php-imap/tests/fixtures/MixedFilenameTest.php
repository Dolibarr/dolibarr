<?php
/*
* File: MixedFilenameTest.php
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
 * Class MixedFilenameTest
 *
 * @package Tests\fixtures
 */
class MixedFilenameTest extends FixtureTestCase {

    /**
     * Test the fixture mixed_filename.eml
     *
     * @return void
     */
    public function testFixture() : void {
        $message = $this->getFixture("mixed_filename.eml");

        self::assertEquals("Свежий прайс-лист", $message->subject);
        self::assertFalse($message->hasTextBody());
        self::assertFalse($message->hasHTMLBody());

        self::assertEquals("2018-02-02 19:23:06", $message->date->first()->setTimezone('UTC')->format("Y-m-d H:i:s"));

        $from = $message->from->first();
        self::assertEquals("Прайсы || ПартКом", $from->personal);
        self::assertEquals("support", $from->mailbox);
        self::assertEquals("part-kom.ru", $from->host);
        self::assertEquals("support@part-kom.ru", $from->mail);
        self::assertEquals("Прайсы || ПартКом <support@part-kom.ru>", $from->full);

        self::assertEquals("foo@bar.com", $message->to->first());

        self::assertCount(1, $message->attachments());

        $attachment = $message->attachments()->first();
        self::assertInstanceOf(Attachment::class, $attachment);
        self::assertEquals("Price4VladDaKar.xlsx", $attachment->name);
        self::assertEquals('xlsx', $attachment->getExtension());
        self::assertEquals('text', $attachment->type);
        self::assertEquals("application/octet-stream", $attachment->content_type);
        self::assertEquals("b832983842b0ad65db69e4c7096444c540a2393e2d43f70c2c9b8b9fceeedbb1", hash('sha256', $attachment->content));
        self::assertEquals(94, $attachment->size);
        self::assertEquals(2, $attachment->part_number);
        self::assertEquals("attachment", $attachment->disposition);
        self::assertNotEmpty($attachment->id);
    }
}