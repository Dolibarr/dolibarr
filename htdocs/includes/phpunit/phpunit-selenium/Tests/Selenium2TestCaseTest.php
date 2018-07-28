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
 */

use PHPUnit_Extensions_Selenium2TestCase_Keys as Keys;

/**
 * Tests for PHPUnit_Extensions_Selenium2TestCase.
 *
 * @package    PHPUnit_Selenium
 * @author     Giorgio Sironi <info@giorgiosironi.com>
 * @copyright  2010-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.phpunit.de/
 */
class Extensions_Selenium2TestCaseTest extends Tests_Selenium2TestCase_BaseTestCase
{
    public function testOpen()
    {
        $this->url('html/test_open.html');
        $this->assertStringEndsWith('html/test_open.html', $this->url());
    }

    public function testVersionCanBeReadFromTheTestCaseClass()
    {
        $this->assertEquals(1, version_compare(PHPUnit_Extensions_Selenium2TestCase::VERSION, "1.2.0"));
    }

    public function testCamelCaseUrlsAreSupported()
    {
        $this->url('html/CamelCasePage.html');
        $this->assertStringEndsWith('html/CamelCasePage.html', $this->url());
        $this->assertEquals('CamelCase page', $this->title());
    }

    public function testAbsoluteUrlsAreSupported()
    {
        $this->url(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_TESTS_URL . 'html/test_open.html');
        $this->assertEquals('Test open', $this->title());
    }

    public function testElementSelection()
    {
        $this->url('html/test_open.html');
        $element = $this->byCssSelector('body');
        $this->assertEquals('This is a test of the open command.', $element->text());

        $this->url('html/test_click_page1.html');
        $link = $this->byId('link');
        $this->assertEquals('Click here for next page', $link->text());
    }

    public function testMultipleElementsSelection()
    {
        $this->url('html/test_element_selection.html');
        $elements = $this->elements($this->using('css selector')->value('div'));
        $this->assertEquals(4, count($elements));
        $this->assertEquals('Other div', $elements[0]->text());
    }

    public function testElementFromResponseValue()
    {
        $this->url('html/test_open.html');
        $elementArray = $this->execute(array(
            'script' => 'return document.body;',
            'args' => array(),
        ));
        $element = $this->elementFromResponseValue($elementArray);
        $this->assertEquals('This is a test of the open command.', $element->text());
    }

    public function testSelectOptionsInMultiselect()
    {
        $this->url('html/test_multiselect.html');
        $this->select($this->byId('theSelect'))->selectOptionByValue("option1");
        $selectedOptions = $this->select($this->byId('theSelect'))->selectedLabels();
        $this->assertEquals(array('First Option','Second Option'), $selectedOptions);
        $this->select($this->byId('theSelect'))->selectOptionByLabel("Fourth Option");
        $selectedOptions = $this->select($this->byId('theSelect'))->selectedLabels();
        $this->assertEquals(array('First Option','Second Option','Fourth Option'), $selectedOptions);
    }

    public function testClearMultiselectSelectedOptions()
    {
        $this->url('html/test_multiselect.html');
        $selectedOptions = $this->select($this->byId('theSelect'))->selectedLabels();
        $this->assertEquals(array('Second Option'), $selectedOptions);
        $this->select($this->byId('theSelect'))->clearSelectedOptions();
        $selectedOptions = $this->select($this->byId('theSelect'))->selectedLabels();
        $this->assertEquals(array(), $selectedOptions);
    }

    public function testTheElementWithFocusCanBeInspected()
    {
        $this->url('html/test_select.html');

        // Select input and check if active
        $theInput = $this->byCssSelector('input[name="theInput"]');
        $theInput->click();
        $this->assertTrue($this->active()->equals($theInput), 'Input not recognized as active.');

        // Select select-group and check if active
        $selectGroup = $this->byCssSelector('#selectWithOptgroup');
        $selectGroup->click();
        $this->assertTrue($this->active()->equals($selectGroup), 'Select-group not recognized as active.');

        // Make sure that input is not recognized as selected
        $this->assertFalse($this->active()->equals($theInput), 'Input falsely recognized as active.');
    }

    public function testActivePageElementReceivesTheKeyStrokes()
    {
        $this->markTestIncomplete('Firefox (geckodriver) does not support this command yet');

        $this->timeouts()->implicitWait(10000);

        $this->url('html/test_send_keys.html');
        $this->byId('q')->click();
        $this->keys('phpunit ');
        $this->assertEquals('phpunit', $this->byId('result')->text());
    }

    public function testElementsCanBeSelectedAsChildrenOfAlreadyFoundElements()
    {
        $this->url('html/test_element_selection.html');
        $parent = $this->byCssSelector('div#parentElement');
        $child = $parent->element($this->using('css selector')->value('span'));
        $this->assertEquals('Child span', $child->text());

        $rows = $this->byCssSelector('table')->elements($this->using('css selector')->value('tr'));
        $this->assertEquals(2, count($rows));
    }

