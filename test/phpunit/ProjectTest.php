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
	 * testTaskAddTimeSpentStatus: adding time spent sets a draft, validated or ongoing task to ongoing
	 * (or closed when progress is 100%), and never changes the status of a closed or canceled task.
	 *
	 * @return	void
	 */
	public function testTaskAddTimeSpentStatus()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$project = new Project($db);
		$project->initAsSpecimen();
		$project->ref = 'PJTIMESPENT'.dol_print_date(dol_now(), 'dayhourlog').mt_rand(100, 999);
		$projectid = $project->create($user);
		$this->assertGreaterThan(0, $projectid, 'Project create: '.$project->error);

		$task = new Task($db);
		$task->initAsSpecimen();
		$task->ref = 'TKTIMESPENT'.mt_rand(100, 999);
		$task->fk_project = $projectid;
		$taskid = $task->create($user);
		$this->assertGreaterThan(0, $taskid, 'Task create: '.$task->error);

		// array(status before, progress submitted or null to keep the stored one, expected status after)
		$cases = array(
			'draft becomes ongoing' => array(Task::STATUS_DRAFT, 0, Task::STATUS_ONGOING),
			'validated becomes ongoing' => array(Task::STATUS_VALIDATED, 0, Task::STATUS_ONGOING),
			'ongoing stays ongoing with progress 0' => array(Task::STATUS_ONGOING, 0, Task::STATUS_ONGOING),
			'ongoing stays ongoing without progress' => array(Task::STATUS_ONGOING, null, Task::STATUS_ONGOING),
			'ongoing becomes closed with progress 100' => array(Task::STATUS_ONGOING, 100, Task::STATUS_CLOSED),
			'closed stays closed' => array(Task::STATUS_CLOSED, 50, Task::STATUS_CLOSED),
			'canceled stays canceled' => array(Task::STATUS_CANCELED, 0, Task::STATUS_CANCELED),
		);
		foreach ($cases as $label => $case) {
			list($statusbefore, $progress, $statusexpected) = $case;

			$resql = $db->query("UPDATE ".MAIN_DB_PREFIX."projet_task SET fk_statut = ".((int) $statusbefore).", progress = 0 WHERE rowid = ".((int) $taskid));
			$this->assertNotFalse($resql, $label.': '.$db->lasterror());

			$task = new Task($db);
			$task->fetch($taskid);
			if ($progress !== null) {
				$task->progress = $progress;
			}
			$task->timespent_date = dol_now();
			$task->timespent_withhour = 0;
			$task->timespent_duration = 3600;
			$task->timespent_fk_user = $user->id;
			$result = $task->addTimeSpent($user);
			$this->assertGreaterThan(0, $result, $label.': '.$task->error);
			$this->assertEquals($statusexpected, $task->status, $label.' (object)');

			$task = new Task($db);
			$task->fetch($taskid);
			$this->assertEquals($statusexpected, $task->status, $label.' (database)');
		}
	}

	/**
	 * testProjectOdtTemplateWithoutOptionalBlocks: the ODT generator accepts a template without the blocks tasks, projectfiles and projectcontacts
	 *
	 * @return	void
	 */
	public function testProjectOdtTemplateWithoutOptionalBlocks()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		require_once DOL_DOCUMENT_ROOT.'/core/modules/project/doc/doc_generic_project_odt.modules.php';

		$project = new Project($db);
		$project->initAsSpecimen();
		$project->ref = 'ODFTEST31043';
		$this->assertGreaterThan(0, $project->create($user));
		// A contact is needed to run the block of the contacts
		$this->assertGreaterThan(0, $project->add_contact($user->id, 'PROJECTLEADER', 'internal'));

		$objdoc = new doc_generic_project_odt($db);
		try {
			// An ODT with none of the blocks of a project template, then the template of the project that has all of them
			$result = $objdoc->write_file($project, $langs, DOL_DOCUMENT_ROOT.'/install/doctemplates/invoices/template_invoice.odt');
			$this->assertEquals(1, $result);
			$result = $objdoc->write_file($project, $langs, DOL_DOCUMENT_ROOT.'/install/doctemplates/projects/template_project.odt');
			$this->assertEquals(1, $result);
		} finally {
			dol_delete_dir_recursive($conf->project->dir_output.'/'.$project->ref);
		}

		print __METHOD__." result=".$result."\n";
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
