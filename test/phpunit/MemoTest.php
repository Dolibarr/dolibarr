<?php
/* Copyright (C) 2026		Frédéric France			<frederic.france@free.fr>
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
 * \file    test/phpunit/MemoTest.php
 * \ingroup quickmemo
 * \brief   PHPUnit test for Memo class.
 */

global $conf, $user, $langs, $db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/quickmemo/class/memo.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

$langs->load("main");

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}

$conf->global->MAIN_DISABLE_ALL_MAILS = 1;




/**
 * Class MemoTest
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class MemoTest extends CommonClassTest
{
	/**
	 * Global test setup
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		global $conf, $user, $langs, $db;
		$db->begin(); // This is to have all actions inside a transaction even if test launched without suite.

		if (!isModEnabled('quickmemo')) {
			print __METHOD__." module quickmemo must be enabled.\n";
			die(1);
		}
	}


	/**
	 * testMemoCreate
	 *
	 * @return int
	 */
	public function testMemoCreate()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Memo($db);
		$localobject->initAsSpecimen();
		$localobject->fk_element = 0;
		$localobject->element_type = 'generic';
		$result = $localobject->create($user);

		print __METHOD__." result=".$result."\n";
		$this->assertGreaterThanOrEqual(0, $result);

		return $result;
	}

	/**
	 * testMemoFetch
	 *
	 * @param   int	$id Id memo
	 * @return  Memo
	 *
	 * @depends	testMemoCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testMemoFetch($id)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Memo($db);
		$result = $localobject->fetch($id);

		$this->assertGreaterThanOrEqual(0, $result);
		print __METHOD__." id=".$id." result=".$result."\n";
		return $localobject;
	}

	/**
	 * testMemoUpdate
	 *
	 * @param  Memo $localobject Memo
	 * @return int
	 *
	 * @depends	testMemoFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testMemoUpdate($localobject)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->quick_note = 'New quick note after update';
		$result = $localobject->update($user);

		$this->assertGreaterThanOrEqual(0, $result);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		return $localobject->id;
	}

	/**
	 * testMemoDelete
	 *
	 * @param	int		$id		Id of object
	 * @return	int
	 *
	 * @depends	testMemoUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testMemoDelete($id)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Memo($db);
		print __METHOD__." id=".$id."\n";
		$result = $localobject->fetch($id);
		print __METHOD__." result=".$result."\n";
		$this->assertGreaterThanOrEqual(0, $result, 'fetch in testMemoDelete with id='.$id);

		$result = $localobject->delete($user);

		print __METHOD__." result=".$result."\n";
		$this->assertGreaterThanOrEqual(0, $result, 'delete in testMemoDelete');
		return $result;
	}
}