    /**
     * Test on Session and Element
     *
     * @dataProvider getObjectsWithAccessToElement
     */
    public function testShortenedApiForSelectionOfElement($factory)
    {
        $this->url('html/test_element_selection.html');
        $parent = $factory($this);

        $element = $parent->byClassName('theDivClass');
        $this->assertEquals('The right div', $element->text());

        $element = $parent->byCssSelector('div.theDivClass');
        $this->assertEquals('The right div', $element->text());

        $element = $parent->byId('theDivId');
        $this->assertEquals('The right div', $element->text());

        $element = $parent->byName('theDivName');
        $this->assertEquals('The right div', $element->text());

        $element = $parent->byTag('div');
        $this->assertEquals('Other div', $element->text());

        $element = $parent->byXPath('//div[@id]');
        $this->assertEquals('The right div', $element->text());
    }

    public function getObjectsWithAccessToElement()
    {
        return array(
            array(function($s) { return $s; }),
            array(function($s) { return $s->byXPath('//body'); })
        );
    }

    public function testElementsKnowTheirTagName()
    {
        $this->url('html/test_element_selection.html');
        $element = $this->byClassName('theDivClass');
        $this->assertEquals('div', $element->name());
    }

    public function testFormElementsKnowIfTheyAreEnabled()
    {
        $this->url('html/test_form_elements.html');
        $this->assertTrue($this->byId('enabledInput')->enabled());
        $this->assertFalse($this->byId('disabledInput')->enabled());
    }

    public function testElementsKnowTheirAttributes()
    {
        $this->url('html/test_element_selection.html');
        $element = $this->byId('theDivId');
        $this->assertEquals('theDivClass', $element->attribute('class'));
    }

    public function testElementsDiscoverTheirEqualityWithOtherElements()
    {
        $this->url('html/test_element_selection.html');
        $element = $this->byId('theDivId');
        $differentElement = $this->byId('parentElement');
        $equalElement = $this->byId('theDivId');
        $this->assertTrue($element->equals($equalElement));
        $this->assertFalse($element->equals($differentElement));
    }

    public function testElementsKnowWhereTheyAreInThePage()
    {
        $this->url('html/test_element_selection.html');
        $element = $this->byCssSelector('body');
        $location = $element->location();
        $this->assertEquals(0, $location['x']);
        $this->assertEquals(0, $location['y']);
    }

    public function testElementsKnowTheirSize()
    {
        $this->url('html/test_geometry.html');
        $element = $this->byId('rectangle');
        $size = $element->size();
        $this->assertEquals(200, $size['width']);
        $this->assertEquals(100, $size['height']);
    }

    public function testElementsKnowTheirCssPropertiesValues()
    {
        $this->url('html/test_geometry.html');
        $element = $this->byId('colored');
        $this->assertRegExp('/rgb[a]?\(0,\s*0,\s*255[,\s*1]?\)/', $element->css('background-color'));
    }

    public function testClick()
    {
        $this->timeouts()->implicitWait(10000);
        $this->url('html/test_click_page1.html');
        $link = $this->byId('link');
        $link->click();
        $back = $this->byId('previousPage');
        $this->assertEquals('Click Page Target', $this->title());
        $back->click();
        $this->byId('link');
        $this->assertEquals('Click Page 1', $this->title());

        $withImage = $this->byId('linkWithEnclosedImage');
        $withImage->click();
        $back = $this->byId('previousPage');
        $this->assertEquals('Click Page Target', $this->title());
        $back->click();

        $enclosedImage = $this->byId('enclosedImage');
        $enclosedImage->click();
        $back = $this->byId('previousPage');
        $this->assertEquals('Click Page Target', $this->title());
        $back->click();

        $toAnchor = $this->byId('linkToAnchorOnThisPage');
        $toAnchor->click();
        $withOnClick = $this->byId('linkWithOnclickReturnsFalse');
        $this->assertEquals('Click Page 1', $this->title());

        $withOnClick->click();
        $this->assertEquals('Click Page 1', $this->title());
    }

    public function testDoubleclick()
    {
        $this->markTestIncomplete('Moveto command is not in the webdriver specification');

        $this->url('html/test_doubleclick.html');
        $link = $this->byId('link');

        $this->moveto($link);
        $this->doubleclick();

        $this->assertEquals('doubleclicked', $this->alertText());
        $this->acceptAlert();
    }

    public function testByLinkText()
    {
        $this->timeouts()->implicitWait(10000);
        $this->url('html/test_click_page1.html');
        $link = $this->byLinkText('Click here for next page');
        $link->click();
        $this->byId('previousPage');

        $this->assertEquals('Click Page Target', $this->title());
    }

    public function testByPartialLinkText()
    {
        $this->timeouts()->implicitWait(10000);
        $this->url('html/test_click_page1.html');
        $link = $this->byPartialLinkText('next page');
        $link->click();
        $this->byId('previousPage');
        $this->assertEquals('Click Page Target', $this->title());
    }

