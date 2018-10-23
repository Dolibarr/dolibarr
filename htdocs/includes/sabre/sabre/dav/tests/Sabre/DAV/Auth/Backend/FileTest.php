<?php

namespace Sabre\DAV\Auth\Backend;

class FileTest extends \PHPUnit_Framework_TestCase {

    function tearDown() {

        if (file_exists(SABRE_TEMPDIR . '/filebackend')) unlink(SABRE_TEMPDIR . '/filebackend');

    }

    function testConstruct() {

        $file = new File();
        $this->assertTrue($file instanceof File);

    }

    /**
     * @expectedException Sabre\DAV\Exception
     */
    function testLoadFileBroken() {

        file_put_contents(SABRE_TEMPDIR . '/backend', 'user:realm:hash');
        $file = new File(SABRE_TEMPDIR . '/backend');

    }

    function testLoadFile() {

        file_put_contents(SABRE_TEMPDIR . '/backend', 'user:realm:' . md5('user:realm:password'));
        $file = new File();
        $file->loadFile(SABRE_TEMPDIR . '/backend');

        $this->assertFalse($file->getDigestHash('realm', 'blabla'));
        $this->assertEquals(md5('user:realm:password'), $file->getDigestHash('realm', 'user'));

    }

}
