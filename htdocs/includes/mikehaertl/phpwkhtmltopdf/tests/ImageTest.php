<?php
use mikehaertl\wkhtmlto\Image;

class ImageTest extends \PHPUnit\Framework\TestCase
{
    CONST URL = 'http://www.google.com/robots.txt';

    // Create image through constructor
    public function testCanCreateImageFromFile()
    {
        $inFile = $this->getHtmlAsset();
        $outFile = $this->getOutFile('png');
        $binary = $this->getBinary();

        $image = new Image($inFile);
        $image->binary = $binary;
        $this->assertTrue($image->saveAs($outFile));
        $this->assertFileExists($outFile);

        $tmpFile = $image->getImageFilename();
        $this->assertEquals("$binary '$inFile' '$tmpFile'", (string) $image->getCommand());
        unlink($outFile);
    }
    public function testCanCreateImageFromHtmlString()
    {
        $outFile = $this->getOutFile('png');
        $binary = $this->getBinary();

        $image = new Image('<html><h1>Test</h1></html>');
        $image->binary = $binary;
        $this->assertTrue($image->saveAs($outFile));
        $this->assertFileExists($outFile);

        $tmpFile = $image->getImageFilename();
        $this->assertRegExp("#$binary '[^ ]+' '$tmpFile'#", (string) $image->getCommand());
        unlink($outFile);
    }
    public function testCanCreateImageFromUrl()
    {
        $url = self::URL;
        $inFile = $this->getHtmlAsset();
        $outFile = $this->getOutFile('png');
        $binary = $this->getBinary();

        $image = new Image($url);
        $image->binary = $binary;
        $this->assertTrue($image->saveAs($outFile));
        $this->assertFileExists($outFile);

        $tmpFile = $image->getImageFilename();
        $this->assertEquals("$binary '$url' '$tmpFile'", (string) $image->getCommand());
        unlink($outFile);
    }

    // Set page
    public function testCanSetPageFromFile()
    {
        $inFile = $this->getHtmlAsset();
        $outFile = $this->getOutFile('png');
        $binary = $this->getBinary();

        $image = new Image;
        $image->binary = $binary;
        $this->assertInstanceOf('mikehaertl\wkhtmlto\Image', $image->setPage($inFile));
        $this->assertTrue($image->saveAs($outFile));
        $this->assertFileExists($outFile);

        $tmpFile = $image->getImageFilename();
        $this->assertEquals("$binary '$inFile' '$tmpFile'", (string) $image->getCommand());
        unlink($outFile);
    }
    public function testCanSetPageFromHtmlString()
    {
        $outFile = $this->getOutFile('png');
        $binary = $this->getBinary();

        $image = new Image;
        $image->binary = $binary;
        $this->assertInstanceOf('mikehaertl\wkhtmlto\Image', $image->setPage('<html><h1>Test</h1></html>'));
        $this->assertTrue($image->saveAs($outFile));
        $this->assertFileExists($outFile);

        $tmpFile = $image->getImageFilename();
        $this->assertRegExp("#$binary '[^ ]+' '$tmpFile'#", (string) $image->getCommand());
        unlink($outFile);
    }
    public function testCanSetPageFromUrl()
    {
        $url = self::URL;
        $inFile = $this->getHtmlAsset();
        $outFile = $this->getOutFile('png');
        $binary = $this->getBinary();

        $image = new Image;
        $image->binary = $binary;
        $this->assertInstanceOf('mikehaertl\wkhtmlto\Image', $image->setPage($url));
        $this->assertTrue($image->saveAs($outFile));
        $this->assertFileExists($outFile);

        $tmpFile = $image->getImageFilename();
        $this->assertEquals("$binary '$url' '$tmpFile'", (string) $image->getCommand());
        unlink($outFile);
    }

    // Options
    public function testCanOptionsInConstructor()
    {
        $inFile = $this->getHtmlAsset();
        $outFile = $this->getOutFile('png');
        $binary = $this->getBinary();

        $image = new Image(array(
            'binary' => $binary,
            'type' => 'png',
            'transparent',
            'width'    => 800,
            'allow' => array(
                '/tmp',
                '/test',
            ),
        ));
        $this->assertInstanceOf('mikehaertl\wkhtmlto\Image', $image->setPage($inFile));
        $this->assertTrue($image->saveAs($outFile));

        $tmpFile = $image->getimageFilename();
        $this->assertFileExists($outFile);
        $this->assertEquals("$binary --transparent --width '800' --allow '/tmp' --allow '/test' '$inFile' '$tmpFile'", (string) $image->getCommand());
        unlink($outFile);
    }
    public function testCanSetOptions()
    {
        $inFile = $this->getHtmlAsset();
        $outFile = $this->getOutFile('png');
        $binary = $this->getBinary();

        $image = new Image;
        $image->setOptions(array(
            'binary' => $binary,
            'type' => 'png',
            'transparent',
            'width'    => 800,
            'allow' => array(
                '/tmp',
                '/test',
            ),
        ));
        $this->assertInstanceOf('mikehaertl\wkhtmlto\Image', $image->setPage($inFile));
        $this->assertTrue($image->saveAs($outFile));

        $tmpFile = $image->getimageFilename();
        $this->assertFileExists($outFile);
        $this->assertEquals("$binary --transparent --width '800' --allow '/tmp' --allow '/test' '$inFile' '$tmpFile'", (string) $image->getCommand());
        unlink($outFile);
    }

    // Xvfb
    public function testCanUseXvfbRun()
    {
        $inFile = $this->getHtmlAsset();
        $outFile = $this->getOutFile('png');
        $binary = $this->getBinary();

        $image = new Image(array(
            'binary' => $binary,
            'commandOptions' => array(
                'enableXvfb' => true,
            ),
        ));

        $this->assertInstanceOf('mikehaertl\wkhtmlto\Image', $image->setPage($inFile));
        $this->assertTrue($image->saveAs($outFile));

        $tmpFile = $image->getImageFilename();
        $command = (string)$image->getCommand();
        $this->assertEquals("xvfb-run -a --server-args=\"-screen 0, 1024x768x24\" $binary '$inFile' '$tmpFile'", $command);
        unlink($outFile);
    }


    protected function getBinary()
    {
        return '/usr/local/bin/wkhtmltoimage';
    }

    protected function getHtmlAsset()
    {
        return __DIR__.'/assets/test.html';
    }

    protected function getOutFile($type)
    {
        return __DIR__.'/test.'.$type;
    }
}
