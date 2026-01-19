<?php
/*
* File: EmbeddedEmailWithoutContentDispositionTest.php
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
 * Class EmbeddedEmailWithoutContentDispositionTest
 *
 * @package Tests\fixtures
 */
class EmbeddedEmailWithoutContentDispositionTest extends FixtureTestCase {

    /**
     * Test the fixture embedded_email_without_content_disposition.eml
     *
     * @return void
     */
    public function testFixture() : void {
        $message = $this->getFixture("embedded_email_without_content_disposition.eml");

        self::assertEquals("Subject", $message->subject);
        self::assertEquals([
            'from webmail.my-office.cz (localhost [127.0.0.1]) by keira.cofis.cz ; Fri, 29 Jan 2016 14:25:40 +0100',
        ], $message->received->toArray());
        self::assertEquals("AC39946EBF5C034B87BABD5343E96979012671D9F7E4@VM002.cerk.cc", $message->message_id);
        self::assertEquals("pl-PL, nl-NL", $message->accept_language);
        self::assertEquals("1.0", $message->mime_version);
        self::assertEquals("TexT\r\n\r\n[cid:file.jpg]", $message->getTextBody());
        self::assertEquals("<html><p>TexT</p></html>", $message->getHTMLBody());

        self::assertEquals("2019-04-05 11:48:50", $message->date->first()->setTimezone('UTC')->format("Y-m-d H:i:s"));
        self::assertEquals("demo@cerstor.cz", $message->from);
        self::assertEquals("demo@cerstor.cz", $message->to);

        $attachments = $message->getAttachments();
        self::assertCount(4, $attachments);

        $attachment = $attachments[0];
        self::assertInstanceOf(Attachment::class, $attachment);
        self::assertEquals("file.jpg", $attachment->name);
        self::assertEquals('jpg', $attachment->getExtension());
        self::assertEquals('text', $attachment->type);
        self::assertEquals("image/jpeg", $attachment->content_type);
        self::assertEquals("6b7fa434f92a8b80aab02d9bf1a12e49ffcae424e4013a1c4f68b67e3d2bbcd0", hash("sha256", $attachment->content));
        self::assertEquals(96, $attachment->size);
        self::assertEquals(2, $attachment->part_number);
        self::assertEquals("inline", $attachment->disposition);
        self::assertNotEmpty($attachment->id);

        $attachment = $attachments[1];
        self::assertInstanceOf(Attachment::class, $attachment);
        self::assertEquals('a1abc19a', $attachment->name);
        self::assertEquals('text', $attachment->type);
        self::assertEquals('', $attachment->getExtension());
        self::assertEquals("message/rfc822", $attachment->content_type);
        self::assertEquals("2476c8b91a93c6b2fe1bfff593cb55956c2fe8e7ca6de9ad2dc9d101efe7a867", hash("sha256", $attachment->content));
        self::assertEquals(2073, $attachment->size);
        self::assertEquals(3, $attachment->part_number);
        self::assertNull($attachment->disposition);
        self::assertNotEmpty($attachment->id);

        $attachment = $attachments[2];
        self::assertInstanceOf(Attachment::class, $attachment);
        self::assertEquals("file3.xlsx", $attachment->name);
        self::assertEquals('xlsx', $attachment->getExtension());
        self::assertEquals('text', $attachment->type);
        self::assertEquals("application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", $attachment->content_type);
        self::assertEquals("87737d24c106b96e177f9564af6712e2c6d3e932c0632bfbab69c88b0bb934dc", hash("sha256", $attachment->content));
        self::assertEquals(40, $attachment->size);
        self::assertEquals(4, $attachment->part_number);
        self::assertEquals("attachment", $attachment->disposition);
        self::assertNotEmpty($attachment->id);

        $attachment = $attachments[3];
        self::assertInstanceOf(Attachment::class, $attachment);
        self::assertEquals("file4.zip", $attachment->name);
        self::assertEquals('zip', $attachment->getExtension());
        self::assertEquals('text', $attachment->type);
        self::assertEquals("application/x-zip-compressed", $attachment->content_type);
        self::assertEquals("87737d24c106b96e177f9564af6712e2c6d3e932c0632bfbab69c88b0bb934dc", hash("sha256", $attachment->content));
        self::assertEquals(40, $attachment->size);
        self::assertEquals(5, $attachment->part_number);
        self::assertEquals("attachment", $attachment->disposition);
        self::assertNotEmpty($attachment->id);
    }
}