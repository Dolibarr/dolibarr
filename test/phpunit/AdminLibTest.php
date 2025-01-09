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
}
