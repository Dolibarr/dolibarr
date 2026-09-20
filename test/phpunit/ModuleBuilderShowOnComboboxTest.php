<?php
/* Copyright (C) 2026 Quentin VIAL--GOUTEYRON <quentin.vial-gouteyron@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    test/phpunit/ModuleBuilderShowOnComboboxTest.php
 * \ingroup modulebuilder
 * \brief   PHPUnit test for ModuleBuilder showoncombobox handling (#35076): the property must be serialized
 *          into the generated object class for any field, not only ref/code.
 */

global $conf, $user, $langs, $db;

require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/files.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/modulebuilder.lib.php';
require_once dirname(__FILE__).'/../../htdocs/modulebuilder/class/NamingContract.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;

/**
 * Class ModuleBuilderShowOnComboboxTest
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 * @phan-file-suppress PhanUndeclaredExtendedClass
 * @phan-file-suppress PhanUndeclaredClass
 * @phan-file-suppress PhanUndeclaredMethod
 * @phan-file-suppress PhanTypeMismatchArgument
 */
class ModuleBuilderShowOnComboboxTest extends CommonClassTest
{
	/**
	 * Guard the serialization contract the #35076 fix relies on: a regular (non ref/code) field carrying
	 * showoncombobox=1 must be serialized by rebuildObjectClass(), and dropped when the flag is 0. The
	 * actual capture happens in the addproperty page action (not unit-testable), verified there by inspection.
	 *
	 * @return void
	 */
	public function testShowOnComboboxSerializedForRegularField()
	{
		$tpl = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
		$module = 'showcbmod';
		$objectname = 'CbObject';
		$nc = new NamingContract($module, $objectname);

		$base = sys_get_temp_dir().'/mbshowcb_'.getmypid();
		dol_delete_dir_recursive($base);
		dol_mkdir($base.'/class');
		dol_mkdir($base.'/sql');

		file_put_contents($base.'/class/'.strtolower($objectname).'.class.php', $nc->applyTo(file_get_contents($tpl.'/class/myobject.class.php')));
		file_put_contents($base.'/sql/llx_'.$module.'_'.strtolower($objectname).'.sql', $nc->applyTo(file_get_contents($tpl.'/sql/llx_mymodule_myobject.sql')));
		file_put_contents($base.'/sql/llx_'.$module.'_'.strtolower($objectname).'.key.sql', $nc->applyTo(file_get_contents($tpl.'/sql/llx_mymodule_myobject.key.sql')));

		// A regular text field (not 'ref' nor 'code') asking to be shown in the record combobox.
		$addfieldentry = array(
			'name' => 'mylabelfield', 'label' => 'MyLabelField', 'type' => 'varchar(128)',
			'showoncombobox' => 1, 'notnull' => 0, 'position' => 200, 'enabled' => 1, 'visible' => 1
		);

		$object = rebuildObjectClass($base, $module, $objectname, '0', $base, $addfieldentry);
		$this->assertIsObject($object, 'rebuildObjectClass must return the object');

		$class = file_get_contents($base.'/class/'.strtolower($objectname).'.class.php');
		// Bound the match to the field's own array (up to the next "fieldname" => array( entry) so it cannot
		// leak into a neighbouring field that also carries showoncombobox.
		$this->assertMatchesRegularExpression('/"mylabelfield"\s*=>\s*array\((?:(?!=>\s*array\().)*"showoncombobox"\s*=>\s*"1"/s', $class, 'showoncombobox must be serialized for a regular field');

		// Same field with the flag turned off must NOT serialize showoncombobox (toggle off / default).
		$addfieldentry['showoncombobox'] = 0;
		rebuildObjectClass($base, $module, $objectname, '0', $base, $addfieldentry);
		$classoff = file_get_contents($base.'/class/'.strtolower($objectname).'.class.php');
		$this->assertDoesNotMatchRegularExpression('/"mylabelfield"\s*=>\s*array\((?:(?!=>\s*array\().)*"showoncombobox"/s', $classoff, 'showoncombobox must not be serialized when the flag is 0');

		dol_delete_dir_recursive($base);
	}
}
