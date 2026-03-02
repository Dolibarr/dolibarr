<?php
/*
* File: AttachmentLongFilenameTest.php
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
 * Class AttachmentLongFilenameTest
 *
 * @package Tests\fixtures
 */
class AttachmentLongFilenameTest extends FixtureTestCase {

    /**
     * Test the fixture attachment_long_filename.eml
     *
     * @return void
     */
    public function testFixture() : void {
        $message = $this->getFixture("attachment_long_filename.eml");

        self::assertEquals("", $message->subject);
        self::assertEquals("multipart/mixed", $message->content_type->last());
        self::assertFalse($message->hasTextBody());
        self::assertFalse($message->hasHTMLBody());

        $attachments = $message->attachments();
        self::assertCount(3, $attachments);

        $attachment = $attachments[0];
        self::assertInstanceOf(Attachment::class, $attachment);
        self::assertEquals("Buchungsbestätigung- Rechnung-Geschäftsbedingungen-Nr.B123-45 - XXXX xxxxxxxxxxxxxxxxx XxxX, Lüdxxxxxxxx - VM Klaus XXXXXX - xxxxxxxx.pdf", $attachment->name);
        self::assertEquals("Buchungsbestätigung- Rechnung-Geschäftsbedingungen-Nr.B123-45 - XXXXX xxxxxxxxxxxxxxxxx XxxX, Lüxxxxxxxxxx - VM Klaus XXXXXX - xxxxxxxx.pdf", $attachment->filename);
        self::assertEquals('text', $attachment->type);
        self::assertEquals('pdf', $attachment->getExtension());
        self::assertEquals("text/plain", $attachment->content_type);
        self::assertEquals("ca51ce1fb15acc6d69b8a5700256172fcc507e02073e6f19592e341bd6508ab8", hash("sha256", $attachment->content));
        self::assertEquals(4, $attachment->size);
        self::assertEquals(0, $attachment->part_number);
        self::assertEquals("attachment", $attachment->disposition);
        self::assertNotEmpty($attachment->id);

        $attachment = $attachments[1];
        self::assertInstanceOf(Attachment::class, $attachment);
        self::assertEquals('01_A€àäąбيد@Z-0123456789-qwertyuiopasdfghjklzxcvbnmopqrstuvz-0123456789-qwertyuiopasdfghjklzxcvbnmopqrstuvz-0123456789-qwertyuiopasdfghjklzxcvbnmopqrstuvz.txt', $attachment->name);
        self::assertEquals("cebd34e48eaa06311da3d3130d5a9b465b096dc1094a6548f8c94c24ca52f34e", hash("sha256", $attachment->filename));
        self::assertEquals('text', $attachment->type);
        self::assertEquals('txt', $attachment->getExtension());
        self::assertEquals("text/plain", $attachment->content_type);
        self::assertEquals("ca51ce1fb15acc6d69b8a5700256172fcc507e02073e6f19592e341bd6508ab8", hash("sha256", $attachment->content));
        self::assertEquals(4, $attachment->size);
        self::assertEquals(1, $attachment->part_number);
        self::assertEquals("attachment", $attachment->disposition);
        self::assertNotEmpty($attachment->id);

        $attachment = $attachments[2];
        self::assertInstanceOf(Attachment::class, $attachment);
        self::assertEquals('02_A€àäąбيد@Z-0123456789-qwertyuiopasdfghjklzxcvbnmopqrstuvz-0123456789-qwertyuiopasdfghjklzxcvbnmopqrstuvz-0123456789-qwertyuiopasdfghjklzxcvbnmopqrstuvz.txt', $attachment->name);
        self::assertEquals('02_A€àäąбيد@Z-0123456789-qwertyuiopasdfghjklzxcvbnmopqrstuvz-0123456789-qwertyuiopasdfghjklzxcvbnmopqrstuvz-0123456789-qwertyuiopasdfghjklzxcvbnmopqrstuvz.txt', $attachment->filename);
        self::assertEquals('text', $attachment->type);
        self::assertEquals("text/plain", $attachment->content_type);
        self::assertEquals('txt', $attachment->getExtension());
        self::assertEquals("ca51ce1fb15acc6d69b8a5700256172fcc507e02073e6f19592e341bd6508ab8", hash("sha256", $attachment->content));
        self::assertEquals(4, $attachment->size);
        self::assertEquals(2, $attachment->part_number);
        self::assertEquals("attachment", $attachment->disposition);
        self::assertNotEmpty($attachment->id);
    }
}