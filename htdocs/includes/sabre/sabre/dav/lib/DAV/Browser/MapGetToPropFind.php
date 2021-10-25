<?php

namespace Sabre\DAV\Browser;

use Sabre\DAV;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * This is a simple plugin that will map any GET request for non-files to
 * PROPFIND allprops-requests.
 *
 * This should allow easy debugging of PROPFIND
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class MapGetToPropFind extends DAV\ServerPlugin {

    /**
     * reference to server class
     *
     * @var DAV\Server
     */
    protected $server;

    /**
     * Initializes the plugin and subscribes to events
     *
     * @param DAV\Server $server
     * @return void
     */
    function initialize(DAV\Server $server) {

        $this->server = $server;
        $this->server->on('method:GET', [$this, 'httpGet'], 90);
    }

    /**
     * This method intercepts GET requests to non-files, and changes it into an HTTP PROPFIND request
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return bool
     */
    function httpGet(RequestInterface $request, ResponseInterface $response) {

        $node = $this->server->tree->getNodeForPath($request->getPath());
        if ($node instanceof DAV\IFile) return;

        $subRequest = clone $request;
        $subRequest->setMethod('PROPFIND');

        $this->server->invokeMethod($subRequest, $response);
        return false;

    }

}
