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
 * Object representing a <select> element.
 *
 * @package    PHPUnit_Selenium
 * @author     Giorgio Sironi <info@giorgiosironi.com>
 * @copyright  2010-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version    Release: @package_version@
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 1.2.2
 */
class PHPUnit_Extensions_Selenium2TestCase_Element_Select
    extends PHPUnit_Extensions_Selenium2TestCase_Element
{
    /**
     * @return PHPUnit_Extensions_Selenium2TestCase_Element_Select
     */
    public static function fromElement(PHPUnit_Extensions_Selenium2TestCase_Element $element)
    {
        return new self($element->driver, $element->url);
    }

    /**
     * @return string
     */
    public function selectedLabel()
    {
        $selectedOption = $this->selectedOption();
        if ($selectedOption === NULL) {
            return '';
        }
        return $selectedOption->text();
    }

    /**
     * @return string
     */
    public function selectedValue()
    {
        $selectedOption = $this->selectedOption();
        if ($selectedOption === NULL) {
            return '';
        }
        return $selectedOption->value();
    }

    /**
     * @return string
     */
    public function selectedId()
    {
        $selectedOption = $this->selectedOption();
        if ($selectedOption === NULL) {
            return '';
        }
        return $selectedOption->attribute('id');
    }

    /**
     * @return array
     */
    public function selectedLabels()
    {
        $labels = array();
        foreach ($this->selectedOptions() as $option) {
            $labels[] = $option->text();
        }
        return $labels;
    }

    /**
     * @return array
     */
    public function selectedValues()
    {
        $values = array();
        foreach ($this->selectedOptions() as $option) {
            $values[] = $option->value();
        }
        return $values;
    }

    /**
     * @return array
     */
    public function selectedIds()
    {
        $id = array();
        foreach ($this->selectedOptions() as $option) {
            $values[] = $option->attribute('id');
        }
        return $id;
    }

    /**
     * @param string $label the text appearing in the option
     * @return void
     */
    public function selectOptionByLabel($label)
    {
        $toSelect = $this->using('xpath')->value(".//option[.='$label']");
        $this->selectOptionByCriteria($toSelect);
    }

    /**
     * @param string $value the value attribute of the option
     * @return void
     */
    public function selectOptionByValue($value)
    {
        $toSelect = $this->using('xpath')->value(".//option[@value='$value']");
        $this->selectOptionByCriteria($toSelect);
    }

    /**
     * @param PHPUnit_Extensions_Selenium2TestCase_ElementCriteria $localCriteria  condiotions for selecting an option
     * @return void
     */
    public function selectOptionByCriteria(PHPUnit_Extensions_Selenium2TestCase_ElementCriteria $localCriteria)
    {
        $option = $this->element($localCriteria);
        if (!$option->selected()) {
            $option->click();
        }
    }

    /**
     * @return array
     */
    public function selectOptionValues()
    {
        $options = array();
        foreach ($this->options() as $option) {
            $options[] = $option->value();
        }
        return $options;
    }

    /**
     * @return array
     */
    public function selectOptionLabels()
    {
        $options = array();
        foreach ($this->options() as $option) {
            $options[] = $option->text();
        }
        return $options;
    }

    /***
     * @return array
     */
    private function selectedOptions()
    {
        $options = array();
        foreach ($this->options() as $option) {
            if ($option->selected()) {
                $options[] = $option;
            }
        }
        return $options;
    }

    public function clearSelectedOptions()
    {
        foreach ($this->selectedOptions() as $option) {
            $option->click();
        }
    }

    private function selectedOption()
    {
        foreach ($this->options() as $option) {
            if ($option->selected()) {
                return $option;
            }
        }
        return NULL;
    }

    private function options()
    {
        $onlyTheOptions = $this->using('css selector')->value('option');
        return $this->elements($onlyTheOptions);
    }
}
