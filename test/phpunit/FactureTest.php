<?php
/* Copyright (C) 2010       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2023       Alexandre Janniaux      <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 *      \file       test/phpunit/FactureTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/compta/facture/class/facture.class.php';
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
class FactureTest extends CommonClassTest
{
	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		self::assertTrue(isModEnabled('facture'), " module customer invoice must be enabled");
		self::assertFalse(isModEnabled('ecotaxdeee'), " module ecotaxdeee must not be enabled");
		parent::setUpBeforeClass();
	}


	/**
	 * testFactureCreate
	 *
	 * @return int
	 */
	public function testFactureCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Facture($db);
		$localobject->initAsSpecimen();
		$result = $localobject->create($user);
		$this->assertLessThan($result, 0);
		print __METHOD__." result=".$result."\n";
		return $result;
	}

	/**
	 * testFactureFetch
	 *
	 * @param   int $id     Id invoice
	 * @return  int
	 *
	 * @depends testFactureCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testFactureFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Facture($db);
		$result = $localobject->fetch($id);

		$this->assertLessThan($result, 0);
		print __METHOD__." id=".$id." result=".$result."\n";
		return $localobject;
	}

	/**
	 * testFactureFetch
	 *
	 * @param   Facture $localobject Invoice
	 * @return  int
	 *
	 * @depends testFactureFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testFactureUpdate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$this->changeProperties($localobject);
		$result = $localobject->update($user);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $localobject;
	}

	/**
	 * testFactureValid
	 *
	 * @param   Facture $localobject Invoice
	 * @return  void
	 *
	 * @depends testFactureUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testFactureValid($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->validate($user);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$this->assertLessThan($result, 0);

		// Test everything is still the same as specimen
		$newlocalobject = new Facture($db);
		$newlocalobject->initAsSpecimen();
		$this->changeProperties($newlocalobject);

		// Hack to avoid test to be wrong when module sellyoursaas is on
		unset($localobject->array_options['options_commission']);
		unset($localobject->array_options['options_reseller']);

		$arraywithdiff = $this->objCompare(
			$localobject,
			$newlocalobject,
			true,
			// Not comparing:
			array(
				'newref','oldref','id','lines','client','thirdparty','brouillon','user_creation_id','date_creation','date_validation','datem','date_modification',
				'ref','statut','status','paye','specimen','ref','actiontypecode','actionmsg2','actionmsg','mode_reglement','cond_reglement',
				'cond_reglement_doc', 'modelpdf',
				'multicurrency_total_ht','multicurrency_total_tva',	'multicurrency_total_ttc','fk_multicurrency','multicurrency_code','multicurrency_tx',
				'retained_warranty' ,'retained_warranty_date_limit', 'retained_warranty_fk_cond_reglement', 'specimen', 'situation_cycle_ref', 'situation_counter', 'situation_final',
				'trackid','user_creat','user_valid'
			)
		);
		$this->assertEquals($arraywithdiff, array());    // Actual, Expected

		return $localobject;
	}

	/**
	 * testFactureOther
	 *
	 * @param   Facture $localobject Invoice
	 * @return  int
	 *
	 * @depends testFactureValid
	 * The depends says test is run only if previous is ok
	 */
	public function testFactureOther($localobject)
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

		$result = $localobject->demande_prelevement($user);
		print __METHOD__." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $localobject->id;
	}

	/**
	 * testFactureDelete
	 *
	 * @param   int $id     Id of invoice
	 * @return  int
	 *
	 * @depends testFactureOther
	 * The depends says test is run only if previous is ok
	 */
	public function testFactureDelete($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// Force default setup
		unset($conf->global->INVOICE_CAN_ALWAYS_BE_REMOVED);
		unset($conf->global->INVOICE_CAN_NEVER_BE_REMOVED);

		$localobject = new Facture($db);
		$result = $localobject->fetch($id);

		// Create another invoice and validate it after $localobject
		$localobject2 = new Facture($db);
		$result = $localobject2->initAsSpecimen();
		$result = $localobject2->create($user);
		$result = $localobject2->validate($user);
		print 'Invoice $localobject ref = '.$localobject->ref."\n";
		print 'Invoice $localobject2 created with ref = '.$localobject2->ref."\n";

		$conf->global->INVOICE_CAN_NEVER_BE_REMOVED = 1;

		$result = $localobject2->delete($user);					// Deletion is KO, option INVOICE_CAN_NEVER_BE_REMOVED is on
		print __METHOD__." id=".$localobject2->id." ref=".$localobject2->ref." result=".$result."\n";
		$this->assertEquals(0, $result, 'Deletion should fail, option INVOICE_CAN_NEVER_BE_REMOVED is on');

		unset($conf->global->INVOICE_CAN_NEVER_BE_REMOVED);

		$result = $localobject->delete($user);					// Deletion is KO, it is not last invoice
		print __METHOD__." id=".$localobject->id." ref=".$localobject->ref." result=".$result."\n";
		$this->assertEquals(0, $result, 'Deletion should fail, it is not last invoice');

		$result = $localobject2->delete($user);					// Deletion is OK, it is last invoice
		print __METHOD__." id=".$localobject2->id." ref=".$localobject2->ref." result=".$result."\n";
		$this->assertGreaterThan(0, $result, 'Deletion should work, it is last invoice');

		$result = $localobject->delete($user);					// Deletion is KO, it is not last invoice
		print __METHOD__." id=".$localobject->id." ref=".$localobject->ref." result=".$result."\n";
		$this->assertGreaterThan(0, $result, 'Deletion should work, it is again last invoice');

		return $result;
	}

	/**
	 * Edit an object to test updates
	 *
	 * @param   Facture $localobject        Object Facture
	 * @return  void
	 */
	public function changeProperties(&$localobject)
	{
		$localobject->note_private = 'New note';
		//$localobject->note='New note after update';
	}
}
