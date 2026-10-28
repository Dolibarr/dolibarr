<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024-2025  Frédéric France         <frederic.france@free.fr>
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
 *      \file       test/phpunit/ExpenseReportTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db,$mysoc;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/expensereport/class/expensereport.class.php';

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
class ExpenseReportTest extends CommonClassTest
{
	/**
	 * testExpenseReportCreate
	 *
	 * @return	void
	 */
	public function testExpenseReportCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// Create expense report
		$localobject = new ExpenseReport($db);
		$localobject->initAsSpecimen();         // Init a specimen with lines
		$localobject->status = 0;
		$localobject->fk_statut = 0;
		$localobject->date_fin = null;  // Force bad value

		$result = $localobject->create($user);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(-1, $result, "Error on test ExpenseReport create 1 : ".$localobject->error);       // must be -1 because of missing mandatory fields

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."expensereport where ref=''";
		$db->query($sql);

		// Create expense report
		$localobject2 = new ExpenseReport($db);
		$localobject2->initAsSpecimen();        // Init a specimen with lines
		$localobject2->status = 0;
		$localobject2->fk_statut = 0;

		$result = $localobject2->create($user);
		print __METHOD__." result=".$result."\n";
		$this->assertGreaterThanOrEqual(0, $result, "Error on test ExpenseReport create 2 : ".$localobject2->error);

