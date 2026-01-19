<?php
/*
* File: MissingDateTest.php
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
 * Class MissingDateTest
 *
 * @package Tests\fixtures
 */
class MissingDateTest extends FixtureTestCase {

    /**
     * Test the fixture missing_date.eml
     *
     * @return void
     */
    public function testFixture() : void {
        $message = $this->getFixture("missing_date.eml");

        self::assertEquals("Nuu", $message->getSubject());
        self::assertEquals("Hi", $message->getTextBody());
        self::assertFalse($message->hasHTMLBody());
        self::assertFalse($message->date->first());
        self::assertEquals("from@here.com", $message->from->first()->mail);
        self::assertEquals("to@here.com", $message->to->first()->mail);
    }
}