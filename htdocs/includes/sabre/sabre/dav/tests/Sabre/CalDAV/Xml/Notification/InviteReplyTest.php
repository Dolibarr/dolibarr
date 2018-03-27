<?php

namespace Sabre\CalDAV\Xml\Notification;

use Sabre\DAV;
use Sabre\Xml\Writer;

class InviteReplyTest extends \PHPUnit_Framework_TestCase {

    /**
     * @param array $notification
     * @param string $expected
     * @dataProvider dataProvider
     */
    function testSerializers($notification, $expected) {

        $notification = new InviteReply($notification);

        $this->assertEquals('foo', $notification->getId());
        $this->assertEquals('"1"', $notification->getETag());

        $simpleExpected = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<cs:root xmlns:cs="http://calendarserver.org/ns/"><cs:invite-reply/></cs:root>';

        $writer = new Writer();
        $writer->namespaceMap = [
            'http://calendarserver.org/ns/' => 'cs',
        ];
        $writer->openMemory();
        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('{http://calendarserver.org/ns/}root');
        $writer->write($notification);
        $writer->endElement();

        $this->assertEquals($simpleExpected, $writer->outputMemory());

        $writer = new Writer();
        $writer->contextUri = '/';
        $writer->namespaceMap = [
            'http://calendarserver.org/ns/' => 'cs',
            'DAV:'                          => 'd',
        ];
        $writer->openMemory();
        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('{http://calendarserver.org/ns/}root');
        $notification->xmlSerializeFull($writer);
        $writer->endElement();

        $this->assertXmlStringEqualsXmlString($expected, $writer->outputMemory());


    }

    function dataProvider() {

        $dtStamp = new \DateTime('2012-01-01 00:00:00 GMT');
        return [
            [
                [
                    'id'        => 'foo',
                    'dtStamp'   => $dtStamp,
                    'etag'      => '"1"',
                    'inReplyTo' => 'bar',
                    'href'      => 'mailto:foo@example.org',
                    'type'      => DAV\Sharing\Plugin::INVITE_ACCEPTED,
                    'hostUrl'   => 'calendar'
                ],
<<<FOO
<?xml version="1.0" encoding="UTF-8"?>
<cs:root xmlns:cs="http://calendarserver.org/ns/" xmlns:d="DAV:">
  <cs:dtstamp>20120101T000000Z</cs:dtstamp>
  <cs:invite-reply>
    <cs:uid>foo</cs:uid>
    <cs:in-reply-to>bar</cs:in-reply-to>
    <d:href>mailto:foo@example.org</d:href>
    <cs:invite-accepted/>
    <cs:hosturl>
      <d:href>/calendar</d:href>
    </cs:hosturl>
  </cs:invite-reply>
</cs:root>

FOO
            ],
            [
                [
                    'id'        => 'foo',
                    'dtStamp'   => $dtStamp,
                    'etag'      => '"1"',
                    'inReplyTo' => 'bar',
                    'href'      => 'mailto:foo@example.org',
                    'type'      => DAV\Sharing\Plugin::INVITE_DECLINED,
                    'hostUrl'   => 'calendar',
                    'summary'   => 'Summary!'
                ],
<<<FOO
<?xml version="1.0" encoding="UTF-8"?>
<cs:root xmlns:cs="http://calendarserver.org/ns/" xmlns:d="DAV:">
  <cs:dtstamp>20120101T000000Z</cs:dtstamp>
  <cs:invite-reply>
    <cs:uid>foo</cs:uid>
    <cs:in-reply-to>bar</cs:in-reply-to>
    <d:href>mailto:foo@example.org</d:href>
    <cs:invite-declined/>
    <cs:hosturl>
      <d:href>/calendar</d:href>
    </cs:hosturl>
    <cs:summary>Summary!</cs:summary>
  </cs:invite-reply>
</cs:root>

FOO
            ],

        ];

    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testMissingArg() {

        new InviteReply([]);

    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testUnknownArg() {

        new InviteReply([
            'foo-i-will-break' => true,

            'id'        => 1,
            'etag'      => '"bla"',
            'href'      => 'abc',
            'dtStamp'   => 'def',
            'inReplyTo' => 'qrs',
            'type'      => 'ghi',
            'hostUrl'   => 'jkl',
        ]);

    }

}
