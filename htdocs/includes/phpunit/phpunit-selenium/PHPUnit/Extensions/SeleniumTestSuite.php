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
 * @since      File available since Release 1.2.2
 */

/**
 * TestSuite class for Selenium 1 tests
 *
 * @package    PHPUnit_Selenium
 * @author     Giorgio Sironi <info@giorgiosironi.com>
 * @copyright  2010-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version    Release: @package_version@
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 1.2.0
 */
class PHPUnit_Extensions_SeleniumTestSuite extends PHPUnit_Framework_TestSuite
{
    /**
     * Overriding the default: Selenium suites are always built from a TestCase class.
     * @var boolean
     */
    protected $testCase = TRUE;

    /**
     * Making the method public.
     */
    public function addTestMethod(ReflectionClass $class, ReflectionMethod $method)
    {
        return parent::addTestMethod($class, $method);
    }

    /**
     * @param string $className     extending PHPUnit_Extensions_SeleniumTestCase
     * @return PHPUnit_Extensions_SeleniumTestSuite
     */
    public static function fromTestCaseClass($className)
    {
        $suite = new self();
        $suite->setName($className);

        $class            = new ReflectionClass($className);
        $classGroups      = PHPUnit_Util_Test::getGroups($className);
        $staticProperties = $class->getStaticProperties();
        if (isset($staticProperties['browsers'])) {
            $browsers = $staticProperties['browsers'];
        } else if (is_callable("{$className}::browsers")) {
            $browsers = $className::browsers();
        } else {
            $browsers = null;
        }

        //BC: renamed seleneseDirectory -> selenesePath
        if (!isset($staticProperties['selenesePath']) && isset($staticProperties['seleneseDirectory'])) {
            $staticProperties['selenesePath'] = $staticProperties['seleneseDirectory'];
        }

        // Create tests from Selenese/HTML files.
        if (isset($staticProperties['selenesePath']) &&
            (is_dir($staticProperties['selenesePath']) || is_file($staticProperties['selenesePath']))) {

            if (is_dir($staticProperties['selenesePath'])) {
                $files = array_merge(
                  self::getSeleneseFiles($staticProperties['selenesePath'], '.htm'),
                  self::getSeleneseFiles($staticProperties['selenesePath'], '.html')
                );
            } else {
                $files[] = realpath($staticProperties['selenesePath']);
            }

            // Create tests from Selenese/HTML files for multiple browsers.
            if ($browsers) {
                foreach ($browsers as $browser) {
                    $browserSuite = PHPUnit_Extensions_SeleniumBrowserSuite::fromClassAndBrowser($className, $browser);

                    foreach ($files as $file) {
                        self::addGeneratedTestTo($browserSuite,
                          new $className($file, array(), '', $browser),
                          $classGroups
                        );
                    }

                    $suite->addTest($browserSuite);
                }
            }
            else {
                // Create tests from Selenese/HTML files for single browser.
                foreach ($files as $file) {
                    self::addGeneratedTestTo($suite,
                                              new $className($file),
                                              $classGroups);
                }
            }
        }

        // Create tests from test methods for multiple browsers.
        if ($browsers) {
            foreach ($browsers as $browser) {
                $browserSuite = PHPUnit_Extensions_SeleniumBrowserSuite::fromClassAndBrowser($className, $browser);
                foreach ($class->getMethods() as $method) {
                    $browserSuite->addTestMethod($class, $method);
                }
                $browserSuite->setupSpecificBrowser($browser);

                $suite->addTest($browserSuite);
            }
        }
        else {
            // Create tests from test methods for single browser.
            foreach ($class->getMethods() as $method) {
                $suite->addTestMethod($class, $method);
            }
        }

        return $suite;
    }

    private static function addGeneratedTestTo(PHPUnit_Framework_TestSuite $suite, PHPUnit_Framework_TestCase $test, $classGroups)
    {
        list ($methodName, ) = explode(' ', $test->getName());
        $test->setDependencies(
              PHPUnit_Util_Test::getDependencies(get_class($test), $methodName)
        );
        $suite->addTest($test, $classGroups);
    }

    /**
     * @param  string $directory
     * @param  string $suffix
     * @return array
     */
    private static function getSeleneseFiles($directory, $suffix)
    {
        $facade = new File_Iterator_Facade;

        return $facade->getFilesAsArray($directory, $suffix);
    }

}
