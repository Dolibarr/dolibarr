<?php
/*
* File: EmbeddedEmailTest.php
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
 * Class EmbeddedEmailTest
 *
 * @package Tests\fixtures
 */
class EmbeddedEmailTest extends FixtureTestCase {

    /**
     * Test the fixture embedded_email.eml
     *
     * @return void
     */
    public function testFixture() : void {
        $message = $this->getFixture("embedded_email.eml");

        self::assertEquals("embedded message", $message->subject);
        self::assertEquals([
            'from webmail.my-office.cz (localhost [127.0.0.1]) by keira.cofis.cz ; Fri, 29 Jan 2016 14:25:40 +0100',
        ], $message->received->toArray());
        self::assertEquals("7e5798da5747415e5b82fdce042ab2a6@cerstor.cz", $message->message_id);
        self::assertEquals("demo@cerstor.cz", $message->return_path);
        self::assertEquals("1.0", $message->mime_version);
        self::assertEquals("Roundcube Webmail/1.0.0", $message->user_agent);
        self::assertEquals("email that contains embedded message", $message->getTextBody());
        self::assertFalse($message->hasHTMLBody());

        self::assertEquals("2016-01-29 13:25:40", $message->date->first()->setTimezone('UTC')->format("Y-m-d H:i:s"));
        self::assertEquals("demo@cerstor.cz", $message->from);
        self::assertEquals("demo@cerstor.cz", $message->x_sender);
        self::assertEquals("demo@cerstor.cz", $message->to);

        $attachments = $message->getAttachments();
        self::assertCount(1, $attachments);

        $attachment = $attachments[0];
        self::assertInstanceOf(Attachment::class, $attachment);
        self::assertEquals("demo.eml", $attachment->name);
        self::assertEquals('text', $attachment->type);
        self::assertEquals('eml', $attachment->getExtension());
        self::assertEquals("message/rfc822", $attachment->content_type);
        self::assertEquals("a1f965f10a9872e902a82dde039a237e863f522d238a1cb1968fe3396dbcac65", hash("sha256", $attachment->content));
        self::assertEquals(893, $attachment->size);
        self::assertEquals(1, $attachment->part_number);
        self::assertEquals("attachment", $attachment->disposition);
        self::assertNotEmpty($attachment->id);
    }
}