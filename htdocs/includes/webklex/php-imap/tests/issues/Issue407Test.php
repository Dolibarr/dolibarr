<?php
/*
* File: Issue407Test.php
* Category: Test
* Author: M.Goldenbaum
* Created: 23.06.23 21:40
* Updated: -
*
* Description:
*  -
*/

namespace Tests\issues;

use PHPUnit\Framework\TestCase;
use Tests\live\LiveMailboxTestCase;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\IMAP;
use Webklex\PHPIMAP\Message;

class Issue407Test extends LiveMailboxTestCase {

    /**
     * @return void
     * @throws \Webklex\PHPIMAP\Exceptions\AuthFailedException
     * @throws \Webklex\PHPIMAP\Exceptions\ConnectionFailedException
     * @throws \Webklex\PHPIMAP\Exceptions\EventNotFoundException
     * @throws \Webklex\PHPIMAP\Exceptions\FolderFetchingException
     * @throws \Webklex\PHPIMAP\Exceptions\ImapBadRequestException
     * @throws \Webklex\PHPIMAP\Exceptions\ImapServerErrorException
     * @throws \Webklex\PHPIMAP\Exceptions\InvalidMessageDateException
     * @throws \Webklex\PHPIMAP\Exceptions\MaskNotFoundException
     * @throws \Webklex\PHPIMAP\Exceptions\MessageContentFetchingException
     * @throws \Webklex\PHPIMAP\Exceptions\MessageFlagException
     * @throws \Webklex\PHPIMAP\Exceptions\MessageHeaderFetchingException
     * @throws \Webklex\PHPIMAP\Exceptions\ResponseException
     * @throws \Webklex\PHPIMAP\Exceptions\RuntimeException
     */
    public function testIssue() {
        $folder = $this->getFolder('INBOX');
        self::assertInstanceOf(Folder::class, $folder);

        $message = $this->appendMessageTemplate($folder, "plain.eml");
        self::assertInstanceOf(Message::class, $message);

        $message->setFlag("Seen");

        $flags = $this->getClient()->getConnection()->flags($message->uid, IMAP::ST_UID)->validatedData();

        self::assertIsArray($flags);
        self::assertSame(1, count($flags));
        self::assertSame("\\Seen", $flags[$message->uid][0]);

        $message->delete();
    }

}