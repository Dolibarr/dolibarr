<?php
/* Copyright (C) 2026 ATM Consulting
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
 */

/**
 * \file       test/phpunit/ProjectLeadSeparationTest.php
 * \ingroup    project
 * \brief      PHPUnit test for the project/opportunity UI separation (issue #23821)
 */

global $conf, $user, $langs, $db;

require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/projet/class/project.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/modules/project/mod_lead_simple.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

/**
 * Class ProjectLeadSeparationTest
 *
 * @backupGlobals disabled
 */
class ProjectLeadSeparationTest extends CommonClassTest
{
	/**
	 * Resolve WON/LOST rowids, creating them if the test dictionary lacks them.
	 *
	 * @return array{won:int,lost:int}	Resolved rowids keyed by lowercased code
	 */
	private function ensureWonLost()
	{
		global $db;

		$ids = array();
		foreach (array('WON' => 100, 'LOST' => 0) as $code => $percent) {
			$id = (int) dol_getIdFromCode($db, $code, 'c_lead_status', 'code', 'rowid');
			if ($id <= 0) {
				$sql = "INSERT INTO ".$db->prefix()."c_lead_status(code, label, percent, active)";
				$sql .= " VALUES ('".$db->escape($code)."', '".$db->escape($code)."', ".((int) $percent).", 1)";
				$db->query($sql);
				$id = (int) $db->last_insert_id($db->prefix()."c_lead_status");
			}
			$ids[strtolower($code)] = $id;
		}

		return $ids;
	}

	/**
	 * Create a minimal validated project fixture.
	 *
	 * @param  int      $usageopp	usage_opportunity flag (0/1)
	 * @param  int|null $opp		fk_opp_status value (null allowed)
	 * @return int					Created rowid
	 */
	private function makeProject($usageopp, $opp)
	{
		global $db, $user;

		$p = new Project($db);
		$p->ref = 'TESTSEP'.uniqid();
		$p->title = 'separation fixture';
		$p->statut = Project::STATUS_VALIDATED;
		$p->usage_opportunity = $usageopp;
		// Project::create() persists the fk_opp_status column from the $opp_status property.
		$p->opp_status = $opp;
		$id = $p->create($user);
		$this->assertGreaterThan(0, $id, 'fixture project create');

		return $id;
	}

	/**
	 * @return void
	 */
	public function testGetViewFilterSQLInvalidViewReturnsEmpty()
	{
		$this->assertSame('', Project::getViewFilterSQL('bogus'));
	}

	/**
	 * @return void
	 */
	public function testGetViewFilterSQLLeadFragment()
	{
		$sql = Project::getViewFilterSQL('lead', 'p');
		$this->assertStringContainsString('p.usage_opportunity = 1', $sql);
		$this->assertStringContainsString('p.fk_opp_status IS NULL', $sql);
	}

	/**
	 * @return void
	 */
	public function testPartitionIsExhaustiveAndExclusive()
	{
		global $db;

		$ids = $this->ensureWonLost();

		$idOpen = $this->makeProject(1, null);			// open opportunity
		$idLost = $this->makeProject(1, $ids['lost']);	// lost opportunity -> must fall into PROJECT view
		$idPure = $this->makeProject(0, null);			// plain project

		$inView = function ($id, $view) use ($db) {
			$sql = "SELECT rowid FROM ".$db->prefix()."projet as p WHERE p.rowid = ".((int) $id);
			$sql .= " AND ".Project::getViewFilterSQL($view, 'p');
			$resql = $db->query($sql);
			$num = $db->num_rows($resql);
			$db->free($resql);
			return $num == 1;
		};

		// Open opportunity: lead view only
		$this->assertTrue($inView($idOpen, 'lead'), 'open opp in lead');
		$this->assertFalse($inView($idOpen, 'project'), 'open opp not in project');
		// Lost opportunity: project view only (fixes the LOST gap)
		$this->assertFalse($inView($idLost, 'lead'), 'lost opp not in lead');
		$this->assertTrue($inView($idLost, 'project'), 'lost opp in project');
		// Plain project: project view only
		$this->assertFalse($inView($idPure, 'lead'), 'plain project not in lead');
		$this->assertTrue($inView($idPure, 'project'), 'plain project in project');
	}

	/**
	 * Create a validated opportunity project and return the loaded object.
	 *
	 * @param  int|null $opp	fk_opp_status value (null = open opportunity)
	 * @return Project			Validated opportunity project
	 */
	private function makeValidatedOpportunity($opp)
	{
		global $db, $user;

		$p = new Project($db);
		$p->ref = 'TESTCLO'.uniqid();
		$p->title = 'close fixture';
		$p->usage_opportunity = 1;
		$p->opp_status = $opp;
		$id = $p->create($user);
		$this->assertGreaterThan(0, $id, 'opportunity create');

		$res = $p->setValid($user);
		$this->assertGreaterThan(0, $res, 'opportunity validate');

		return $p;
	}

	/**
	 * @return void
	 */
	public function testSetCloseRejectsOpenOpportunity()
	{
		global $conf;

		$this->ensureWonLost();
		$conf->global->PROJECT_USE_OPPORTUNITIES = 1;

		$p = $this->makeValidatedOpportunity(null);
		$res = $p->setClose($GLOBALS['user']);	// no WON/LOST -> refused

		$this->assertSame(-1, $res);
		$this->assertStringContainsString('ErrorCloseRequiresWonLost', $p->error);
	}

	/**
	 * @return void
	 */
	public function testSetCloseAcceptsWon()
	{
		global $conf;

		$ids = $this->ensureWonLost();
		$conf->global->PROJECT_USE_OPPORTUNITIES = 1;

		$p = $this->makeValidatedOpportunity(null);
		$res = $p->setClose($GLOBALS['user'], $ids['won']);	// WON provided -> accepted

		$this->assertSame(1, $res);
	}

	/**
	 * @return void
	 */
	public function testLeadNumberingPrefixAndIsolation()
	{
		global $db, $user;

		// A PJ reference must not shift the OPP counter.
		$pj = new Project($db);
		$pj->ref = 'PJ9912-9000';
		$pj->title = 'pj numbering';
		$pj->usage_opportunity = 0;
		$this->assertGreaterThan(0, $pj->create($user));

		$model = new mod_lead_simple();
		$project = new Project($db);
		$project->date_c = dol_mktime(0, 0, 0, 1, 1, 2026);
		$ref = $model->getNextValue(null, $project);

		$this->assertStringStartsWith('OPP', $ref);
		$this->assertMatchesRegularExpression('/^OPP[0-9]{4}-[0-9]{4}$/', $ref);
	}
}
