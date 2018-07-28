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
 * Object representing a DOM element.
 *
 * @package    PHPUnit_Selenium
 * @author     Giorgio Sironi <info@giorgiosironi.com>
 * @copyright  2010-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version    Release: @package_version@
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 1.2.0
 * @method string attribute($name) Retrieves an element's attribute
 * @method void clear() Empties the content of a form element.
 * @method void click() Clicks on element
 * @method string css($propertyName) Retrieves the value of a CSS property
 * @method bool displayed() Checks an element's visibility
 * @method bool enabled() Checks a form element's state
 * @method bool equals(PHPUnit_Extensions_Selenium2TestCase_Element $another) Checks if the two elements are the same on the page
 * @method array rect() Retrieves the element's coordinates: keys 'x', 'y', 'width' and 'height' in the returned array
 * @method array location() Retrieves the element's position in the page: keys 'x' and 'y' in the returned array
 * @method bool selected() Checks the state of an option or other form element
 * @method array size() Retrieves the dimensions of the element: 'width' and 'height' of the returned array
 * @method void submit() Submits a form; can be called on its children
 * @method string text() Get content of ordinary elements
 */
class PHPUnit_Extensions_Selenium2TestCase_Element
    extends PHPUnit_Extensions_Selenium2TestCase_Element_Accessor
{
    /**
     * @return \self
     * @throws InvalidArgumentException
     */
    public static function fromResponseValue(
            array $value,
            PHPUnit_Extensions_Selenium2TestCase_URL $parentFolder,
            PHPUnit_Extensions_Selenium2TestCase_Driver $driver)
    {
        if (!isset($value['ELEMENT'])) {
            throw new InvalidArgumentException('Element not found.');
        }
        $url = $parentFolder->descend($value['ELEMENT']);
        return new self($driver, $url);
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->url->lastSegment();
    }

    /**
     * @return array    class names
     */
    protected function initCommands()
    {
        return array(
            'attribute' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_Attribute',
            'clear' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_GenericPost',
            'click' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_Click',
            'css' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_Css',
            'displayed' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_GenericAccessor',
            'enabled' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_GenericAccessor',
            'equals' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_Equals',
            'location' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_GenericAccessor',
            'name' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_GenericAccessor',
            'rect' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_Rect',
            'selected' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_GenericAccessor',
            'size' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_GenericAccessor',
            'submit' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_GenericPost',
            'text' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_GenericAccessor',
            'value' => 'PHPUnit_Extensions_Selenium2TestCase_ElementCommand_Value',
            'tap' => $this->touchCommandFactoryMethod('touch/click'),
            'scroll' => $this->touchCommandFactoryMethod('touch/scroll'),
            'doubletap' => $this->touchCommandFactoryMethod('touch/doubleclick'),
            'longtap' => $this->touchCommandFactoryMethod('touch/longclick'),
            'flick' => $this->touchCommandFactoryMethod('touch/flick')
        );
    }

    protected function getSessionUrl()
    {
        return $this->url->ascend()->ascend();
    }

    private function touchCommandFactoryMethod($urlSegment)
    {
        $url = $this->getSessionUrl()->addCommand($urlSegment);
        $self = $this;
        return function ($jsonParameters, $commandUrl) use ($url, $self) {
            if ((is_array($jsonParameters) &&
                    !isset($jsonParameters['element'])) ||
                    is_null($jsonParameters)) {
                $jsonParameters['element'] = $self->getId();
            }
            return new PHPUnit_Extensions_Selenium2TestCase_ElementCommand_GenericPost($jsonParameters, $url);
        };
    }

    /**
     * Retrieves the tag name
     * @return string
     */
    public function name()
    {
        return strtolower(parent::name());
    }

    /**
     * Generates an array that is structured as the WebDriver Object of the JSONWireProtocoll
     *
     * @return array
     */
    public function toWebDriverObject()
    {
        return array('ELEMENT' => (string)$this->getId());
    }

    /**
     * Get or set value of form elements. If the element already has a value, the set one will be appended to it.
     * Created **ONLY** for keeping backward compatibility, since in selenium v2.42.0 it was removed
     * The currently recommended solution is to use `$element->attribute('value')`
     * @see https://code.google.com/p/selenium/source/detail?r=953007b48e83f90450f3e41b11ec31e2928f1605
     * @see https://code.google.com/p/selenium/source/browse/java/CHANGELOG
     *
     * @param string $newValue
     * @return null|string
     */
    public function value($newValue = NULL)
    {
        if ($newValue !== NULL) {
            return parent::value($newValue);
        }

        return $this->attribute('value');
    }
}
