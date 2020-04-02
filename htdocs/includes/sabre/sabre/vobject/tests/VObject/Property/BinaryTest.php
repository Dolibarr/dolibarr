<?php

namespace Sabre\VObject\Property;

use Sabre\VObject;

class BinaryTest extends \PHPUnit_Framework_TestCase {

    /**
     * @expectedException \InvalidArgumentException
     */
    function testMimeDir() {

        $vcard = new VObject\Component\VCard(['VERSION' => '3.0']);
        $vcard->add('PHOTO', ['a', 'b']);

    }

}
