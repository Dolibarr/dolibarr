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
 * Browser session for Selenium 2: main point of entry for functionality.
 *
 * @package    PHPUnit_Selenium
 * @author     Giorgio Sironi <info@giorgiosironi.com>
 * @copyright  2010-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version    Release: @package_version@
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 1.2.0
 * @method void acceptAlert() Press OK on an alert, or confirms a dialog
 * @method mixed alertText($value = NULL) Gets the alert dialog text, or sets the text for a prompt dialog
 * @method void back()
 * @method void dismissAlert() Press Cancel on an alert, or does not confirm a dialog
 * @method void doubleclick() Double-clicks at the current mouse coordinates (set by moveto).
 * @method string execute(array $javaScriptCode) Injects arbitrary JavaScript in the page and returns the last. See unit tests for usage
 * @method string executeAsync(array $javaScriptCode) Injects arbitrary JavaScript and wait for the callback (last element of arguments) to be called. See unit tests for usage
 * @method void forward()
 * @method void frame(mixed $element) Changes the focus to a frame in the page (by frameCount of type int, htmlId of type string, htmlName of type string or element of type \PHPUnit_Extensions_Selenium2TestCase_Element)
 * @method void moveto(\PHPUnit_Extensions_Selenium2TestCase_Element $element) Move the mouse by an offset of the specificed element.
 * @method void refresh()
 * @method string source() Returns the HTML source of the page
 * @method string title()
 * @method void|string url($url = NULL)
 * @method void window($name) Changes the focus to another window
 * @method string windowHandle() Retrieves the current window handle
 * @method string windowHandles() Retrieves a list of all available window handles
 * @method string keys() Send a sequence of key strokes to the active element.
 * @method string file($file_path) Upload a local file. Returns the fully qualified path to the transferred file.
 * @method array log(string $type) Get the log for a given log type. Log buffer is reset after each request.
 * @method array logTypes() Get available log types.
 */
