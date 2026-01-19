<?php
/*
* File: EmbeddedEmailWithoutContentDispositionEmbeddedTest.php
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
 * Class EmbeddedEmailWithoutContentDispositionEmbeddedTest
 *
 * @package Tests\fixtures
 */
class EmbeddedEmailWithoutContentDispositionEmbeddedTest extends FixtureTestCase {

    /**
     * Test the fixture embedded_email_without_content_disposition-embedded.eml
     *
     * @return void
     */
    public function testFixture() : void {
        $message = $this->getFixture("embedded_email_without_content_disposition-embedded.eml");

        self::assertEquals("embedded_message_subject", $message->subject);
        self::assertEquals([
            'from webmail.my-office.cz (localhost [127.0.0.1]) by keira.cofis.cz ; Fri, 29 Jan 2016 14:25:40 +0100',
        ], $message->received->toArray());
        self::assertEquals("AC39946EBF5C034B87BABD5343E96979012671D40E38@VM002.cerk.cc", $message->message_id);
        self::assertEquals("pl-PL, nl-NL", $message->accept_language);
        self::assertEquals("pl-PL", $message->content_language);
        self::assertEquals("1.0", $message->mime_version);
        self::assertEquals("some txt", $message->getTextBody());
        self::assertEquals("<html>\r\n <p>some txt</p>\r\n</html>", $message->getHTMLBody());

        self::assertEquals("2019-04-05 10:10:49", $message->date->first()->setTimezone('UTC')->format("Y-m-d H:i:s"));
        self::assertEquals("demo@cerstor.cz", $message->from);
        self::assertEquals("demo@cerstor.cz", $message->to);

        $attachments = $message->getAttachments();
        self::assertCount(2, $attachments);

        $attachment = $attachments[0];
        self::assertInstanceOf(Attachment::class, $attachment);
        self::assertEquals("file1.xlsx", $attachment->name);
        self::assertEquals('text', $attachment->type);
        self::assertEquals('xlsx', $attachment->getExtension());
        self::assertEquals("application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", $attachment->content_type);
        self::assertEquals("87737d24c106b96e177f9564af6712e2c6d3e932c0632bfbab69c88b0bb934dc", hash("sha256", $attachment->content));
        self::assertEquals(40, $attachment->size);
        self::assertEquals(2, $attachment->part_number);
        self::assertEquals("attachment", $attachment->disposition);
        self::assertNotEmpty($attachment->id);

        $attachment = $attachments[1];
        self::assertInstanceOf(Attachment::class, $attachment);
        self::assertEquals("file2.xlsx", $attachment->name);
        self::assertEquals('xlsx', $attachment->getExtension());
        self::assertEquals('text', $attachment->type);
        self::assertEquals("application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", $attachment->content_type);
        self::assertEquals("87737d24c106b96e177f9564af6712e2c6d3e932c0632bfbab69c88b0bb934dc", hash("sha256", $attachment->content));
        self::assertEquals(40, $attachment->size);
        self::assertEquals(3, $attachment->part_number);
        self::assertEquals("attachment", $attachment->disposition);
        self::assertNotEmpty($attachment->id);
    }
}