<?php
/*
* File: SimpleMultipartTest.php
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
 * Class SimpleMultipartTest
 *
 * @package Tests\fixtures
 */
class SimpleMultipartTest extends FixtureTestCase {

    /**
     * Test the fixture simple_multipart.eml
     *
     * @return void
     */
    public function testFixture() : void {
        $message = $this->getFixture("simple_multipart.eml");

        self::assertEquals("test", $message->getSubject());
        self::assertEquals("MyPlain", $message->getTextBody());
        self::assertEquals("MyHtml", $message->getHTMLBody());
        self::assertEquals("2017-09-27 10:48:51", $message->date->first()->setTimezone('UTC')->format("Y-m-d H:i:s"));
        self::assertEquals("from@there.com", $message->from->first()->mail);
        self::assertEquals("to@here.com", $message->to->first()->mail);
    }
}