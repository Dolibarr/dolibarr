<?php
/* Copyright (C) 2010       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2026  Frédéric France         <frederic.france@free.fr>
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
require_once dirname(__FILE__).'/../../htdocs/core/modules/modBlockedLog.class.php';
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
		self::assertTrue(isModEnabled('invoice'), " module customer invoice must be enabled");
		self::assertFalse(isModEnabled('ecotaxdeee'), " module ecotaxdeee must not be enabled");
		parent::setUpBeforeClass();

		// We disable module blocked log to avoid interference with tests
		global $db;
		$blockedlogmodule = new modBlockedLog($db);
		$blockedlogmodule->remove();
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

		// Specimen lines are built from real products picked at random (see Facture::initAsSpecimen), so the
		// exact line count is not stable (a kit/BOM product can expand into extra lines) - only check totals coherence.
		$this->assertNotEmpty($localobject->lines);
		$this->assertLineTotalsMatchHeader($localobject, 'after fetch');

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
	 * testFactureAddLine
	 *
	 * @param	Facture	$localobject	Invoice
	 * @return	array{0:Facture,1:int}	Invoice and id of the line added
	 *
	 * @depends testFactureUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testFactureAddLine($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->fetch_thirdparty();
		$beforelinecount = count($localobject->lines);
		$beforetotalht = (float) $localobject->total_ht;

		$lineid = $localobject->addline('PHPUnit addline test', 100, 2, 20);	// 2 x 100 HT at 20% VAT = 200 HT / 40 VAT / 240 TTC

		print __METHOD__." id=".$localobject->id." lineid=".$lineid."\n";
		$this->assertGreaterThan(0, $lineid, $localobject->errorsToString());

		$localobject->fetch($localobject->id);
		$this->assertCount($beforelinecount + 1, $localobject->lines);
		$this->assertEqualsWithDelta($beforetotalht + 200, (float) $localobject->total_ht, 0.01, 'total_ht not updated after addline');
		$this->assertLineTotalsMatchHeader($localobject, 'after addline');

		return array($localobject, $lineid);
	}

	/**
	 * testFactureUpdateLine
	 *
	 * @param	array{0:Facture,1:int}	$params	Invoice and id of the line to update
	 * @return	array{0:Facture,1:int}			Invoice and id of the line updated
	 *
	 * @depends testFactureAddLine
	 * The depends says test is run only if previous is ok
	 */
	public function testFactureUpdateLine($params)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		list($localobject, $lineid) = $params;
		$beforelinecount = count($localobject->lines);
		$beforetotalht = (float) $localobject->total_ht;

		$result = $localobject->updateline($lineid, 'PHPUnit addline test', 100, 3, 0, '', '', 20);	// qty 2 -> 3, so +100 HT / +20 VAT / +120 TTC

		print __METHOD__." id=".$localobject->id." lineid=".$lineid." result=".$result."\n";
		$this->assertGreaterThan(0, $result, $localobject->errorsToString());

		$localobject->fetch($localobject->id);
		$this->assertCount($beforelinecount, $localobject->lines);
		$this->assertEqualsWithDelta($beforetotalht + 100, (float) $localobject->total_ht, 0.01, 'total_ht not updated after updateline');
		$this->assertLineTotalsMatchHeader($localobject, 'after updateline');

		return array($localobject, $lineid);
	}

	/**
	 * testFactureDeleteLine
	 *
	 * @param	array{0:Facture,1:int}	$params	Invoice and id of the line to delete
	 * @return	Facture
	 *
	 * @depends testFactureUpdateLine
	 * The depends says test is run only if previous is ok
	 */
	public function testFactureDeleteLine($params)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		list($localobject, $lineid) = $params;
		$beforelinecount = count($localobject->lines);
		$beforetotalht = (float) $localobject->total_ht;

		$result = $localobject->deleteLine($lineid);

		print __METHOD__." id=".$localobject->id." lineid=".$lineid." result=".$result."\n";
		$this->assertGreaterThan(0, $result, $localobject->errorsToString());

		$localobject->fetch($localobject->id);
		// Back to the original specimen lines, with the same totals
		$this->assertCount($beforelinecount - 1, $localobject->lines);
		$this->assertEqualsWithDelta($beforetotalht - 300, (float) $localobject->total_ht, 0.01, 'total_ht not updated after deleteLine');
		$this->assertLineTotalsMatchHeader($localobject, 'after deleteLine');

		return $localobject;
	}

	/**
	 * testFactureValid
	 *
	 * @param   Facture $localobject Invoice
	 * @return  Facture
	 *
	 * @depends testFactureDeleteLine
	 * The depends says test is run only if previous is ok
	 */
	public function testFactureValid($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// Force to default setup
		$conf->global->FAC_FORCE_DATE_VALIDATION = 0;
		$conf->global->INVOICE_CHECK_POSTERIOR_DATE = 0;

		$result = $localobject->validate($user);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$this->assertLessThan($result, 0);

		// Test everything is still the same as a freshly built specimen with the same mutation applied
		// (catches unwanted field changes introduced by update()/validate())
		$this->assertMatchesFreshSpecimen(
			$localobject,
			function ($specimen) {
				$this->changeProperties($specimen);
			},
			array(
				'newref', 'oldcopy', 'oldref', 'id', 'lines', 'line', 'client', 'thirdparty', 'brouillon', 'fk_user_author', 'fk_user_modif', 'user_modification_id', 'date_creation', 'date_validation', 'datem', 'date_modification',
				'ref', 'statut', 'status', 'paye', 'ref', 'actiontypecode', 'actionmsg2', 'actionmsg', 'mode_reglement', 'cond_reglement',
				'cond_reglement_doc', 'modelpdf',
				// Totals are ignored here: specimen lines reference random real products, and a kit/BOM product can
				// expand into extra lines with a different amount - total correctness is checked by assertLineTotalsMatchHeader() instead.
				'total_ht', 'total_tva', 'total_ttc',
				'multicurrency_total_ht', 'multicurrency_total_tva',	'multicurrency_total_ttc', 'fk_multicurrency', 'multicurrency_code', 'multicurrency_tx',
				'retained_warranty', 'retained_warranty_date_limit', 'retained_warranty_fk_cond_reglement', 'specimen', 'situation_cycle_ref', 'situation_counter', 'situation_final',
				'trackid', 'user_creat', 'user_valid', 'note'
			)
		);

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

		var_dump($localobject2->is_erasable());

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