    public function testClicksOnJavaScriptHref()
    {
        $this->url('html/test_click_javascript_page.html');
        $this->clickOnElement('link');
        $this->assertEquals('link clicked', $this->byId('result')->text());
    }

    public function testTypingViaTheKeyboard()
    {
        $this->url('html/test_type_page1.html');
        $usernameInput = $this->byName('username');
        $usernameInput->value('TestUser');
        $this->assertEquals('TestUser', $usernameInput->value());

        $passwordInput = $this->byName('password');
        $passwordInput->value('testUserPassword');
        $this->assertEquals('testUserPassword', $passwordInput->value());

        $this->clickOnElement('submitButton');
        $h2 = $this->byCssSelector('h2');
        $this->assertRegExp('/Welcome, TestUser!/', $h2->text());
    }

    /**
     * #190
     */
    public function testTypingAddsCharactersToTheCurrentValueOfAnElement()
    {
        $this->url('html/test_type_page1.html');
        $usernameInput = $this->byName('username');
        $usernameInput->value('first');
        $usernameInput->value('second');
        $this->assertEquals('firstsecond', $usernameInput->value());
    }

    /**
     * #165
     */
    public function testNumericValuesCanBeTyped()
    {
        $this->url('html/test_type_page1.html');
        $usernameInput = $this->byName('username');
        $usernameInput->value(1.13);
    }

    public function testFormsCanBeSubmitted()
    {
        $this->url('html/test_type_page1.html');
        $usernameInput = $this->byName('username');
        $usernameInput->value('TestUser');

        $this->byCssSelector('form')->submit();
        $h2 = $this->byCssSelector('h2');
        $this->assertRegExp('/Welcome, TestUser!/', $h2->text());
    }

    /**
     * @depends testTypingViaTheKeyboard
     */
    public function testTextTypedInAreasCanBeCleared()
    {
        $this->url('html/test_type_page1.html');
        $usernameInput = $this->byName('username');
        $usernameInput->value('TestUser');
        $usernameInput->clear();
        $this->assertEquals('', $usernameInput->value());
    }

    public function testTypingNonLatinText()
    {
        $this->url('html/test_type_page1.html');
        $usernameInput = $this->byName('username');
        $usernameInput->value('テストユーザ');
        $this->assertEquals('テストユーザ', $usernameInput->value());
    }

    public function testSelectElements()
    {
        $this->url('html/test_select.html');
        $option = $this->byId('o2');
        $this->assertEquals('Second Option', $option->text());
        $this->assertEquals('option2', $option->value());
        $this->assertTrue($option->selected());
        $option = $this->byId('o3');
        $this->assertFalse($option->selected());
        $option->click();
        $this->assertTrue($option->selected());
    }

    public function testASelectObjectCanBeBuildWithASpecificAPI()
    {
        $this->url('html/test_select.html');
        $select = $this->select($this->byCssSelector('select'));

        // basic
        $this->assertEquals('Second Option', $select->selectedLabel());
        $this->assertEquals('option2', $select->selectedValue());

        // by text, value attribute or generic criteria
        $select->selectOptionByLabel('Fourth Option');
        $this->assertEquals('option4', $select->selectedValue());

        $select->selectOptionByValue('option3');
        $this->assertEquals('Third Option', $select->selectedLabel());

        $select->selectOptionByCriteria($this->using('id')->value('o4'));
        $this->assertEquals('option4', $select->selectedValue());

        // empty values
        $select->selectOptionByValue('');
        $this->assertEquals('Empty Value Option', $select->selectedLabel());

        $select->selectOptionByLabel('');
        $this->assertEquals('', $select->selectedLabel());

    }

    /**
     * Ticket 119
     */
    public function testSelectOptionSelectsDescendantElement()
    {
        $this->url('html/test_select.html');
        $select = $this->select($this->byCssSelector('#secondSelect'));
        $this->assertEquals("option2", $select->selectedValue());

        $select->selectOptionByLabel("First Option");
        $this->assertEquals("option1", $select->selectedValue());

        $select->selectOptionByValue("option2");
        $this->assertEquals("option2", $select->selectedValue());
    }

    /**
     * Ticket 170
     */
    public function testSelectOptgroupDoNotGetInTheWay()
    {
        $this->url('html/test_select.html');
        $select = $this->select($this->byCssSelector('#selectWithOptgroup'));

        $select->selectOptionByLabel("Second");
        $this->assertEquals("2", $select->selectedValue());

        $select->selectOptionByValue("1");
        $this->assertEquals("1", $select->selectedValue());
    }

    public function testCheckboxesCanBeSelectedAndDeselected()
    {
        $this->markTestIncomplete("Flaky: fails on clicking in some browsers.");
        $this->url('html/test_check_uncheck.html');
        $beans = $this->byId('option-beans');
        $butter = $this->byId('option-butter');

        $this->assertTrue($beans->selected());
        $this->assertFalse($butter->selected());

        $butter->click();
        $this->assertTrue($butter->selected());
        $butter->click();
        $this->assertFalse($butter->selected());
    }

