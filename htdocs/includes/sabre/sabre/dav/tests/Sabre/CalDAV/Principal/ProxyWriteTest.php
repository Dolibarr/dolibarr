<?php

namespace Sabre\CalDAV\Principal;

use Sabre\DAVACL;

class ProxyWriteTest extends ProxyReadTest {

    function getInstance() {

        $backend = new DAVACL\PrincipalBackend\Mock();
        $principal = new ProxyWrite($backend, [
            'uri' => 'principal/user',
        ]);
        $this->backend = $backend;
        return $principal;

    }

    function testGetName() {

        $i = $this->getInstance();
        $this->assertEquals('calendar-proxy-write', $i->getName());

    }
    function testGetDisplayName() {

        $i = $this->getInstance();
        $this->assertEquals('calendar-proxy-write', $i->getDisplayName());

    }

    function testGetPrincipalUri() {

        $i = $this->getInstance();
        $this->assertEquals('principal/user/calendar-proxy-write', $i->getPrincipalUrl());

    }

}
