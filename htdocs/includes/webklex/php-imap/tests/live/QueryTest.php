<?php
/*
* File: QueryTest.php
* Category: -
* Author: M.Goldenbaum
* Created: 04.03.23 03:52
* Updated: -
*
* Description:
*  -
*/

namespace Tests\live;

use Carbon\Carbon;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Exceptions\AuthFailedException;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Exceptions\EventNotFoundException;
use Webklex\PHPIMAP\Exceptions\FolderFetchingException;
use Webklex\PHPIMAP\Exceptions\GetMessagesFailedException;
use Webklex\PHPIMAP\Exceptions\ImapBadRequestException;
use Webklex\PHPIMAP\Exceptions\ImapServerErrorException;
use Webklex\PHPIMAP\Exceptions\InvalidMessageDateException;
use Webklex\PHPIMAP\Exceptions\InvalidWhereQueryCriteriaException;
use Webklex\PHPIMAP\Exceptions\MaskNotFoundException;
use Webklex\PHPIMAP\Exceptions\MessageContentFetchingException;
use Webklex\PHPIMAP\Exceptions\MessageFlagException;
use Webklex\PHPIMAP\Exceptions\MessageHeaderFetchingException;
use Webklex\PHPIMAP\Exceptions\MessageSearchValidationException;
use Webklex\PHPIMAP\Exceptions\ResponseException;
use Webklex\PHPIMAP\Exceptions\RuntimeException;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Query\WhereQuery;

/**
 * Class QueryTest
 *
 * @package Tests
 */
class QueryTest extends LiveMailboxTestCase {

    /**
     * Try to create a new query instance
     *
     * @return void
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws FolderFetchingException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MaskNotFoundException
     * @throws ResponseException
     * @throws RuntimeException
     */
    public function testQuery(): void {
        $folder = $this->getFolder('INBOX');
        self::assertInstanceOf(Folder::class, $folder);

        self::assertInstanceOf(WhereQuery::class, $folder->query());
        self::assertInstanceOf(WhereQuery::class, $folder->search());
        self::assertInstanceOf(WhereQuery::class, $folder->messages());
    }

