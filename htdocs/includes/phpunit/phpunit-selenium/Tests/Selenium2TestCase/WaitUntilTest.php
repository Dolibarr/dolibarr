<?php
/**
 * PHPUnit
 *
 * Copyright (c) 2010-2011, Sebastian Bergmann <sb@sebastian-bergmann.de>.
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
 * @author     Ivan Kurnosov <zerkms@zerkms.com>
 * @copyright  2010-2011 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.phpunit.de/
 */

/**
 * Tests for session::waitUntil() command.
 *
 * @package    PHPUnit_Selenium
 * @author     Ivan Kurnosov <zerkms@zerkms.com>
 * @copyright  2010-2011 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.phpunit.de/
 */
class Tests_Selenium2TestCase_WaitUntilTest extends Tests_Selenium2TestCase_BaseTestCase
{
    public function testWaitSuccessfully()
    {
        $this->url('html/test_wait.html');

        $this->waitUntil(function($testCase) {
            try {
                $testCase->byXPath('//div[@id="parent"][contains(text(), "default text")]');
            } catch (PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {
                return TRUE;
            }
        }, 8000);
    }

    /**
     * @expectedException PHPUnit_Extensions_Selenium2TestCase_WebDriverException
     */
    public function testWaitUnsuccessfully()
    {
        $this->url('html/test_wait.html');

        $this->waitUntil(function($testCase) {
            try {
                $testCase->byXPath('//div[@id="parent"][contains(text(), "default text")]');
            } catch (PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {
                return TRUE;
            }
        }, 42);
    }

    /**
     * @expectedException PHPUnit_Extensions_Selenium2TestCase_Exception
     * @expectedExceptionMessage The valid callback is expected
     */
    public function testInvalidCallback()
    {
        $this->waitUntil('not a callback');
    }

    public function testImplicitWaitIsRestoredAfterFailure()
    {
        $this->url('html/test_wait.html');
        $this->timeouts()->implicitWait(7000);

        try {
            $this->waitUntil(function($testCase) {
                $testCase->byId('testBox');
                return TRUE;
            });
            $this->fail('Should fail because of the element not exists there yet');
        } catch (PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {}

        // in this case - element should be found, because of the implicitWait
        $element = $this->byId('testBox');
        $this->assertEquals('testBox', $element->attribute('id'));
    }

    public function testImplicitWaitIsRestoredAfterSuccess()
    {
        $this->url('html/test_wait.html');
        $this->timeouts()->implicitWait(8000);

        $this->waitUntil(function($testCase) {
            $testCase->byId('parent');
            return TRUE;
        });

        // in this case - element should be found, because we set a 8000ms implicitWait before the waitUntil.
        $element = $this->byId('testBox');
        $this->assertEquals('testBox', $element->attribute('id'));
    }

    public function testReturnValue()
    {
        $result = $this->waitUntil(function() {
            return 'return value';
        });

        $this->assertEquals('return value', $result);
    }
}
