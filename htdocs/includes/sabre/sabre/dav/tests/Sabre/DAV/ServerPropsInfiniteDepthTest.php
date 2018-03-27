<?php

namespace Sabre\DAV;

use Sabre\HTTP;

require_once 'Sabre/DAV/AbstractServer.php';

class ServerPropsInfiniteDepthTest extends AbstractServer {

    protected function getRootNode() {

        return new FSExt\Directory(SABRE_TEMPDIR);

    }

    function setUp() {

        if (file_exists(SABRE_TEMPDIR . '../.sabredav')) unlink(SABRE_TEMPDIR . '../.sabredav');
        parent::setUp();
        file_put_contents(SABRE_TEMPDIR . '/test2.txt', 'Test contents2');
        mkdir(SABRE_TEMPDIR . '/col');
        mkdir(SABRE_TEMPDIR . '/col/col');
        file_put_contents(SABRE_TEMPDIR . 'col/col/test.txt', 'Test contents');
        $this->server->addPlugin(new Locks\Plugin(new Locks\Backend\File(SABRE_TEMPDIR . '/.locksdb')));
        $this->server->enablePropfindDepthInfinity = true;

    }

    function tearDown() {

        parent::tearDown();
        if (file_exists(SABRE_TEMPDIR . '../.locksdb')) unlink(SABRE_TEMPDIR . '../.locksdb');

    }

    private function sendRequest($body) {

        $request = new HTTP\Request('PROPFIND', '/', ['Depth' => 'infinity']);
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();

    }

    function testPropFindEmptyBody() {

        $this->sendRequest("");

        $this->assertEquals(207, $this->response->status, 'Incorrect status received. Full response body: ' . $this->response->getBodyAsString());

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
        // 8 resources are to be returned: /, col, col/col, col/col/test.txt, dir, dir/child.txt, test.txt and test2.txt
        $this->assertEquals(8, count($data));

    }

    function testSupportedLocks() {

        $xml = '<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
  <d:prop>
    <d:supportedlock />
  </d:prop>
</d:propfind>';

        $this->sendRequest($xml);

        $body = $this->response->getBodyAsString();
        $this->assertEquals(207, $this->response->getStatus(), $body);

        $body = preg_replace("/xmlns(:[A-Za-z0-9_])?=(\"|\')DAV:(\"|\')/", "xmlns\\1=\"urn:DAV\"", $body);
        $xml = simplexml_load_string($body);
        $xml->registerXPathNamespace('d', 'urn:DAV');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry');
        $this->assertEquals(16, count($data), 'We expected sixteen \'d:lockentry\' tags');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry/d:lockscope');
        $this->assertEquals(16, count($data), 'We expected sixteen \'d:lockscope\' tags');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry/d:locktype');
        $this->assertEquals(16, count($data), 'We expected sixteen \'d:locktype\' tags');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry/d:lockscope/d:shared');
        $this->assertEquals(8, count($data), 'We expected eight \'d:shared\' tags');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry/d:lockscope/d:exclusive');
        $this->assertEquals(8, count($data), 'We expected eight \'d:exclusive\' tags');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supportedlock/d:lockentry/d:locktype/d:write');
        $this->assertEquals(16, count($data), 'We expected sixteen \'d:write\' tags');
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
        $this->assertEquals(8, count($data), 'We expected eight \'d:lockdiscovery\' tags');

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
        $this->assertEquals(8, count($val), $body);
        $this->assertEquals('HTTP/1.1 404 Not Found', (string)$val[0]);

    }

}
