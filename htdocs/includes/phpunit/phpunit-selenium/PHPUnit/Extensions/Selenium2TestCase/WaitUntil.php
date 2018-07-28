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
 * @since      File available since Release 1.2.12
 */

/**
 * The WaitUntil implementation, inspired by Java and .NET clients
 *
 * @package    PHPUnit_Selenium
 * @author     Ivan Kurnosov <zerkms@zerkms.com>
 * @copyright  2010-2011 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version    Release: @package_version@
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 1.2.12
 * @see        http://selenium.googlecode.com/svn/trunk/dotnet/src/WebDriver.Support/UI/WebDriverWait.cs
 * @see        http://selenium.googlecode.com/svn/trunk/java/client/src/org/openqa/selenium/support/ui/FluentWait.java
 */
class PHPUnit_Extensions_Selenium2TestCase_WaitUntil
{
    /**
     * PHPUnit Test Case instance
     *
     * @var PHPUnit_Extensions_Selenium2TestCase
     */
    private $_testCase;

    /**
     * Default timeout, ms
     *
     * @var int
     */
    private $_defaultTimeout = 0;

    /**
     * The sleep interval between iterations, ms
     *
     * @var int
     */
    private $_defaultSleepInterval = 500;

    /**
     * @param PHPUnit_Extensions_Selenium2TestCase $testCase
     */
    public function __construct(PHPUnit_Extensions_Selenium2TestCase $testCase)
    {
        $this->_testCase = $testCase;
    }

    /**
     * @param $callback Callback to run until it returns not null or timeout occurs
     * @param null $timeout
     * @return mixed
     * @throws PHPUnit_Extensions_Selenium2TestCase_Exception
     * @throws PHPUnit_Extensions_Selenium2TestCase_WebDriverException
     */
    public function run($callback, $timeout = NULL)
    {
        if (!is_callable($callback)) {
            throw new PHPUnit_Extensions_Selenium2TestCase_Exception('The valid callback is expected');
        }

        // if there was an implicit timeout specified - remember it and temporarily turn it off
        $implicitWait = $this->_testCase->timeouts()->getLastImplicitWaitValue();
        if ($implicitWait) {
            $this->_testCase->timeouts()->implicitWait(0);
        }

        if (is_null($timeout)) {
            $timeout = $this->_defaultTimeout;
        }

        $timeout /= 1000;

        $endTime = microtime(TRUE) + $timeout;

        $lastException = NULL;

        while (TRUE) {
            try {
                $result = call_user_func($callback, $this->_testCase);

                if (!is_null($result)) {
                    if ($implicitWait) {
                        $this->_testCase->timeouts()->implicitWait($implicitWait);
                    }

                    return $result;
                }
            } catch(Exception $e) {
                $lastException = $e;
            }

            if (microtime(TRUE) > $endTime) {
                if ($implicitWait) {
                    $this->_testCase->timeouts()->implicitWait($implicitWait);
                }

                $message = "Timed out after {$timeout} second" . ($timeout != 1 ? 's' : '');
                throw new PHPUnit_Extensions_Selenium2TestCase_WebDriverException($message,
                    PHPUnit_Extensions_Selenium2TestCase_WebDriverException::Timeout, $lastException);
            }

            usleep($this->_defaultSleepInterval * 1000);
        }
    }
}