    /**
     * Try to create a new query instance with a where clause
     *
     * @return void
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws EventNotFoundException
     * @throws FolderFetchingException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws InvalidMessageDateException
     * @throws MaskNotFoundException
     * @throws MessageContentFetchingException
     * @throws MessageFlagException
     * @throws MessageHeaderFetchingException
     * @throws ResponseException
     * @throws RuntimeException
     * @throws GetMessagesFailedException
     * @throws InvalidWhereQueryCriteriaException
     * @throws MessageSearchValidationException
     */
    public function testQueryWhere(): void {
        $client = $this->getClient();

        $delimiter = $this->getManager()->getConfig()->get("options.delimiter");
        $folder_path = implode($delimiter, ['INBOX', 'search']);

        $folder = $client->getFolder($folder_path);
        if ($folder !== null) {
            self::assertTrue($this->deleteFolder($folder));
        }
        $folder = $client->createFolder($folder_path, false);

        $messages = [
            $this->appendMessageTemplate($folder, '1366671050@github.com.eml'),
            $this->appendMessageTemplate($folder, 'attachment_encoded_filename.eml'),
            $this->appendMessageTemplate($folder, 'attachment_long_filename.eml'),
            $this->appendMessageTemplate($folder, 'attachment_no_disposition.eml'),
            $this->appendMessageTemplate($folder, 'bcc.eml'),
            $this->appendMessageTemplate($folder, 'boolean_decoded_content.eml'),
            $this->appendMessageTemplate($folder, 'email_address.eml'),
            $this->appendMessageTemplate($folder, 'embedded_email.eml'),
            $this->appendMessageTemplate($folder, 'embedded_email_without_content_disposition.eml'),
            $this->appendMessageTemplate($folder, 'embedded_email_without_content_disposition-embedded.eml'),
            $this->appendMessageTemplate($folder, 'example_attachment.eml'),
            $this->appendMessageTemplate($folder, 'example_bounce.eml'),
            $this->appendMessageTemplate($folder, 'four_nested_emails.eml'),
            $this->appendMessageTemplate($folder, 'gbk_charset.eml'),
            $this->appendMessageTemplate($folder, 'html_only.eml'),
            $this->appendMessageTemplate($folder, 'imap_mime_header_decode_returns_false.eml'),
            $this->appendMessageTemplate($folder, 'inline_attachment.eml'),
            $this->appendMessageTemplate($folder, 'issue-275.eml'),
            $this->appendMessageTemplate($folder, 'issue-275-2.eml'),
            $this->appendMessageTemplate($folder, 'issue-348.eml'),
            $this->appendMessageTemplate($folder, 'ks_c_5601-1987_headers.eml'),
            $this->appendMessageTemplate($folder, 'mail_that_is_attachment.eml'),
            $this->appendMessageTemplate($folder, 'missing_date.eml'),
            $this->appendMessageTemplate($folder, 'missing_from.eml'),
            $this->appendMessageTemplate($folder, 'mixed_filename.eml'),
            $this->appendMessageTemplate($folder, 'multipart_without_body.eml'),
            $this->appendMessageTemplate($folder, 'multiple_html_parts_and_attachments.eml'),
            $this->appendMessageTemplate($folder, 'multiple_nested_attachments.eml'),
            $this->appendMessageTemplate($folder, 'nestes_embedded_with_attachment.eml'),
            $this->appendMessageTemplate($folder, 'null_content_charset.eml'),
            $this->appendMessageTemplate($folder, 'pec.eml'),
            $this->appendMessageTemplate($folder, 'plain.eml'),
            $this->appendMessageTemplate($folder, 'plain_only.eml'),
            $this->appendMessageTemplate($folder, 'plain_text_attachment.eml'),
            $this->appendMessageTemplate($folder, 'references.eml'),
            $this->appendMessageTemplate($folder, 'simple_multipart.eml'),
            $this->appendMessageTemplate($folder, 'structured_with_attachment.eml'),
            $this->appendMessageTemplate($folder, 'thread_my_topic.eml'),
            $this->appendMessageTemplate($folder, 'thread_re_my_topic.eml'),
            $this->appendMessageTemplate($folder, 'thread_unrelated.eml'),
            $this->appendMessageTemplate($folder, 'undefined_charset_header.eml'),
            $this->appendMessageTemplate($folder, 'undisclosed_recipients_minus.eml'),
            $this->appendMessageTemplate($folder, 'undisclosed_recipients_space.eml'),
            $this->appendMessageTemplate($folder, 'unknown_encoding.eml'),
            $this->appendMessageTemplate($folder, 'without_charset_plain_only.eml'),
            $this->appendMessageTemplate($folder, 'without_charset_simple_multipart.eml'),
        ];

        $folder->getClient()->expunge();

        $query = $folder->query()->all();
        self::assertEquals(count($messages), $query->count());

        $query = $folder->query()->whereSubject("test");
        self::assertEquals(11, $query->count());

        $query = $folder->query()->whereOn(Carbon::now());
        self::assertEquals(count($messages), $query->count());

        self::assertTrue($this->deleteFolder($folder));
    }