    public function testRadioBoxesCanBeSelected()
    {
        $this->url('html/test_check_uncheck.html');
        $spud = $this->byId('base-spud');
        $rice = $this->byId('base-rice');

        $this->assertTrue($spud->selected());
        $this->assertFalse($rice->selected());

        $rice->click();
        $this->assertFalse($spud->selected());
        $this->assertTrue($rice->selected());

        $spud->click();
        $this->assertTrue($spud->selected());
        $this->assertFalse($rice->selected());
    }

    public function testWaitPeriodsAreImplicitInSelection()
    {
        $this->timeouts()->implicitWait(10000);
        $this->url('html/test_delayed_element.html');
        $element = $this->byId('createElementButton')->click();
        $div = $this->byXPath("//div[@id='delayedDiv']");
        $this->assertEquals('Delayed div.', $div->text());
    }

    public function testTimeoutsCanBeDefinedForAsynchronousExecutionOfJavaScript()
    {
        $this->url('html/test_open.html');
        $this->timeouts()->asyncScript(10000);
        $script = 'var callback = arguments[0];
                   window.setTimeout(function() {
                       callback(document.title);
                   }, 1000);
        ';
        $result = $this->executeAsync(array(
            'script' => $script,
            'args'   => array()
        ));
        $this->assertEquals("Test open", $result);
    }

    public function testTheBackAndForwardButtonCanBeUsedToNavigate()
    {
        $this->url('html/test_click_page1.html');
        $this->assertEquals('Click Page 1', $this->title());

        $this->clickOnElement('link');
        $this->byId('previousPage');
        $this->assertEquals('Click Page Target', $this->title());

        $this->back();
        $this->assertEquals('Click Page 1', $this->title());

        $this->forward();
        $this->assertEquals('Click Page Target', $this->title());
    }

    public function testThePageCanBeRefreshed()
    {
        $this->url('html/test_page.slow.html');
        $this->assertStringEndsWith('html/test_page.slow.html', $this->url());
        $this->assertEquals('Slow Loading Page', $this->title());

        $this->clickOnElement('changeSpan');
        $this->assertEquals('Changed the text', $this->byId('theSpan')->text());
        $this->refresh();
        $this->assertEquals('This is a slow-loading page.', $this->byId('theSpan')->text());

        $this->clickOnElement('changeSpan');
        $this->assertEquals('Changed the text', $this->byId('theSpan')->text());
    }

    public function testLinkEventsAreGenerated()
    {
        $this->markTestIncomplete("Waiting for new phpunit-selenium release");
        $this->url('html/test_form_events.html');
        $eventLog = $this->byId('eventlog');
        $eventLog->clear();

        $this->clickOnElement('theLink');
        $this->waitUntil(function () {
            $this->alertIsPresent();
        }, 8000);

        $this->assertEquals('link clicked', $text);
        $this->acceptAlert();
        $this->assertContains('{click(theLink)}', $eventLog->value());
    }

    public function testButtonEventsAreGenerated()
    {
        $this->url('html/test_form_events.html');
        $eventLog = $this->byId('eventlog');
        $eventLog->clear();

        $this->clickOnElement('theButton');

        // Not generated with firefox
        //$this->assertContains('{focus(theButton)}', $eventLog->value());
        $this->assertContains('{click(theButton)}', $eventLog->value());
        $eventLog->clear();

        $this->clickOnElement('theSubmit');
        $this->assertContains('{click(theSubmit)} {submit}', $eventLog->value());
    }

    public function testSelectEventsAreGeneratedbutOnlyIfANewSelectionIsMade()
    {
        $this->url('html/test_form_events.html');
        $select = $this->select($this->byId('theSelect'));
        $eventLog = $this->byId('eventlog');
        $eventLog->clear();

        $select->selectOptionByLabel('First Option');
        $this->assertEquals('option1', $select->selectedValue());
        $this->assertContains('{focus(theSelect)}', $eventLog->value());
        $this->assertContains('{change(theSelect)}', $eventLog->value());

        $eventLog->clear();
        $select->selectOptionByLabel('First Option');
        $this->assertEquals('option1', $select->selectedValue());
        $this->assertEquals('', $eventLog->value());
    }

    public function testRadioEventsAreGenerated()
    {
        $this->markTestIncomplete("Flaky: fails on focus in some browsers.");
        $this->url('html/test_form_events.html');
        $first = $this->byId('theRadio1');
        $second = $this->byId('theRadio2');
        $eventLog = $this->byId('eventlog');

        $this->assertFalse($first->selected());
        $this->assertFalse($second->selected());
        $this->assertEquals('', $eventLog->value());

        $first->click();
        $this->assertContains('{focus(theRadio1)}', $eventLog->value());
        $this->assertContains('{click(theRadio1)}', $eventLog->value());
        $this->assertContains('{change(theRadio1)}', $eventLog->value());
        $this->assertNotContains('theRadio2', $eventLog->value());

        $eventLog->clear();
        $first->click();
        $this->assertContains('{focus(theRadio1)}', $eventLog->value());
        $this->assertContains('{click(theRadio1)}', $eventLog->value());
    }

