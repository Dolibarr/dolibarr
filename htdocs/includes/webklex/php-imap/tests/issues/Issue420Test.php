<?php
/*
* File: Issue355Test.php
* Category: -
* Author: M.Goldenbaum
* Created: 10.01.23 10:48
* Updated: -
*
* Description:
*  -
*/

namespace Tests\issues;

use PHPUnit\Framework\TestCase;
use Webklex\PHPIMAP\Config;
use Webklex\PHPIMAP\Header;

class Issue420Test extends TestCase {

    public function testIssue() {
        $raw_header = "Subject: =?UTF-8?B?VGlja2V0IE5vOiBb7aC97bOpMTddIE1haWxib3ggSW5ib3ggLSAoMTcpIEluY29taW5nIGZhaWxlZCBtZXNzYWdlcw==?=\r\n";

        $header = new Header($raw_header, Config::make());
        $subject = $header->get("subject");

        // Ticket No: [��17] Mailbox Inbox - (17) Incoming failed messages
        $this->assertEquals('Ticket No: [??17] Mailbox Inbox - (17) Incoming failed messages', utf8_decode($subject->toString()));
    }

}