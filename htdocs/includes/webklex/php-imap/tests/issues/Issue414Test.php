<?php
/*
* File: Issue410Test.php
* Category: -
* Author: M.Goldenbaum
* Created: 23.06.23 20:41
* Updated: -
*
* Description:
*  -
*/

namespace Tests\issues;

use PHPUnit\Framework\TestCase;
use Webklex\PHPIMAP\Message;

class Issue414Test extends TestCase {

    public function testIssueEmail() {
        $filename = implode(DIRECTORY_SEPARATOR, [__DIR__, "..", "messages", "issue-414.eml"]);
        $message = Message::fromFile($filename);

        self::assertSame("Test", (string)$message->subject);

        $attachments = $message->getAttachments();

        self::assertSame(2, $attachments->count());

        $attachment = $attachments->first();
        self::assertEmpty($attachment->description);
        self::assertSame("exampleMyFile.txt", $attachment->filename);
        self::assertSame("exampleMyFile.txt", $attachment->name);
        self::assertSame("be62f7e6", $attachment->id);

        $attachment = $attachments->last();
        self::assertEmpty($attachment->description);
        self::assertSame("phpfoo", $attachment->filename);
        self::assertSame("phpfoo", $attachment->name);
        self::assertSame("12e1d38b", $attachment->hash);
    }

}