    public function testCheckboxEventsAreGenerated()
    {
        $this->markTestIncomplete("Flaky: fails on focus in some browsers.");
        $this->url('html/test_form_events.html');
        $checkbox = $this->byId('theCheckbox');
        $eventLog = $this->byId('eventlog');
        $this->assertFalse($checkbox->selected());
        $this->assertEquals('', $eventLog->value());

        $checkbox->click();
        $this->assertContains('{focus(theCheckbox)}', $eventLog->value());
        $this->assertContains('{click(theCheckbox)}', $eventLog->value());
        $this->assertContains('{change(theCheckbox)}', $eventLog->value());

        $eventLog->clear();
        $checkbox->click();
        $this->assertContains('{focus(theCheckbox)}', $eventLog->value());
        $this->assertContains('{click(theCheckbox)}', $eventLog->value());
        $this->assertContains('{change(theCheckbox)}', $eventLog->value());
    }

    public function testTextEventsAreGenerated()
    {
        $this->markTestIncomplete('focus event not generated with firefox (geckodriver)');

        $this->url('html/test_form_events.html');
        $textBox = $this->byId('theTextbox');
        $eventLog = $this->byId('eventlog');
        $this->assertEquals('', $textBox->value());
        $this->assertEquals('', $eventLog->value());

        $textBox->value('first value');
        $this->assertContains('{focus(theTextbox)}', $eventLog->value());
    }

    public function testMouseEventsAreGenerated()
    {
        $this->url('html/test_form_events.html');
        $this->clickOnElement('theTextbox');
        $this->clickOnElement('theButton');
        $eventLog = $this->byId('eventlog');
        $this->assertContains('{mouseover(theTextbox)}', $eventLog->value());
        $this->assertContains('{mousedown(theButton)}', $eventLog->value());
        $this->assertContains('{mouseover(theTextbox)}', $eventLog->value());
        $this->assertContains('{mousedown(theButton)}', $eventLog->value());
    }

    public function testKeyEventsAreGenerated()
    {
        $this->url('html/test_form_events.html');
        $this->byId('theTextbox')->value('t');

        $this->assertContains('{keydown(theTextbox - 84)}'
                           . ' {keypress(theTextbox)}'
                           . ' {keyup(theTextbox - 84)}',
                               $this->byId('eventlog')->value());
    }

    public function testConfirmationsAreHandledAsAlerts()
    {
        $this->markTestIncomplete("Waiting for new phpunit-selenium release");
        $this->url('html/test_confirm.html');
        $this->clickOnElement('confirmAndLeave');
        $text = "";

        $this->waitUntil(function () {
            $this->alertIsPresent();
        }, 8000);
        $this->assertEquals('You are about to go to a dummy page.', $this->alertText());
        $this->dismissAlert();
        $this->assertEquals('Test Confirm', $this->title());

        $this->clickOnElement('confirmAndLeave');

        $this->waitUntil(function () {
            $this->alertIsPresent();
        }, 8000);
        $this->assertEquals('You are about to go to a dummy page.', $this->alertText());
        $this->acceptAlert();
        $this->assertEquals('This is a dummy page.', $this->byId('theSpan')->text());
    }

    public function testPromptsCanBeAnsweredByTyping()
    {
        $this->markTestIncomplete("Waiting for new phpunit-selenium release");
        $this->url('html/test_prompt.html');

        $this->clickOnElement('promptAndLeave');
        $this->waitUntil(function () {
            $this->alertIsPresent();
        }, 8000);
        $this->assertEquals("Type 'yes' and click OK", $this->alertText());
        $this->dismissAlert();
        $this->assertEquals('Test Prompt', $this->title());

        $this->clickOnElement('promptAndLeave');
        $this->waitUntil(function () {
            $this->alertIsPresent();
        }, 8000);
        $this->alertText('yes');
        $this->acceptAlert();
        $this->assertEquals('Dummy Page', $this->title());
    }

    public function testInvisibleElementsDoNotHaveADisplayedText()
    {
        $this->url('html/test_visibility.html');
        $this->assertEquals('A visible paragraph', $this->byId('visibleParagraph')->text());
        $this->assertTrue($this->byId('visibleParagraph')->displayed());

        $this->assertEquals('', $this->byId('hiddenParagraph')->text());
        $this->assertFalse($this->byId('hiddenParagraph')->displayed());

        $this->assertEquals('', $this->byId('suppressedParagraph')->text());
        $this->assertEquals('', $this->byId('classSuppressedParagraph')->text());
        $this->assertEquals('', $this->byId('jsClassSuppressedParagraph')->text());
        $this->assertEquals('', $this->byId('hiddenSubElement')->text());
        $this->assertEquals('sub-element that is explicitly visible', $this->byId('visibleSubElement')->text());
        $this->assertEquals('', $this->byId('suppressedSubElement')->text());
        $this->assertEquals('', $this->byId('jsHiddenParagraph')->text());
    }

