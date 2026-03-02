<?php
/*
* File: StructureTest.php
* Category: -
* Author: M.Goldenbaum
* Created: 28.12.22 18:11
* Updated: -
*
* Description:
*  -
*/

namespace Tests;

use PHPUnit\Framework\TestCase;
use Webklex\PHPIMAP\Config;
use Webklex\PHPIMAP\Exceptions\InvalidMessageDateException;
use Webklex\PHPIMAP\Exceptions\MessageContentFetchingException;
use Webklex\PHPIMAP\Header;
use Webklex\PHPIMAP\Structure;

class StructureTest extends TestCase {

    /** @var Config $config */
    protected Config $config;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp(): void {
        $this->config = Config::make();
    }

    /**
     * Test parsing email headers
     *
     * @throws InvalidMessageDateException
     * @throws MessageContentFetchingException
     */
    public function testStructureParsing(): void {
        $email = file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, "messages", "1366671050@github.com.eml"]));
        if(!str_contains($email, "\r\n")){
            $email = str_replace("\n", "\r\n", $email);
        }

        $raw_header = substr($email, 0, strpos($email, "\r\n\r\n"));
        $raw_body = substr($email, strlen($raw_header)+8);

        $header = new Header($raw_header, $this->config);
        $structure = new Structure($raw_body, $header);

        self::assertSame(2, count($structure->parts));

        $textPart = $structure->parts[0];

        self::assertSame("UTF-8", $textPart->charset);
        self::assertSame("text/plain", $textPart->content_type);
        self::assertSame(278, $textPart->bytes);

        $htmlPart = $structure->parts[1];

        self::assertSame("UTF-8", $htmlPart->charset);
        self::assertSame("text/html", $htmlPart->content_type);
        self::assertSame(1478, $htmlPart->bytes);
    }
}