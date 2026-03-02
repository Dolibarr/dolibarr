<?php
/*
* File: FourNestedEmailsTest.php
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
 * Class FourNestedEmailsTest
 *
 * @package Tests\fixtures
 */
class FourNestedEmailsTest extends FixtureTestCase {

    /**
     * Test the fixture four_nested_emails.eml
     *
     * @return void
     */
    public function testFixture() : void {
        $message = $this->getFixture("four_nested_emails.eml");

        self::assertEquals("3-third-subject", $message->subject);
        self::assertEquals("3-third-content", $message->getTextBody());
        self::assertFalse($message->hasHTMLBody());
        self::assertFalse($message->date->first());
        self::assertEquals("test@example.com", $message->from->first()->mail);
        self::assertEquals("test@example.com", $message->to->first()->mail);

        $attachments = $message->getAttachments();
        self::assertCount(1, $attachments);

        $attachment = $attachments[0];
        self::assertInstanceOf(Attachment::class, $attachment);
        self::assertEquals("2-second-email.eml", $attachment->name);
        self::assertEquals('text', $attachment->type);
        self::assertEquals('eml', $attachment->getExtension());
        self::assertEquals("message/rfc822", $attachment->content_type);
        self::assertEquals("85012e6a26d064a0288ee62618b3192687385adb4a4e27e48a28f738a325ca46", hash("sha256", $attachment->content));
        self::assertEquals(1376, $attachment->size);
        self::assertEquals(2, $attachment->part_number);
        self::assertEquals("attachment", $attachment->disposition);
        self::assertNotEmpty($attachment->id);

    }
}