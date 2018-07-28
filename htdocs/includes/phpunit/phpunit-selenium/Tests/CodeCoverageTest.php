<?php
class CodeCoverageTest extends PHPUnit_Extensions_Selenium2TestCase
{
    protected $coverageScriptUrl = 'http://localhost/phpunit_coverage.php';

    public function setUp()
    {
        $this->markTestIncomplete('Would require PHP 5.4 for running .php files on the server');
        $this->setBrowser(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM2_BROWSER);
        $this->setBrowserUrl('http://localhost/');
    }

    public function testCoverageIsRetrieved()
    {
        $this->url('example.php');
    }
}
