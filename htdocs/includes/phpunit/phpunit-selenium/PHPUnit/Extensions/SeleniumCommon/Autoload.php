<?php
/**
 * PHPUnit
 *
 * Copyright (c) 2002-2013, Sebastian Bergmann <sebastian@phpunit.de>.
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
 * @author     Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright  2002-2010 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.phpunit.de/
 * @since      File available since Release 1.1.0
 */

require_once 'File/Iterator/Autoload.php';

spl_autoload_register(
  function ($class) {
      static $classes = NULL;
      static $path = NULL;

      if ($classes === NULL) {
          $classes = array(
            'phpunit_extensions_selenium2testcase' => '/Extensions/Selenium2TestCase.php',
            'phpunit_extensions_selenium2testcase_command' => '/Extensions/Selenium2TestCase/Command.php',
            'phpunit_extensions_selenium2testcase_commandsholder' => '/Extensions/Selenium2TestCase/CommandsHolder.php',
            'phpunit_extensions_selenium2testcase_driver' => '/Extensions/Selenium2TestCase/Driver.php',
            'phpunit_extensions_selenium2testcase_element' => '/Extensions/Selenium2TestCase/Element.php',
            'phpunit_extensions_selenium2testcase_element_accessor' => '/Extensions/Selenium2TestCase/Element/Accessor.php',
            'phpunit_extensions_selenium2testcase_element_select' => '/Extensions/Selenium2TestCase/Element/Select.php',
            'phpunit_extensions_selenium2testcase_elementcommand_attribute' => '/Extensions/Selenium2TestCase/ElementCommand/Attribute.php',
            'phpunit_extensions_selenium2testcase_elementcommand_click' => '/Extensions/Selenium2TestCase/ElementCommand/Click.php',
            'phpunit_extensions_selenium2testcase_elementcommand_css' => '/Extensions/Selenium2TestCase/ElementCommand/Css.php',
            'phpunit_extensions_selenium2testcase_elementcommand_equals' => '/Extensions/Selenium2TestCase/ElementCommand/Equals.php',
            'phpunit_extensions_selenium2testcase_elementcommand_genericaccessor' => '/Extensions/Selenium2TestCase/ElementCommand/GenericAccessor.php',
            'phpunit_extensions_selenium2testcase_elementcommand_genericpost' => '/Extensions/Selenium2TestCase/ElementCommand/GenericPost.php',
            'phpunit_extensions_selenium2testcase_elementcommand_value' => '/Extensions/Selenium2TestCase/ElementCommand/Value.php',
            'phpunit_extensions_selenium2testcase_elementcriteria' => '/Extensions/Selenium2TestCase/ElementCriteria.php',
            'phpunit_extensions_selenium2testcase_exception' => '/Extensions/Selenium2TestCase/Exception.php',
            'phpunit_extensions_selenium2testcase_keys' => '/Extensions/Selenium2TestCase/Keys.php',
            'phpunit_extensions_selenium2testcase_keysholder' => '/Extensions/Selenium2TestCase/KeysHolder.php',
            'phpunit_extensions_selenium2testcase_noseleniumexception' => '/Extensions/Selenium2TestCase/NoSeleniumException.php',
            'phpunit_extensions_selenium2testcase_response' => '/Extensions/Selenium2TestCase/Response.php',
            'phpunit_extensions_selenium2testcase_screenshotlistener' => '/Extensions/Selenium2TestCase/ScreenshotListener.php',
            'phpunit_extensions_selenium2testcase_session' => '/Extensions/Selenium2TestCase/Session.php',
            'phpunit_extensions_selenium2testcase_session_cookie' => '/Extensions/Selenium2TestCase/Session/Cookie.php',
            'phpunit_extensions_selenium2testcase_session_cookie_builder' => '/Extensions/Selenium2TestCase/Session/Cookie/Builder.php',
            'phpunit_extensions_selenium2testcase_session_storage' => '/Extensions/Selenium2TestCase/Session/Storage.php',
            'phpunit_extensions_selenium2testcase_session_timeouts' => '/Extensions/Selenium2TestCase/Session/Timeouts.php',
            'phpunit_extensions_selenium2testcase_sessioncommand_acceptalert' => '/Extensions/Selenium2TestCase/SessionCommand/AcceptAlert.php',
            'phpunit_extensions_selenium2testcase_sessioncommand_active' => '/Extensions/Selenium2TestCase/SessionCommand/Active.php',
            'phpunit_extensions_selenium2testcase_sessioncommand_alerttext' => '/Extensions/Selenium2TestCase/SessionCommand/AlertText.php',
            'phpunit_extensions_selenium2testcase_sessioncommand_click' => '/Extensions/Selenium2TestCase/SessionCommand/Click.php',
            'phpunit_extensions_selenium2testcase_sessioncommand_dismissalert' => '/Extensions/Selenium2TestCase/SessionCommand/DismissAlert.php',
            'phpunit_extensions_selenium2testcase_sessioncommand_file' => '/Extensions/Selenium2TestCase/SessionCommand/File.php',
            'phpunit_extensions_selenium2testcase_sessioncommand_frame' => '/Extensions/Selenium2TestCase/SessionCommand/Frame.php',
            'phpunit_extensions_selenium2testcase_sessioncommand_genericaccessor' => '/Extensions/Selenium2TestCase/SessionCommand/GenericAccessor.php',
            'phpunit_extensions_selenium2testcase_sessioncommand_genericattribute' => '/Extensions/Selenium2TestCase/SessionCommand/GenericAttribute.php',
            'phpunit_extensions_selenium2testcase_sessioncommand_keys' => '/Extensions/Selenium2TestCase/SessionCommand/Keys.php',
            'phpunit_extensions_selenium2testcase_sessioncommand_location' => '/Extensions/Selenium2TestCase/SessionCommand/Location.php',
            'phpunit_extensions_selenium2testcase_sessioncommand_moveto' => '/Extensions/Selenium2TestCase/SessionCommand/MoveTo.php',
            'phpunit_extensions_selenium2testcase_sessioncommand_orientation' => '/Extensions/Selenium2TestCase/SessionCommand/Orientation.php',
            'phpunit_extensions_selenium2testcase_sessioncommand_url' => '/Extensions/Selenium2TestCase/SessionCommand/Url.php',
            'phpunit_extensions_selenium2testcase_sessioncommand_window' => '/Extensions/Selenium2TestCase/SessionCommand/Window.php',
            'phpunit_extensions_selenium2testcase_sessionstrategy' => '/Extensions/Selenium2TestCase/SessionStrategy.php',
            'phpunit_extensions_selenium2testcase_sessionstrategy_isolated' => '/Extensions/Selenium2TestCase/SessionStrategy/Isolated.php',
            'phpunit_extensions_selenium2testcase_sessionstrategy_shared' => '/Extensions/Selenium2TestCase/SessionStrategy/Shared.php',
            'phpunit_extensions_selenium2testcase_statecommand' => '/Extensions/Selenium2TestCase/StateCommand.php',
            'phpunit_extensions_selenium2testcase_url' => '/Extensions/Selenium2TestCase/URL.php',
            'phpunit_extensions_selenium2testcase_waituntil' => '/Extensions/Selenium2TestCase/WaitUntil.php',
            'phpunit_extensions_selenium2testcase_webdriverexception' => '/Extensions/Selenium2TestCase/WebDriverException.php',
            'phpunit_extensions_selenium2testcase_window' => '/Extensions/Selenium2TestCase/Window.php',
            'phpunit_extensions_seleniumbrowsersuite' => '/Extensions/SeleniumBrowserSuite.php',
            'phpunit_extensions_seleniumcommon_remotecoverage' => '/Extensions/SeleniumCommon/RemoteCoverage.php',
            'phpunit_extensions_seleniumcommon_exithandler' => '/Extensions/SeleniumCommon/ExitHandler.php',
            'phpunit_extensions_seleniumtestsuite' => '/Extensions/SeleniumTestSuite.php'
          );

          $path = dirname(dirname(dirname(__FILE__)));
      }

      $cn = strtolower($class);

      if (isset($classes[$cn])) {
          require $path . $classes[$cn];
      }
  }
);
