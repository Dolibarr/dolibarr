<?php
/*
* File: MultipleHtmlPartsAndAttachmentsTest.php
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
 * Class MultipleHtmlPartsAndAttachmentsTest
 *
 * @package Tests\fixtures
 */
class MultipleHtmlPartsAndAttachmentsTest extends FixtureTestCase {

    /**
     * Test the fixture multiple_html_parts_and_attachments.eml
     *
     * @return void
     */
    public function testFixture() : void {
        $message = $this->getFixture("multiple_html_parts_and_attachments.eml");

        self::assertEquals("multiple_html_parts_and_attachments", $message->subject);
        self::assertEquals("This is the first html part\r\n\r\n￼\r\n\r\nThis is the second html part\r\n\r\n￼\r\n\r\nThis is the last html part\r\nhttps://www.there.com", $message->getTextBody());
        self::assertEquals("<html><head><meta http-equiv=\"content-type\" content=\"text/html; charset=us-ascii\"></head><body style=\"overflow-wrap: break-word; -webkit-nbsp-mode: space; line-break: after-white-space;\">This is the <b>first</b> html <u>part</u><br><br></body></html>\n<html><body style=\"overflow-wrap: break-word; -webkit-nbsp-mode: space; line-break: after-white-space;\"><head><meta http-equiv=\"content-type\" content=\"text/html; charset=us-ascii\"></head><br><br>This is <strike>the</strike> second html <i>part</i><br><br></body></html>\n<html><head><meta http-equiv=\"content-type\" content=\"text/html; charset=us-ascii\"></head><body style=\"overflow-wrap: break-word; -webkit-nbsp-mode: space; line-break: after-white-space;\"><br><br><font size=\"2\"><i>This</i> is the last <b>html</b> part</font><div>https://www.there.com</div><div><br></div><br><br>\r\n<br></body></html>", $message->getHTMLBody());

        self::assertEquals("2023-02-16 09:19:02", $message->date->first()->setTimezone('UTC')->format("Y-m-d H:i:s"));

        $from = $message->from->first();
        self::assertEquals("FromName", $from->personal);
        self::assertEquals("from", $from->mailbox);
        self::assertEquals("there.com", $from->host);
        self::assertEquals("from@there.com", $from->mail);
        self::assertEquals("FromName <from@there.com>", $from->full);

        self::assertEquals("to@there.com", $message->to->first());

        $attachments = $message->attachments();
        self::assertInstanceOf(AttachmentCollection::class, $attachments);
        self::assertCount(2, $attachments);

        $attachment = $attachments[0];
        self::assertInstanceOf(Attachment::class, $attachment);
        self::assertEquals("attachment1.pdf", $attachment->name);
        self::assertEquals('pdf', $attachment->getExtension());
        self::assertEquals('text', $attachment->type);
        self::assertEquals("application/pdf", $attachment->content_type);
        self::assertEquals("c162adf19e0f67e26ef0b7f791b33a60b2c23b175560a505dc7f9ec490206e49", hash("sha256", $attachment->content));
        self::assertEquals(4814, $attachment->size);
        self::assertEquals(2, $attachment->part_number);
        self::assertEquals("inline", $attachment->disposition);
        self::assertNotEmpty($attachment->id);

        $attachment = $attachments[1];
        self::assertInstanceOf(Attachment::class, $attachment);
        self::assertEquals("attachment2.pdf", $attachment->name);
        self::assertEquals('pdf', $attachment->getExtension());
        self::assertEquals('text', $attachment->type);
        self::assertEquals("application/pdf", $attachment->content_type);
        self::assertEquals("a337b37e9d3edb172a249639919f0eee3d344db352046d15f8f9887e55855a25", hash("sha256", $attachment->content));
        self::assertEquals(5090, $attachment->size);
        self::assertEquals(4, $attachment->part_number);
        self::assertEquals("inline", $attachment->disposition);
        self::assertNotEmpty($attachment->id);
    }
}