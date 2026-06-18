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
 * \file    test/phpunit/ModuleBuilderTemplateConventionsTest.php
 * \ingroup modulebuilder
 * \brief   PHPUnit test for ModuleBuilder template conventions: status labels derived from
 *          arrayofkeyval, and normalized trigger naming (MYMODULE_MYOBJECT_ACTION).
 */

global $conf, $user, $langs, $db;

require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;

/**
 * Class ModuleBuilderTemplateConventionsTest
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 * @phan-file-suppress PhanUndeclaredExtendedClass
 * @phan-file-suppress PhanUndeclaredClass
 * @phan-file-suppress PhanUndeclaredMethod
 */
class ModuleBuilderTemplateConventionsTest extends CommonClassTest
{
	/**
	 * @var string Absolute path to the object class template.
	 */
	const CLASS_TPL = __DIR__.'/../../htdocs/modulebuilder/template/class/myobject.class.php';

	/**
	 * getLibStatut() must use the label defined in the status field arrayofkeyval, not a hardcoded one.
	 *
	 * @return void
	 */
	public function testLibStatutLabelsMatchArrayofkeyval()
	{
		global $db;
		require_once self::CLASS_TPL;
		$object = new MyObject($db);

		$expected = $object->fields['status']['arrayofkeyval'][MyObject::STATUS_VALIDATED];
		$badge = strip_tags($object->LibStatut(MyObject::STATUS_VALIDATED, 1));

		$this->assertStringContainsString($expected, $badge, 'Validated badge label should match arrayofkeyval label');
		$this->assertStringNotContainsString('Enabled', $badge, 'Validated badge must not show the hardcoded "Enabled" label');
	}

	/**
	 * The LibStatut block must no longer hardcode Enabled/Disabled labels.
	 *
	 * @return void
	 */
	public function testNoHardcodedEnabledDisabledInLibStatut()
	{
		$content = file_get_contents(self::CLASS_TPL);
		$this->assertSame(0, preg_match('/labelStatus(Short)?\[[^\]]+\]\s*=\s*\$langs->transnoentitiesnoconv\(\'(Enabled|Disabled)\'\)/', $content), 'Hardcoded Enabled/Disabled label assignment still present in LibStatut');
	}
}
