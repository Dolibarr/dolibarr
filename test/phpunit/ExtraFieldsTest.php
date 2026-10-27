<?php
/* Copyright (C) 2026		ATM Consulting		<support@atm-consulting.fr>
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
 *      \file       test/phpunit/ExtraFieldsTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/extrafields.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class ExtraFieldsTest extends CommonClassTest
{
	/**
	 * Data provider for testIsEmptyValue
	 *
	 * @return array<string,array{0:mixed,1:string,2:bool}>
	 */
	public static function isEmptyValueProvider()
	{
		return array(
			'int null' => array(null, 'int', true),
			'int empty string' => array('', 'int', true),
			'int zero string' => array('0', 'int', false),
			'int zero' => array(0, 'int', false),
			'int value' => array('5', 'int', false),
			'double zero string' => array('0', 'double', false),
			'double zero decimal' => array('0.00', 'double', false),
			'price zero string' => array('0', 'price', false),
			'varchar zero string' => array('0', 'varchar', true),
			'varchar value' => array('abc', 'varchar', false),
			'select empty' => array('0', 'select', true),
			'sellist zero' => array('0', 'sellist', true),
			'link minus one' => array('-1', 'link', true),
		);
	}

	/**
	 * Zero is a valid value for a mandatory numeric extrafield.
	 *
	 * @param	mixed	$value		Value to test
	 * @param	string	$type		Extrafield type
	 * @param	bool	$expected	Expected result
	 * @return	void
	 *
	 * @dataProvider isEmptyValueProvider
	 */
	public function testIsEmptyValue($value, $type, $expected)
	{
		$this->assertSame($expected, ExtraFields::isEmptyValue($value, $type));
	}
}
