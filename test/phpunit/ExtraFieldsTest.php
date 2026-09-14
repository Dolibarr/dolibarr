<?php
/* Copyright (C) 2026 Jam <jambalaya.pyoncafe@gmail.com>
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
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/extrafields.class.php';
require_once dirname(__FILE__).'/../../htdocs/societe/class/societe.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


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
	 * Build an ExtraFields object with a "select" list depending on another "select" list.
	 * The child list has one value for the parent value 'a', one for the parent value 'b',
	 * and one value with no dependency at all.
	 *
	 * @return	ExtraFields		Object with the definitions loaded in memory (nothing is written in database)
	 */
	private function getExtraFieldsWithDependentList()
	{
		global $db;

		$extrafields = new ExtraFields($db);

		$elementtype = 'societe';
		foreach (array('parentlist', 'childlist') as $code) {
			$extrafields->attributes[$elementtype]['type'][$code] = 'select';
			$extrafields->attributes[$elementtype]['label'][$code] = $code;
			$extrafields->attributes[$elementtype]['size'][$code] = '';
			$extrafields->attributes[$elementtype]['default'][$code] = '';
			$extrafields->attributes[$elementtype]['computed'][$code] = '';
			$extrafields->attributes[$elementtype]['unique'][$code] = 0;
			$extrafields->attributes[$elementtype]['required'][$code] = 0;
			$extrafields->attributes[$elementtype]['perms'][$code] = '';
			$extrafields->attributes[$elementtype]['langfile'][$code] = '';
			$extrafields->attributes[$elementtype]['list'][$code] = '1';
			$extrafields->attributes[$elementtype]['totalizable'][$code] = 0;
			$extrafields->attributes[$elementtype]['help'][$code] = '';
			$extrafields->attributes[$elementtype]['alwayseditable'][$code] = 0;
		}

		$extrafields->attributes[$elementtype]['param']['parentlist'] = array('options' => array('a' => 'Value A', 'b' => 'Value B'));
		$extrafields->attributes[$elementtype]['param']['childlist'] = array('options' => array(
			'childofa' => 'Child of A|parentlist:a',
			'childofb' => 'Child of B|parentlist:b',
			'nodepend' => 'No dependency',
		));

		return $extrafields;
	}

	/**
	 * Build a thirdparty holding a value for the parent list
	 *
	 * @param	string		$parentvalue	Value saved for the parent list
	 * @param	string		$childvalue		Value saved for the dependent list
	 * @return	Societe						Object not saved in database
	 */
	private function getObjectWithParentValue($parentvalue, $childvalue = '')
	{
		global $db;

		$object = new Societe($db);
		$object->array_options['options_parentlist'] = $parentvalue;
		$object->array_options['options_childlist'] = $childvalue;

		return $object;
	}

	/**
	 * When the whole form is shown, all the values must be output, whatever the parent value is:
	 * the filtering is done by javascript, on the value chosen in the parent list of the same form.
	 *
	 * @return void
	 */
	public function testShowInputFieldKeepsAllValuesOfADependentListByDefault()
	{
		$extrafields = $this->getExtraFieldsWithDependentList();
		$object = $this->getObjectWithParentValue('a');

		$out = $extrafields->showInputField('childlist', '', '', '', '', '', $object, 'societe');

		print __METHOD__." out=".$out."\n";
		$this->assertStringContainsString('value="childofa"', $out);
		$this->assertStringContainsString('value="childofb"', $out);
		$this->assertStringContainsString('parent="parentlist:a"', $out);
	}

	/**
	 * When the field is edited alone, the parent list is not on the form, so the values must be
	 * filtered on the value already saved for the parent list.
	 *
	 * @return void
	 */
	public function testShowInputFieldFiltersADependentListOnTheSavedParentValue()
	{
		$extrafields = $this->getExtraFieldsWithDependentList();
		$object = $this->getObjectWithParentValue('a');

		$out = $extrafields->showInputField('childlist', '', '', '', '', '', $object, 'societe', 0, 1);

		print __METHOD__." out=".$out."\n";
		$this->assertStringContainsString('value="childofa"', $out);
		$this->assertStringNotContainsString('value="childofb"', $out);
		// A value that does not depend on the parent list is always kept
		$this->assertStringContainsString('value="nodepend"', $out);
	}

	/**
	 * If nothing is saved for the parent list, we can't filter, so all the values must be kept.
	 *
	 * @return void
	 */
	public function testShowInputFieldKeepsAllValuesWhenParentValueIsEmpty()
	{
		$extrafields = $this->getExtraFieldsWithDependentList();
		$object = $this->getObjectWithParentValue('');

		$out = $extrafields->showInputField('childlist', '', '', '', '', '', $object, 'societe', 0, 1);

		print __METHOD__." out=".$out."\n";
		$this->assertStringContainsString('value="childofa"', $out);
		$this->assertStringContainsString('value="childofb"', $out);
	}

	/**
	 * The value currently saved must always be output, even when it does not match the parent value,
	 * otherwise editing the field would silently clear it.
	 *
	 * @return void
	 */
	public function testShowInputFieldKeepsTheSelectedValueNotMatchingTheParentValue()
	{
		$extrafields = $this->getExtraFieldsWithDependentList();
		$object = $this->getObjectWithParentValue('a', 'childofb');

		$out = $extrafields->showInputField('childlist', 'childofb', '', '', '', '', $object, 'societe', 0, 1);

		print __METHOD__." out=".$out."\n";
		$this->assertStringContainsString('value="childofb"', $out);
		$this->assertStringContainsString('selected', $out);
		$this->assertStringContainsString('value="childofa"', $out);
	}
}
