<?php

namespace Sabre\DAV;

class SimpleFileTest extends \PHPUnit_Framework_TestCase {

    function testAll() {

        $file = new SimpleFile('filename.txt', 'contents', 'text/plain');

        $this->assertEquals('filename.txt', $file->getName());
        $this->assertEquals('contents', $file->get());
        $this->assertEquals(8, $file->getSize());
        $this->assertEquals('"' . sha1('contents') . '"', $file->getETag());
        $this->assertEquals('text/plain', $file->getContentType());

    }

}
