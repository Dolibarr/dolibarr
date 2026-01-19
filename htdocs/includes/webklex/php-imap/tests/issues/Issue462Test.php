<?php
/*
* File: Issue462Test.php
* Category: -
* Author: M.Goldenbaum
* Created: 23.06.23 20:41
* Updated: -
*
* Description:
*  -
*/

namespace Tests\issues;

use Tests\fixtures\FixtureTestCase;
use Webklex\PHPIMAP\Config;
use Webklex\PHPIMAP\Exceptions\AuthFailedException;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Exceptions\ImapBadRequestException;
use Webklex\PHPIMAP\Exceptions\ImapServerErrorException;
use Webklex\PHPIMAP\Exceptions\InvalidMessageDateException;
use Webklex\PHPIMAP\Exceptions\MaskNotFoundException;
use Webklex\PHPIMAP\Exceptions\MessageContentFetchingException;
use Webklex\PHPIMAP\Exceptions\ResponseException;
use Webklex\PHPIMAP\Exceptions\RuntimeException;

class Issue462Test extends FixtureTestCase {

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
        $config = Config::make();
        $config->set('options.rfc822', false);
        $message = $this->getFixture("issue-462.eml", $config);
        self::assertSame("Undeliverable: Some subject", (string)$message->subject);
        self::assertSame("postmaster@ <sending_domain.tld postmaster@sending_domain.tld>", (string)$message->from->first());
    }
}