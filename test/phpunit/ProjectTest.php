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
 *      \file       test/phpunit/ProjectTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/projet/class/project.class.php';
require_once dirname(__FILE__).'/../../htdocs/projet/class/task.class.php';
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
class ProjectTest extends CommonClassTest
{
	/**
	 * testProjectCreate
	 *
	 * @return	void
	 */
	public function testProjectCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Project($db);
		$localobject->initAsSpecimen();
		$result = $localobject->create($user);

		$this->assertLessThan($result, 0);
		print __METHOD__." result=".$result."\n";
		return $result;
	}

	/**
	 * testProjectFetch
	 *
	 * @param	int		$id		Id of object
	 * @return	void
	 *
	 * @depends	testProjectCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testProjectFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Project($db);
		$result = $localobject->fetch($id);

		$this->assertLessThan($result, 0);
		print __METHOD__." id=".$id." result=".$result."\n";
		return $localobject;
	}

	/**
	 * testProjectValid
	 *
	 * @param	Project	$localobject	Project
	 * @return	Project
	 *
	 * @depends	testProjectFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testProjectValid($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->setValid($user);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $localobject;
	}

	/**
	 * testProjectOther
	 *
	 * @param	Project	$localobject	Project
	 * @return	int
	 *
	 * @depends testProjectValid
	 * The depends says test is run only if previous is ok
	 */
	public function testProjectOther($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->setClose($user);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $localobject->id;
	}


	/**
	 * testTaskCreate
	 *
	 * @param	int		$idproject		ID project
	 * @return	void
	 *
	 * @depends testProjectOther
	 * The depends says test is run only if previous is ok
	 */
	public function testTaskCreate($idproject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Task($db);
		$localobject->initAsSpecimen();
		$localobject->fk_project = $idproject;
		$localobject->billable = 1;
		$result = $localobject->create($user);

		$this->assertLessThan($result, 0);
		print __METHOD__." result=".$result."\n";
		return $result;
	}

	/**
	 * testTaskFetch
	 *
	 * @param	int		$id		Id of object
	 * @return	void
	 *
	 * @depends	testTaskCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testTaskFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Task($db);
		$result = $localobject->fetch($id);

		$this->assertLessThan($result, 0);
		print __METHOD__." id=".$id." result=".$result."\n";

		return $localobject;
	}

	/**
	 * testTaskOther
	 *
	 * @param	Task	$localobject	Task
	 * @return	int
	 *
	 * @depends testTaskFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testTaskOther($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$usertoprocess = $user;
		$onlyopenedproject = 0;

		$taskstatic = new Task($db);
		//$result = $localobject->setClose($user);
		$projectsrole = $taskstatic->getUserRolesForProjectsOrTasks($usertoprocess, null, $localobject->fk_project, 0, $onlyopenedproject);
		$tasksrole = $taskstatic->getUserRolesForProjectsOrTasks(null, $usertoprocess, $localobject->fk_project, 0, $onlyopenedproject);

		print __METHOD__." id=".$localobject->id."\n";
		$this->assertEquals(count($projectsrole), 0);
		$this->assertEquals(count($tasksrole), 0);

		return $localobject->fk_project;
	}


	/**
	 * testProjectDelete
	 *
	 * @param	int		$id		Id of project
	 * @return	void
	 *
	 * @depends	testTaskOther
	 * The depends says test is run only if previous is ok
	 */
	public function testProjectDelete($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Project($db);
		$result = $localobject->fetch($id);
		$result = $localobject->delete($user);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $result;
	}
}
