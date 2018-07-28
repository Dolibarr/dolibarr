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
 * @since      File available since Release 1.2.0
 */

/**
 * TestCase class that uses Selenium 2
 * (WebDriver API and JsonWire protocol) to provide
 * the functionality required for web testing.
 *
 * @package    PHPUnit_Selenium
 * @author     Giorgio Sironi <info@giorgiosironi.com>
 * @copyright  2010-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version    Release: @package_version@
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 1.2.0
 * @method void acceptAlert() Press OK on an alert, or confirms a dialog
 * @method mixed alertText() alertText($value = NULL) Gets the alert dialog text, or sets the text for a prompt dialog
 * @method void back()
 * @method \PHPUnit_Extensions_Selenium2TestCase_Element byClassName() byClassName($value)
 * @method \PHPUnit_Extensions_Selenium2TestCase_Element byCssSelector() byCssSelector($value)
 * @method \PHPUnit_Extensions_Selenium2TestCase_Element byId() byId($value)
 * @method \PHPUnit_Extensions_Selenium2TestCase_Element byLinkText() byLinkText($value)
 * @method \PHPUnit_Extensions_Selenium2TestCase_Element byName() byName($value)
 * @method \PHPUnit_Extensions_Selenium2TestCase_Element byTag() byTag($value)
 * @method \PHPUnit_Extensions_Selenium2TestCase_Element byXPath() byXPath($value)
 * @method void click() click(int $button = 0) Click any mouse button (at the coordinates set by the last moveto command).
 * @method void clickOnElement() clickOnElement($id)
 * @method string currentScreenshot() BLOB of the image file
 * @method void dismissAlert() Press Cancel on an alert, or does not confirm a dialog
 * @method void doubleclick() Double clicks (at the coordinates set by the last moveto command).
 * @method \PHPUnit_Extensions_Selenium2TestCase_Element element() element(\PHPUnit_Extensions_Selenium2TestCase_ElementCriteria $criteria) Retrieves an element
 * @method array elements() elements(\PHPUnit_Extensions_Selenium2TestCase_ElementCriteria $criteria) Retrieves an array of Element instances
 * @method string execute() execute($javaScriptCode) Injects arbitrary JavaScript in the page and returns the last
 * @method string executeAsync() executeAsync($javaScriptCode) Injects arbitrary JavaScript and wait for the callback (last element of arguments) to be called
 * @method void forward()
 * @method void frame() frame(mixed $element) Changes the focus to a frame in the page (by frameCount of type int, htmlId of type string, htmlName of type string or element of type \PHPUnit_Extensions_Selenium2TestCase_Element)
 * @method void moveto() moveto(\PHPUnit_Extensions_Selenium2TestCase_Element $element) Move the mouse by an offset of the specificed element.
 * @method void refresh()
 * @method \PHPUnit_Extensions_Selenium2TestCase_Element_Select select() select($element)
 * @method string source() Returns the HTML source of the page
 * @method \PHPUnit_Extensions_Selenium2TestCase_Session_Timeouts timeouts()
 * @method string title()
 * @method void|string url() url($url = NULL)
 * @method PHPUnit_Extensions_Selenium2TestCase_ElementCriteria using() using($strategy) Factory Method for Criteria objects
 * @method void window() window($name) Changes the focus to another window
 * @method string windowHandle() Retrieves the current window handle
 * @method string windowHandles() Retrieves a list of all available window handles
 * @method string keys($string) Send a sequence of key strokes to the active element.
 * @method string file($file_path) Upload a local file. Returns the fully qualified path to the transferred file.
 * @method array log(string $type) Get the log for a given log type. Log buffer is reset after each request.
 * @method array logTypes() Get available log types.
 * @method void closeWindow() Close the current window.
 * @method void close() Close the current window and clear session data.
 * @method \PHPUnit_Extensions_Selenium2TestCase_Element active() Get the element on the page that currently has focus.
 */
abstract class PHPUnit_Extensions_Selenium2TestCase extends PHPUnit_Framework_TestCase
{
    const VERSION = '2.0.3';

    /**
     * @var string  override to provide code coverage data from the server
     */
    protected $coverageScriptUrl;

    /**
     * @var PHPUnit_Extensions_Selenium2TestCase_Session
     */
    private $session;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @var PHPUnit_Extensions_Selenium2TestCase_SessionStrategy
     */
    protected static $sessionStrategy;

    /**
     * @var PHPUnit_Extensions_Selenium2TestCase_SessionStrategy
     */
    protected static $browserSessionStrategy;

    /**
     * @var PHPUnit_Extensions_Selenium2TestCase_SessionStrategy
     */
    protected $localSessionStrategy;

    /**
     * @var array
     */
    private static $lastBrowserParams;

    /**
     * @var string
     */
    private $testId;

    /**
     * @var boolean
     */
    private $collectCodeCoverageInformation;

