<?php
/**
 * Tests for PHPUnit_Extensions_SeleniumTestCase.
 *
 * @package    PHPUnit_Selenium
 * @author     Giorgio Sironi <info@giorgiosironi.com>
 * @copyright  2010-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.phpunit.de/
 */
class Extensions_Selenium2TestCase_CustomDesiredCapabilitiesTest extends Tests_Selenium2TestCase_BaseTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->setDesiredCapabilities(array(
            'platform' => 'ANY'
        ));
    }

    public function testOpen()
    {
        $this->url('html/test_open.html');
        $this->assertStringEndsWith('html/test_open.html', $this->url());
    }
}