    /**
     * Test query where criteria
     *
     * @return void
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws FolderFetchingException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws InvalidWhereQueryCriteriaException
     * @throws MaskNotFoundException
     * @throws ResponseException
     * @throws RuntimeException
     */
    public function testQueryWhereCriteria(): void {
        $folder = $this->getFolder('INBOX');
        self::assertInstanceOf(Folder::class, $folder);

        $this->assertWhereSearchCriteria($folder, 'SUBJECT', 'Test');
        $this->assertWhereSearchCriteria($folder, 'BODY', 'Test');
        $this->assertWhereSearchCriteria($folder, 'TEXT', 'Test');
        $this->assertWhereSearchCriteria($folder, 'KEYWORD', 'Test');
        $this->assertWhereSearchCriteria($folder, 'UNKEYWORD', 'Test');
        $this->assertWhereSearchCriteria($folder, 'FLAGGED', 'Seen');
        $this->assertWhereSearchCriteria($folder, 'UNFLAGGED', 'Seen');
        $this->assertHeaderSearchCriteria($folder, 'Message-ID', 'Seen');
        $this->assertHeaderSearchCriteria($folder, 'In-Reply-To', 'Seen');
        $this->assertWhereSearchCriteria($folder, 'BCC', 'test@example.com');
        $this->assertWhereSearchCriteria($folder, 'CC', 'test@example.com');
        $this->assertWhereSearchCriteria($folder, 'FROM', 'test@example.com');
        $this->assertWhereSearchCriteria($folder, 'TO', 'test@example.com');
        $this->assertWhereSearchCriteria($folder, 'UID', '1');
        $this->assertWhereSearchCriteria($folder, 'UID', '1,2');
        $this->assertWhereSearchCriteria($folder, 'ALL');
        $this->assertWhereSearchCriteria($folder, 'NEW');
        $this->assertWhereSearchCriteria($folder, 'OLD');
        $this->assertWhereSearchCriteria($folder, 'SEEN');
        $this->assertWhereSearchCriteria($folder, 'UNSEEN');
        $this->assertWhereSearchCriteria($folder, 'RECENT');
        $this->assertWhereSearchCriteria($folder, 'ANSWERED');
        $this->assertWhereSearchCriteria($folder, 'UNANSWERED');
        $this->assertWhereSearchCriteria($folder, 'DELETED');
        $this->assertWhereSearchCriteria($folder, 'UNDELETED');
        $this->assertHeaderSearchCriteria($folder, 'Content-Language','en_US');
        $this->assertWhereSearchCriteria($folder, 'CUSTOM X-Spam-Flag NO');
        $this->assertWhereSearchCriteria($folder, 'CUSTOM X-Spam-Flag YES');
        $this->assertWhereSearchCriteria($folder, 'NOT');
        $this->assertWhereSearchCriteria($folder, 'OR');
        $this->assertWhereSearchCriteria($folder, 'AND');
        $this->assertWhereSearchCriteria($folder, 'BEFORE', '01-Jan-2020', true);
        $this->assertWhereSearchCriteria($folder, 'BEFORE', Carbon::now()->subDays(1), true);
        $this->assertWhereSearchCriteria($folder, 'ON', '01-Jan-2020', true);
        $this->assertWhereSearchCriteria($folder, 'ON', Carbon::now()->subDays(1), true);
        $this->assertWhereSearchCriteria($folder, 'SINCE', '01-Jan-2020', true);
        $this->assertWhereSearchCriteria($folder, 'SINCE', Carbon::now()->subDays(1), true);
    }

    /**
     * Assert where search criteria
     * @param Folder $folder
     * @param string $criteria
     * @param string|Carbon|null $value
     * @param bool $date
     *
     * @return void
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws InvalidWhereQueryCriteriaException
     * @throws ResponseException
     * @throws RuntimeException
     */
    protected function assertWhereSearchCriteria(Folder $folder, string $criteria, Carbon|string|null $value = null, bool $date = false): void {
        $query = $folder->query()->where($criteria, $value);
        self::assertInstanceOf(WhereQuery::class, $query);

        $item = $query->getQuery()->first();
        $criteria = str_replace("CUSTOM ", "", $criteria);
        $expected = $value === null ? [$criteria] : [$criteria, $value];
        if ($date === true && $value instanceof Carbon) {
            $date_format = $folder->getClient()->getConfig()->get('date_format', 'd M y');
            $expected[1] = $value->format($date_format);
        }

        self::assertIsArray($item);
        self::assertIsString($item[0]);
        if($value !== null) {
            self::assertCount(2, $item);
            self::assertIsString($item[1]);
        }else{
            self::assertCount(1, $item);
        }
        self::assertSame($expected, $item);
    }

    /**
     * Assert header search criteria
     * @param Folder $folder
     * @param string $criteria
     * @param mixed|null $value
     *
     * @return void
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws InvalidWhereQueryCriteriaException
     * @throws ResponseException
     * @throws RuntimeException
     */
    protected function assertHeaderSearchCriteria(Folder $folder, string $criteria, mixed $value = null): void {
        $query = $folder->query()->whereHeader($criteria, $value);
        self::assertInstanceOf(WhereQuery::class, $query);

        $item = $query->getQuery()->first();

        self::assertIsArray($item);
        self::assertIsString($item[0]);
        self::assertCount(1, $item);
        self::assertSame(['HEADER '.$criteria.' '.$value], $item);
    }
}