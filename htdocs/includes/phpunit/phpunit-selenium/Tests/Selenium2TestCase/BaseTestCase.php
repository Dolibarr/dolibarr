<?php
class Tests_Selenium2TestCase_BaseTestCase extends PHPUnit_Extensions_Selenium2TestCase
{
    public function setUp()
    {
        $this->setHost(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_HOST);
        $this->setPort((int)PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_PORT);
        $this->setBrowser(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM2_BROWSER);
        if (!defined('PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_TESTS_URL')) {
            $this->markTestSkipped("You must serve the selenium-1-tests folder from an HTTP server and configure the PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_TESTS_URL constant accordingly.");
        }
        $this->setBrowserUrl(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_TESTS_URL);
    }
}
