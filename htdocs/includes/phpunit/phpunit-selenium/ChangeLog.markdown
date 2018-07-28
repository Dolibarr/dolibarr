PHPUnit_Selenium
================

This is the list of changes made to PHPUnit_Selenium.

PHPUnit_Selenium 2.0.3
----------------------

* Added rect() command
* Revamp of testing VM using Java 8, PHP 5.6

PHPUnit_Selenium 2.0.2
----------------------

* Supporting only PHPUnit 4.8 excluding 5.x and newer

PHPUnit_Selenium 2.0.1
----------------------

* Supporting only PHPUnit 4.x

PHPUnit_Selenium 2.0.0
----------------------

* Only Selenium2TestCase is supported in the 2.x releases
* file() command
* Windows compatibility of code coverage

PHPUnit_Selenium 1.4.2
----------------------

* First release with new canonical repository giorgiosironi/phpunit-selenium

PHPUnit_Selenium 1.4.1
----------------------

* PHPUnit 4.x is now supported

PHPUnit_Selenium 1.4.0
----------------------

* PEAR is now not supported as an installation method, being deprecated from the PHPUnit parent project
* phpunit_coverage.php does not realy on PEAR now but on finding an autoload.php file
* A Vagrant VM is provided for contributors to easily run the tests
* Supporting browsers() static method with same behavior as $browsers static property
* Added $this->log() and $this->logTypes()

PHPUnit_Selenium 1.3.3
----------------------

* Supporting browsers() static method with same behavior as $browsers static property
* Added $this->log() and $this->logTypes()

PHPUnit_Selenium 1.3.2
----------------------

* Compatibility with Selenium 2.34 and upper
* Introduced experimental file() support

PHPUnit_Selenium 1.3.1
----------------------

* setupPage() method that can be defined to be executed after the session is opened but before tests start
* Docblocks work now in Eclipse PDT

PHPUnit_Selenium 1.3.0
----------------------

* BC break: setBrowserUrl() argument is not loaded at the start of a test
* waitUntil() now works nicely with implicitWait()
* keysHolder() is deprecated, use keys() instead
* More complete frame() supportk
* Research of elements inside other element objects with by*() methods
* Supporting Selenium 2.32.0
* Element names are always lowercase for consistency
* Pause support for runSelenese() HTML cases


PHPUnit_Selenium 1.2.12
----------------------

* Added waitUntil(), byTag()
* Added specialKeys() for non-alphanumeric keys

PHPUnit_Selenium 1.2.11
----------------------

* Fixing Composer autoload support.

PHPUnit_Selenium 1.2.9
----------------------

* Support for PHPUnit 3.7, requiring PHP 5.3.
* New getter methods available for extendibility.
* Window maximization command.
* Multiple strategies for browser sessions: isolated and shared can coexist.

PHPUnit_Selenium 1.2.8
----------------------

* Implemented ScreenshotListener for taking screenshots in a red Selenium2TestCase.

PHPUnit_Selenium 1.2.7
----------------------

* Implemented #130: version number available programmatically.
* Implemented $this->keys().
* Session is now closed on failure.
* Added various docblocks for SeleniumTestCase.
* Browser session can now be started even in setUp().

PHPUnit_Selenium 1.2.6
----------------------

* Fixed #114 and #115: regressions of @depends/@dataProvider.
* Added $this->cookie() for adding and removing cookies via a Builder.
* Added Selenium2TestCase_Exception in the Cookie api.
* Supporting absolute URLs (http://...) in $this->url().
* Supporting uppercase URLs.
* Raising error message for stale elements reference (#117).
* No 500 errors when communicating with Selenium Server.
* Supporting Selenium 2.20.
* Tests for 404 pages.
* Supporting @depends/@dataProvider and similar annotations in SeleniumTestCase.
* Added getCssCount() in SeleniumTestCase.

PHPUnit_Selenium 1.2.5
----------------------

* Added Window object accessible via $this->currentWindow().
* Implemented $this->timeouts()->asyncScript().
* Fixed #105: $browsers static property.

PHPUnit_Selenium 1.2.4
----------------------

* Implemented $element->size().
* Implemented $element->location().
* Implemented $element->name(), $element->attribute(), $element->equals(), $element->enabled(), $element->displayed(), $element->css().
* Implemented $this->elements() for multiple element selection in the whole page.
* Implemented $this->frame() to switch focus between frames on a page.
* Implemented $this->execute() and $this->executeAsync() for executing arbitrary JavaScript.
* Implemented $this->windowHandle(), $this->windowHandles and $this->source().
* Implemented $this->alertText("...") for answering prompts.
* Supporting form submit (also via children elements).
* Supporting radio boxes.
* Supporting implicit waits on $this->by*().
* Supporting back and forward buttons via $this->back() and $this->forward().
* Supporting refresh of pages via $this->refresh().
* Supporting $element->clear().
* Correctly marking Selenium 1 tests as skipped when server is not running.

PHPUnit_Selenium 1.2.3
----------------------

* Fixed package.xml to include missing SeleniumTestSuite.php file.

PHPUnit_Selenium 1.2.2
----------------------

* Implemented Select object, available via $this->select().
* Added defaults for Selenium Server host and port.
* Added @method annotations on Selenium2TestCase.
* Fixed #83: `setUpBeforeClass` and `tearDownAfterClass` do not work with `PHPUnit_Extensions_SeleniumTestCase`.
* Fixed #85: using POST instead of GET in Selenium RC Driver.
* Supporting AndroidDriver, both on devices and emulators.
* Supporting UTF-8 characters in Element::value().

PHPUnit_Selenium 1.2.1
----------------------

* Fixed #82: `@depends` annotation does not work with `PHPUnit_Extensions_SeleniumTestCase`.
* `package.xml` misses classes for Selenium 2 support.

PHPUnit_Selenium 1.2.0
----------------------

* Introduced `PHPUnit_Extensions_Selenium2TestCase` class for using WebDriver API.
* Introduced session sharing for WebDriver API.
* Introduced URL opening and element selection in WebDriver API.
* Introduced clicking on elements and `clickOnElement($id)` shorthand in WebDriver API.
* Introduced partial `alert()` management in WebDriver API.
* Introduced element manipulation in WebDriver API: text accessor, value mutator.
* Introduced `by*()` quick selectors in WebDriver API.
* Extracted a base command class for extending the supported session and element commands in WebDriver API.

