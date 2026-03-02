<?php
/*
* File: NullContentCharsetTest.php
* Category: -
* Author: M.Goldenbaum
* Created: 09.03.23 02:24
* Updated: -
*
* Description:
*  -
*/

namespace Tests\fixtures;

/**
 * Class NullContentCharsetTest
 *
 * @package Tests\fixtures
 */
class NullContentCharsetTest extends FixtureTestCase {

    /**
     * Test the fixture null_content_charset.eml
     *
     * @return void
     */
    public function testFixture() : void {
        $message = $this->getFixture("null_content_charset.eml");

        self::assertEquals("test", $message->getSubject());
        self::assertEquals("Hi!", $message->getTextBody());
        self::assertEquals("1.0", $message->mime_version);
        self::assertFalse($message->hasHTMLBody());

        self::assertEquals("2017-09-27 10:48:51", $message->date->first()->setTimezone('UTC')->format("Y-m-d H:i:s"));
        self::assertEquals("from@there.com", $message->from->first()->mail);
        self::assertEquals("to@here.com", $message->to->first()->mail);
    }
}