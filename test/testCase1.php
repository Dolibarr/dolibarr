<?php
require_once 'PHPUnit/Framework/TestCase.php';
class testCase1 extends PHPUnit_Framework_TestCase
{
    private $odf;
    private $filesNeeded = array('76cdb2bad9582d23c1f6f4d868218d6c' => 'fake.zip' , 'd35214db5b8bf0be4d717b8b5883a2fc' => 'modele_template.odt' , '64e7caa4a131cf1e6411cb3b3ba2b8d4' => 'php_elephant.gif');
    const TEST_PATH = 'testfiles';
    public function __construct()
    {
        parent::__construct();
        if (! is_dir(self::TEST_PATH)) {
            exit('directory ' . self::TEST_PATH . ' not found');
        }
        foreach ($this->filesNeeded as $md5 => $variable) {
            if (! file_exists(self::TEST_PATH . '/' . $variable)) {
                exit("file $variable needed but not found");
            }
            if ($md5 != md5(file_get_contents(self::TEST_PATH . '/' . $variable))) {
                exit("file $variable corrupted");
            }
        }
    }
    protected function setUp()
    {
        $tmp = tempnam(null, md5(uniqid()));
        copy(self::TEST_PATH . '/modele_template.odt', $tmp);
    }
    protected function tearDown()
    {// not needed yet
	}
    public function testCreateWithNoFileFails()
    {
        $this->setExpectedException('odfException');
    }
    public function testCreateWithSomeKindOfZipFileNonOoFails()
    {
        $this->setExpectedException('odfException');
    }
    public function testToString()
    {
        $this->assertType('string', (string) $this->odf);
    }
    public function testExport()
    {
        $this->markTestSkipped('PHPUnit limitation to test ' . __METHOD__);
    }
    public function testSetExistingBlock()
    {
        $this->assertType('Segment', $this->odf->setSegment('loop1'));
        $this->assertRegExp('/loop1/', $this->odf->printDeclaredSegments());
    }
    public function testSetUnexistingBlock()
    {
        $this->setExpectedException('odfException');
        $this->odf->setSegment('IDontExist');
    }
    public function testParse()
    {
        $this->odf->setVars('societe', 'anaska');
        $this->odf->saveToDisk();
        $this->assertRegExp('/anaska/', (string) $this->odf);
    }
    public function testMergeSegments()
    {
        $loop1 = $this->odf->setSegment('loop1');
        $loop1->setVars('testloop1', 'phpunittest');
		$loop1->merge();
        $loop1->setVars('testloop1', 'phpunittest2');
		$loop1->merge();
        $this->odf->mergeSegment($loop1);
        $this->assertRegExp('/phpunittest/', (string) $this->odf);
        $this->assertRegExp('/phpunittest2/', (string) $this->odf);
    }
    public function testMergeImbricatedSegments()
    {
        $loop1 = $this->odf->setSegment('loop1');
		for ($j = 0; $j < 3; $j++) {
			$loop1->setVars('testloop1', 'phpunittest_l1_' . $j);
			for ($i = 0; $i < 3; $i++) {
				$loop1->loop2->testloop2('phpunittest_l2_' . $j . $i);
				$loop1->loop2->merge();
			}
			for ($i = 0; $i <= 3; $i++) {
				$loop1->loop3->testloop3('phpunittest_l3_' . $j . $i);
				$loop1->loop3->merge();
			}
			$loop1->merge();
		}
        $this->odf->mergeSegment($loop1);

		for ($j = 0; $j < 3; $j++) {
			$this->assertRegExp('/phpunittest_l1_' . $j . '/', (string) $this->odf);
			for ($i = 0; $i < 3; $i++) {
				$this->assertRegExp('/phpunittest_l2_' . $j . $i . '/', (string) $this->odf);
			}
			for ($i = 0; $i <= 3; $i++) {
				$this->assertRegExp('/phpunittest_l3_' . $j . $i . '/', (string) $this->odf);
			}
		}
    }
    public function testMergeArraylinesSegments()
    {
        $commande = $this->odf->setSegment('commande');
		for ($j = 0; $j < 3; $j++) {
			$commande->setVars('commande_cod', 'phpunittest_cod_' . $j);
			$commande->setVars('commande_des', 'phpunittest_des_' . $j);
			$commande->setVars('commande_qte', 'phpunittest_qte_' . $j);
			$commande->setVars('commande_prix', 'phpunittest_prix_' . $j);
			$commande->merge();
		}
        $this->odf->mergeSegment($commande);

		for ($j = 0; $j < 3; $j++) {
			$this->assertRegExp('/phpunittest_cod_' . $j . '/', (string) $this->odf);
			$this->assertRegExp('/phpunittest_des_' . $j . '/', (string) $this->odf);
			$this->assertRegExp('/phpunittest_qte_' . $j . '/', (string) $this->odf);
			$this->assertRegExp('/phpunittest_prix_' . $j . '/', (string) $this->odf);
		}
    }
    public function testSaveToDiskToADifferentFile()
    {
        $this->odf->setImage('logo', self::TEST_PATH . '/php_elephant.gif');
        $this->odf->saveToDisk('foo.odt');
        $this->assertFileExists('foo.odt');
        unlink('foo.odt');
    }
    public function testSetImageWithInvalidImageFails()
    {
        $this->setExpectedException('odfException');
        $this->odf->setImage('logo', 'IDontExist');
    }
    public function testSetImage()
    {
        $this->odf->setImage('logo', self::TEST_PATH . '/php_elephant.gif');
        $this->assertRegExp('/php_elephant.gif/', $this->odf->printVars());
        $this->odf->saveToDisk();
        $this->assertRegExp('#xlink:href="Pictures/php_elephant\.gif"#', (string) $this->odf);
    }
    public function testGetUnexistantConfig()
    {
		$this->assertEquals(false, $this->odf->getConfig('IDontExist'));
    }
}
