<?php
/*
* File: MultipleNestedAttachmentsTest.php
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
 * Class MultipleNestedAttachmentsTest
 *
 * @package Tests\fixtures
 */
class MultipleNestedAttachmentsTest extends FixtureTestCase {

    /**
     * Test the fixture multiple_nested_attachments.eml
     *
     * @return void
     */
    public function testFixture() : void {
        $message = $this->getFixture("multiple_nested_attachments.eml");

        self::assertEquals("", $message->subject);
        self::assertEquals("------------------------------------------------------------------------", $message->getTextBody());
        self::assertEquals("<html>\r\n  <head>\r\n\r\n    <meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\">\r\n  </head>\r\n  <body text=\"#000000\" bgcolor=\"#FFFFFF\">\r\n    <p><br>\r\n    </p>\r\n    <div class=\"moz-signature\">\r\n      <meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\">\r\n      <title></title>\r\n      Â <img src=\"cid:part1.8B953FBA.0E5A242C@xyz.xyz\" alt=\"\">\r\n      <hr>\r\n      <table width=\"20\" cellspacing=\"2\" cellpadding=\"2\" height=\"31\">\r\n        <tbody>\r\n          <tr>\r\n            <td><br>\r\n            </td>\r\n            <td valign=\"middle\"><br>\r\n            </td>\r\n          </tr>\r\n        </tbody>\r\n      </table>\r\n    </div>\r\n  </body>\r\n</html>", $message->getHTMLBody());

        self::assertEquals("2018-01-15 09:54:09", $message->date->first()->setTimezone('UTC')->format("Y-m-d H:i:s"));
        self::assertFalse($message->from->first());
        self::assertFalse($message->to->first());

        $attachments = $message->attachments();
        self::assertInstanceOf(AttachmentCollection::class, $attachments);
        self::assertCount(2, $attachments);

        $attachment = $attachments[0];
        self::assertInstanceOf(Attachment::class, $attachment);
        self::assertEquals("mleokdgdlgkkecep.png", $attachment->name);
        self::assertEquals('png', $attachment->getExtension());
        self::assertEquals('text', $attachment->type);
        self::assertEquals("image/png", $attachment->content_type);
        self::assertEquals("e0e99b0bd6d5ea3ced99add53cc98b6f8eea6eae8ddd773fd06f3489289385fb", hash("sha256", $attachment->content));
        self::assertEquals(114, $attachment->size);
        self::assertEquals(3, $attachment->part_number);
        self::assertEquals("inline", $attachment->disposition);
        self::assertNotEmpty($attachment->id);

        $attachment = $attachments[1];
        self::assertInstanceOf(Attachment::class, $attachment);
        self::assertEquals("FF4D00-1.png", $attachment->name);
        self::assertEquals('png', $attachment->getExtension());
        self::assertEquals('text', $attachment->type);
        self::assertEquals("image/png", $attachment->content_type);
        self::assertEquals("e0e99b0bd6d5ea3ced99add53cc98b6f8eea6eae8ddd773fd06f3489289385fb", hash("sha256", $attachment->content));
        self::assertEquals(114, $attachment->size);
        self::assertEquals(4, $attachment->part_number);
        self::assertEquals("attachment", $attachment->disposition);
        self::assertNotEmpty($attachment->id);
    }
}