<?php

namespace Sabre\DAV;

use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class TestPlugin extends ServerPlugin {

    public $beforeMethod;

    function getFeatures() {

        return ['drinking'];

    }

    function getHTTPMethods($uri) {

        return ['BEER','WINE'];

    }

    function initialize(Server $server) {

        $server->on('beforeMethod', [$this, 'beforeMethod']);

    }

    function beforeMethod(RequestInterface $request, ResponseInterface $response) {

        $this->beforeMethod = $request->getMethod();
        return true;

    }

}
