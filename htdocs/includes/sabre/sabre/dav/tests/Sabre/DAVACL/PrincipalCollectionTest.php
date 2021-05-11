<?php

namespace Sabre\DAVACL;

class PrincipalCollectionTest extends \PHPUnit_Framework_TestCase {

    function testBasic() {

        $backend = new PrincipalBackend\Mock();
        $pc = new PrincipalCollection($backend);
        $this->assertTrue($pc instanceof PrincipalCollection);

        $this->assertEquals('principals', $pc->getName());

    }

    /**
     * @depends testBasic
     */
    function testGetChildren() {

        $backend = new PrincipalBackend\Mock();
        $pc = new PrincipalCollection($backend);

        $children = $pc->getChildren();
        $this->assertTrue(is_array($children));

        foreach ($children as $child) {
            $this->assertTrue($child instanceof IPrincipal);
        }

    }

    /**
     * @depends testBasic
     * @expectedException Sabre\DAV\Exception\MethodNotAllowed
     */
    function testGetChildrenDisable() {

        $backend = new PrincipalBackend\Mock();
        $pc = new PrincipalCollection($backend);
        $pc->disableListing = true;

        $children = $pc->getChildren();

    }

    function testFindByUri() {

        $backend = new PrincipalBackend\Mock();
        $pc = new PrincipalCollection($backend);
        $this->assertEquals('principals/user1', $pc->findByUri('mailto:user1.sabredav@sabredav.org'));
        $this->assertNull($pc->findByUri('mailto:fake.user.sabredav@sabredav.org'));
        $this->assertNull($pc->findByUri(''));
    }

}
