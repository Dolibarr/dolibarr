<?php

namespace Sabre\DAV\Locks;

use Sabre\DAV;
use Sabre\HTTP;

require_once 'Sabre/DAV/AbstractServer.php';

class PluginTest extends DAV\AbstractServer {

    /**
     * @var Plugin
     */
    protected $locksPlugin;

    function setUp() {

        parent::setUp();
        $locksBackend = new Backend\File(SABRE_TEMPDIR . '/locksdb');
        $locksPlugin = new Plugin($locksBackend);
        $this->server->addPlugin($locksPlugin);
        $this->locksPlugin = $locksPlugin;

    }

    function testGetInfo() {

        $this->assertArrayHasKey(
            'name',
            $this->locksPlugin->getPluginInfo()
        );

    }

    function testGetFeatures() {

        $this->assertEquals([2], $this->locksPlugin->getFeatures());

    }

    function testGetHTTPMethods() {

        $this->assertEquals(['LOCK', 'UNLOCK'], $this->locksPlugin->getHTTPMethods(''));

    }

    function testLockNoBody() {

        $request = new HTTP\Request('LOCK', '/test.txt');
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Type'    => ['application/xml; charset=utf-8'],
            ],
            $this->response->getHeaders()
         );

        $this->assertEquals(400, $this->response->status);

    }

    function testLock() {

        $request = new HTTP\Request('LOCK', '/test.txt');
        $request->setBody('<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->response->getHeader('Lock-Token') . ')');

        $this->assertEquals(200, $this->response->status, 'Got an incorrect status back. Response body: ' . $this->response->body);

        $body = preg_replace("/xmlns(:[A-Za-z0-9_])?=(\"|\')DAV:(\"|\')/", "xmlns\\1=\"urn:DAV\"", $this->response->body);
        $xml = simplexml_load_string($body);
        $xml->registerXPathNamespace('d', 'urn:DAV');

        $elements = [
            '/d:prop',
            '/d:prop/d:lockdiscovery',
            '/d:prop/d:lockdiscovery/d:activelock',
            '/d:prop/d:lockdiscovery/d:activelock/d:locktype',
            '/d:prop/d:lockdiscovery/d:activelock/d:lockroot',
            '/d:prop/d:lockdiscovery/d:activelock/d:lockroot/d:href',
            '/d:prop/d:lockdiscovery/d:activelock/d:locktype/d:write',
            '/d:prop/d:lockdiscovery/d:activelock/d:lockscope',
            '/d:prop/d:lockdiscovery/d:activelock/d:lockscope/d:exclusive',
            '/d:prop/d:lockdiscovery/d:activelock/d:depth',
            '/d:prop/d:lockdiscovery/d:activelock/d:owner',
            '/d:prop/d:lockdiscovery/d:activelock/d:timeout',
            '/d:prop/d:lockdiscovery/d:activelock/d:locktoken',
            '/d:prop/d:lockdiscovery/d:activelock/d:locktoken/d:href',
        ];

        foreach ($elements as $elem) {
            $data = $xml->xpath($elem);
            $this->assertEquals(1, count($data), 'We expected 1 match for the xpath expression "' . $elem . '". ' . count($data) . ' were found. Full response body: ' . $this->response->body);
        }

        $depth = $xml->xpath('/d:prop/d:lockdiscovery/d:activelock/d:depth');
        $this->assertEquals('infinity', (string)$depth[0]);

        $token = $xml->xpath('/d:prop/d:lockdiscovery/d:activelock/d:locktoken/d:href');
        $this->assertEquals($this->response->getHeader('Lock-Token'), '<' . (string)$token[0] . '>', 'Token in response body didn\'t match token in response header.');

    }

    /**
     * @depends testLock
     */
    function testDoubleLock() {

        $request = new HTTP\Request('LOCK', '/test.txt');
        $request->setBody('<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->response = new HTTP\ResponseMock();
        $this->server->httpResponse = $this->response;

        $this->server->exec();

        $this->assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));

        $this->assertEquals(423, $this->response->status, 'Full response: ' . $this->response->body);

    }

    /**
     * @depends testLock
     */
    function testLockRefresh() {

        $request = new HTTP\Request('LOCK', '/test.txt');
        $request->setBody('<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $this->server->httpRequest = $request;
        $this->server->exec();

        $lockToken = $this->response->getHeader('Lock-Token');

        $this->response = new HTTP\ResponseMock();
        $this->server->httpResponse = $this->response;

        $request = new HTTP\Request('LOCK', '/test.txt', ['If' => '(' . $lockToken . ')']);
        $request->setBody('');

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));

        $this->assertEquals(200, $this->response->status, 'We received an incorrect status code. Full response body: ' . $this->response->getBody());

    }

    /**
     * @depends testLock
     */
    function testLockRefreshBadToken() {

        $request = new HTTP\Request('LOCK', '/test.txt');
        $request->setBody('<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $this->server->httpRequest = $request;
        $this->server->exec();

        $lockToken = $this->response->getHeader('Lock-Token');

        $this->response = new HTTP\ResponseMock();
        $this->server->httpResponse = $this->response;

        $request = new HTTP\Request('LOCK', '/test.txt', ['If' => '(' . $lockToken . 'foobar) (<opaquelocktoken:anotherbadtoken>)']);
        $request->setBody('');

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));

        $this->assertEquals(423, $this->response->getStatus(), 'We received an incorrect status code. Full response body: ' . $this->response->getBody());

    }

    /**
     * @depends testLock
     */
    function testLockNoFile() {

        $request = new HTTP\Request('LOCK', '/notfound.txt');
        $request->setBody('<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->response->getHeader('Lock-Token') . ')');

        $this->assertEquals(201, $this->response->status);

    }

    /**
     * @depends testLock
     */
    function testUnlockNoToken() {

        $request = new HTTP\Request('UNLOCK', '/test.txt');
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Type'    => ['application/xml; charset=utf-8'],
            ],
            $this->response->getHeaders()
         );

        $this->assertEquals(400, $this->response->status);

    }

    /**
     * @depends testLock
     */
    function testUnlockBadToken() {

        $request = new HTTP\Request('UNLOCK', '/test.txt', ['Lock-Token' => '<opaquelocktoken:blablabla>']);
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Type'    => ['application/xml; charset=utf-8'],
            ],
            $this->response->getHeaders()
         );

        $this->assertEquals(409, $this->response->status, 'Got an incorrect status code. Full response body: ' . $this->response->body);

    }

    /**
     * @depends testLock
     */
    function testLockPutNoToken() {

        $request = new HTTP\Request('LOCK', '/test.txt');
        $request->setBody('<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->response->getHeader('Lock-Token') . ')');

        $this->assertEquals(200, $this->response->status);

        $request = new HTTP\Request('PUT', '/test.txt');
        $request->setBody('newbody');
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->response->getHeader('Lock-Token') . ')');

        $this->assertEquals(423, $this->response->status);

    }

    /**
     * @depends testLock
     */
    function testUnlock() {

        $request = new HTTP\Request('LOCK', '/test.txt');
        $this->server->httpRequest = $request;

        $request->setBody('<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $this->server->invokeMethod($request, $this->server->httpResponse);
        $lockToken = $this->server->httpResponse->getHeader('Lock-Token');

        $request = new HTTP\Request('UNLOCK', '/test.txt', ['Lock-Token' => $lockToken]);
        $this->server->httpRequest = $request;
        $this->server->httpResponse = new HTTP\ResponseMock();
        $this->server->invokeMethod($request, $this->server->httpResponse);

        $this->assertEquals(204, $this->server->httpResponse->status, 'Got an incorrect status code. Full response body: ' . $this->response->body);
        $this->assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Length'  => ['0'],
            ],
            $this->server->httpResponse->getHeaders()
         );


    }

    /**
     * @depends testLock
     */
    function testUnlockWindowsBug() {

        $request = new HTTP\Request('LOCK', '/test.txt');
        $this->server->httpRequest = $request;

        $request->setBody('<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $this->server->invokeMethod($request, $this->server->httpResponse);
        $lockToken = $this->server->httpResponse->getHeader('Lock-Token');

        // See Issue 123
        $lockToken = trim($lockToken, '<>');

        $request = new HTTP\Request('UNLOCK', '/test.txt', ['Lock-Token' => $lockToken]);
        $this->server->httpRequest = $request;
        $this->server->httpResponse = new HTTP\ResponseMock();
        $this->server->invokeMethod($request, $this->server->httpResponse);

        $this->assertEquals(204, $this->server->httpResponse->status, 'Got an incorrect status code. Full response body: ' . $this->response->body);
        $this->assertEquals([
            'X-Sabre-Version' => [DAV\Version::VERSION],
            'Content-Length'  => ['0'],
            ],
            $this->server->httpResponse->getHeaders()
         );


    }

    /**
     * @depends testLock
     */
    function testLockRetainOwner() {

        $request = HTTP\Sapi::createFromServerArray([
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'LOCK',
        ]);
        $this->server->httpRequest = $request;

        $request->setBody('<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>Evert</D:owner>
</D:lockinfo>');

        $this->server->invokeMethod($request, $this->server->httpResponse);
        $lockToken = $this->server->httpResponse->getHeader('Lock-Token');

        $locks = $this->locksPlugin->getLocks('test.txt');
        $this->assertEquals(1, count($locks));
        $this->assertEquals('Evert', $locks[0]->owner);


    }

    /**
     * @depends testLock
     */
    function testLockPutBadToken() {

        $serverVars = [
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'LOCK',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->response->getHeader('Lock-Token') . ')');

        $this->assertEquals(200, $this->response->status);

        $serverVars = [
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'PUT',
            'HTTP_IF'        => '(<opaquelocktoken:token1>)',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('newbody');
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->response->getHeader('Lock-Token') . ')');

        // $this->assertEquals('412 Precondition failed',$this->response->status);
        $this->assertEquals(423, $this->response->status);

    }

    /**
     * @depends testLock
     */
    function testLockDeleteParent() {

        $serverVars = [
            'REQUEST_URI'    => '/dir/child.txt',
            'REQUEST_METHOD' => 'LOCK',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->response->getHeader('Lock-Token') . ')');

        $this->assertEquals(200, $this->response->status);

        $serverVars = [
            'REQUEST_URI'    => '/dir',
            'REQUEST_METHOD' => 'DELETE',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(423, $this->response->status);
        $this->assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));

    }
    /**
     * @depends testLock
     */
    function testLockDeleteSucceed() {

        $serverVars = [
            'REQUEST_URI'    => '/dir/child.txt',
            'REQUEST_METHOD' => 'LOCK',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->response->getHeader('Lock-Token') . ')');

        $this->assertEquals(200, $this->response->status);

        $serverVars = [
            'REQUEST_URI'    => '/dir/child.txt',
            'REQUEST_METHOD' => 'DELETE',
            'HTTP_IF'        => '(' . $this->response->getHeader('Lock-Token') . ')',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(204, $this->response->status);
        $this->assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));

    }

    /**
     * @depends testLock
     */
    function testLockCopyLockSource() {

        $serverVars = [
            'REQUEST_URI'    => '/dir/child.txt',
            'REQUEST_METHOD' => 'LOCK',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->response->getHeader('Lock-Token') . ')');

        $this->assertEquals(200, $this->response->status);

        $serverVars = [
            'REQUEST_URI'      => '/dir/child.txt',
            'REQUEST_METHOD'   => 'COPY',
            'HTTP_DESTINATION' => '/dir/child2.txt',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(201, $this->response->status, 'Copy must succeed if only the source is locked, but not the destination');
        $this->assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));

    }
    /**
     * @depends testLock
     */
    function testLockCopyLockDestination() {

        $serverVars = [
            'REQUEST_URI'    => '/dir/child2.txt',
            'REQUEST_METHOD' => 'LOCK',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->response->getHeader('Lock-Token') . ')');

        $this->assertEquals(201, $this->response->status);

        $serverVars = [
            'REQUEST_URI'      => '/dir/child.txt',
            'REQUEST_METHOD'   => 'COPY',
            'HTTP_DESTINATION' => '/dir/child2.txt',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(423, $this->response->status, 'Copy must succeed if only the source is locked, but not the destination');
        $this->assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));

    }

    /**
     * @depends testLock
     */
    function testLockMoveLockSourceLocked() {

        $serverVars = [
            'REQUEST_URI'    => '/dir/child.txt',
            'REQUEST_METHOD' => 'LOCK',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->response->getHeader('Lock-Token') . ')');

        $this->assertEquals(200, $this->response->status);

        $serverVars = [
            'REQUEST_URI'      => '/dir/child.txt',
            'REQUEST_METHOD'   => 'MOVE',
            'HTTP_DESTINATION' => '/dir/child2.txt',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(423, $this->response->status, 'Copy must succeed if only the source is locked, but not the destination');
        $this->assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));

    }

    /**
     * @depends testLock
     */
    function testLockMoveLockSourceSucceed() {

        $serverVars = [
            'REQUEST_URI'    => '/dir/child.txt',
            'REQUEST_METHOD' => 'LOCK',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->response->getHeader('Lock-Token') . ')');

        $this->assertEquals(200, $this->response->status);

        $serverVars = [
            'REQUEST_URI'      => '/dir/child.txt',
            'REQUEST_METHOD'   => 'MOVE',
            'HTTP_DESTINATION' => '/dir/child2.txt',
            'HTTP_IF'          => '(' . $this->response->getHeader('Lock-Token') . ')',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(201, $this->response->status, 'A valid lock-token was provided for the source, so this MOVE operation must succeed. Full response body: ' . $this->response->body);

    }

    /**
     * @depends testLock
     */
    function testLockMoveLockDestination() {

        $serverVars = [
            'REQUEST_URI'    => '/dir/child2.txt',
            'REQUEST_METHOD' => 'LOCK',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->response->getHeader('Lock-Token') . ')');

        $this->assertEquals(201, $this->response->status);

        $serverVars = [
            'REQUEST_URI'      => '/dir/child.txt',
            'REQUEST_METHOD'   => 'MOVE',
            'HTTP_DESTINATION' => '/dir/child2.txt',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(423, $this->response->status, 'Copy must succeed if only the source is locked, but not the destination');
        $this->assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));

    }
    /**
     * @depends testLock
     */
    function testLockMoveLockParent() {

        $serverVars = [
            'REQUEST_URI'    => '/dir',
            'REQUEST_METHOD' => 'LOCK',
            'HTTP_DEPTH'     => 'infinite',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->response->getHeader('Lock-Token') . ')');

        $this->assertEquals(200, $this->response->status);

        $serverVars = [
            'REQUEST_URI'      => '/dir/child.txt',
            'REQUEST_METHOD'   => 'MOVE',
            'HTTP_DESTINATION' => '/dir/child2.txt',
            'HTTP_IF'          => '</dir> (' . $this->response->getHeader('Lock-Token') . ')',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals(201, $this->response->status, 'We locked the parent of both the source and destination, but the move didn\'t succeed.');
        $this->assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));

    }

    /**
     * @depends testLock
     */
    function testLockPutGoodToken() {

        $serverVars = [
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'LOCK',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->response->getHeader('Lock-Token') . ')');

        $this->assertEquals(200, $this->response->status);

        $serverVars = [
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'PUT',
            'HTTP_IF'        => '(' . $this->response->getHeader('Lock-Token') . ')',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('newbody');
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->response->getHeader('Lock-Token') . ')');

        $this->assertEquals(204, $this->response->status);

    }

    /**
     * @depends testLock
     */
    function testLockPutUnrelatedToken() {

        $request = new HTTP\Request('LOCK', '/unrelated.txt');
        $request->setBody('<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
    <D:owner>
        <D:href>http://example.org/~ejw/contact.html</D:href>
    </D:owner>
</D:lockinfo>');

        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->response->getHeader('Lock-Token') . ')');

        $this->assertEquals(201, $this->response->getStatus());

        $request = new HTTP\Request(
            'PUT',
            '/test.txt',
            ['If' => '</unrelated.txt> (' . $this->response->getHeader('Lock-Token') . ')']
        );
        $request->setBody('newbody');
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertEquals('application/xml; charset=utf-8', $this->response->getHeader('Content-Type'));
        $this->assertTrue(preg_match('/^<opaquelocktoken:(.*)>$/', $this->response->getHeader('Lock-Token')) === 1, 'We did not get a valid Locktoken back (' . $this->response->getHeader('Lock-Token') . ')');

        $this->assertEquals(204, $this->response->status);

    }

    function testPutWithIncorrectETag() {

        $serverVars = [
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'PUT',
            'HTTP_IF'        => '(["etag1"])',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('newbody');
        $this->server->httpRequest = $request;
        $this->server->exec();
        $this->assertEquals(412, $this->response->status);

    }

    /**
     * @depends testPutWithIncorrectETag
     */
    function testPutWithCorrectETag() {

        // We need an ETag-enabled file node.
        $tree = new DAV\Tree(new DAV\FSExt\Directory(SABRE_TEMPDIR));
        $this->server->tree = $tree;

        $filename = SABRE_TEMPDIR . '/test.txt';
        $etag = sha1(
            fileinode($filename) .
            filesize($filename) .
            filemtime($filename)
        );
        $serverVars = [
            'REQUEST_URI'    => '/test.txt',
            'REQUEST_METHOD' => 'PUT',
            'HTTP_IF'        => '(["' . $etag . '"])',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody('newbody');
        $this->server->httpRequest = $request;
        $this->server->exec();
        $this->assertEquals(204, $this->response->status, 'Incorrect status received. Full response body:' . $this->response->body);

    }

    function testDeleteWithETagOnCollection() {

        $serverVars = [
            'REQUEST_URI'    => '/dir',
            'REQUEST_METHOD' => 'DELETE',
            'HTTP_IF'        => '(["etag1"])',
        ];
        $request = HTTP\Sapi::createFromServerArray($serverVars);

        $this->server->httpRequest = $request;
        $this->server->exec();
        $this->assertEquals(412, $this->response->status);

    }

    function testGetTimeoutHeader() {

        $request = HTTP\Sapi::createFromServerArray([
            'HTTP_TIMEOUT' => 'second-100',
        ]);

        $this->server->httpRequest = $request;
        $this->assertEquals(100, $this->locksPlugin->getTimeoutHeader());

    }

    function testGetTimeoutHeaderTwoItems() {

        $request = HTTP\Sapi::createFromServerArray([
            'HTTP_TIMEOUT' => 'second-5, infinite',
        ]);

        $this->server->httpRequest = $request;
        $this->assertEquals(5, $this->locksPlugin->getTimeoutHeader());

    }

    function testGetTimeoutHeaderInfinite() {

        $request = HTTP\Sapi::createFromServerArray([
            'HTTP_TIMEOUT' => 'infinite, second-5',
        ]);

        $this->server->httpRequest = $request;
        $this->assertEquals(LockInfo::TIMEOUT_INFINITE, $this->locksPlugin->getTimeoutHeader());

    }

    /**
     * @expectedException Sabre\DAV\Exception\BadRequest
     */
    function testGetTimeoutHeaderInvalid() {

        $request = HTTP\Sapi::createFromServerArray([
            'HTTP_TIMEOUT' => 'yourmom',
        ]);

        $this->server->httpRequest = $request;
        $this->locksPlugin->getTimeoutHeader();

    }


}
