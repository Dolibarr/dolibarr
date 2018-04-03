<?php

namespace Sabre\CalDAV\Xml\Request;

use Sabre\DAV;
use Sabre\DAV\Xml\XmlTest;

class InviteReplyTest extends XmlTest {

    protected $elementMap = [
        '{http://calendarserver.org/ns/}invite-reply' => 'Sabre\\CalDAV\\Xml\\Request\\InviteReply',
    ];

    function testDeserialize() {

        $xml = <<<XML
<?xml version="1.0"?>
<cs:invite-reply xmlns:cs="http://calendarserver.org/ns/" xmlns:d="DAV:">
    <d:href>/principal/1</d:href>
    <cs:hosturl><d:href>/calendar/1</d:href></cs:hosturl>
    <cs:invite-accepted />
    <cs:in-reply-to>blabla</cs:in-reply-to>
    <cs:summary>Summary</cs:summary>
</cs:invite-reply>
XML;

        $result = $this->parse($xml);
        $inviteReply = new InviteReply('/principal/1', '/calendar/1', 'blabla', 'Summary', DAV\Sharing\Plugin::INVITE_ACCEPTED);

        $this->assertEquals(
            $inviteReply,
            $result['value']
        );

    }

    function testDeserializeDeclined() {

        $xml = <<<XML
<?xml version="1.0"?>
<cs:invite-reply xmlns:cs="http://calendarserver.org/ns/" xmlns:d="DAV:">
    <d:href>/principal/1</d:href>
    <cs:hosturl><d:href>/calendar/1</d:href></cs:hosturl>
    <cs:invite-declined />
    <cs:in-reply-to>blabla</cs:in-reply-to>
    <cs:summary>Summary</cs:summary>
</cs:invite-reply>
XML;

        $result = $this->parse($xml);
        $inviteReply = new InviteReply('/principal/1', '/calendar/1', 'blabla', 'Summary', DAV\Sharing\Plugin::INVITE_DECLINED);

        $this->assertEquals(
            $inviteReply,
            $result['value']
        );

    }

    /**
     * @expectedException \Sabre\DAV\Exception\BadRequest
     */
    function testDeserializeNoHostUrl() {

        $xml = <<<XML
<?xml version="1.0"?>
<cs:invite-reply xmlns:cs="http://calendarserver.org/ns/" xmlns:d="DAV:">
    <d:href>/principal/1</d:href>
    <cs:invite-declined />
    <cs:in-reply-to>blabla</cs:in-reply-to>
    <cs:summary>Summary</cs:summary>
</cs:invite-reply>
XML;

        $this->parse($xml);

    }
}