    /**
     * @var PHPUnit_Extensions_Selenium2TestCase_KeysHolder
     */
    private $keysHolder;

    /**
     * @param boolean
     */
    public static function shareSession($shareSession)
    {
        if (!is_bool($shareSession)) {
            throw new InvalidArgumentException("The shared session support can only be switched on or off.");
        }
        if (!$shareSession) {
            self::$sessionStrategy = self::defaultSessionStrategy();
        } else {
            self::$sessionStrategy = new PHPUnit_Extensions_Selenium2TestCase_SessionStrategy_Shared(self::defaultSessionStrategy());
        }
    }

    private static function sessionStrategy()
    {
        if (!self::$sessionStrategy) {
            self::$sessionStrategy = self::defaultSessionStrategy();
        }
        return self::$sessionStrategy;
    }

    private static function defaultSessionStrategy()
    {
        return new PHPUnit_Extensions_Selenium2TestCase_SessionStrategy_Isolated;
    }

    public function __construct($name = NULL, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->parameters = array(
            'host' => 'localhost',
            'port' => 4444,
            'browser' => NULL,
            'browserName' => NULL,
            'desiredCapabilities' => array(),
            'seleniumServerRequestsTimeout' => 60
        );

        $this->keysHolder = new PHPUnit_Extensions_Selenium2TestCase_KeysHolder();
    }

    public function setupSpecificBrowser($params)
    {
        $this->setUpSessionStrategy($params);
        $params = array_merge($this->parameters, $params);
        $this->setHost($params['host']);
        $this->setPort($params['port']);
        $this->setBrowser($params['browserName']);
        $this->parameters['browser'] = $params['browser'];
        $this->setDesiredCapabilities($params['desiredCapabilities']);
        $this->setSeleniumServerRequestsTimeout(
            $params['seleniumServerRequestsTimeout']);
    }

    protected function setUpSessionStrategy($params)
    {
        // This logic enables us to have a session strategy reused for each
        // item in self::$browsers. We don't want them both to share one
        // and we don't want each test for a specific browser to have a
        // new strategy
        if ($params == self::$lastBrowserParams) {
            // do nothing so we use the same session strategy for this
            // browser
        } elseif (isset($params['sessionStrategy'])) {
            $strat = $params['sessionStrategy'];
            if ($strat != "isolated" && $strat != "shared") {
                throw new InvalidArgumentException("Session strategy must be either 'isolated' or 'shared'");
            } elseif ($strat == "isolated") {
                self::$browserSessionStrategy = new PHPUnit_Extensions_Selenium2TestCase_SessionStrategy_Isolated;
            } else {
                self::$browserSessionStrategy = new PHPUnit_Extensions_Selenium2TestCase_SessionStrategy_Shared(self::defaultSessionStrategy());
            }
        } else {
            self::$browserSessionStrategy = self::defaultSessionStrategy();
        }
        self::$lastBrowserParams = $params;
        $this->localSessionStrategy = self::$browserSessionStrategy;

    }

    private function getStrategy()
    {
        if ($this->localSessionStrategy) {
            return $this->localSessionStrategy;
        } else {
            return self::sessionStrategy();
        }
    }

    public function prepareSession()
    {
        try {
            if (!$this->session) {
                $this->session = $this->getStrategy()->session($this->parameters);
            }
        } catch (PHPUnit_Extensions_Selenium2TestCase_NoSeleniumException $e) {
            $this->markTestSkipped("The Selenium Server is not active on host {$this->parameters['host']} at port {$this->parameters['port']}.");
        }
        return $this->session;
    }

    public function run(PHPUnit_Framework_TestResult $result = NULL)
    {
        $this->testId = get_class($this) . '__' . $this->getName();

        if ($result === NULL) {
            $result = $this->createResult();
        }

        $this->collectCodeCoverageInformation = $result->getCollectCodeCoverageInformation();

        parent::run($result);

        if ($this->collectCodeCoverageInformation) {
            $coverage = new PHPUnit_Extensions_SeleniumCommon_RemoteCoverage(
                $this->coverageScriptUrl,
                $this->testId
            );
            $result->getCodeCoverage()->append(
                $coverage->get(), $this
            );
        }

        // do not call this before to give the time to the Listeners to run
        $this->getStrategy()->endOfTest($this->session);

        return $result;
    }

    /**
     * @throws RuntimeException
     */
    protected function runTest()
    {
        $this->prepareSession();

        $thrownException = NULL;

        if ($this->collectCodeCoverageInformation) {
            $this->url($this->coverageScriptUrl);   // phpunit_coverage.php won't do anything if the cookie isn't set, which is exactly what we want
            $this->session->cookie()->add('PHPUNIT_SELENIUM_TEST_ID', $this->testId)->set();
        }

        try {
            $this->setUpPage();
            $result = parent::runTest();

            if (!empty($this->verificationErrors)) {
                $this->fail(implode("\n", $this->verificationErrors));
            }
        } catch (Exception $e) {
            $thrownException = $e;
        }
        
        if ($this->collectCodeCoverageInformation) {
            $this->session->cookie()->remove('PHPUNIT_SELENIUM_TEST_ID');
        }

        if (NULL !== $thrownException) {
            throw $thrownException;
        }

        return $result;
    }


