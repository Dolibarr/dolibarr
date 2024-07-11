<?php
/* Copyright (C) 2010-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023      Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *      \file       test/phpunit/JsonLibTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

define('PHPUNIT_MODE', 1);

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (! defined('NOREQUIREUSER')) {
	define('NOREQUIREUSER', '1');
}
if (! defined('NOREQUIREDB')) {
	define('NOREQUIREDB', '1');
}
if (! defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (! defined('NOREQUIRETRAN')) {
	define('NOREQUIRETRAN', '1');
}
if (! defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1');
}
if (! defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (! defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1'); // If there is no menu to show
}
if (! defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1'); // If we don't need to load the html.form.class.php
}
if (! defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (! defined("NOLOGIN")) {
	define("NOLOGIN", '1');       // If this page is public (can be called outside logged session)
}


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class JsonLibTest extends CommonClassTest
{
	/**
	 * testJsonEncode
	 *
	 * @return  void
	 */
	public function testJsonEncode()
	{
		//$this->sharedFixture
		global $conf,$user,$langs,$db;
		$this->savconf = $conf;
		$this->savuser = $user;
		$this->savlangs = $langs;
		$this->savdb = $db;

		// Try to decode a string encoded with serialize
		$encoded = 'a:1:{s:7:"options";a:3:{s:3:"app";s:11:"Application";s:6:"system";s:6:"System";s:6:"option";s:6:"Option";}}';
		$decoded = json_decode($encoded, true);
		$this->assertEquals(null, $decoded, 'test to json_decode() a string that was encoded with serialize()');

		$encoded = 'rubishstring!aa{bcd';
		$decoded = json_decode($encoded, true);
		$this->assertEquals(null, $decoded, 'test to json_decode() a string that was encoded with serialize()');

		// Do a test with an array starting with 0
		$arraytotest = array(0 => array('key' => 1,'value' => 'PRODREF','label' => 'Product ref with é and special chars \\ \' "'));
		$arrayencodedexpected = '[{"key":1,"value":"PRODREF","label":"Product ref with \u00e9 and special chars \\\\ \' \""}]';

		$encoded = json_encode($arraytotest);
		$this->assertEquals($arrayencodedexpected, $encoded);
		$decoded = json_decode($encoded, true);
		$this->assertEquals($arraytotest, $decoded, 'test for json_xxx');

		$encoded = dol_json_encode($arraytotest);
		$this->assertEquals($arrayencodedexpected, $encoded);
		$decoded = dol_json_decode($encoded, true);
		$this->assertEquals($arraytotest, $decoded, 'test for dol_json_xxx');

		// Same test but array start with 2 instead of 0
		$arraytotest = array(2 => array('key' => 1,'value' => 'PRODREF','label' => 'Product ref with é and special chars \\ \' "'));
		$arrayencodedexpected = '{"2":{"key":1,"value":"PRODREF","label":"Product ref with \u00e9 and special chars \\\\ \' \""}}';

		$encoded = json_encode($arraytotest);
		$this->assertEquals($arrayencodedexpected, $encoded);
		$decoded = json_decode($encoded, true);
		$this->assertEquals($arraytotest, $decoded, 'test for json_xxx');

		$encoded = dol_json_encode($arraytotest);
		$this->assertEquals($arrayencodedexpected, $encoded);
		$decoded = dol_json_decode($encoded, true);
		$this->assertEquals($arraytotest, $decoded, 'test for dol_json_xxx');

		$encoded = dol_json_encode(123);
		$this->assertEquals(123, $encoded);
		$decoded = dol_json_decode($encoded, true);
		$this->assertEquals(123, $decoded, 'test for dol_json_xxx 123');

		$encoded = dol_json_encode('abc');
		$this->assertEquals('"abc"', $encoded);
		$decoded = dol_json_decode($encoded, true);
		$this->assertEquals('abc', $decoded, "test for dol_json_xxx 'abc'");

		// Test with object
		$now = gmmktime(12, 0, 0, 1, 1, 1970);
		$objecttotest = new stdClass();
		$objecttotest->property1 = 'abc';
		$objecttotest->property2 = 1234;
		$objecttotest->property3 = $now;
		$encoded = dol_json_encode($objecttotest);
		$this->assertEquals('{"property1":"abc","property2":1234,"property3":43200}', $encoded);
	}
}
