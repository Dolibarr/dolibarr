<?php
use mikehaertl\tmp\File;

class FileTest extends \PHPUnit\Framework\TestCase
{
    public function testCanCreateFile()
    {
        $content = 'test content';
        $tmp = new File($content);
        $fileName = $tmp->getFileName();

        $this->assertFileExists($fileName);
        $readContent = file_get_contents($fileName);
        $this->assertEquals($content, $readContent);
        unset($tmp);
        $this->assertFileNotExists($fileName);
    }

    public function testCanCreateFileWithSuffix()
    {
        $content = 'test content';
        $tmp = new File($content, null, 'test_');
        $fileName = $tmp->getFileName();
        $baseName = basename($fileName);

        $this->assertEquals('test_', substr($baseName, 0,5));
    }

    public function testCanCreateFileWithPrefix()
    {
        $content = 'test content';
        $tmp = new File($content, '.html');
        $fileName = $tmp->getFileName();

        $this->assertEquals('.html', substr($fileName, -5));
    }

    public function testCanCreateFileInDirectory()
    {
        $dir = __DIR__.'/tmp';
        @mkdir($dir);
        $content = 'test content';
        $tmp = new File($content, null, null, $dir);
        $fileName = $tmp->getFileName();
        $this->assertEquals($dir, dirname($fileName));

        unset($tmp);
        @rmdir($dir);
    }

    public function testCanSaveFileAs()
    {
        $out = __DIR__.'/test.txt';
        $content = 'test content';
        $tmp = new File($content);
        $fileName = $tmp->getFileName();

        $this->assertFileExists($fileName);
        $this->assertTrue($tmp->saveAs($out));
        $this->assertFileExists($out);
        $readContent = file_get_contents($out);
        $this->assertEquals($content, $readContent);
        unset($tmp);
        $this->assertFileNotExists($fileName);
        $this->assertFileExists($out);
        unlink($out);
    }

    public function testCanKeepTempFile()
    {
        $out = __DIR__.'/test.txt';
        $content = 'test content';
        $tmp = new File($content);
        $tmp->delete = false;
        $fileName = $tmp->getFileName();

        $this->assertFileExists($fileName);
        $this->assertTrue($tmp->saveAs($out));
        $this->assertFileExists($out);
        unset($tmp);
        $this->assertFileExists($fileName);
        $this->assertFileExists($out);
        unlink($out);
    }

    public function testCanCastToFileName()
    {
        $content = 'test content';
        $tmp = new File($content);
        $fileName = $tmp->getFileName();

        $this->assertEquals($fileName, (string)$tmp);
    }
}


