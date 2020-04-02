<?php

namespace Sabre\DAV;

require_once 'Sabre/TestUtil.php';

class ObjectTreeTest extends \PHPUnit_Framework_TestCase {

    protected $tree;

    function setup() {

        \Sabre\TestUtil::clearTempDir();
        mkdir(SABRE_TEMPDIR . '/root');
        mkdir(SABRE_TEMPDIR . '/root/subdir');
        file_put_contents(SABRE_TEMPDIR . '/root/file.txt', 'contents');
        file_put_contents(SABRE_TEMPDIR . '/root/subdir/subfile.txt', 'subcontents');
        $rootNode = new FSExt\Directory(SABRE_TEMPDIR . '/root');
        $this->tree = new Tree($rootNode);

    }

    function teardown() {

        \Sabre\TestUtil::clearTempDir();

    }

    function testGetRootNode() {

        $root = $this->tree->getNodeForPath('');
        $this->assertInstanceOf('Sabre\\DAV\\FSExt\\Directory', $root);

    }

    function testGetSubDir() {

        $root = $this->tree->getNodeForPath('subdir');
        $this->assertInstanceOf('Sabre\\DAV\\FSExt\\Directory', $root);

    }

    function testCopyFile() {

       $this->tree->copy('file.txt', 'file2.txt');
       $this->assertTrue(file_exists(SABRE_TEMPDIR . '/root/file2.txt'));
       $this->assertEquals('contents', file_get_contents(SABRE_TEMPDIR . '/root/file2.txt'));

    }

    /**
     * @depends testCopyFile
     */
    function testCopyDirectory() {

       $this->tree->copy('subdir', 'subdir2');
       $this->assertTrue(file_exists(SABRE_TEMPDIR . '/root/subdir2'));
       $this->assertTrue(file_exists(SABRE_TEMPDIR . '/root/subdir2/subfile.txt'));
       $this->assertEquals('subcontents', file_get_contents(SABRE_TEMPDIR . '/root/subdir2/subfile.txt'));

    }

    /**
     * @depends testCopyFile
     */
    function testMoveFile() {

       $this->tree->move('file.txt', 'file2.txt');
       $this->assertTrue(file_exists(SABRE_TEMPDIR . '/root/file2.txt'));
       $this->assertFalse(file_exists(SABRE_TEMPDIR . '/root/file.txt'));
       $this->assertEquals('contents', file_get_contents(SABRE_TEMPDIR . '/root/file2.txt'));

    }

    /**
     * @depends testMoveFile
     */
    function testMoveFileNewParent() {

       $this->tree->move('file.txt', 'subdir/file2.txt');
       $this->assertTrue(file_exists(SABRE_TEMPDIR . '/root/subdir/file2.txt'));
       $this->assertFalse(file_exists(SABRE_TEMPDIR . '/root/file.txt'));
       $this->assertEquals('contents', file_get_contents(SABRE_TEMPDIR . '/root/subdir/file2.txt'));

    }

    /**
     * @depends testCopyDirectory
     */
    function testMoveDirectory() {

       $this->tree->move('subdir', 'subdir2');
       $this->assertTrue(file_exists(SABRE_TEMPDIR . '/root/subdir2'));
       $this->assertTrue(file_exists(SABRE_TEMPDIR . '/root/subdir2/subfile.txt'));
       $this->assertFalse(file_exists(SABRE_TEMPDIR . '/root/subdir'));
       $this->assertEquals('subcontents', file_get_contents(SABRE_TEMPDIR . '/root/subdir2/subfile.txt'));

    }

}
