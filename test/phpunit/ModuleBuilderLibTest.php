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
 *      \brief      PHPUnit test for modulebuilder.lib.php tab and card action selection helpers
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db,$mysoc;
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

		// Wrapper must stay strictly equivalent to the generic filter it delegates to
		$this->assertSame(filterEnabledKeys(array('agenda', 'contact'), $map), filterEnabledTabs(array('agenda', 'contact'), $map));
	}

	/**
	 * testGetModuleBuilderObjectCardActions
	 *
	 * @return void
	 */
	public function testGetModuleBuilderObjectCardActions()
	{
		$map = getModuleBuilderObjectCardActions();
		$this->assertSame(array('sendmail', 'clone', 'validate', 'setdraft', 'statuschange', 'delete'), array_keys($map));

		$markers = array();
		foreach ($map as $actionkey => $meta) {
			foreach (array('marker', 'label', 'default', 'mode') as $index) {
				$this->assertArrayHasKey($index, $meta, 'Missing index '.$index.' for card action '.$actionkey);
			}
			// The marker is injected into a regular expression built in modulebuilder/index.php
			$this->assertMatchesRegularExpression('/^[A-Z]+$/', $meta['marker'], 'Invalid marker for card action '.$actionkey);
			$this->assertContains($meta['mode'], array('remove', 'activate'), 'Invalid mode for card action '.$actionkey);
			$markers[] = $meta['marker'];
		}

		// Two actions sharing a marker would purge each other blocks
		$this->assertSame($markers, array_unique($markers));

		// Cancel / Re-Open ships commented out in the template: it must stay off by default
		$this->assertSame(0, $map['statuschange']['default']);
		$this->assertSame('activate', $map['statuschange']['mode']);
	}

	/**
	 * testGetModuleBuilderDefaultEnabledKeys
	 *
	 * @return void
	 */
	public function testGetModuleBuilderDefaultEnabledKeys()
	{
		// Default selection must reproduce the card page generated before this option existed
		$this->assertSame(array('sendmail', 'clone', 'validate', 'setdraft', 'delete'), getModuleBuilderDefaultEnabledKeys(getModuleBuilderObjectCardActions()));

		// All tabs are enabled by default
		$this->assertSame(array_keys(getModuleBuilderObjectTabs()), getModuleBuilderDefaultEnabledKeys(getModuleBuilderObjectTabs()));
	}

	/**
	 * testFilterEnabledKeys
	 *
	 * @return void
	 */
	public function testFilterEnabledKeys()
	{
		$map = getModuleBuilderObjectCardActions();

		// Nominal: returns requested keys in map order
		$this->assertSame(array('clone', 'delete'), filterEnabledKeys(array('delete', 'clone'), $map));

		// Unknown key is rejected
		$this->assertSame(array('delete'), filterEnabledKeys(array('delete', 'evil'), $map));

		// Empty / non-array returns empty
		$this->assertSame(array(), filterEnabledKeys(array(), $map));
		$this->assertSame(array(), filterEnabledKeys('', $map));

		// Duplicates collapsed
		$this->assertSame(array('validate'), filterEnabledKeys(array('validate', 'validate'), $map));
	}
}
