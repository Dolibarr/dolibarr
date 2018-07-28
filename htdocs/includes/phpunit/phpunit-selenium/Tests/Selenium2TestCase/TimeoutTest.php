<?php
class Tests_Selenium2TestCase_TimeoutTest extends Tests_Selenium2TestCase_BaseTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->setSeleniumServerRequestsTimeout(60);
    }

    public function testOpen()
    {
        $this->url('html/test_open.html');
        $this->assertStringEndsWith('html/test_open.html', $this->url());
    }

    /**
     * @expectedException PHPUnit_Extensions_Selenium2TestCase_Exception
     */
    public function testAnImplicitWaitValueToRespectOnTheServerMustBeSmallerThanTheSeleniumServerCallsTimeout()
    {
        $this->timeouts()->implicitWait(120000);
    }

    public function testGetLastImplicitWaitValue()
    {
        $this->assertEquals(0, $this->timeouts()->getLastImplicitWaitValue());
        $this->timeouts()->implicitWait(42);
        $this->assertEquals(42, $this->timeouts()->getLastImplicitWaitValue());
    }
}
