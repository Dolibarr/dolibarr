<?php

namespace Sabre\DAV;

use Sabre\HTTP\RequestInterface;

class ClientMock extends Client {

    public $request;
    public $response;

    public $url;
    public $curlSettings;

    /**
     * Just making this method public
     *
     * @param string $url
     * @return string
     */
    function getAbsoluteUrl($url) {

        return parent::getAbsoluteUrl($url);

    }

    function doRequest(RequestInterface $request) {

        $this->request = $request;
        return $this->response;

    }

}