    public function testScreenshotsCanBeTakenAtAnyMoment()
    {
        $this->url('html/test_open.html');
        $screenshot = $this->currentScreenshot();
        $this->assertTrue(is_string($screenshot));
        $this->assertTrue(strlen($screenshot) > 0);
        $this->markTestIncomplete('By guaranteeing the size of the window, we could add a deterministic assertion for the image.');
    }

    public function testACurrentWindowHandleAlwaysExist()
    {
        $this->url('html/test_open.html');
        $window  = $this->windowHandle();
        $this->assertTrue(is_string($window));
        $this->assertTrue(strlen($window) > 0);
        $allHandles  = $this->windowHandles();
        $this->assertEquals(array('0' => $window), $allHandles);
    }

    public function testThePageSourceCanBeRead()
    {
        $this->url('html/test_open.html');
        $source = $this->source();

        // No guarantee that it will exactly match the contents of the file
        //$this->assertStringStartsWith('<!--', $source);

        $this->assertContains('<body>', $source);
        $this->assertStringEndsWith('</html>', $source);
    }

    public function testJavaScriptCanBeEmbeddedForExecution()
    {
        $this->url('html/test_open.html');
        $script = 'return document.title;';
        $result = $this->execute(array(
            'script' => $script,
            'args'   => array()
        ));
        $this->assertEquals("Test open", $result);
    }

    public function testAsynchronousJavaScriptCanBeEmbeddedForExecution()
    {
        $this->url('html/test_open.html');
        $script = 'var callback = arguments[0]; callback(document.title);';
        $result = $this->executeAsync(array(
            'script' => $script,
            'args'   => array()
        ));
        $this->assertEquals("Test open", $result);
    }

    public function testInputMethodFrameworksCanBeManagedViaTheApi()
    {
        $this->markTestIncomplete("Need to create an IME object.");
        $this->ime()->availableEngines();
        $this->ime()->activeEngine();
        $this->ime()->activated();
        $this->ime()->deactive();
        $this->ime()->activate();
    }

    public function testDifferentFramesFromTheMainOneCanGetFocusById()
    {
        $this->url('html/test_frames.html');
        $this->frame('my_iframe_id');
        $this->assertEquals('This is a test of the open command.', $this->byCssSelector('body')->text());

        $this->frame(NULL);
        $this->assertContains('This page contains frames.', $this->byCssSelector('body')->text());
    }

    public function testDifferentFramesFromTheMainOneCanGetFocusByFrameCount()
    {
        $this->url('html/test_frames.html');
        $this->frame(0);
        $this->assertEquals('This is a test of the open command.', $this->byCssSelector('body')->text());

        $this->frame(NULL);
        $this->assertContains('This page contains frames.', $this->byCssSelector('body')->text());
    }

    public function testDifferentFramesFromTheMainOneCanGetFocusByName()
    {
        $this->url('html/test_frames.html');
        $this->frame('my_iframe_name');
        $this->assertEquals('This is a test of the open command.', $this->byCssSelector('body')->text());

        $this->frame(NULL);
        $this->assertContains('This page contains frames.', $this->byCssSelector('body')->text());
    }

    public function testDifferentFramesFromTheMainOneCanGetFocusByElement()
    {
        $this->url('html/test_frames.html');
        $frame = $this->byId('my_iframe_id');
        $this->frame($frame);
        $this->assertEquals('This is a test of the open command.', $this->byCssSelector('body')->text());

        $this->frame(NULL);
        $this->assertContains('This page contains frames.', $this->byCssSelector('body')->text());
    }

    public function testDifferentWindowsCanBeFocusedOnDuringATest()
    {
        $this->markTestIncomplete("Bug with title command and popup. See https://bugzilla.mozilla.org/show_bug.cgi?id=1255946");

        $this->url('html/test_select_window.html');
        $this->byId('popupPage')->click();

        $this->window('myPopupWindow');
        $this->assertEquals('Select Window Popup', $this->title());

        $this->window('');
        $this->assertEquals('Select Window Base', $this->title());

        $this->window('myPopupWindow');
        $this->byId('closePage')->click();

        $this->window('');
        $this->assertEquals('Select Window Base', $this->title());
    }

    public function testWindowsCanBeManipulatedAsAnObject()
    {
        $this->timeouts()->implicitWait(10000);
        $this->url('html/test_select_window.html');
        $this->byId('popupPage')->click();

        $this->window('myPopupWindow');
        $popup = $this->currentWindow();
        $this->assertTrue($popup instanceof PHPUnit_Extensions_Selenium2TestCase_Window);
        $popup->size(array('width' => 150, 'height' => 200));
        $size = $popup->size();
        $this->assertEquals(150, $size['width']);
        $this->assertEquals(200, $size['height']);

        $this->byId('closePage')->click();
        $this->window('');
        $this->byId('popupPage');
        $this->assertEquals('Select Window Base', $this->title());
    }

