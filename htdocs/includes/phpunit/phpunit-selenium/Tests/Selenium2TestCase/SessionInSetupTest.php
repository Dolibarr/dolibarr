<?php
class Tests_Selenium2TestCase_SessionInSetupTest extends PHPUnit_Extensions_Selenium2TestCase
{
    public function setUp()
    {
        $this->setHost(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_HOST);
        $this->setPort((int)PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_PORT);
        $this->setBrowser(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM2_BROWSER);
        $this->setBrowserUrl(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_TESTS_URL);
        $this->prepareSession();
        $this->url('html/test_open.html');
    }

    public function testTheSessionStartedInSetupAndCanBeUsedNow()
    {
        $this->assertStringEndsWith('html/test_open.html', $this->url());
    }
}
