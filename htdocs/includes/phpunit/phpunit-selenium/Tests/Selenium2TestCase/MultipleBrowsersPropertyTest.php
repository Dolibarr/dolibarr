<?php
/**
 * PHPUnit
 *
 * Copyright (c) 2010-2013, Sebastian Bergmann <sebastian@phpunit.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Sebastian Bergmann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    PHPUnit_Selenium
 * @author     Giorgio Sironi <info@giorgiosironi.com>
 * @copyright  2010-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.phpunit.de/
 */

/**
 * Tests for PHPUnit_Extensions_Selenium2TestCase.
 *
 * @package    PHPUnit_Selenium
 * @author     Jonathan Lipps <jlipps@gmail.com>
 * @copyright  2010-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.phpunit.de/
 */
class Extensions_Selenium2TestCaseMultipleBrowsersPropertyTest extends PHPUnit_Extensions_Selenium2TestCase
{
    public static $browsers = array(
        array(
            'browserName' => 'firefox',
            'host'        => 'localhost',
            'port'        => 4444,
            'sessionStrategy' => 'shared'
        ),
        array(
            'browserName' => 'firefox',
            'host'        => 'localhost',
            'port'        => 4444,
            'sessionStrategy' => 'isolated'
        ),
        array(
            'browserName' => 'safari',
            'host'        => 'localhost',
            'port'        => 4444
        )
    );

    private $_browserWeSetUp = '';


    public function setUp()
    {
        if (!defined('PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_TESTS_URL')) {
            $this->markTestSkipped("You must serve the selenium-1-tests folder from an HTTP server and configure the PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_TESTS_URL constant accordingly.");
        }
        if ($this->getBrowser() == 'safari') {
            $this->markTestSkipped("Skipping safari since it might not be present");
        }
        $this->setBrowserUrl(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_TESTS_URL);
    }

    public function setupSpecificBrowser($params)
    {
        $this->_browserWeSetUp = $params['browserName'];
        parent::setupSpecificBrowser($params);
    }

    public function testOpen()
    {
        $this->url(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_TESTS_URL);
        $this->assertEquals($this->_browserWeSetUp, $this->getBrowser());
    }

    public function testSessionIsLaunchedCorrectly()
    {
        $this->url('html/test_open.html');
        $this->assertStringEndsWith('html/test_open.html', $this->url());
    }

    /**
     * @dataProvider urls
     */
    public function testDataProvidersAreRecognized($url)
    {
        $this->url($url);
        $this->assertStringEndsWith($url, $this->url());
        $body = $this->byCssSelector('body');
        $this->assertEquals('This is a test of the open command.', $body->text());
    }

    public static function urls()
    {
        return array(
            array('html/test_open.html')
        );
    }

    public function testTheBrowserNameIsAccessible()
    {
        $this->assertEquals($this->_browserWeSetUp, $this->getBrowser());
    }
}
