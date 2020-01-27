<?php

namespace Sabre\CardDAV;

use Sabre\DAV;
use Sabre\DAVACL;
use Sabre\HTTP;

abstract class AbstractPluginTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Sabre\CardDAV\Plugin
     */
    protected $plugin;
    /**
     * @var Sabre\DAV\Server
     */
    protected $server;
    /**
     * @var Sabre\CardDAV\Backend\Mock;
     */
    protected $backend;

    function setUp() {

        $this->backend = new Backend\Mock();
        $principalBackend = new DAVACL\PrincipalBackend\Mock();

        $tree = [
            new AddressBookRoot($principalBackend, $this->backend),
            new DAVACL\PrincipalCollection($principalBackend)
        ];

        $this->plugin = new Plugin();
        $this->plugin->directories = ['directory'];
        $this->server = new DAV\Server($tree);
        $this->server->sapi = new HTTP\SapiMock();
        $this->server->addPlugin($this->plugin);
        $this->server->debugExceptions = true;

    }

}
