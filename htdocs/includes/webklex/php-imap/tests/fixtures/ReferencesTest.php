<?php
/*
* File: ReferencesTest.php
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
 * Class ReferencesTest
 *
 * @package Tests\fixtures
 */
class ReferencesTest extends FixtureTestCase {

    /**
     * Test the fixture references.eml
     *
     * @return void
     */
    public function testFixture() : void {
        $message = $this->getFixture("references.eml");

        self::assertEquals("", $message->subject);
        self::assertEquals("Hi\r\nHow are you?", $message->getTextBody());
        self::assertFalse($message->hasHTMLBody());
        self::assertFalse($message->date->first());

        self::assertEquals("b9e87bd5e661a645ed6e3b832828fcc5@example.com", $message->in_reply_to);
        self::assertEquals("", $message->from->first()->personal);
        self::assertEquals("", $message->from->first()->host);
        self::assertEquals("no_host", $message->from->first()->mail);
        self::assertFalse($message->to->first());

        self::assertEquals([
            "231d9ac57aec7d8c1a0eacfeab8af6f3@example.com",
            "08F04024-A5B3-4FDE-BF2C-6710DE97D8D9@example.com"
        ], $message->getReferences()->all());

        self::assertEquals([
            'This one: is "right" <ding@dong.com>',
            'No-address'
            ], $message->cc->map(function($address){
                /** @var \Webklex\PHPIMAP\Address $address */
                return $address->full;
            }));
    }
}