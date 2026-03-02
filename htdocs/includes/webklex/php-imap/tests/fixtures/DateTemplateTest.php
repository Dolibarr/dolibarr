<?php
/*
* File: DateTemplateTest.php
* Category: -
* Author: M.Goldenbaum
* Created: 09.03.23 02:24
* Updated: -
*
* Description:
*  -
*/

namespace Tests\fixtures;

use Webklex\PHPIMAP\Exceptions\AuthFailedException;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Exceptions\ImapBadRequestException;
use Webklex\PHPIMAP\Exceptions\ImapServerErrorException;
use Webklex\PHPIMAP\Exceptions\InvalidMessageDateException;
use Webklex\PHPIMAP\Exceptions\MaskNotFoundException;
use Webklex\PHPIMAP\Exceptions\MessageContentFetchingException;
use Webklex\PHPIMAP\Exceptions\ResponseException;
use Webklex\PHPIMAP\Exceptions\RuntimeException;
use Webklex\PHPIMAP\Message;
use \ReflectionException;


/**
 * Class DateTemplateTest
 *
 * @package Tests\fixtures
 */
class DateTemplateTest extends FixtureTestCase {

    /**
     * Test if the date is parsed correctly
     * @var array|string[] $dates
     */
    protected array $dates = [
        "Fri, 5 Apr 2019 12:10:49 +0200" => "2019-04-05 10:10:49",
        "04 Jan 2018 10:12:47 UT" => "2018-01-04 10:12:47",
        "22 Jun 18 03:56:36 PM -05:00 (GMT -05:00)" => "2018-06-22 20:56:36",
        "Sat, 31 Aug 2013 20:08:23 +0580" => "2013-08-31 14:38:23",
        "Fri, 1 Feb 2019 01:30:04 +0600 (+06)" => "2019-01-31 19:30:04",
        "Mon, 4 Feb 2019 04:03:49 -0300 (-03)" => "2019-02-04 07:03:49",
        "Sun, 6 Apr 2008 21:24:33 UT" => "2008-04-06 21:24:33",
        "Wed, 11 Sep 2019 15:23:06 +0600 (+06)" => "2019-09-11 09:23:06",
        "14 Sep 2019 00:10:08 UT +0200" => "2019-09-14 00:10:08",
        "Tue, 08 Nov 2022 18:47:20 +0000 14:03:33 +0000" => "2022-11-08 18:47:20",
        "Sat, 10, Dec 2022 09:35:19 +0100" => "2022-12-10 08:35:19",
        "Thur, 16 Mar 2023 15:33:07 +0400" => "2023-03-16 11:33:07",
        "fr., 25 nov. 2022 06:27:14 +0100/fr., 25 nov. 2022 06:27:14 +0100" => "2022-11-25 05:27:14",
        "Di., 15 Feb. 2022 06:52:44 +0100 (MEZ)/Di., 15 Feb. 2022 06:52:44 +0100 (MEZ)" => "2022-02-15 05:52:44",
        "Mi., 23 Apr. 2025 09:48:37 +0200 (MESZ)" => "2025-04-23 07:48:37",
    ];

    /**
     * Test the fixture date-template.eml
     *
     * @return void
     * @throws InvalidMessageDateException
     * @throws ReflectionException
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MaskNotFoundException
     * @throws MessageContentFetchingException
     * @throws ResponseException
     * @throws RuntimeException
     */
    public function testFixture() : void {
        try {
            $message = $this->getFixture("date-template.eml");
            $this->fail("Expected InvalidMessageDateException");
        } catch (InvalidMessageDateException $e) {
            self::assertTrue(true);
        }

        self::$manager->setConfig([
            "options" => [
                "fallback_date" => "2021-01-01 00:00:00",
            ],
        ]);
        $message = $this->getFixture("date-template.eml", self::$manager->getConfig());

        self::assertEquals("test", $message->subject);
        self::assertEquals("1.0", $message->mime_version);
        self::assertEquals("Hi!", $message->getTextBody());
        self::assertFalse($message->hasHTMLBody());
        self::assertEquals("2021-01-01 00:00:00", $message->date->first()->timezone("UTC")->format("Y-m-d H:i:s"));
        self::assertEquals("from@there.com", (string)$message->from);
        self::assertEquals("to@here.com", $message->to);

        self::$manager->setConfig([
                                      "options" => [
                                          "fallback_date" => null,
                                      ],
                                  ]);

        $filename = implode(DIRECTORY_SEPARATOR, [__DIR__, "..",  "messages", "date-template.eml"]);
        $blob = file_get_contents($filename);
        self::assertNotFalse($blob);

        foreach ($this->dates as $date => $expected) {
            $message = Message::fromString(str_replace("%date_raw_header%", $date, $blob));
            self::assertEquals("test", $message->subject);
            self::assertEquals("1.0", $message->mime_version);
            self::assertEquals("Hi!", $message->getTextBody());
            self::assertFalse($message->hasHTMLBody());
            self::assertEquals($expected, $message->date->first()->timezone("UTC")->format("Y-m-d H:i:s"), "Date \"$date\" should be \"$expected\"");
            self::assertEquals("from@there.com", (string)$message->from);
            self::assertEquals("to@here.com", $message->to);
        }
    }
}