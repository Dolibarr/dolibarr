<?php
use mikehaertl\wkhtmlto\Pdf;

class PdfTest extends \PHPUnit\Framework\TestCase
{
    CONST URL = 'http://www.google.com/robots.txt';

    // Create PDF through constructor
    public function testCanCreatePdfFromFile()
    {
        $inFile = $this->getHtmlAsset();
        $outFile = $this->getOutFile();
        $binary = $this->getBinary();

        $pdf = new Pdf($inFile);
        $pdf->binary = $binary;
        $this->assertTrue($pdf->saveAs($outFile));
        $this->assertFileExists($outFile);

        $tmpFile = $pdf->getPdfFilename();
        $this->assertEquals("$binary '$inFile' '$tmpFile'", (string) $pdf->getCommand());
        unlink($outFile);
    }
    public function testCanCreatePdfFromHtmlString()
    {
        $outFile = $this->getOutFile();
        $binary = $this->getBinary();

        $pdf = new Pdf('<html><h1>Test</h1></html>');
        $pdf->binary = $binary;
        $this->assertTrue($pdf->saveAs($outFile));
        $this->assertFileExists($outFile);

        $tmpFile = $pdf->getPdfFilename();
        $this->assertRegExp("#$binary '[^ ]+' '$tmpFile'#", (string) $pdf->getCommand());
        unlink($outFile);
    }
    public function testCanCreatePdfFromXmlString()
    {
        $outFile = $this->getOutFile();
        $binary = $this->getBinary();

        $pdf = new Pdf('<?xml version="1.0" encoding="UTF-8" standalone="no"?><svg xmlns="http://www.w3.org/2000/svg" version="1.0"></svg>');
        $pdf->binary = $binary;
        $this->assertTrue($pdf->saveAs($outFile));
        $this->assertFileExists($outFile);

        $tmpFile = $pdf->getPdfFilename();
        $this->assertRegExp("#$binary '[^ ]+' '$tmpFile'#", (string) $pdf->getCommand());
        unlink($outFile);
    }
    public function testCanCreatePdfFromUrl()
    {
        $url = self::URL;
        $outFile = $this->getOutFile();
        $binary = $this->getBinary();

        $pdf = new Pdf($url);
        $pdf->binary = $binary;
        $this->assertTrue($pdf->saveAs($outFile));
        $this->assertFileExists($outFile);

        $tmpFile = $pdf->getPdfFilename();
        $this->assertEquals("$binary '$url' '$tmpFile'", (string) $pdf->getCommand());
        unlink($outFile);
    }


