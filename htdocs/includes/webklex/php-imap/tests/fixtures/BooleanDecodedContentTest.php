<?php
/*
* File: BooleanDecodedContentTest.php
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
 * Class BooleanDecodedContentTest
 *
 * @package Tests\fixtures
 */
class BooleanDecodedContentTest extends FixtureTestCase {

    /**
     * Test the fixture boolean_decoded_content.eml
     *
     * @return void
     */
    public function testFixture() : void {
        $message = $this->getFixture("boolean_decoded_content.eml");

        self::assertEquals("Nuu", $message->subject);
        self::assertEquals("Here is the problem mail\r\n \r\nBody text", $message->getTextBody());
        self::assertEquals("Here is the problem mail\r\n \r\nBody text", $message->getHTMLBody());

        self::assertEquals("2017-09-13 11:05:45", $message->date->first()->setTimezone('UTC')->format("Y-m-d H:i:s"));
        self::assertEquals("from@there.com", $message->from);
        self::assertEquals("to@here.com", $message->to);

        $attachments = $message->getAttachments();
        self::assertCount(1, $attachments);

        $attachment = $attachments[0];
        self::assertInstanceOf(Attachment::class, $attachment);
        self::assertEquals("Example Domain.pdf", $attachment->name);
        self::assertEquals('text', $attachment->type);
        self::assertEquals('pdf', $attachment->getExtension());
        self::assertEquals("application/pdf", $attachment->content_type);
        self::assertEquals("1c449aaab4f509012fa5eaa180fd017eb7724ccacabdffc1c6066d3756dcde5c", hash("sha256", $attachment->content));
        self::assertEquals(53, $attachment->size);
        self::assertEquals(2, $attachment->part_number);
        self::assertEquals("attachment", $attachment->disposition);
        self::assertNotEmpty($attachment->id);
    }
}