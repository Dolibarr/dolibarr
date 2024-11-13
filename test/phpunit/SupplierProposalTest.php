<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       test/phpunit/SupplierProposalTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/supplier_proposal/class/supplier_proposal.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for user nb 1 (that should be admin)\n";
	$user->fetch(1);

	//$user->addrights(0, 'supplier_proposal');

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
class SupplierProposalTest extends CommonClassTest
{
	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		global $conf,$user,$langs,$db;
		$db->begin();	// This is to have all actions inside a transaction even if test launched without suite.

		if (!getDolGlobalString('MAIN_MODULE_SUPPLIERPROPOSAL')) {
			print "\n".__METHOD__." module Supplier proposal must be enabled.\n";
			die(1);
		}

		print __METHOD__."\n";
	}

	/**
	 * Init phpunit tests
	 *
	 * @return	void
	 */
	protected function setUp(): void
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		print __METHOD__."\n";
		//print $db->getVersion()."\n";

		// Set permission not set by default sql sample
		$user->addrights(0, 'supplier_proposal');
		$user->getrights('supplier_proposal', 1);
	}

	/**
	 * testSupplierProposalCreate
	 *
	 * @return	void
	 */
	public function testSupplierProposalCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new SupplierProposal($db);
		$localobject->initAsSpecimen();
		$result = $localobject->create($user);

		$this->assertLessThan($result, 0);
		print __METHOD__." result=".$result."\n";
		return $result;
	}

	/**
	 * testSupplierProposalFetch
	 *
	 * @param	int		$id		Id of object
	 * @return	void
	 *
	 * @depends	testSupplierProposalCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testSupplierProposalFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new SupplierProposal($db);
		$result = $localobject->fetch($id);

		$this->assertLessThan($result, 0);
		print __METHOD__." id=".$id." result=".$result."\n";
		return $localobject;
	}

	/**
	 * testSupplierProposalAddLine
	 *
	 * @param	SupplierProposal	$localobject	Proposal
	 * @return	void
	 *
	 * @depends	testSupplierProposalFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testSupplierProposalAddLine($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->fetch_thirdparty();
		$result = $localobject->addline('Added line', 10, 2, 19.6);

		$this->assertLessThan($result, 0);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		return $localobject;
	}

	/**
	 * testSupplierProposalValid
	 *
	 * @param	SupplierProposal	$localobject	Proposal
	 * @return	SupplierProposal
	 *
	 * @depends	testSupplierProposalAddLine
	 * The depends says test is run only if previous is ok
	 */
	public function testSupplierProposalValid($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $user->addrights(0, 'supplier_proposal');
		$this->assertLessThan($result, 0);

		$result = $user->getrights('supplier_proposal', 1);
		//$this->assertLessThan($result, 0);

		$result = $localobject->valid($user);
		$this->assertLessThan($result, 0);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $localobject;
	}

	/**
	 * testSupplierProposalOther
	 *
	 * @param	SupplierProposal	$localobject	Proposal
	 * @return	int
	 *
	 * @depends testSupplierProposalValid
	 * The depends says test is run only if previous is ok
	 */
	public function testSupplierProposalOther($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		/*$result=$localobject->setstatus(0);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		*/

		$localobject->info($localobject->id);
		print __METHOD__." localobject->date_creation=".$localobject->date_creation."\n";
		$this->assertNotEquals($localobject->date_creation, '');

		return $localobject->id;
	}

	/**
	 * testSupplierProposalDelete
	 *
	 * @param	int		$id		Id of proposal
	 * @return	void
	 *
	 * @depends	testSupplierProposalOther
	 * The depends says test is run only if previous is ok
	 */
	public function testSupplierProposalDelete($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new SupplierProposal($db);
		$result = $localobject->fetch($id);
		$result = $localobject->delete($user);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $result;
	}
}
