<?php
/*
* File: AttachmentEncodedFilenameTest.php
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
 * Class AttachmentEncodedFilenameTest
 *
 * @package Tests\fixtures
 */
class AttachmentEncodedFilenameTest extends FixtureTestCase {

    /**
     * Test the fixture attachment_encoded_filename.eml
     *
     * @return void
     */
    public function testFixture() : void {
        $message = $this->getFixture("attachment_encoded_filename.eml");

        self::assertEquals("", $message->subject);
        self::assertEquals("multipart/mixed", $message->content_type->last());
        self::assertFalse($message->hasTextBody());
        self::assertFalse($message->hasHTMLBody());

        self::assertCount(1, $message->attachments());

        $attachment = $message->attachments()->first();
        self::assertInstanceOf(Attachment::class, $attachment);
        self::assertEquals("Prostřeno_2014_poslední volné termíny.xls", $attachment->filename);
        self::assertEquals("Prostřeno_2014_poslední volné termíny.xls", $attachment->name);
        self::assertEquals('xls', $attachment->getExtension());
        self::assertEquals('text', $attachment->type);
        self::assertEquals("application/vnd.ms-excel", $attachment->content_type);
        self::assertEquals("a0ef7cfbc05b73dbcb298fe0bc224b41900cdaf60f9904e3fea5ba6c7670013c", hash("sha256", $attachment->content));
        self::assertEquals(146, $attachment->size);
        self::assertEquals(0, $attachment->part_number);
        self::assertEquals("attachment", $attachment->disposition);
        self::assertNotEmpty($attachment->id);
    }
}