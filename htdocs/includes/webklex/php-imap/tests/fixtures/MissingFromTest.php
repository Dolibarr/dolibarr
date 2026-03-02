<?php
/*
* File: MissingFromTest.php
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
 * Class MissingFromTest
 *
 * @package Tests\fixtures
 */
class MissingFromTest extends FixtureTestCase {

    /**
     * Test the fixture missing_from.eml
     *
     * @return void
     */
    public function testFixture() : void {
        $message = $this->getFixture("missing_from.eml");

        self::assertEquals("Nuu", $message->getSubject());
        self::assertEquals("Hi", $message->getTextBody());
        self::assertFalse($message->hasHTMLBody());
        self::assertEquals("2017-09-13 11:05:45", $message->date->first()->setTimezone('UTC')->format("Y-m-d H:i:s"));
        self::assertFalse($message->from->first());
        self::assertEquals("to@here.com", $message->to->first()->mail);
    }
}