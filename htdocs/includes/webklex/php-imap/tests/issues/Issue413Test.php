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
use Tests\live\LiveMailboxTestCase;
use Webklex\PHPIMAP\Config;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Message;

class Issue413Test extends LiveMailboxTestCase {

    /**
     * Live server test
     *
     * @return void
     * @throws \Webklex\PHPIMAP\Exceptions\AuthFailedException
     * @throws \Webklex\PHPIMAP\Exceptions\ConnectionFailedException
     * @throws \Webklex\PHPIMAP\Exceptions\EventNotFoundException
     * @throws \Webklex\PHPIMAP\Exceptions\FolderFetchingException
     * @throws \Webklex\PHPIMAP\Exceptions\GetMessagesFailedException
     * @throws \Webklex\PHPIMAP\Exceptions\ImapBadRequestException
     * @throws \Webklex\PHPIMAP\Exceptions\ImapServerErrorException
     * @throws \Webklex\PHPIMAP\Exceptions\InvalidMessageDateException
     * @throws \Webklex\PHPIMAP\Exceptions\MaskNotFoundException
     * @throws \Webklex\PHPIMAP\Exceptions\MessageContentFetchingException
     * @throws \Webklex\PHPIMAP\Exceptions\MessageFlagException
     * @throws \Webklex\PHPIMAP\Exceptions\MessageHeaderFetchingException
     * @throws \Webklex\PHPIMAP\Exceptions\MessageNotFoundException
     * @throws \Webklex\PHPIMAP\Exceptions\ResponseException
     * @throws \Webklex\PHPIMAP\Exceptions\RuntimeException
     */
    public function testLiveIssueEmail() {
        $folder = $this->getFolder('INBOX');
        self::assertInstanceOf(Folder::class, $folder);

        /** @var Message $message */
        $_message = $this->appendMessageTemplate($folder, 'issue-413.eml');

        $message = $folder->messages()->getMessageByMsgn($_message->msgn);
        self::assertEquals($message->uid, $_message->uid);

        self::assertSame("Test Message", (string)$message->subject);
        self::assertSame("This is just a test, so ignore it (if you can!)\r\n\r\nTony Marston", $message->getTextBody());

        $message->delete();
    }

    /**
     * Static parsing test
     *
     * @return void
     * @throws \ReflectionException
     * @throws \Webklex\PHPIMAP\Exceptions\AuthFailedException
     * @throws \Webklex\PHPIMAP\Exceptions\ConnectionFailedException
     * @throws \Webklex\PHPIMAP\Exceptions\ImapBadRequestException
     * @throws \Webklex\PHPIMAP\Exceptions\ImapServerErrorException
     * @throws \Webklex\PHPIMAP\Exceptions\InvalidMessageDateException
     * @throws \Webklex\PHPIMAP\Exceptions\MaskNotFoundException
     * @throws \Webklex\PHPIMAP\Exceptions\MessageContentFetchingException
     * @throws \Webklex\PHPIMAP\Exceptions\ResponseException
     * @throws \Webklex\PHPIMAP\Exceptions\RuntimeException
     */
    public function testIssueEmail() {
        $filename = implode(DIRECTORY_SEPARATOR, [__DIR__, "..", "messages", "issue-413.eml"]);
        $message = Message::fromFile($filename);

        self::assertSame("Test Message", (string)$message->subject);
        self::assertSame("This is just a test, so ignore it (if you can!)\r\n\r\nTony Marston", $message->getTextBody());
    }

}