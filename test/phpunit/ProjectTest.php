<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
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

	/**
	 * The 'openedopp' and 'notopenedopp' statistics filters must be an exhaustive partition:
	 * a LOST opportunity has to be reported by exactly one of them, and plain projects stay
	 * on the 'notopenedopp' side.
	 *
	 * @return void
	 */
	public function testProjectStatsOpportunityPartition()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$idlost = (int) dol_getIdFromCode($db, 'LOST', 'c_lead_status', 'code', 'rowid');
		$idprosp = (int) dol_getIdFromCode($db, 'PROSP', 'c_lead_status', 'code', 'rowid');
		$this->assertGreaterThan(0, $idlost, 'Dictionary c_lead_status must provide the LOST status');
		$this->assertGreaterThan(0, $idprosp, 'Dictionary c_lead_status must provide the PROSP status');

		$idlostopp = $this->createProjectForOpportunityStats($db, $user, 1, $idlost);
		$idopenopp = $this->createProjectForOpportunityStats($db, $user, 1, $idprosp);
		$idproject = $this->createProjectForOpportunityStats($db, $user, 0, 0);

		// A lost opportunity is not open any more, but it must not disappear from the statistics
		$this->assertSame(0, $this->countProjectInStats($db, 'openedopp', $idlostopp));
		$this->assertSame(1, $this->countProjectInStats($db, 'notopenedopp', $idlostopp));

		// Unchanged behaviour for an open opportunity and for a plain project
		$this->assertSame(1, $this->countProjectInStats($db, 'openedopp', $idopenopp));
		$this->assertSame(0, $this->countProjectInStats($db, 'notopenedopp', $idopenopp));
		$this->assertSame(0, $this->countProjectInStats($db, 'openedopp', $idproject));
		$this->assertSame(1, $this->countProjectInStats($db, 'notopenedopp', $idproject));
	}

	/**
	 * Create a project row dedicated to the opportunity statistics test
	 *
	 * @param	DoliDB	$db					Database handler
	 * @param	User	$user				User doing the creation
	 * @param	int		$usageopportunity	1 if the project is used to follow an opportunity
	 * @param	int		$oppstatus			Opportunity status rowid (c_lead_status), 0 for none
	 * @return	int							Id of the created project
	 */
	private function createProjectForOpportunityStats($db, $user, $usageopportunity, $oppstatus)
	{
		$project = new Project($db);
		$project->initAsSpecimen();
		$project->ref = 'PJSTAT'.$usageopportunity.$oppstatus.dol_print_date(dol_now(), '%Y%m%d%H%M%S');
		$project->usage_opportunity = $usageopportunity;
		$project->opp_status = $oppstatus;
		$result = $project->create($user);

		$this->assertGreaterThan(0, $result, 'Failed to create the project of the statistics test');

		return (int) $result;
	}

	/**
	 * Count how many times a given project is reported by one opportunity statistics filter
	 *
	 * @param	DoliDB	$db			Database handler
	 * @param	string	$oppfilter	Value of ProjectStats::opp_status, 'openedopp' or 'notopenedopp'
	 * @param	int		$id			Id of the project to look for
	 * @return	int					Number of matching rows, 0 or 1
	 */
	private function countProjectInStats($db, $oppfilter, $id)
	{
		require_once dirname(__FILE__).'/../../htdocs/projet/class/projectstats.class.php';

		$stats = new ProjectStats($db);
		$stats->opp_status = $oppfilter;
		$sqlwhere = $stats->buildWhere();

		$sql = "SELECT COUNT(t.rowid) as nb";
		$sql .= " FROM ".$db->prefix()."projet as t";
		$sql .= $sqlwhere;
		$sql .= " AND t.rowid = ".((int) $id);

		$resql = $db->query($sql);
		$this->assertNotFalse($resql, 'Statistics filter produced an invalid SQL request');
		$obj = $db->fetch_object($resql);
		$nb = (int) $obj->nb;
		$db->free($resql);

		return $nb;
	}
}
