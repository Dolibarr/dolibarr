<?php
/*
* File: KsC56011987HeadersTest.php
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
 * Class KsC56011987HeadersTest
 *
 * @package Tests\fixtures
 */
class KsC56011987HeadersTest extends FixtureTestCase {

    /**
     * Test the fixture ks_c_5601-1987_headers.eml
     *
     * @return void
     */
    public function testFixture() : void {
        $message = $this->getFixture("ks_c_5601-1987_headers.eml");

        self::assertEquals("RE: 회원님께 Ersi님이 메시지를 보냈습니다.", $message->subject);
        self::assertEquals("=?ks_c_5601-1987?B?yLi/+LTUsrIgRXJzabTUwMwguN69w8H2uKYgurizwr3AtM+02S4=?=", $message->thread_topic);
        self::assertEquals("1.0", $message->mime_version);
        self::assertEquals("Content", $message->getTextBody());
        self::assertFalse($message->hasHTMLBody());
        self::assertEquals("2017-09-27 10:48:51", $message->date->first()->setTimezone('UTC')->format("Y-m-d H:i:s"));
        self::assertEquals("to@here.com", $message->to->first()->mail);


        $from = $message->from->first();
        self::assertEquals("김 현진", $from->personal);
        self::assertEquals("from", $from->mailbox);
        self::assertEquals("there.com", $from->host);
        self::assertEquals("from@there.com", $from->mail);
        self::assertEquals("김 현진 <from@there.com>", $from->full);
    }
}