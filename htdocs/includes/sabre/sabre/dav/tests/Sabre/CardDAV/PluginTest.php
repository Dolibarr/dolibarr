<?php

namespace Sabre\CardDAV;

use Sabre\DAV;

class PluginTest extends AbstractPluginTest {

    function testConstruct() {

        $this->assertEquals('{' . Plugin::NS_CARDDAV . '}addressbook', $this->server->resourceTypeMapping['Sabre\\CardDAV\\IAddressBook']);

        $this->assertTrue(in_array('addressbook', $this->plugin->getFeatures()));
        $this->assertEquals('carddav', $this->plugin->getPluginInfo()['name']);

    }

    function testSupportedReportSet() {

        $this->assertEquals([
            '{' . Plugin::NS_CARDDAV . '}addressbook-multiget',
            '{' . Plugin::NS_CARDDAV . '}addressbook-query',
        ], $this->plugin->getSupportedReportSet('addressbooks/user1/book1'));

    }

    function testSupportedReportSetEmpty() {

        $this->assertEquals([
        ], $this->plugin->getSupportedReportSet(''));

    }

    function testAddressBookHomeSet() {

        $result = $this->server->getProperties('principals/user1', ['{' . Plugin::NS_CARDDAV . '}addressbook-home-set']);

        $this->assertEquals(1, count($result));
        $this->assertTrue(isset($result['{' . Plugin::NS_CARDDAV . '}addressbook-home-set']));
        $this->assertEquals('addressbooks/user1/', $result['{' . Plugin::NS_CARDDAV . '}addressbook-home-set']->getHref());

    }

    function testDirectoryGateway() {

        $result = $this->server->getProperties('principals/user1', ['{' . Plugin::NS_CARDDAV . '}directory-gateway']);

        $this->assertEquals(1, count($result));
        $this->assertTrue(isset($result['{' . Plugin::NS_CARDDAV . '}directory-gateway']));
        $this->assertEquals(['directory'], $result['{' . Plugin::NS_CARDDAV . '}directory-gateway']->getHrefs());

    }

    function testReportPassThrough() {

        $this->assertNull($this->plugin->report('{DAV:}foo', new \DomDocument(), ''));

    }

    function testHTMLActionsPanel() {

        $output = '';
        $r = $this->server->emit('onHTMLActionsPanel', [$this->server->tree->getNodeForPath('addressbooks/user1'), &$output]);
        $this->assertFalse($r);

        $this->assertTrue(!!strpos($output, 'Display name'));

    }

    function testAddressbookPluginProperties() {

        $ns = '{' . Plugin::NS_CARDDAV . '}';
        $propFind = new DAV\PropFind('addressbooks/user1/book1', [
            $ns . 'supported-address-data',
            $ns . 'supported-collation-set',
        ]);
        $node = $this->server->tree->getNodeForPath('addressbooks/user1/book1');
        $this->plugin->propFindEarly($propFind, $node);

        $this->assertInstanceOf(
            'Sabre\\CardDAV\\Xml\\Property\\SupportedAddressData',
            $propFind->get($ns . 'supported-address-data')
        );
        $this->assertInstanceOf(
            'Sabre\\CardDAV\\Xml\\Property\\SupportedCollationSet',
            $propFind->get($ns . 'supported-collation-set')
        );


    }

    function testGetTransform() {

        $request = new \Sabre\HTTP\Request('GET', '/addressbooks/user1/book1/card1', ['Accept: application/vcard+json']);
        $response = new \Sabre\HTTP\ResponseMock();
        $this->server->invokeMethod($request, $response);

        $this->assertEquals(200, $response->getStatus());

    }

}
