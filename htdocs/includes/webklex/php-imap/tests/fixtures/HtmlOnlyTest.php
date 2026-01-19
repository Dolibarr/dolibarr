<?php
/*
* File: HtmlOnlyTest.php
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
 * Class HtmlOnlyTest
 *
 * @package Tests\fixtures
 */
class HtmlOnlyTest extends FixtureTestCase {

    /**
     * Test the fixture html_only.eml
     *
     * @return void
     */
    public function testFixture() : void {
        $message = $this->getFixture("html_only.eml");

        self::assertEquals("Nuu", $message->subject);
        self::assertEquals("<html><body>Hi</body></html>", $message->getHTMLBody());
        self::assertFalse($message->hasTextBody());
        self::assertEquals("2017-09-13 11:05:45", $message->date->first()->setTimezone('UTC')->format("Y-m-d H:i:s"));
        self::assertEquals("from@there.com", $message->from->first()->mail);
        self::assertEquals("to@here.com", $message->to->first()->mail);
    }
}