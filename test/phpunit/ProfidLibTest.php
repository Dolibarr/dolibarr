<?php
/* Copyright (C) 2010-2023 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       test/phpunit/ProfidLibTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db,$mysoc;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/profid.lib.php';
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
class ProfidLibTest extends CommonClassTest
{
	/**
	 * testIsValidLuhn
	 *
	 * @return void
	 */
	public function testIsValidLuhn()
	{
		// Tests OK
		$this->assertTrue(isValidLuhn(972487086));		// int
		$this->assertTrue(isValidLuhn("972487086"));	// string
		// Tests KO
		$this->assertFalse(isValidLuhn(123456789));		// int
		$this->assertFalse(isValidLuhn("123456789"));	// string
	}


	/**
	 * testIsValidSiren
	 *
	 * @return void
	 */
	public function testIsValidSiren()
	{
		// Tests OK
		$this->assertTrue(isValidSiren("732829320"));
		$this->assertTrue(isValidSiren(" 732 829 320 "));	// formatted with spaces
		// Tests NOK
		$this->assertFalse(isValidSiren("123456ABC"));		// not numeric
		$this->assertFalse(isValidSiren("43336767"));		// Luhn test OK but length != 9
		$this->assertFalse(isValidSiren("123456789"));		// 9 digits but Luhn test KO
	}



	/**
	 * testIsValidSiret
	 *
	 * @return void
	 */
	public function testIsValidSiret()
	{
		// Tests OK
		$this->assertTrue(isValidSiret("73282932000074"));
		$this->assertTrue(isValidSiret(" 732 829 320 00074 "));		// formatted with spaces
		$this->assertTrue(isValidSiret("35600000049837"));			// Specific cases of "La Poste" companies
		// Tests NOK
		$this->assertFalse(isValidSiret("123456ABC12345"));			// not numeric
		$this->assertFalse(isValidSiret("3624679471379"));			// Luhn test OK but length != 14
		$this->assertFalse(isValidSiret("12345678912345"));			// 14 digits but Luhn test KO
	}



	/**
	 * testIsValidTinForPT
	 *
	 * @return void
	 */
	public function testIsValidTinForPT()
	{
		// Tests OK
		$this->assertTrue(isValidTinForPT("123456789"));
		$this->assertTrue(isValidTinForPT(" 123 456 789 "));		// formatted with spaces
		// Tests NOK
		$this->assertFalse(isValidTinForPT("123456ABC"));			// not numeric
		$this->assertFalse(isValidTinForPT("12345678"));			// length != 9
	}



	/**
	 * testIsValidTinForDZ
	 *
	 * @return void
	 */
	public function testIsValidTinForDZ()
	{
		// Tests OK
		$this->assertTrue(isValidTinForDZ("123456789123456"));
		$this->assertTrue(isValidTinForDZ(" 12345 67891 23456 "));		// formatted with spaces
		// Tests NOK
		$this->assertFalse(isValidTinForDZ("123456789123ABC"));			// not numeric
		$this->assertFalse(isValidTinForDZ("123456789123"));			// length != 15
	}



	/**
	 * testIsValidTinForBE
	 *
	 * @return void
	 */
	public function testIsValidTinForBE()
	{
		// Tests OK
		$this->assertTrue(isValidTinForBE("0123.123.123"));
		$this->assertTrue(isValidTinForBE("1234.123.123"));
		// Tests NOK
		$this->assertFalse(isValidTinForBE("2345.123.123"));		// First digit shall be 0 or 1
		$this->assertFalse(isValidTinForBE("1234 123 123"));		// formatted with spaces instead of dots
		$this->assertFalse(isValidTinForBE("1234123123"));			// without dots formatting
		$this->assertFalse(isValidTinForBE("ABCD.123.123"));		// not digits only
	}

	// TODO
	/**
	 * testIsValidTinForES
	 *
	 * @return void
	 */
	/*
	public function testIsValidTinForES()
	{
		// Tests for NIF
		$this->assertEquals(1, isValidTinForES(""));			// valid NIF
		$this->assertEquals(-1, isValidTinForES(""));			// valid regex, but invalid control key
		// Tests for CIF
		$this->assertEquals(2, isValidTinForES(""));			// valid CIF
		$this->assertEquals(-2, isValidTinForES(""));			// valid regex, but invalid control key
		// Tests for NIE
		$this->assertEquals(3, isValidTinForES(""));			// valid NIE
		$this->assertEquals(-3, isValidTinForES(""));			// valid regex, but invalid control key
		// Tests for unknown error
		$this->assertEquals(-4, isValidTinForES(""));			// invalid regex for both NIF, CIF and NIE
	}
	*/
}
