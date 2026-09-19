<?php
/* Copyright (C) 2026 ATM Consulting <contact@atm-consulting.fr>
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
 */

/**
 *      \file       test/phpunit/ModuleBuilderLibTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test for modulebuilder.lib.php tab selection helpers
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/modulebuilder.lib.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks backupGlobals must be disabled to have db,conf,user and lang not erased.
 * @phan-file-suppress PhanUndeclaredClass
 * @phan-file-suppress PhanUndeclaredExtendedClass
 * @phan-file-suppress PhanUndeclaredMethod
 * @phan-file-suppress PhanTypeMismatchArgumentProbablyReal
 */
class ModuleBuilderLibTest extends CommonClassTest
{
	/**
	 * testGetModuleBuilderObjectTabs
	 *
	 * @return void
	 */
	public function testGetModuleBuilderObjectTabs()
	{
		$map = getModuleBuilderObjectTabs();
		$this->assertSame(array('contact', 'note', 'document', 'agenda'), array_keys($map));
		$this->assertSame('myobject_contact.php', $map['contact']['file']);
		$this->assertSame('showtabofpageagenda', $map['agenda']['var']);
		$this->assertSame('DOCUMENT', $map['document']['marker']);
	}

	/**
	 * testFilterEnabledTabs
	 *
	 * @return void
	 */
	public function testFilterEnabledTabs()
	{
		$map = getModuleBuilderObjectTabs();

		// Nominal: returns requested keys in map order
		$this->assertSame(array('contact', 'agenda'), filterEnabledTabs(array('agenda', 'contact'), $map));

		// Unknown key is rejected
		$this->assertSame(array('contact'), filterEnabledTabs(array('contact', 'evil'), $map));

		// Empty / non-array returns empty
		$this->assertSame(array(), filterEnabledTabs(array(), $map));
		$this->assertSame(array(), filterEnabledTabs('', $map));

		// Duplicates collapsed
		$this->assertSame(array('note'), filterEnabledTabs(array('note', 'note'), $map));
	}
}
