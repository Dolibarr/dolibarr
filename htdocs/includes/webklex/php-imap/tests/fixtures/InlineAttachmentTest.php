<?php
/*
* File: InlineAttachmentTest.php
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
use Webklex\PHPIMAP\Support\AttachmentCollection;

/**
 * Class InlineAttachmentTest
 *
 * @package Tests\fixtures
 */
class InlineAttachmentTest extends FixtureTestCase {

    /**
     * Test the fixture inline_attachment.eml
     *
     * @return void
     */
    public function testFixture() : void {
        $message = $this->getFixture("inline_attachment.eml");

        self::assertEquals("", $message->subject);
        self::assertFalse($message->hasTextBody());
        self::assertEquals('<img style="height: auto;" src="cid:ii_15f0aad691bb745f" border="0"/>', $message->getHTMLBody());

        self::assertFalse($message->date->first());
        self::assertFalse($message->from->first());
        self::assertFalse($message->to->first());


        $attachments = $message->attachments();
        self::assertInstanceOf(AttachmentCollection::class, $attachments);
        self::assertCount(1, $attachments);

        $attachment = $attachments[0];

        self::assertInstanceOf(Attachment::class, $attachment);
        self::assertEquals('d2913999', $attachment->name);
        self::assertEquals('d2913999', $attachment->filename);
        self::assertEquals('ii_15f0aad691bb745f', $attachment->id);
        self::assertEquals('text', $attachment->type);
        self::assertEquals('', $attachment->getExtension());
        self::assertEquals("image/png", $attachment->content_type);
        self::assertEquals("6568c9e9c35a7fa06f236e89f704d8c9b47183a24f2c978dba6c92e2747e3a13", hash("sha256", $attachment->content));
        self::assertEquals(1486, $attachment->size);
        self::assertEquals(1, $attachment->part_number);
        self::assertEquals("inline", $attachment->disposition);
        self::assertEquals("<ii_15f0aad691bb745f>", $attachment->content_id);
        self::assertNotEmpty($attachment->id);
    }
}