<?php
/*
* File: PecTest.php
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
 * Class PecTest
 *
 * @package Tests\fixtures
 */
class PecTest extends FixtureTestCase {

    /**
     * Test the fixture pec.eml
     *
     * @return void
     */
    public function testFixture() : void {
        $message = $this->getFixture("pec.eml");

        self::assertEquals("Certified", $message->subject);
        self::assertEquals("Signed", $message->getTextBody());
        self::assertEquals("<html><body>Signed</body></html>", $message->getHTMLBody());

        self::assertEquals("2017-10-02 10:13:43", $message->date->first()->setTimezone('UTC')->format("Y-m-d H:i:s"));
        self::assertEquals("test@example.com", $message->from->first()->mail);
        self::assertEquals("test@example.com", $message->to->first()->mail);

        $attachments = $message->attachments();

        self::assertInstanceOf(AttachmentCollection::class, $attachments);
        self::assertCount(3, $attachments);

        $attachment = $attachments[0];
        self::assertInstanceOf(Attachment::class, $attachment);
        self::assertEquals("data.xml", $attachment->name);
        self::assertEquals('xml', $attachment->getExtension());
        self::assertEquals('text', $attachment->type);
        self::assertEquals("application/xml", $attachment->content_type);
        self::assertEquals("<xml/>", $attachment->content);
        self::assertEquals(8, $attachment->size);
        self::assertEquals(3, $attachment->part_number);
        self::assertEquals("inline", $attachment->disposition);
        self::assertNotEmpty($attachment->id);

        $attachment = $attachments[1];
        self::assertInstanceOf(Attachment::class, $attachment);
        self::assertEquals("postacert.eml", $attachment->name);
        self::assertEquals('eml', $attachment->getExtension());
        self::assertEquals('text', $attachment->type);
        self::assertEquals("message/rfc822", $attachment->content_type);
        self::assertEquals("To: test@example.com\r\nFrom: test@example.com\r\nSubject: test-subject\r\nDate: Mon, 2 Oct 2017 12:13:50 +0200\r\nContent-Type: text/plain; charset=iso-8859-15; format=flowed\r\nContent-Transfer-Encoding: 7bit\r\n\r\ntest-content", $attachment->content);
        self::assertEquals(216, $attachment->size);
        self::assertEquals(4, $attachment->part_number);
        self::assertEquals("inline", $attachment->disposition);
        self::assertNotEmpty($attachment->id);

        $attachment = $attachments[2];
        self::assertInstanceOf(Attachment::class, $attachment);
        self::assertEquals("smime.p7s", $attachment->name);
        self::assertEquals('p7s', $attachment->getExtension());
        self::assertEquals('text', $attachment->type);
        self::assertEquals("application/x-pkcs7-signature", $attachment->content_type);
        self::assertEquals("1", $attachment->content);
        self::assertEquals(4, $attachment->size);
        self::assertEquals(5, $attachment->part_number);
        self::assertEquals("attachment", $attachment->disposition);
        self::assertNotEmpty($attachment->id);
    }
}