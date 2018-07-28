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
 * @copyright  2010-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.phpunit.de/
 */


/**
 * Provides access to /element and /elements commands
 * 
 * @package    PHPUnit_Selenium
 * @copyright  2010-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version    Release: @package_version@
 * @link       http://www.phpunit.de/
 */
abstract class PHPUnit_Extensions_Selenium2TestCase_Element_Accessor
    extends PHPUnit_Extensions_Selenium2TestCase_CommandsHolder
{

    /**
     * @param string $value     e.g. 'container'
     * @return PHPUnit_Extensions_Selenium2TestCase_Element
     */
    public function byClassName($value)
    {
        return $this->by('class name', $value);
    }

    /**
     * @param string $value     e.g. 'div.container'
     * @return PHPUnit_Extensions_Selenium2TestCase_Element
     */
    public function byCssSelector($value)
    {
        return $this->by('css selector', $value);
    }

    /**
     * @param string $value     e.g. 'uniqueId'
     * @return PHPUnit_Extensions_Selenium2TestCase_Element
     */
    public function byId($value)
    {
        return $this->by('id', $value);
    }

    /**
     * @param string $value     e.g. 'Link text'
     * @return PHPUnit_Extensions_Selenium2TestCase_Element
     */
    public function byLinkText($value)
    {
        return $this->by('link text', $value);
    }

    /**
     * @param string $value     e.g. 'Link te'
     * @return PHPUnit_Extensions_Selenium2TestCase_Element
     */
    public function byPartialLinkText($value)
    {
        return $this->by('partial link text', $value);
    }

    /**
     * @param string $value     e.g. 'email_address'
     * @return PHPUnit_Extensions_Selenium2TestCase_Element
     */
    public function byName($value)
    {
        return $this->by('name', $value);
    }

    /**
     * @param string $value     e.g. 'body'
     * @return PHPUnit_Extensions_Selenium2TestCase_Element
     */
    public function byTag($value)
    {
        return $this->by('tag name', $value);
    }

    /**
     * @param string $value     e.g. '/div[@attribute="value"]'
     * @return PHPUnit_Extensions_Selenium2TestCase_Element
     */
    public function byXPath($value)
    {
        return $this->by('xpath', $value);
    }

    /**
     * @return PHPUnit_Extensions_Selenium2TestCase_Element
     */
    public function element(PHPUnit_Extensions_Selenium2TestCase_ElementCriteria $criteria)
    {
        $value = $this->postCommand('element', $criteria);
        return PHPUnit_Extensions_Selenium2TestCase_Element::fromResponseValue(
                $value, $this->getSessionUrl()->descend('element'), $this->driver);
    }

    /**
     * @return array    instances of PHPUnit_Extensions_Selenium2TestCase_Element
     */
    public function elements(PHPUnit_Extensions_Selenium2TestCase_ElementCriteria $criteria)
    {
        $values = $this->postCommand('elements', $criteria);
        $elements = array();
        foreach ($values as $value) {
            $elements[] =
                PHPUnit_Extensions_Selenium2TestCase_Element::fromResponseValue(
                    $value, $this->getSessionUrl()->descend('element'), $this->driver);
        }
        return $elements;
    }

    /**
     * @param string $strategy
     * @return PHPUnit_Extensions_Selenium2TestCase_ElementCriteria
     */
    public function using($strategy)
    {
        return new PHPUnit_Extensions_Selenium2TestCase_ElementCriteria($strategy);
    }

    /**
     * @return PHPUnit_Extensions_Selenium2TestCase_URL
     */
    protected abstract function getSessionUrl();

    /**
     * @param string $strategy     supported by JsonWireProtocol element/ command
     * @param string $value
     * @return PHPUnit_Extensions_Selenium2TestCase_Element
     */
    private function by($strategy, $value)
    {
        return $this->element($this->using($strategy)->value($value));
    }

}
