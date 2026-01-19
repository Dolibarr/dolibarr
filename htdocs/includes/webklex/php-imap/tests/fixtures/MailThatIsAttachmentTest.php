<?php
/*
* File: MailThatIsAttachmentTest.php
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
 * Class MailThatIsAttachmentTest
 *
 * @package Tests\fixtures
 */
class MailThatIsAttachmentTest extends FixtureTestCase {

    /**
     * Test the fixture mail_that_is_attachment.eml
     *
     * @return void
     */
    public function testFixture() : void {
        $message = $this->getFixture("mail_that_is_attachment.eml");

        self::assertEquals("Report domain: yyy.cz Submitter: google.com Report-ID: 2244696771454641389", $message->subject);
        self::assertEquals("2244696771454641389@google.com", $message->message_id);
        self::assertEquals("1.0", $message->mime_version);
        self::assertFalse($message->hasTextBody());
        self::assertFalse($message->hasHTMLBody());

        self::assertEquals("2015-02-15 10:21:51", $message->date->first()->setTimezone('UTC')->format("Y-m-d H:i:s"));
        self::assertEquals("xxx@yyy.cz", $message->to->first()->mail);
        self::assertEquals("xxx@yyy.cz", $message->sender->first()->mail);

        $from = $message->from->first();
        self::assertEquals("noreply-dmarc-support via xxx", $from->personal);
        self::assertEquals("xxx", $from->mailbox);
        self::assertEquals("yyy.cz", $from->host);
        self::assertEquals("xxx@yyy.cz", $from->mail);
        self::assertEquals("noreply-dmarc-support via xxx <xxx@yyy.cz>", $from->full);

        self::assertCount(1, $message->attachments());

        $attachment = $message->attachments()->first();
        self::assertInstanceOf(Attachment::class, $attachment);
        self::assertEquals("google.com!yyy.cz!1423872000!1423958399.zip", $attachment->name);
        self::assertEquals('zip', $attachment->getExtension());
        self::assertEquals('text', $attachment->type);
        self::assertEquals("application/zip", $attachment->content_type);
        self::assertEquals("c0d4f47b6fde124cea7460c3e509440d1a062705f550b0502b8ba0cbf621c97a", hash("sha256", $attachment->content));
        self::assertEquals(1062, $attachment->size);
        self::assertEquals(0, $attachment->part_number);
        self::assertEquals("attachment", $attachment->disposition);
        self::assertNotEmpty($attachment->id);
    }
}