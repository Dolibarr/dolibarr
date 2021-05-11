<?php

namespace Sabre\DAV\Property;

use Sabre\DAV;
use Sabre\HTTP;

require_once 'Sabre/HTTP/ResponseMock.php';
require_once 'Sabre/DAV/AbstractServer.php';

class SupportedReportSetTest extends DAV\AbstractServer {

    function sendPROPFIND($body) {

        $serverVars = [
            'REQUEST_URI'    => '/',
            'REQUEST_METHOD' => 'PROPFIND',
            'HTTP_DEPTH'     => '0',
        ];

        $request = HTTP\Sapi::createFromServerArray($serverVars);
        $request->setBody($body);

        $this->server->httpRequest = ($request);
        $this->server->exec();

    }

    /**
     */
    function testNoReports() {

        $xml = '<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
  <d:prop>
    <d:supported-report-set />
  </d:prop>
</d:propfind>';

        $this->sendPROPFIND($xml);

        $this->assertEquals(207, $this->response->status, 'We expected a multi-status response. Full response body: ' . $this->response->body);

        $body = preg_replace("/xmlns(:[A-Za-z0-9_])?=(\"|\')DAV:(\"|\')/", "xmlns\\1=\"urn:DAV\"", $this->response->body);
        $xml = simplexml_load_string($body);
        $xml->registerXPathNamespace('d', 'urn:DAV');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop');
        $this->assertEquals(1, count($data), 'We expected 1 \'d:prop\' element');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supported-report-set');
        $this->assertEquals(1, count($data), 'We expected 1 \'d:supported-report-set\' element');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:status');
        $this->assertEquals(1, count($data), 'We expected 1 \'d:status\' element');

        $this->assertEquals('HTTP/1.1 200 OK', (string)$data[0], 'The status for this property should have been 200');

    }

    /**
     * @depends testNoReports
     */
    function testCustomReport() {

        // Intercepting the report property
        $this->server->on('propFind', function(DAV\PropFind $propFind, DAV\INode $node) {
            if ($prop = $propFind->get('{DAV:}supported-report-set')) {
                $prop->addReport('{http://www.rooftopsolutions.nl/testnamespace}myreport');
                $prop->addReport('{DAV:}anotherreport');
            }
        }, 200);

        $xml = '<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
  <d:prop>
    <d:supported-report-set />
  </d:prop>
</d:propfind>';

        $this->sendPROPFIND($xml);

        $this->assertEquals(207, $this->response->status, 'We expected a multi-status response. Full response body: ' . $this->response->body);

        $body = preg_replace("/xmlns(:[A-Za-z0-9_])?=(\"|\')DAV:(\"|\')/", "xmlns\\1=\"urn:DAV\"", $this->response->body);
        $xml = simplexml_load_string($body);
        $xml->registerXPathNamespace('d', 'urn:DAV');
        $xml->registerXPathNamespace('x', 'http://www.rooftopsolutions.nl/testnamespace');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop');
        $this->assertEquals(1, count($data), 'We expected 1 \'d:prop\' element');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supported-report-set');
        $this->assertEquals(1, count($data), 'We expected 1 \'d:supported-report-set\' element');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supported-report-set/d:supported-report');
        $this->assertEquals(2, count($data), 'We expected 2 \'d:supported-report\' elements');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supported-report-set/d:supported-report/d:report');
        $this->assertEquals(2, count($data), 'We expected 2 \'d:report\' elements');

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supported-report-set/d:supported-report/d:report/x:myreport');
        $this->assertEquals(1, count($data), 'We expected 1 \'x:myreport\' element. Full body: ' . $this->response->body);

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:prop/d:supported-report-set/d:supported-report/d:report/d:anotherreport');
        $this->assertEquals(1, count($data), 'We expected 1 \'d:anotherreport\' element. Full body: ' . $this->response->body);

        $data = $xml->xpath('/d:multistatus/d:response/d:propstat/d:status');
        $this->assertEquals(1, count($data), 'We expected 1 \'d:status\' element');

        $this->assertEquals('HTTP/1.1 200 OK', (string)$data[0], 'The status for this property should have been 200');

    }

}
