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
 * @author     Christian Becker <chris@beckr.org>
 * @copyright  2010-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.phpunit.de/
 * @since      
 */

/**
 * Indicates an exception as a result of a non-sucessful WebDriver response status code.
 *
 * @package    PHPUnit_Selenium
 * @author     Christian Becker <chris@beckr.org>
 * @copyright  2010-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version    Release: @package_version@
 * @link       http://www.phpunit.de/
 * @since      
 */
class PHPUnit_Extensions_Selenium2TestCase_WebDriverException extends PHPUnit_Extensions_Selenium2TestCase_Exception
{
    /* @see http://code.google.com/p/selenium/wiki/JsonWireProtocol#Response_Status_Codes */
    const Success = 0;
    const NoSuchDriver = 6;
    const NoSuchElement = 7;
    const NoSuchFrame = 8;
    const UnknownCommand = 9;
    const StaleElementReference = 10;
    const ElementNotVisible = 11;
    const InvalidElementState = 12;
    const UnknownError = 13;
    const ElementIsNotSelectable = 15;
    const JavaScriptError = 17;
    const XPathLookupError = 19;
    const Timeout = 21;
    const NoSuchWindow = 23;
    const InvalidCookieDomain = 24;
    const UnableToSetCookie = 25;
    const UnexpectedAlertOpen = 26;
    const NoAlertOpenError = 27;
    const ScriptTimeout = 28;
    const InvalidElementCoordinates = 29;
    const IMENotAvailable = 30;
    const IMEEngineActivationFailed = 31;
    const InvalidSelector = 32;
    const SessionNotCreatedException = 33;
    const MoveTargetOutOfBounds = 34;
}