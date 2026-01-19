<?php
/*
* File: PlainOnlyTest.php
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
 * Class PlainOnlyTest
 *
 * @package Tests\fixtures
 */
class PlainOnlyTest extends FixtureTestCase {

    /**
     * Test the fixture plain_only.eml
     *
     * @return void
     */
    public function testFixture() : void {
        $message = $this->getFixture("plain_only.eml");

        self::assertEquals("Nuu", $message->getSubject());
        self::assertEquals("Hi", $message->getTextBody());
        self::assertFalse($message->hasHTMLBody());
        self::assertEquals("2017-09-13 11:05:45", $message->date->first()->setTimezone('UTC')->format("Y-m-d H:i:s"));
        self::assertEquals("from@there.com", $message->from->first()->mail);
        self::assertEquals("to@here.com", $message->to->first()->mail);
    }
}