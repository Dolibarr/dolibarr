<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *      \file       test/phpunit/AdminLibTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/admin.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/modulebuilder.lib.php';
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
 * @remarks backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class AdminLibTest extends CommonClassTest
{
	/**
	 * testVersionCompare
	 *
	 * @return	void
	 */
	public function testVersionCompare()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = versioncompare(array(3,1,-4), array(3,1,1));
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(-3, $result);
		$result = versioncompare(array(3,1,0), array(3,1,1));
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(-3, $result);
		$result = versioncompare(array(3,1,0), array(3,2,0));
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(-2, $result);
		$result = versioncompare(array(3,1,0), array(3,1,0));
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(0, $result);

		// Upgrade required
		$v1 = '21.0.0-beta'; $v2 = '21.0.0-rc';
		$versioncompared = abs(versioncompare(preg_split('/[\.\-]/', $v1), preg_split('/[\.\-]/', $v2)));
		$upgraderequired = in_array($versioncompared, array(-4, -2, -1, 1, 2, 4));
		$this->assertEquals(true, $upgraderequired, 'Error with v1='.$v1.' v2='.$v2.' versioncompared='.$versioncompared);

		$v1 = '21.0.0-beta'; $v2 = '21.0.0';
		$versioncompared = abs(versioncompare(preg_split('/[\.\-]/', $v1), preg_split('/[\.\-]/', $v2)));
		$upgraderequired = in_array($versioncompared, array(-4, -2, -1, 1, 2, 4));
		$this->assertEquals(true, $upgraderequired, 'Error with v1='.$v1.' v2='.$v2.' versioncompared='.$versioncompared);

		$v1 = '21.1.1'; $v2 = '21.0.1';
		$versioncompared = abs(versioncompare(preg_split('/[\.\-]/', $v1), preg_split('/[\.\-]/', $v2)));
		$upgraderequired = in_array($versioncompared, array(-4, -2, -1, 1, 2, 4));
		$this->assertEquals(true, $upgraderequired, 'Error with v1='.$v1.' v2='.$v2.' versioncompared='.$versioncompared);

		$v1 = '22.0.1'; $v2 = '21.0.1';
		$versioncompared = abs(versioncompare(preg_split('/[\.\-]/', $v1), preg_split('/[\.\-]/', $v2)));
		$upgraderequired = in_array($versioncompared, array(-4, -2, -1, 1, 2, 4));
		$this->assertEquals(true, $upgraderequired, 'Error with v1='.$v1.' v2='.$v2.' versioncompared='.$versioncompared);

		// Upgrade not required
		$v1 = '21.0.0'; $v2 = '21.0.0';
		$versioncompared = abs(versioncompare(preg_split('/[\.\-]/', $v1), preg_split('/[\.\-]/', $v2)));
		$upgraderequired = in_array($versioncompared, array(-4, -2, -1, 1, 2, 4));
		$this->assertEquals(false, $upgraderequired, 'Error with v1='.$v1.' v2='.$v2.' versioncompared='.$versioncompared);

		$v1 = '21.0.1'; $v2 = '21.0.0';
		$versioncompared = abs(versioncompare(preg_split('/[\.\-]/', $v1), preg_split('/[\.\-]/', $v2)));
		$upgraderequired = in_array($versioncompared, array(-4, -2, -1, 1, 2, 4));
		$this->assertEquals(false, $upgraderequired, 'Error with v1='.$v1.' v2='.$v2.' versioncompared='.$versioncompared);

		return $result;
	}

	/**
	 * testEnableModule
	 *
	 * @return  void
	 */
	public function testEnableModule()
	{
		global $conf, $db, $langs, $user;

		require_once dirname(__FILE__).'/../../htdocs/core/modules/modExpenseReport.class.php';
		print "Enable module modExpenseReport";
		$moduledescriptor = new modExpenseReport($db);

		$result = $moduledescriptor->remove();

		$result = $moduledescriptor->init();
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(1, $result, "Enable module modExpenseReport");
		$conf->setValues($db);

		require_once dirname(__FILE__).'/../../htdocs/core/modules/modApi.class.php';
		print "Enable module modAPI";
		$moduledescriptor = new modApi($db);

		$result = $moduledescriptor->remove();

		$result = $moduledescriptor->init();
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(1, $result, "Enable module modAPI");
		$conf->setValues($db);
	}

	/**
	 * testDolModuleBuilderSqlIdentifier
	 *
	 * @return  void
	 */
	public function testDolModuleBuilderSqlIdentifier()
	{
		// An identifier that fits in 64 characters is returned unchanged
		$this->assertSame('idx_mymodule_myobject_fk_product_parent', dolModuleBuilderSqlIdentifier('idx_', 'MyModule', 'MyObject', 'fk_product_parent'));
		$this->assertSame('llx_mymodule_myobject_fk_soc', dolModuleBuilderSqlIdentifier('llx_', 'MyModule', 'MyObject', 'fk_soc'));

		// Only the module and the table are lowercased: the name of the field keeps its case, as a field
		// declared in the module builder may hold uppercase characters, and its index must keep its name
		$this->assertSame('idx_mymodule_myobject_fkSocType', dolModuleBuilderSqlIdentifier('idx_', 'MyModule', 'MyObject', 'fkSocType'));
		$this->assertSame('llx_mymodule_myobject_dateValidation', dolModuleBuilderSqlIdentifier('llx_', 'MyModule', 'MyObject', 'dateValidation'));

		// Two fields sharing their first characters keep two distinct identifiers as long as both fit
		$this->assertNotSame(
			dolModuleBuilderSqlIdentifier('idx_', 'MyModule', 'MyObject', 'fk_product_parent'),
			dolModuleBuilderSqlIdentifier('idx_', 'MyModule', 'MyObject', 'fk_product_child')
		);

		// Beyond the limit each part is truncated: module 30, table 20, and the key takes the remaining budget,
		// which gives exactly 64 characters whatever the prefix
		$this->assertSame(
			'idx_'.str_repeat('m', 30).'_'.str_repeat('t', 20).'_'.str_repeat('k', 8),
			dolModuleBuilderSqlIdentifier('idx_', str_repeat('m', 40), str_repeat('t', 40), str_repeat('k', 40))
		);
		$this->assertSame(
			'uk_'.str_repeat('m', 30).'_'.str_repeat('t', 20).'_'.str_repeat('k', 9),
			dolModuleBuilderSqlIdentifier('uk_', str_repeat('m', 40), str_repeat('t', 40), str_repeat('k', 40))
		);
		$this->assertSame(
			'llx_'.str_repeat('m', 30).'_'.str_repeat('t', 20).'_'.str_repeat('k', 8),
			dolModuleBuilderSqlIdentifier('llx_', str_repeat('m', 40), str_repeat('t', 40), str_repeat('k', 40))
		);
		foreach (array('idx_', 'uk_', 'llx_') as $prefix) {
			$name = dolModuleBuilderSqlIdentifier($prefix, str_repeat('m', 80), str_repeat('t', 80), str_repeat('k', 80));
			$this->assertSame(64, dol_strlen($name), 'Identifier not truncated to 64 for prefix '.$prefix);
		}

		// The rule is deterministic: the same inputs always give the same name, with no hashed nor random part
		$this->assertSame(
			dolModuleBuilderSqlIdentifier('idx_', 'MyModule', 'MyObject', 'fk_soc'),
			dolModuleBuilderSqlIdentifier('idx_', 'MyModule', 'MyObject', 'fk_soc')
		);

		// Residual limit of any truncation rule: past 64 characters, two keys sharing their first characters
		// still give the same identifier, so the second ALTER TABLE of the generated .key.sql fails on error 1061
		$this->assertSame(
			dolModuleBuilderSqlIdentifier('idx_', str_repeat('m', 30), str_repeat('t', 20), 'fk_product_parent'),
			dolModuleBuilderSqlIdentifier('idx_', str_repeat('m', 30), str_repeat('t', 20), 'fk_product_child')
		);
	}
}