    public function testWindowsCanBeClosed()
    {
        $this->url('html/test_select_window.html');
        $this->byId('popupPage')->click();

        $this->window('myPopupWindow');
        $this->closeWindow();

        $this->window('');
        $this->assertEquals('Select Window Base', $this->title());
        $this->assertEquals(1, count($this->windowHandles()));
    }

    public function testCookiesCanBeSetAndRead()
    {
        $this->url('html/');
        $cookies = $this->cookie();
        $cookies->add('name', 'value')->set();
        $this->assertEquals('value', $cookies->get('name'));
    }

    /**
     * @depends testCookiesCanBeSetAndRead
     */
    public function testCookiesCanBeDeletedOneAtTheTime()
    {
        $this->url('html/');
        $cookies = $this->cookie();
        $cookies->add('name', 'value')->set();
        $cookies->remove('name');
        $this->assertThereIsNoCookieNamed('name');
    }

    public function testCookiesCanBeDeletedAllAtOnce()
    {
        $this->url('html/');
        $cookies = $this->cookie();
        $cookies->add('id', 'id_value')->set();
        $cookies->add('name', 'name_value')->set();
        $cookies->clear();
        $this->assertThereIsNoCookieNamed('id');
        $this->assertThereIsNoCookieNamed('name');
    }

    public function testAdvancedParametersOfCookieCanBeSet()
    {
        $this->url('/');
        $cookies = $this->cookie();
        $cookies->add('name', 'value')
                ->path('/html')
                ->domain('127.0.0.1')
                ->expiry(time()+60*60*24)
                ->secure(FALSE)
                ->set();
        $this->assertThereIsNoCookieNamed('name');
        $this->url('/html');
        $this->assertEquals('value', $cookies->get('name'));
    }

    private function assertThereIsNoCookieNamed($name)
    {
        try {
            $this->cookie()->get($name);
            $this->fail('The cookie shouldn\'t exist anymore.');
        } catch (PHPUnit_Extensions_Selenium2TestCase_Exception $e) {
            $this->assertEquals("There is no '$name' cookie available on this page.", $e->getMessage());
        }
    }

    public function testTheBrowsersOrientationCanBeModified()
    {
        $this->markTestIncomplete('Which browsers support this functionality?');
        $this->orientation('LANDSCAPE');
        $this->orientation('PORTRAIT');
        $this->orientation();
    }

    public function testTheMouseCanBeMovedToAKnownPosition()
    {
        // @TODO: remove markTestIncomplete() when the following bugs are fixed
        // @see https://code.google.com/p/selenium/issues/detail?id=5939
        // @see https://code.google.com/p/selenium/issues/detail?id=3578
        $this->markTestIncomplete('This is broken in a firefox driver yet');
        $this->url('html/test_moveto.html');
        $this->moveto(array(
            'element' => $this->byId('moveto'),
            'xoffset' => 10,
            'yoffset' => 10,
        ));
        $this->buttondown();

        $deltaX = 42;
        $deltaY = 11;
        $this->moveto(array(
            'xoffset' => $deltaX,
            'yoffset' => $deltaY,
        ));
        $this->buttonup();

        $down = explode(',', $this->byId('down')->text());
        $up = explode(',', $this->byId('up')->text());

        $this->assertCount(2, $down);
        $this->assertCount(2, $up);
        $this->assertEquals($deltaX, $up[0] - $down[0]);
        $this->assertEquals($deltaY, $up[1] - $down[1]);
    }

    public function testMoveToRequiresElementParamToBeValidElement()
    {
        $this->url('html/test_moveto.html');

        try {
            $this->moveto('moveto');
            $this->fail('A single non-element parameter should cause an exception');
        } catch (PHPUnit_Extensions_Selenium2TestCase_Exception $e) {
            $this->assertStringStartsWith('Only moving over an element is supported', $e->getMessage());
        }

        try {
            $this->moveto(array(
                'element' => 'moveto'
            ));
            $this->fail('An "element" array parameter with non-element value should cause an exception');
        } catch (PHPUnit_Extensions_Selenium2TestCase_Exception $e) {
            $this->assertStringStartsWith('Only moving over an element is supported', $e->getMessage());
        }
    }

    public function testMouseButtonsCanBeClickedMultipleTimes()
    {
        $this->markTestIncomplete('Moveto command is not in the webdriver specification');
        $this->moveto(array(
            'element' => 'id', // or Element object
            'xoffset' => 0,
            'yofsset' => 0
        ));
        $this->doubleClick();
    }