    // Add pages
    public function testCanAddPagesFromFile()
    {
        $inFile = $this->getHtmlAsset();
        $outFile = $this->getOutFile();
        $binary = $this->getBinary();

        $pdf = new Pdf;
        $pdf->binary = $binary;
        $this->assertInstanceOf('mikehaertl\wkhtmlto\Pdf', $pdf->addPage($inFile));
        $this->assertInstanceOf('mikehaertl\wkhtmlto\Pdf', $pdf->addPage($inFile));
        $this->assertTrue($pdf->saveAs($outFile));

        $tmpFile = $pdf->getPdfFilename();
        $command = (string)$pdf->getCommand();
        $this->assertEquals("$binary '$inFile' '$inFile' '$tmpFile'", $command);
        unlink($outFile);
    }
    public function testCanAddPagesFromHtmlString()
    {
        $outFile = $this->getOutFile();
        $binary = $this->getBinary();

        $pdf = new Pdf;
        $pdf->binary = $binary;
        $this->assertInstanceOf('mikehaertl\wkhtmlto\Pdf', $pdf->addPage('<html><h1>Test</h1></html>'));
        $this->assertInstanceOf('mikehaertl\wkhtmlto\Pdf', $pdf->addPage('<html><h1>Test</h1></html>'));
        $this->assertTrue($pdf->saveAs($outFile));
        $this->assertFileExists($outFile);

        $tmpFile = $pdf->getPdfFilename();
        $this->assertRegExp("#$binary '[^ ]+' '[^ ]+' '$tmpFile'#", (string) $pdf->getCommand());
        unlink($outFile);
    }
    public function testCanAddPagesFromUrl()
    {
        $url = self::URL;
        $outFile = $this->getOutFile();
        $binary = $this->getBinary();

        $pdf = new Pdf;
        $pdf->binary = $binary;
        $this->assertInstanceOf('mikehaertl\wkhtmlto\Pdf', $pdf->addPage($url));
        $this->assertInstanceOf('mikehaertl\wkhtmlto\Pdf', $pdf->addPage($url));
        $this->assertTrue($pdf->saveAs($outFile));
        $this->assertFileExists($outFile);

        $tmpFile = $pdf->getPdfFilename();
        $this->assertEquals("$binary '$url' '$url' '$tmpFile'", (string) $pdf->getCommand());
        unlink($outFile);
    }
    public function testCanAddPageFromHtmlString()
    {
        $outFile = $this->getOutFile();
        $binary = $this->getBinary();

        $pdf = new Pdf;
        $pdf->binary = $binary;
        $pdf->addPage('<html><h1>test</h1></html>');
        $pdf->saveAs($outFile);
        $this->assertRegexp('/tmp_wkhtmlto_pdf_.*?\.html/', $pdf->getCommand()->getExecCommand());
        unlink($outFile);
    }
    public function testCanAddPageFromXmlString()
    {
        $outFile = $this->getOutFile();
        $binary = $this->getBinary();

        $pdf = new Pdf;
        $pdf->binary = $binary;
        $pdf->addPage('<xml>test</xml>');
        $pdf->saveAs($outFile);
        $this->assertRegexp('/tmp_wkhtmlto_pdf_.*?\.xml/', $pdf->getCommand()->getExecCommand());
        unlink($outFile);
    }
    public function testCanAddHtmlPageFromStringByType()
    {
        $outFile = $this->getOutFile();
        $binary = $this->getBinary();

        $pdf = new Pdf;
        $pdf->binary = $binary;
        $pdf->addPage('Test', array(), Pdf::TYPE_HTML);
        $pdf->saveAs($outFile);
        $this->assertRegexp('/tmp_wkhtmlto_pdf_.*?\.html/', $pdf->getCommand()->getExecCommand());
        unlink($outFile);
    }
    public function testCanAddXmlPageFromStringByType()
    {
        $outFile = $this->getOutFile();
        $binary = $this->getBinary();

        $pdf = new Pdf;
        $pdf->binary = $binary;
        $pdf->addPage('Test', array(), Pdf::TYPE_XML);
        $pdf->saveAs($outFile);
        $this->assertRegexp('/tmp_wkhtmlto_pdf_.*?\.xml/', $pdf->getCommand()->getExecCommand());
        unlink($outFile);
    }



    // Cover page
    public function testCanAddCoverFromFile()
    {
        $inFile = $this->getHtmlAsset();
        $outFile = $this->getOutFile();
        $binary = $this->getBinary();

        $pdf = new Pdf;
        $pdf->binary = $binary;
        $this->assertInstanceOf('mikehaertl\wkhtmlto\Pdf', $pdf->addCover($inFile));
        $this->assertTrue($pdf->saveAs($outFile));

        $tmpFile = $pdf->getPdfFilename();
        $command = (string)$pdf->getCommand();
        $this->assertEquals("$binary cover '$inFile' '$tmpFile'", $command);
        unlink($outFile);
    }
    public function testCanAddCoverFromHtmlString()
    {
        $outFile = $this->getOutFile();
        $binary = $this->getBinary();

        $pdf = new Pdf;
        $pdf->binary = $binary;
        $this->assertInstanceOf('mikehaertl\wkhtmlto\Pdf', $pdf->addCover('<html><h1>Test</h1></html>'));
        $this->assertTrue($pdf->saveAs($outFile));
        $this->assertFileExists($outFile);

        $tmpFile = $pdf->getPdfFilename();
        $this->assertRegExp("#$binary cover '[^ ]+' '$tmpFile'#", (string) $pdf->getCommand());
        unlink($outFile);
    }
    public function testCanAddCoverFromUrl()
    {
        $url = self::URL;
        $outFile = $this->getOutFile();
        $binary = $this->getBinary();

        $pdf = new Pdf;
        $pdf->binary = $binary;
        $this->assertInstanceOf('mikehaertl\wkhtmlto\Pdf', $pdf->addCover($url));
        $this->assertTrue($pdf->saveAs($outFile));
        $this->assertFileExists($outFile);

        $tmpFile = $pdf->getPdfFilename();
        $this->assertEquals("$binary cover '$url' '$tmpFile'", (string) $pdf->getCommand());
        unlink($outFile);
    }