		return $result;
	}


	/**
	 * testExpenseReportFetch
	 *
	 * @param   int $id     Id of expense report
	 * @return  void
	 *
	 * @depends testExpenseReportCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testExpenseReportFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new ExpenseReport($db);
		$result = $localobject->fetch($id);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $localobject;
	}

	/**
	 * testExpenseReportValid
	 *
	 * @param   ExpenseReport $localobject     ExpenseReport
	 * @return  void
	 *
	 * @depends testExpenseReportFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testExpenseReportValid($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->setValidate($user);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $localobject;
	}

	/**
	 * testExpenseReportApprove
	 *
	 * @param   ExpenseReport $localobject ExpenseReport
	 * @return  void
	 *
	 * @depends testExpenseReportValid
	 * The depends says test is run only if previous is ok
	 */
	public function testExpenseReportApprove($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->setApproved($user);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $localobject;
	}

	/**
	 * testExpenseReportCancel
	 *
	 * @param   ExpenseReport  $localobject        ExpenseReport
	 * @return  void
	 *
	 * @depends testExpenseReportApprove
	 * The depends says test is run only if previous is ok
	 */
	public function testExpenseReportCancel($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->set_cancel($user, 'Because...');

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $localobject;
	}

	/**
	 * testExpenseReportOther
	 *
	 * @param   ExpenseReport $localobject     ExpenseReport
	 * @return  void
	 *
	 * @depends testExpenseReportCancel
	 * The depends says test is run only if previous is ok
	 */
	public function testExpenseReportOther($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->getSumPayments();
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertGreaterThanOrEqual(0, $result);

		return $localobject->id;
	}

	/**
	 * testExpenseReportDelete
	 *
	 * @param   int $id     Id of order
	 * @return  void
	 *
	 * @depends testExpenseReportOther
	 * The depends says test is run only if previous is ok
	 */
	public function testExpenseReportDelete($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new ExpenseReport($db);
		$result = $localobject->fetch($id);
		$result = $localobject->delete($user);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertGreaterThan(0, $result);
		return $result;
	}

	/**
	 * testExpenseReportLineFetch
	 *
	 * @return	void
	 */
	public function testExpenseReportLineFetch()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$expensereport = new ExpenseReport($db);
		$expensereport->initAsSpecimen();
		$expensereport->status = 0;
		$expensereport->fk_statut = 0;
		$createresult = $expensereport->create($user);
		$this->assertGreaterThan(0, $createresult, "Setup failed, cannot create expense report: ".$expensereport->error);

		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."expensereport_det WHERE fk_expensereport = ".((int) $expensereport->id)." ORDER BY rowid ASC";
		$resql = $db->query($sql);
		$lineobj = ($resql ? $db->fetch_object($resql) : null);
		$this->assertNotNull($lineobj, "Setup failed, no line created for the expense report");
		$lineid = (int) $lineobj->rowid;

		$line = new ExpenseReportLine($db);
		$fetchresult = $line->fetch($lineid);
		print __METHOD__." lineid=".$lineid." result=".$fetchresult."\n";

		$this->assertGreaterThan(0, $fetchresult, "ExpenseReportLine::fetch() must succeed for an existing line");
		$this->assertEquals($lineid, $line->id, "Fetched line id does not match the requested one");

		$expensereport->delete($user);
	}

	/**
	 * testExpenseReportInForeignCurrency
	 *
	 * Lines typed in the currency of the expense report are converted to the company currency with its rate,
	 * and a new rate converts them again.
	 *
	 * @return	void
	 */
	public function testExpenseReportInForeignCurrency()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$expensereport = new ExpenseReport($db);
		$expensereport->initAsSpecimen();
		$expensereport->lines = array();
		$expensereport->status = 0;
		$expensereport->fk_statut = 0;
		$expensereport->multicurrency_code = 'USD';
		$expensereport->multicurrency_tx = 0.8;
		$this->assertGreaterThan(0, $expensereport->create($user), (string) $expensereport->error);

		$resql = $db->query("SELECT id FROM ".MAIN_DB_PREFIX."c_type_fees WHERE active = 1 ORDER BY id LIMIT 1");
		$typefeesid = (int) $db->fetch_object($resql)->id;

		$expensereport->fetch($expensereport->id);
		$lineid = $expensereport->addline(1, 0, $typefeesid, 0, dol_now(), 'Taxi', 0, 0, 0, 0, 100);
		$this->assertGreaterThan(0, $lineid, (string) $expensereport->error);

		$expensereport->fetch($expensereport->id);
		$this->assertEquals(100, (float) $expensereport->lines[0]->multicurrency_total_ttc);
		$this->assertEquals(125, (float) $expensereport->lines[0]->total_ttc);
		$this->assertEquals(100, (float) $expensereport->multicurrency_total_ttc);
		$this->assertEquals(125, (float) $expensereport->total_ttc);

		$this->assertGreaterThan(0, $expensereport->updateline($lineid, $typefeesid, 0, 0, 'Taxi', 1, 0, dol_now(), $expensereport->id, 0, 0, 0, 50), (string) $expensereport->error);
		$expensereport->fetch($expensereport->id);
		$this->assertEquals(50, (float) $expensereport->multicurrency_total_ttc);
		$this->assertEquals(62.5, (float) $expensereport->total_ttc);

		$this->assertGreaterThan(0, $expensereport->setMulticurrencyRate(0.5, 1));
		$expensereport->fetch($expensereport->id);
		$this->assertEquals(50, (float) $expensereport->multicurrency_total_ttc, 'The amounts typed in the currency of the report are kept');
		$this->assertEquals(100, (float) $expensereport->total_ttc, 'The amounts in company currency follow the new rate');

		$expensereport->delete($user);
	}

	/**
	 * testExpenseReportInCompanyCurrency
	 *
	 * An expense report created with its lines and no currency is in the company currency, with the same amounts in both.
	 *
	 * @return	void
	 */
	public function testExpenseReportInCompanyCurrency()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$expensereport = new ExpenseReport($db);
		$expensereport->initAsSpecimen();
		$expensereport->status = 0;
		$expensereport->fk_statut = 0;
		$this->assertGreaterThan(0, $expensereport->create($user), (string) $expensereport->error);

		$expensereport->fetch($expensereport->id);
		$this->assertSame($conf->currency, $expensereport->multicurrency_code);
		$this->assertGreaterThan(0, (float) $expensereport->total_ttc);
		$this->assertEquals((float) $expensereport->total_ttc, (float) $expensereport->multicurrency_total_ttc);

		$expensereport->delete($user);
	}
}
