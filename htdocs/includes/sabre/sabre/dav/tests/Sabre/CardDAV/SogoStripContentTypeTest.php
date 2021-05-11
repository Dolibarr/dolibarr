<?php

namespace Sabre\CardDAV;

use Sabre\DAV\PropFind;
use Sabre\HTTP;

class SogoStripContentTypeTest extends \Sabre\DAVServerTest {

    protected $setupCardDAV = true;
    protected $carddavAddressBooks = [
        [
            'id'           => 1,
            'uri'          => 'book1',
            'principaluri' => 'principals/user1',
        ],
    ];
    protected $carddavCards = [
        1 => [
            'card1.vcf' => "BEGIN:VCARD\nVERSION:3.0\nUID:12345\nEND:VCARD",
        ],
    ];

    function testDontStrip() {

        $result = $this->server->getProperties('addressbooks/user1/book1/card1.vcf', ['{DAV:}getcontenttype']);
        $this->assertEquals([
            '{DAV:}getcontenttype' => 'text/vcard; charset=utf-8'
        ], $result);

    }
    function testStrip() {

        $this->server->httpRequest = HTTP\Sapi::createFromServerArray([
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.6; rv:10.0.2) Gecko/20120216 Thunderbird/10.0.2 Lightning/1.2.1',
        ]);
        $result = $this->server->getProperties('addressbooks/user1/book1/card1.vcf', ['{DAV:}getcontenttype']);
        $this->assertEquals([
            '{DAV:}getcontenttype' => 'text/x-vcard'
        ], $result);

    }
    function testDontTouchOtherMimeTypes() {

        $this->server->httpRequest = new HTTP\Request('GET', '/addressbooks/user1/book1/card1.vcf', [
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.6; rv:10.0.2) Gecko/20120216 Thunderbird/10.0.2 Lightning/1.2.1',
        ]);

        $propFind = new PropFind('hello', ['{DAV:}getcontenttype']);
        $propFind->set('{DAV:}getcontenttype', 'text/plain');
        $this->carddavPlugin->propFindLate($propFind, new \Sabre\DAV\SimpleCollection('foo'));
        $this->assertEquals('text/plain', $propFind->get('{DAV:}getcontenttype'));

    }

}
