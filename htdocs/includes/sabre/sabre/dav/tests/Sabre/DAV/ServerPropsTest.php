<?php

namespace Sabre\DAV;

use Sabre\HTTP;

require_once 'Sabre/HTTP/ResponseMock.php';
require_once 'Sabre/DAV/AbstractServer.php';

class ServerPropsTest extends AbstractServer {

    protected function getRootNode() {

        return new FSExt\Directory(SABRE_TEMPDIR);

    }

    function setUp() {

        if (file_exists(SABRE_TEMPDIR . '../.sabredav')) unlink(SABRE_TEMPDIR . '../.sabredav');
        parent::setUp();
        file_put_contents(SABRE_TEMPDIR . '/test2.txt', 'Test contents2');
        mkdir(SABRE_TEMPDIR . '/col');
        file_put_contents(SABRE_TEMPDIR . 'col/test.txt', 'Test contents');
        $this->server->addPlugin(new Locks\Plugin(new Locks\Backend\File(SABRE_TEMPDIR . '/.locksdb')));

    }

    function tearDown() {

        parent::tearDown();
        if (file_exists(SABRE_TEMPDIR . '../.locksdb')) unlink(SABRE_TEMPDIR . '../.locksdb');

    }

    private function sendRequest($body, $path = '/', $headers = ['Depth' => '0']) {

        $request = new HTTP\Request('PROPFIND', $path, $headers, $body);

        $this->server->httpRequest = $request;
        $this->server->exec();

    }

    function testPropFindEmptyBody() {

        $this->sendRequest("");
        $this->assertEquals(207, $this->response->status);

        $this->assertEquals([
                'X-Sabre-Version' => [Version::VERSION],
                'Content-Type'    => ['application/xml; charset=utf-8'],
                'DAV'             => ['1, 3, extended-mkcol, 2'],
                'Vary'            => ['Brief,Prefer'],
            ],
            $this->response->getHeaders()
         );

        $body = preg_replace("/xmlns(:[A-Za-z0-9_])?=(\"|\')DAV:(\"|\')/", "xmlns\\1=\"urn:DAV\"", $this->response->body);
        $xml = simplexml_load_string($body);
        $xml->registerXPathNamespace('d', 'urn:DAV');

        list($data) = $xml->xpath('/d:multistatus/d:response/d:href');
        $this->assertEquals('/', (string)$data, 'href element should have been /');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:resourcetype');
        $this->assertEquals(1, count($data));

    }

    function testPropFindEmptyBodyFile() {

        $this->sendRequest("", '/test2.txt', []);
        $this->assertEquals(207, $this->response->status);

        $this->assertEquals([
                'X-Sabre-Version' => [Version::VERSION],
                'Content-Type'    => ['application/xml; charset=utf-8'],
                'DAV'             => ['1, 3, extended-mkcol, 2'],
                'Vary'            => ['Brief,Prefer'],
            ],
            $this->response->getHeaders()
         );

        $body = preg_replace("/xmlns(:[A-Za-z0-9_])?=(\"|\')DAV:(\"|\')/", "xmlns\\1=\"urn:DAV\"", $this->response->body);
        $xml = simplexml_load_string($body);
        $xml->registerXPathNamespace('d', 'urn:DAV');

        list($data) = $xml->xpath('/d:multistatus/d:response/d:href');
        $this->assertEquals('/test2.txt', (string)$data, 'href element should have been /test2.txt');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:getcontentlength');
        $this->assertEquals(1, count($data));

    }

    function testSupportedLocks() {

        $xml = '<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
  <d:prop>
    <d:supportedlock />
  </d:prop>
</d:propfind>';

        $this->sendRequest($xml);

        $body = preg_replace("/xmlns(:[A-Za-z0-9_])?=(\"|\')DAV:(\"|\')/", "xmlns\\1=\"urn:DAV\"", $this->response->body);
        $xml = simplexml_load_string($body);
        $xml->registerXPathNamespace('d', 'urn:DAV');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry');
        $this->assertEquals(2, count($data), 'We expected two \'d:lockentry\' tags');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry/d:lockscope');
        $this->assertEquals(2, count($data), 'We expected two \'d:lockscope\' tags');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry/d:locktype');
        $this->assertEquals(2, count($data), 'We expected two \'d:locktype\' tags');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry/d:lockscope/d:shared');
        $this->assertEquals(1, count($data), 'We expected a \'d:shared\' tag');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry/d:lockscope/d:exclusive');
        $this->assertEquals(1, count($data), 'We expected a \'d:exclusive\' tag');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry/d:locktype/d:write');
        $this->assertEquals(2, count($data), 'We expected two \'d:write\' tags');
    }

    function testLockDiscovery() {

        $xml = '<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
  <d:prop>
    <d:lockdiscovery />
  </d:prop>
</d:propfind>';

        $this->sendRequest($xml);

        $body = preg_replace("/xmlns(:[A-Za-z0-9_])?=(\"|\')DAV:(\"|\')/", "xmlns\\1=\"urn:DAV\"", $this->response->body);
        $xml = simplexml_load_string($body);
        $xml->registerXPathNamespace('d', 'urn:DAV');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:lockdiscovery');
        $this->assertEquals(1, count($data), 'We expected a \'d:lockdiscovery\' tag');

    }

    function testUnknownProperty() {

        $xml = '<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
  <d:prop>
    <d:macaroni />
  </d:prop>
</d:propfind>';

        $this->sendRequest($xml);
        $body = preg_replace("/xmlns(:[A-Za-z0-9_])?=(\"|\')DAV:(\"|\')/", "xmlns\\1=\"urn:DAV\"", $this->response->body);
        $xml = simplexml_load_string($body);
        $xml->registerXPathNamespace('d', 'urn:DAV');
        $pathTests = [
            '/d:multistatus',
            '/d:multistatus/d:response',
            '/d:multistatus/d:response/d:propstat',
            '/d:multistatus/d:response/d:propstat/d:status',
            '/d:multistatus/d:response/d:propstat/d:prop',
            '/d:multistatus/d:response/d:propstat/d:prop/d:macaroni',
        ];
        foreach ($pathTests as $test) {
            $this->assertTrue(count($xml->xpath($test)) == true, 'We expected the ' . $test . ' element to appear in the response, we got: ' . $body);
        }

        $val = $xml->xpath('/d:multistatus/d:response/d:propstat/d:status');
        $this->assertEquals(1, count($val), $body);
        $this->assertEquals('HTTP/1.1 404 Not Found', (string)$val[0]);

    }

    function testParsePropPatchRequest() {

        $body = '<?xml version="1.0"?>
<d:propertyupdate xmlns:d="DAV:" xmlns:s="http://sabredav.org/NS/test">
  <d:set><d:prop><s:someprop>somevalue</s:someprop></d:prop></d:set>
  <d:remove><d:prop><s:someprop2 /></d:prop></d:remove>
  <d:set><d:prop><s:someprop3>removeme</s:someprop3></d:prop></d:set>
  <d:remove><d:prop><s:someprop3 /></d:prop></d:remove>
</d:propertyupdate>';

        $result = $this->server->xml->parse($body);
        $this->assertEquals([
            '{http://sabredav.org/NS/test}someprop'  => 'somevalue',
            '{http://sabredav.org/NS/test}someprop2' => null,
            '{http://sabredav.org/NS/test}someprop3' => null,
        ], $result->properties);

    }

}