    // Add Toc
    public function testCanAddToc()
    {
        $outFile = $this->getOutFile();
        $binary = $this->getBinary();

        $pdf = new Pdf('<html><h1>Test</h1></html>');
        $pdf->binary = $binary;
        $this->assertInstanceOf('mikehaertl\wkhtmlto\Pdf', $pdf->addToc());
        $this->assertTrue($pdf->saveAs($outFile));
        $this->assertFileExists($outFile);

        $tmpFile = $pdf->getPdfFilename();
        $this->assertRegExp("#$binary '[^ ]+' toc '$tmpFile'#", (string) $pdf->getCommand());
        unlink($outFile);
    }

    public function testToString()
    {
        $outFile = $this->getOutFile();
        $binary = $this->getBinary();

        $pdf = new Pdf('<html><h1>Test</h1></html>');
        $pdf->binary = $binary;

        $this->assertTrue($pdf->saveAs($outFile));
        $this->assertFileExists($outFile);

        $this->assertEquals(file_get_contents($outFile), $pdf->toString());
        unlink($outFile);
    }

    // Options
    public function testCanPassGlobalOptionsInConstructor()
    {
        $inFile = $this->getHtmlAsset();
        $outFile = $this->getOutFile();
        $binary = $this->getBinary();

        $pdf = new Pdf(array(
            'binary' => $binary,
            'no-outline',
            'margin-top'    => 0,
            'allow' => array(
                '/tmp',
                '/test',
            ),
        ));
        $this->assertInstanceOf('mikehaertl\wkhtmlto\Pdf', $pdf->addPage($inFile));
        $this->assertTrue($pdf->saveAs($outFile));

        $tmpFile = $pdf->getPdfFilename();
        $this->assertFileExists($outFile);
        $this->assertEquals("$binary --no-outline --margin-top '0' --allow '/tmp' --allow '/test' '$inFile' '$tmpFile'", (string) $pdf->getCommand());
        unlink($outFile);
    }
    public function testCanSetGlobalOptions()
    {
        $inFile = $this->getHtmlAsset();
        $outFile = $this->getOutFile();
        $binary = $this->getBinary();

        $pdf = new Pdf;
        $pdf->setOptions(array(
            'binary' => $binary,
            'no-outline',
            'margin-top'    => 0,
        ));
        $this->assertInstanceOf('mikehaertl\wkhtmlto\Pdf', $pdf->addPage($inFile));
        $this->assertTrue($pdf->saveAs($outFile));
        $this->assertFileExists($outFile);

        $tmpFile = $pdf->getPdfFilename();
        $this->assertEquals("$binary --no-outline --margin-top '0' '$inFile' '$tmpFile'", (string) $pdf->getCommand());
        unlink($outFile);
    }
    public function testSetPageCoverAndTocOptions()
    {
        $inFile = $this->getHtmlAsset();
        $outFile = $this->getOutFile();
        $binary = $this->getBinary();

        $pdf = new Pdf(array(
            'binary' => $binary,
            'no-outline',
            'margin-top'    => 0,
            'header-center' => 'test {x} {y}',
        ));
        $this->assertInstanceOf('mikehaertl\wkhtmlto\Pdf', $pdf->addPage($inFile, array(
            'no-background',
            'zoom' => 1.5,
            'cookie' => array('name'=>'value'),
            'replace' => array(
                '{x}' => 'x',
                '{y}' => '',
            ),
        )));
        $this->assertInstanceOf('mikehaertl\wkhtmlto\Pdf', $pdf->addCover($inFile, array(
            'replace' => array(
                '{x}' => 'a',
                '{y}' => 'b',
            ),
        )));
        $this->assertInstanceOf('mikehaertl\wkhtmlto\Pdf', $pdf->addToc(array(
            'disable-dotted-lines'
        )));
        $this->assertTrue($pdf->saveAs($outFile));
        $this->assertFileExists($outFile);

        $tmpFile = $pdf->getPdfFilename();
        $this->assertEquals("$binary --no-outline --margin-top '0' --header-center 'test {x} {y}' '$inFile' --no-background --zoom '1.5' --cookie 'name' 'value' --replace '{x}' 'x' --replace '{y}' '' cover '$inFile' --replace '{x}' 'a' --replace '{y}' 'b' toc --disable-dotted-lines '$tmpFile'", (string) $pdf->getCommand());
        unlink($outFile);
    }
    public function testCanAddHeaderAndFooterAsHtml()
    {
        $inFile = $this->getHtmlAsset();
        $outFile = $this->getOutFile();
        $binary = $this->getBinary();

        $pdf = new Pdf(array(
            'binary' => $binary,
            'header-html' => '<h1>Header</h1>',
            'footer-html' => '<h1>Footer</h1>',
        ));
        $this->assertInstanceOf('mikehaertl\wkhtmlto\Pdf', $pdf->addPage($inFile));
        $this->assertTrue($pdf->saveAs($outFile));
        $this->assertFileExists($outFile);

        $tmpFile = $pdf->getPdfFilename();
        $this->assertRegExp("#$binary --header-html '/tmp/[^ ]+' --footer-html '/tmp/[^ ]+' '$inFile' '$tmpFile'#", (string) $pdf->getCommand());
        unlink($outFile);
    }
    public function testCanAddHeaderAndFooterAsHtmlToPagesAndCoverAndToc()
    {
        $inFile = $this->getHtmlAsset();
        $outFile = $this->getOutFile();
        $binary = $this->getBinary();

        $pdf = new Pdf(array(
            'binary' => $binary,
        ));
        $this->assertInstanceOf('mikehaertl\wkhtmlto\Pdf', $pdf->addPage('<html>test</html>', array(
            'header-html' => '<h1>Page Header</h1>',
            'footer-html' => '<h1>Page Footer</h1>',
        )));
        $this->assertInstanceOf('mikehaertl\wkhtmlto\Pdf', $pdf->addCover($inFile, array(
            'header-html' => '<h1>Cover Header</h1>',
            'footer-html' => '<h1>Cover Footer</h1>',
        )));
        $this->assertInstanceOf('mikehaertl\wkhtmlto\Pdf', $pdf->addToc(array(
            'header-html' => '<h1>Toc Header</h1>',
            'footer-html' => '<h1>Toc Footer</h1>',
        )));
        $this->assertTrue($pdf->saveAs($outFile));
        $this->assertFileExists($outFile);

        $tmpFile = $pdf->getPdfFilename();
        $this->assertRegExp("#$binary '/tmp/[^ ]+\.html' --header-html '/tmp/[^ ]+\.html' --footer-html '/tmp/[^ ]+\.html' cover '$inFile' --header-html '/tmp/[^ ]+\.html' --footer-html '/tmp/[^ ]+\.html' toc --header-html '/tmp/[^ ]+\.html' --footer-html '/tmp/[^ ]+\.html' '$tmpFile'#", (string) $pdf->getCommand());
        unlink($outFile);
    }


    // Xvfb
    public function testCanUseXvfbRun()
    {
        $inFile = $this->getHtmlAsset();
        $outFile = $this->getOutFile();
        $binary = $this->getBinary();

        $pdf = new Pdf(array(
            'binary' => $binary,
            'commandOptions' => array(
                'enableXvfb' => true,
            ),
        ));

        $this->assertInstanceOf('mikehaertl\wkhtmlto\Pdf', $pdf->addPage($inFile));
        $this->assertTrue($pdf->saveAs($outFile));

        $tmpFile = $pdf->getPdfFilename();
        $this->assertEquals("xvfb-run -a --server-args=\"-screen 0, 1024x768x24\" $binary '$inFile' '$tmpFile'", (string) $pdf->getCommand());
        unlink($outFile);
    }



    protected function getBinary()
    {
        return '/usr/local/bin/wkhtmltopdf';
    }

    protected function getHtmlAsset()
    {
        return __DIR__.'/assets/test.html';
    }

    protected function getOutFile()
    {
        return __DIR__.'/test.pdf';
    }
}
