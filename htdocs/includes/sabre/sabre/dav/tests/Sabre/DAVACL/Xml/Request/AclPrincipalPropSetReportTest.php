<?php

namespace Sabre\DAVACL\Xml\Request;

class AclPrincipalPropSetReportTest extends \Sabre\DAV\Xml\XmlTest {

    protected $elementMap = [

        '{DAV:}acl-principal-prop-set' => 'Sabre\DAVACL\Xml\Request\AclPrincipalPropSetReport',

    ];

    function testDeserialize() {

        $xml = <<<XML
<?xml version="1.0" encoding="utf-8" ?>
<D:acl-principal-prop-set xmlns:D="DAV:">
 <D:prop>
   <D:displayname/>
 </D:prop>
</D:acl-principal-prop-set>
XML;

        $result = $this->parse($xml);

        $this->assertEquals(['{DAV:}displayname'], $result['value']->properties);

    }

}
