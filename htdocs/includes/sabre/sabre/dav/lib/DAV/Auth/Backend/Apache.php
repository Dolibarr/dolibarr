<?php

namespace Sabre\DAV\Auth\Backend;

use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * Apache authenticator
 *
 * This authentication backend assumes that authentication has been
 * configured in apache, rather than within SabreDAV.
 *
 * Make sure apache is properly configured for this to work.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Apache implements BackendInterface {

    /**
     * This is the prefix that will be used to generate principal urls.
     *
     * @var string
     */
    protected $principalPrefix = 'principals/';

    /**
     * When this method is called, the backend must check if authentication was
     * successful.
     *
     * The returned value must be one of the following
     *
     * [true, "principals/username"]
     * [false, "reason for failure"]
     *
     * If authentication was successful, it's expected that the authentication
     * backend returns a so-called principal url.
     *
     * Examples of a principal url:
     *
     * principals/admin
     * principals/user1
     * principals/users/joe
     * principals/uid/123457
     *
     * If you don't use WebDAV ACL (RFC3744) we recommend that you simply
     * return a string such as:
     *
     * principals/users/[username]
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return array
     */
    function check(RequestInterface $request, ResponseInterface $response) {

        $remoteUser = $request->getRawServerValue('REMOTE_USER');
        if (is_null($remoteUser)) {
            $remoteUser = $request->getRawServerValue('REDIRECT_REMOTE_USER');
        }
        if (is_null($remoteUser)) {
            return [false, 'No REMOTE_USER property was found in the PHP $_SERVER super-global. This likely means your server is not configured correctly'];
        }

        return [true, $this->principalPrefix . $remoteUser];

    }

    /**
     * This method is called when a user could not be authenticated, and
     * authentication was required for the current request.
     *
     * This gives you the opportunity to set authentication headers. The 401
     * status code will already be set.
     *
     * In this case of Basic Auth, this would for example mean that the
     * following header needs to be set:
     *
     * $response->addHeader('WWW-Authenticate', 'Basic realm=SabreDAV');
     *
     * Keep in mind that in the case of multiple authentication backends, other
     * WWW-Authenticate headers may already have been set, and you'll want to
     * append your own WWW-Authenticate header instead of overwriting the
     * existing one.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return void
     */
    function challenge(RequestInterface $request, ResponseInterface $response) {

    }

}
