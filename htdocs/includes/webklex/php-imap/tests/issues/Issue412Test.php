<?php
/*
* File: Issue413Test.php
* Category: Test
* Author: M.Goldenbaum
* Created: 23.06.23 21:09
* Updated: -
*
* Description:
*  -
*/

namespace Tests\issues;

use PHPUnit\Framework\TestCase;
use Webklex\PHPIMAP\Config;
use Webklex\PHPIMAP\Message;

class Issue412Test extends TestCase {

    public function testIssueEmail() {
        $filename = implode(DIRECTORY_SEPARATOR, [__DIR__, "..", "messages", "issue-412.eml"]);
        $message = Message::fromFile($filename);

        self::assertSame("RE: TEST MESSAGE", (string)$message->subject);
        self::assertSame("64254d63e92a36ee02c760676351e60a", md5($message->getTextBody()));
        self::assertSame("2e4de288f6a1ed658548ed11fcdb1d79", md5($message->getHTMLBody()));
        self::assertSame(0, $message->attachments()->count());
    }

}