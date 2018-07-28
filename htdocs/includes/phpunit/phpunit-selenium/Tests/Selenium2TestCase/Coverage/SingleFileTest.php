<?php
class Tests_Selenium2TestCase_Coverage_SingleFileTest extends PHPUnit_Framework_TestCase
{
    private $dummyTestId = 'ns_dummyTestId';

    public function setUp()
    {
        if (!extension_loaded('xdebug')) {
            $this->markTestSkipped('Needs xdebug to run');
        }
        $this->coverageFilePattern = __DIR__ . '/*.' . $this->dummyTestId;
        $this->dummyClassSourceFile = __DIR__ . '/DummyClass.php';
    }

    public function testExecutingAFileWithThePrependedAndAppendedCoverageScriptsProducesACoverageData()
    {
        $this->clearCoverageFiles();

        exec('php ' . __DIR__ . '/singleFile.php');
        $coverageFiles = glob($this->coverageFilePattern);
        $this->assertEquals(1, count($coverageFiles));

        $content = unserialize(file_get_contents($coverageFiles[0]));
        $dummyClassCoverage = $content[$this->dummyClassSourceFile];
        $this->assertCovered(6, $dummyClassCoverage);
        $this->assertNotCovered(11, $dummyClassCoverage);

        return $dummyClassCoverage;
    }

    /**
     * @depends testExecutingAFileWithThePrependedAndAppendedCoverageScriptsProducesACoverageData
     */
    public function testTheCoverageScriptReturnsTheContentOfASpecificCoverageFile($expectedDummyClassCoverage)
    {
        $coverage = unserialize(exec('php ' . __DIR__ . '/singleFileCoverage.php ' . $this->dummyTestId));
        $dummyClassCoverage = $coverage[$this->dummyClassSourceFile];
        $this->assertEquals($expectedDummyClassCoverage, $dummyClassCoverage['coverage']);
    }

    private function clearCoverageFiles()
    {
        $coverageFiles = glob($this->coverageFilePattern);
        foreach ($coverageFiles as $file) {
            unlink($file);
        }
    }

    private function assertCovered($line, array $fileCoverage)
    {
        $this->assertEquals(1, $fileCoverage[$line]);
    }

    private function assertNotCovered($line, array $fileCoverage)
    {
        $this->assertEquals(-1, $fileCoverage[$line]);
    }
}
