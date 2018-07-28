<?php
class Tests_Selenium2TestCase_Coverage_CookieTest extends Tests_Selenium2TestCase_BaseTestCase
{
    // this is a dummy URL (returns down coverage data in HTML), but Firefox still sets domain cookie, which is what's needed
    protected $coverageScriptUrl = 'http://127.0.0.1:8080/coverage/dummy.html';

    public function run(PHPUnit_Framework_TestResult $result = NULL)
    {
        // make sure code coverage collection is enabled
        if ($result === NULL) {
            $result = $this->createResult();
        }
        if (!$result->getCollectCodeCoverageInformation()) {
            $result->setCodeCoverage(new PHP_CodeCoverage());
        }

        parent::run($result);

        $result->getCodeCoverage()->clear();
    }

    protected function getTestIdCookie()
    {
        return $this->prepareSession()->cookie()->get('PHPUNIT_SELENIUM_TEST_ID');
    }

    public function testTestIdCookieIsSet()
    {
        $this->url('/');
        return $this->getTestIdCookie();
    }

    /**
     * @depends testTestIdCookieIsSet
     */
    public function testTestsHaveUniqueTestIdCookies($previousTestIdCookie)
    {
        $this->url('/');
        $this->assertNotEquals($this->getTestIdCookie(), $previousTestIdCookie);
    }
}
