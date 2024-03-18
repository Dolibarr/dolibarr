<?php
/* Copyright (C) 2010-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       test/phpunit/UtilsTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/utils.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->getrights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class UtilsTest extends CommonClassTest
{
	/**
	 * testExecuteCLI
	 *
	 * @return  void
	 */
	public function testExecuteCLI()
	{
		// Needs ls. Skip test if not running on *nix system.
		if ($this->fakeAssertIfNotUnix(__METHOD__." only works on *nix")) {
			return;
		}

		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Utils($db);
		$result = $localobject->executeCLI('ls', $conf->admin->dir_temp.'/out.tmp', 1);
		print var_export($result, true);
		$this->assertEquals($result['result'], 0);
		$this->assertEquals($result['error'], '');
		//$this->assertEquals(preg_match('/phpunit/', $result['output']), 1);

		$localobject = new Utils($db);
		$result = $localobject->executeCLI('ls', $conf->admin->dir_temp.'/out.tmp', 2);
		print var_export($result, true);
		$this->assertEquals($result['result'], 0);
		$this->assertEquals($result['error'], '');
		//$this->assertEquals(preg_match('/phpunit/', $result['output']), 1);

		print __METHOD__." result=".$result['result']."\n";
		return $result;
	}
}
