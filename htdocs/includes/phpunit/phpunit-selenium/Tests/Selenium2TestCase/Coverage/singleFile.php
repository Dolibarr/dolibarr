<?php
$_COOKIE['PHPUNIT_SELENIUM_TEST_ID'] = 'ns\\dummyTestId';
require __DIR__ . '/../../../PHPUnit/Extensions/SeleniumCommon/prepend.php';

require_once 'DummyClass.php';
$object = new DummyClass();
$object->coveredMethod();

require __DIR__ . '/../../../PHPUnit/Extensions/SeleniumCommon/append.php';
