<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
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
 *      \file       test/phpunit/UserGroupTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql'); // This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/user/class/usergroup.class.php';
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
class UserGroupTest extends CommonClassTest
{
	/**
	 * testUserGroupCreate
	 *
	 * @return	void
	 */
	public function testUserGroupCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new UserGroup($db);
		$localobject->initAsSpecimen();
		$result = $localobject->create($user);

		$this->assertLessThan($result, 0);
		print __METHOD__." result=".$result."\n";
		return $result;
	}

	/**
	 * testUserGroupFetch
	 *
	 * @param   int $id             Id of group
	 * @return  void
	 * @depends testUserGroupCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testUserGroupFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new UserGroup($db);
		$result = $localobject->fetch($id);

		$this->assertLessThan($result, 0);
		print __METHOD__." id=".$id." result=".$result."\n";
		return $localobject;
	}

	/**
	 * testUserGroupUpdate
	 *
	 * @param   UserGroup $localobject Group
	 * @return  void
	 * @depends testUserGroupFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testUserGroupUpdate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->note = 'New note after update';
		$result = $localobject->update($user);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $localobject;
	}

	/**
	 * testUserGroupAddRight
	 *
	 * @param   UserGroup $localobject Object to show
	 * @return  void
	 * @depends testUserGroupUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testUserGroupAddRight($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->addrights(1, 'bookmarks');
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$this->assertLessThan($result, 0);
		return $localobject;
	}

	/**
	 * testUserGroupDelRight
	 *
	 * @param   UserGroup $localobject Object
	 * @return  void
	 * @depends testUserGroupAddRight
	 * The depends says test is run only if previous is ok
	 */
	public function testUserGroupDelRight($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->delrights(1, 'bookmarks');
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$this->assertLessThan($result, 0);
		return $localobject;
	}

	/**
	 * testUserGroupOther
	 *
	 * @param   UserGroup $localobject Object
	 * @return  void
	 * @depends testUserGroupDelRight
	 * The depends says test is run only if previous is ok
	 */
	public function testUserGroupOther($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->generateDocument('templatenamethadoesnotexist', $langs);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(-1, $result, 'Calling generateDocument with a not existing template should return 0');

		return $localobject->id;
	}

	/**
	 * testUserGroupDelete
	 *
	 * @param   int $id             Id of object
	 * @return  void
	 * @depends testUserGroupOther
	 * The depends says test is run only if previous is ok
	 */
	public function testUserGroupDelete($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new UserGroup($db);
		$result = $localobject->fetch($id);
		$result = $localobject->delete($user);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $result;
	}
}