class PHPUnit_Extensions_Selenium2TestCase_Session
    extends PHPUnit_Extensions_Selenium2TestCase_Element_Accessor
{
    /**
     * @var string  the base URL for this session,
     *              which all relative URLs will refer to
     */
    private $baseUrl;

    /**
     * @var PHPUnit_Extensions_Selenium2TestCase_Session_Timeouts
     */
    private $timeouts;

    /**
     * @var boolean
     */
    private $stopped = FALSE;

    public function __construct($driver,
                                PHPUnit_Extensions_Selenium2TestCase_URL $url,
                                PHPUnit_Extensions_Selenium2TestCase_URL $baseUrl,
                                PHPUnit_Extensions_Selenium2TestCase_Session_Timeouts $timeouts)
    {
        $this->baseUrl = $baseUrl;
        $this->timeouts = $timeouts;
        parent::__construct($driver, $url);
    }

    /**
     * @return string
     */
    public function id()
    {
        return $this->url->lastSegment();
    }

    protected function initCommands()
    {
        $baseUrl = $this->baseUrl;
        return array(
            'acceptAlert' => 'PHPUnit_Extensions_Selenium2TestCase_SessionCommand_AcceptAlert',
            'alertText' => 'PHPUnit_Extensions_Selenium2TestCase_SessionCommand_AlertText',
            'back' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_GenericPost',
            'click' => 'PHPUnit_Extensions_Selenium2TestCase_SessionCommand_Click',
            'buttondown' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_GenericPost',
            'buttonup' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_GenericPost',
            'dismissAlert' => 'PHPUnit_Extensions_Selenium2TestCase_SessionCommand_DismissAlert',
            'doubleclick' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_GenericPost',
            'execute' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_GenericPost',
            'executeAsync' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_GenericPost',
            'forward' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_GenericPost',
            'frame' => 'PHPUnit_Extensions_Selenium2TestCase_SessionCommand_Frame',
            'keys' => 'PHPUnit_Extensions_Selenium2TestCase_SessionCommand_Keys',
            'moveto' => 'PHPUnit_Extensions_Selenium2TestCase_SessionCommand_MoveTo',
            'refresh' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_GenericPost',
            'screenshot' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_GenericAccessor',
            'source' => 'PHPUnit_Extensions_Selenium2TestCase_SessionCommand_GenericAccessor',
            'title' => 'PHPUnit_Extensions_Selenium2TestCase_SessionCommand_GenericAccessor',
            'log' => 'PHPUnit_Extensions_Selenium2TestCase_SessionCommand_Log',
            'logTypes' => $this->attributeCommandFactoryMethod('log/types'),
            'url' => function ($jsonParameters, $commandUrl) use ($baseUrl) {
                return new PHPUnit_Extensions_Selenium2TestCase_SessionCommand_Url($jsonParameters, $commandUrl, $baseUrl);
            },
            'window' => 'PHPUnit_Extensions_Selenium2TestCase_SessionCommand_Window',
            'windowHandle' => 'PHPUnit_Extensions_Selenium2TestCase_SessionCommand_GenericAccessor',
            'windowHandles' => 'PHPUnit_Extensions_Selenium2TestCase_SessionCommand_GenericAccessor',
            'touchDown' => $this->touchCommandFactoryMethod('touch/down'),
            'touchUp' => $this->touchCommandFactoryMethod('touch/up'),
            'touchMove' => $this->touchCommandFactoryMethod('touch/move'),
            'touchScroll' => $this->touchCommandFactoryMethod('touch/scroll'),
            'flick' => $this->touchCommandFactoryMethod('touch/flick'),
            'location' => 'PHPUnit_Extensions_Selenium2TestCase_SessionCommand_Location',
            'orientation' => 'PHPUnit_Extensions_Selenium2TestCase_SessionCommand_Orientation',
            'file' => 'PHPUnit_Extensions_Selenium2TestCase_SessionCommand_File'
        );
    }

    private function attributeCommandFactoryMethod($urlSegment)
    {
        $url = $this->url->addCommand($urlSegment);
        return function ($jsonParameters, $commandUrl) use ($url) {
            return new PHPUnit_Extensions_Selenium2TestCase_SessionCommand_GenericAttribute($jsonParameters, $url);
        };
    }

    private function touchCommandFactoryMethod($urlSegment)
    {
        $url = $this->url->addCommand($urlSegment);
        return function ($jsonParameters, $commandUrl) use ($url) {
            return new PHPUnit_Extensions_Selenium2TestCase_ElementCommand_GenericPost($jsonParameters, $url);
        };
    }

    public function __destruct()
    {
        $this->stop();
    }

    /**
     * @return PHPUnit_Extensions_Selenium2TestCase_URL
     */
    public function getSessionUrl()
    {
        return $this->url;
    }

    /**
     * Closed the browser.
     * @return void
     */
    public function stop()
    {
        if ($this->stopped) {
            return;
        }
        try {
            $this->driver->curl('DELETE', $this->url);
        } catch (Exception $e) {
            // sessions which aren't closed because of sharing can time out on the server. In no way trying to close them should make a test fail, as it already finished before arriving here.
            "Closing sessions: " . $e->getMessage() . "\n";
        }
        $this->stopped = TRUE;
        if ($this->stopped) {
            return;
        }
    }

    /**
     * @return PHPUnit_Extensions_Selenium2TestCase_Element_Select
     */
    public function select(PHPUnit_Extensions_Selenium2TestCase_Element $element)
    {
        $tag = $element->name();
        if ($tag !== 'select') {
            throw new InvalidArgumentException("The element is not a `select` tag but a `$tag`.");
        }
        return PHPUnit_Extensions_Selenium2TestCase_Element_Select::fromElement($element);
    }

    /**
     * @param array   WebElement JSON object
     * @return PHPUnit_Extensions_Selenium2TestCase_Element
     */
    public function elementFromResponseValue($value)
    {
        return PHPUnit_Extensions_Selenium2TestCase_Element::fromResponseValue($value, $this->getSessionUrl()->descend('element'), $this->driver);
    }

    /**
     * @param string $id    id attribute, e.g. 'container'
     * @return void
     */
    public function clickOnElement($id)
    {
        return $this->element($this->using('id')->value($id))->click();
    }

    public function timeouts()
    {
        return $this->timeouts;
    }

    /**
     * @return string   a BLOB of a PNG file
     */
    public function currentScreenshot()
    {
        return base64_decode($this->screenshot());
    }

    /**
     * @return PHPUnit_Extensions_Selenium2TestCase_Window
     */
    public function currentWindow()
    {
        $url = $this->url->descend('window')->descend(trim($this->windowHandle(), '{}'));
        return new PHPUnit_Extensions_Selenium2TestCase_Window($this->driver, $url);
    }

    public function closeWindow()
    {
        $this->driver->curl('DELETE', $this->url->descend('window'));
    }

    /**
     * Get the element on the page that currently has focus.
     *
     * @return PHPUnit_Extensions_Selenium2TestCase_Element
     */
    public function active()
    {
        $command = new PHPUnit_Extensions_Selenium2TestCase_SessionCommand_Active(null, $this->url);
        $response = $this->driver->execute($command);
        return $this->elementFromResponseValue($response->getValue());
    }

    /**
     * @return PHPUnit_Extensions_Selenium2TestCase_Session_Cookie
     */
    public function cookie()
    {
        $url = $this->url->descend('cookie');
        return new PHPUnit_Extensions_Selenium2TestCase_Session_Cookie($this->driver, $url);
    }

    /**
     * @return PHPUnit_Extensions_Selenium2TestCase_Session_Storage
     */
    public function localStorage()
    {
        $url = $this->url->addCommand('localStorage');
        return new PHPUnit_Extensions_Selenium2TestCase_Session_Storage($this->driver, $url);
    }

    public function landscape()
    {
        $this->orientation('LANDSCAPE');
    }

    public function portrait()
    {
        $this->orientation('PORTRAIT');
    }
}