    public function testFingersCanBeMovedAndPressedOnTheScreen()
    {
        $this->markTestIncomplete('Which browser supports these events?');
        $this->touch()->click();
        $this->touch()->down();
        $this->touch()->up();
        $this->touch()->move();
        $this->touch()->scroll();
        $this->touch()->doubleClick();
        $this->touch()->longClick();
        $this->touch()->flick();
    }

    public function testGeoLocationIsAccessible()
    {
        $this->markTestIncomplete();
        $this->location();
    }

    public function testTheBrowserLocalStorageIsAccessible()
    {
        $this->markTestIncomplete('We need a browser which supports WebStorage.');
        //$this->localStorage(); // all keys
        $storage = $this->localStorage();
        $storage->key = 42;
        $this->assertSame("42", $storage->key);
        //$this->localStorage()->size(); // a value
        // how to clear the storage?
    }

    public function testTheBrowserSessionStorageIsAccessible()
    {
        $this->markTestIncomplete();
        $this->sessionStorage(); // all keys
        $this->sessionStorage()->key; // gets a value
        $this->sessionStorage()->key = 2; // sets a value
        $this->sessionStorage()->size(); // a value
        // how to clear the storage?
    }

    public function test404PagesCanBeLoaded()
    {
        $this->url('inexistent.html');
    }

    /**
     * Ticket #113.
     */
    public function testMultipleUrlsCanBeLoadedInATest()
    {
        $this->url('html/test_click_page1.html');
        $this->url('html/test_open.html');
        $this->assertEquals('Test open', $this->title());
        $this->assertStringEndsWith('html/test_open.html', strstr($this->url(), 'html/'));
    }

    public function testNonexistentElement()
    {
        $this->url('html/test_open.html');
        try {
            $el = $this->byId("nonexistent");
        } catch (PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {
            $this->assertEquals(PHPUnit_Extensions_Selenium2TestCase_WebDriverException::NoSuchElement, $e->getCode());
            return;
        }
        $this->fail('The element shouldn\'t exist.');
    }

    public function testSpecialKeys()
    {
        $this->url('html/test_special_keys.html');
        $this->byId('input')->click();

        $this->byId('input')->value(Keys::F2);
        $this->assertEquals('113', $this->byId('check')->text());

        $this->byId('input')->value(Keys::ALT . Keys::ENTER);
        $this->assertEquals('13,alt', $this->byId('check')->text());

        $this->byId('input')->value(Keys::CONTROL . Keys::SHIFT . Keys::HOME);
        $this->assertEquals('36,control,shift', $this->byId('check')->text());

        $this->byId('input')->value(Keys::ALT . Keys::SHIFT . Keys::NUMPAD7);
        $this->assertEquals('103,alt,shift', $this->byId('check')->text());
    }

    public function testSessionClick()
    {
        $this->markTestIncomplete('Moveto command is not in the webdriver specification');
        $this->url('html/test_mouse_buttons.html');
        $input = $this->byId('input');

        $this->moveto($input);

        $this->click();
        $this->assertEquals('0', $this->byId('check')->text());

        $this->click(PHPUnit_Extensions_Selenium2TestCase_SessionCommand_Click::LEFT);
        $this->assertEquals('0', $this->byId('check')->text());

        // I couldn't get it worked in selenium webdriver 2.28: even though the client (phpunit-selenium) sends
        // the button: 1 in the request (checked with wireshark) - it still uses left mouse button (0)
        /*
        $this->click(PHPUnit_Extensions_Selenium2TestCase_SessionCommand_Click::MIDDLE);
        $this->assertEquals('1', $this->byId('check')->text());
        */

        $this->click(PHPUnit_Extensions_Selenium2TestCase_SessionCommand_Click::RIGHT);
        $this->assertEquals('2', $this->byId('check')->text());
    }

    /**
     * @expectedException BadMethodCallException
     */
    public function testSessionClickNotScalar()
    {
        $this->click(array());
    }

    /**
     * @expectedException BadMethodCallException
     */
    public function testSessionClickNotAValidValue()
    {
        $this->click(3);
    }

    public function testGetSelectedOptionDataInMultiselect()
    {
        $this->url('html/test_multiselect.html');
        $this->assertSame('Second Option', $this->select($this->byId('theSelect'))->selectedLabel());
        $this->assertSame('option2', $this->select($this->byId('theSelect'))->selectedValue());
        $this->assertSame('o2', $this->select($this->byId('theSelect'))->selectedId());
        $this->select($this->byId('theSelect'))->clearSelectedOptions();
        $this->assertSame('', $this->select($this->byId('theSelect'))->selectedLabel());
        $this->assertSame('', $this->select($this->byId('theSelect'))->selectedValue());
        $this->assertSame('', $this->select($this->byId('theSelect'))->selectedId());
    }

    public function testElementRectHeightAndWidth()
    {
        $this->url('html/test_element_rect.html');
        $coordinates = $this->byId('rect')->rect();
        $this->assertEquals('50', $coordinates['width']);
        $this->assertEquals('30', $coordinates['height']);
    }
}
