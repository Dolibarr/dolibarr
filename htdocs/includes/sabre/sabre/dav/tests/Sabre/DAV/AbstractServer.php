<?php

namespace Sabre\DAV;

use Sabre\HTTP;

abstract class AbstractServer extends \PHPUnit_Framework_TestCase {

    /**
     * @var Sabre\HTTP\ResponseMock
     */
    protected $response;
    protected $request;
    /**
     * @var Sabre\DAV\Server
     */
    protected $server;
    protected $tempDir = SABRE_TEMPDIR;

    function setUp() {

        $this->response = new HTTP\ResponseMock();
        $this->server = new Server($this->getRootNode());
        $this->server->sapi = new HTTP\SapiMock();
        $this->server->httpResponse = $this->response;
        $this->server->debugExceptions = true;
        $this->deleteTree(SABRE_TEMPDIR, false);
        file_put_contents(SABRE_TEMPDIR . '/test.txt', 'Test contents');
        mkdir(SABRE_TEMPDIR . '/dir');
        file_put_contents(SABRE_TEMPDIR . '/dir/child.txt', 'Child contents');


    }

    function tearDown() {

        $this->deleteTree(SABRE_TEMPDIR, false);

    }

    protected function getRootNode() {

        return new FS\Directory(SABRE_TEMPDIR);

    }

    private function deleteTree($path, $deleteRoot = true) {

        foreach (scandir($path) as $node) {

            if ($node == '.' || $node == '.svn' || $node == '..') continue;
            $myPath = $path . '/' . $node;
            if (is_file($myPath)) {
                unlink($myPath);
            } else {
                $this->deleteTree($myPath);
            }

        }
        if ($deleteRoot) rmdir($path);

    }

}
