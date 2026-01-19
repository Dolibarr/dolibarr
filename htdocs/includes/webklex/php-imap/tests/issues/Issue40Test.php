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
use Tests\fixtures\FixtureTestCase;
use Webklex\PHPIMAP\Attachment;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Exceptions\AuthFailedException;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Exceptions\ImapBadRequestException;
use Webklex\PHPIMAP\Exceptions\ImapServerErrorException;
use Webklex\PHPIMAP\Exceptions\InvalidMessageDateException;
use Webklex\PHPIMAP\Exceptions\MaskNotFoundException;
use Webklex\PHPIMAP\Exceptions\MessageContentFetchingException;
use Webklex\PHPIMAP\Exceptions\ResponseException;
use Webklex\PHPIMAP\Exceptions\RuntimeException;
use Webklex\PHPIMAP\Exceptions\SpoofingAttemptDetectedException;
use Webklex\PHPIMAP\Message;

class Issue40Test extends FixtureTestCase {

    /**
     * @throws RuntimeException
     * @throws MessageContentFetchingException
     * @throws ResponseException
     * @throws ImapBadRequestException
     * @throws InvalidMessageDateException
     * @throws ConnectionFailedException
     * @throws \ReflectionException
     * @throws ImapServerErrorException
     * @throws AuthFailedException
     * @throws MaskNotFoundException
     */
    public function testIssueEmail() {
        $message = $this->getFixture("issue-40.eml");

        self::assertSame("Zly from", (string)$message->subject);
        self::assertSame([
                             'personal' => '',
                             'mailbox'  => 'faked_sender',
                             'host'     => 'sender_domain.pl',
                             'mail'     => 'faked_sender@sender_domain.pl',
                             'full'     => 'faked_sender@sender_domain.pl',
                         ], $message->from->first()->toArray());
        self::assertSame([
                             'personal' => '<real_sender@sender_domain.pl>',
                             'mailbox'  => 'real_sender',
                             'host'     => 'sender_domain.pl',
                             'mail'     => 'real_sender@sender_domain.pl',
                             'full'     => '<real_sender@sender_domain.pl> <real_sender@sender_domain.pl>',
                         ], (array)$message->return_path->first());
        self::assertSame(true, $message->spoofed->first());

        $config = $message->getConfig();
        self::assertSame(false, $config->get("security.detect_spoofing_exception"));
        $config->set("security.detect_spoofing_exception", true);
        self::assertSame(true, $config->get("security.detect_spoofing_exception"));

        $this->expectException(SpoofingAttemptDetectedException::class);
        $this->getFixture("issue-40.eml", $config);
    }

}