    public static function suite($className)
    {
        return PHPUnit_Extensions_SeleniumTestSuite::fromTestCaseClass($className);
    }

    public function onNotSuccessfulTest(Exception $e)
    {
        $this->getStrategy()->notSuccessfulTest();
        parent::onNotSuccessfulTest($e);
    }

    /**
     * Delegate method calls to the Session.
     *
     * @param  string $command
     * @param  array  $arguments
     * @return mixed
     */
    public function __call($command, $arguments)
    {
        if ($this->session === NULL) {
            throw new PHPUnit_Extensions_Selenium2TestCase_Exception("There is currently no active session to execute the '$command' command. You're probably trying to set some option in setUp() with an incorrect setter name. You may consider using setUpPage() instead.");
        }
        $result = call_user_func_array(
          array($this->session, $command), $arguments
        );

        return $result;
    }

    /**
     * @param  string $host
     * @throws InvalidArgumentException
     */
    public function setHost($host)
    {
        if (!is_string($host)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }

        $this->parameters['host'] = $host;
    }

    public function getHost()
    {
        return $this->parameters['host'];
    }

    /**
     * @param  integer $port
     * @throws InvalidArgumentException
     */
    public function setPort($port)
    {
        if (!is_int($port)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'integer');
        }

        $this->parameters['port'] = $port;
    }

    public function getPort()
    {
        return $this->parameters['port'];
    }

    /**
     * @param  string $browser
     * @throws InvalidArgumentException
     */
    public function setBrowser($browserName)
    {
        if (!is_string($browserName)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }

        $this->parameters['browserName'] = $browserName;
    }

    public function getBrowser()
    {
        return $this->parameters['browserName'];
    }

    /**
     * @param  string $browserUrl
     * @throws InvalidArgumentException
     */
    public function setBrowserUrl($browserUrl)
    {
        if (!is_string($browserUrl)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }

        $this->parameters['browserUrl'] = new PHPUnit_Extensions_Selenium2TestCase_URL($browserUrl);
    }

    public function getBrowserUrl()
    {
        if (isset($this->parameters['browserUrl'])) {
            return $this->parameters['browserUrl'];
        }
        return '';
    }

    /**
     * @see http://code.google.com/p/selenium/wiki/JsonWireProtocol
     */
    public function setDesiredCapabilities(array $capabilities)
    {
        $this->parameters['desiredCapabilities'] = $capabilities;
    }


    public function getDesiredCapabilities()
    {
        return $this->parameters['desiredCapabilities'];
    }

    /**
     * @param int $timeout  seconds
     */
    public function setSeleniumServerRequestsTimeout($timeout)
    {
        $this->parameters['seleniumServerRequestsTimeout'] = $timeout;
    }

    public function getSeleniumServerRequestsTimeout()
    {
        return $this->parameters['seleniumServerRequestsTimeout'];
    }

    /**
     * Get test id (generated internally)
     * @return string
     */
    public function getTestId()
    {
        return $this->testId;
    }

    /**
     * Get Selenium2 current session id
     * @return string
     */
    public function getSessionId()
    {
        if ($this->session) {
            return $this->session->id();
        }
        return FALSE;
    }

    /**
     * Wait until callback isn't null or timeout occurs
     *
     * @param $callback
     * @param null $timeout
     * @return mixed
     */
    public function waitUntil($callback, $timeout = NULL)
    {
        $waitUntil = new PHPUnit_Extensions_Selenium2TestCase_WaitUntil($this);
        return $waitUntil->run($callback, $timeout);
    }

    /**
     * Sends a special key
     * Deprecated due to issues with IE webdriver. Use keys() method instead
     * @deprecated
     * @param string $name
     * @throws PHPUnit_Extensions_Selenium2TestCase_Exception
     * @see PHPUnit_Extensions_Selenium2TestCase_KeysHolder
     */
    public function keysSpecial($name)
    {
        $names = explode(',', $name);

        foreach ($names as $key) {
            $this->keys($this->keysHolder->specialKey(trim($key)));
        }
    }

    /**
     * setUp method that is called after the session has been prepared.
     * It is possible to use session-specific commands like url() here.
     */
    public function setUpPage()
    {

    }

    /**
     * Check whether an alert box is present
     */
    public function alertIsPresent()
    {
        try {
            $this->alertText();
            return TRUE;
        } catch (Exception $e) {
            return NULL;
        }
    }
}
