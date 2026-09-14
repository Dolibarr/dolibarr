<?php
/* Copyright (C) 2026  Frédéric France         <frederic.france@free.fr>
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
 *      \file       test/phpunit/PaymentVATTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/compta/tva/class/tva.class.php';
require_once dirname(__FILE__).'/../../htdocs/compta/tva/class/paymentvat.class.php';
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
 * PaymentVAT is the class actually used by the core UI (compta/tva/card.php,
 * compta/paiement_vat.php) to record a payment against a Tva declaration - unlike
 * Tva::addPayment(), which is legacy/unused (see TvaTest.php). PaymentVAT::create() and
 * Tva::getSommePaiement() do agree on the same table/link (payment_vat.fk_tva).
 *
 * Note: like PaymentLoan (see PaymentLoanTest.php), PaymentVAT::create() does not use the
 * $fk_tva/$fk_typepaiement properties it cleans at the top of the method: its INSERT actually reads
 * $chid and $paiementtype (both left uncleaned), matching the real callers
 * (compta/tva/card.php, compta/paiement_vat.php).
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class PaymentVATTest extends CommonClassTest
{
	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		global $db, $conf;

		if (!isModEnabled('tax')) {
			// Activating a module re-runs its SQL install scripts (CREATE/ALTER TABLE), which causes an
			// implicit commit in MySQL/InnoDB: this activation is real and is NOT undone by the
			// rollback in tearDownAfterClass, exactly like an admin enabling it from Setup > Modules
			// would be (see also FactureTest::setUpBeforeClass(), which similarly disables the
			// blockedlog module for real, outside of any transaction). Do this before starting the
			// test transaction below, so the transaction-open counter stays consistent.
			require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
			$result = activateModule('modTax');
			self::assertEmpty($result['errors'], 'Failed to activate module tax: '.implode(', ', $result['errors']));
			$conf->setValues($db);
		}

		self::assertTrue(isModEnabled('tax'), 'module tax must be enabled');

		parent::setUpBeforeClass();
	}

	/**
	 * testPaymentVATCreate
	 *
	 * Pay a freshly created VAT declaration in full, with closepaidvat=1: the declaration must come
	 * back paid.
	 *
	 * @return int Id of the payment
	 */
	public function testPaymentVATCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$tva = new Tva($db);
		$tva->initAsSpecimen();
		$tva->label = 'PHPUnit VAT declaration to pay';
		$tva->amount = 100;
		$tvaid = $tva->create($user);
		$this->assertGreaterThan(0, $tvaid, $tva->errorsToString());

		$typepaymentid = dol_getIdFromCode($db, 'VIR', 'c_paiement', 'code', 'id', 1);
		$this->assertGreaterThan(0, $typepaymentid, 'VIR must be a known active payment type in this environment');

		$localobject = new PaymentVAT($db);
		$localobject->chid = $tvaid; // see class docblock: create() uses chid, not fk_tva
		$localobject->paiementtype = $typepaymentid; // see class docblock: create() uses paiementtype, not fk_typepaiement
		$localobject->datepaye = dol_now();
		$localobject->amounts = array($tvaid => 100);
		$result = $localobject->create($user, 1); // closepaidvat=1: close the VAT declaration if fully paid

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." result=".$result." fk_tva=".$tvaid."\n";

		$tva->fetch($tvaid);
		$this->assertEquals(Tva::STATUS_PAID, $tva->paye, 'A VAT declaration paid in full with closepaidvat=1 must be marked paid');
		$this->assertEqualsWithDelta(100.0, (float) $tva->getSommePaiement(), 0.00001);

		return $result;
	}

	/**
	 * testPaymentVATFetch
	 *
	 * @param	int		$id		Id of object
	 * @return	PaymentVAT
	 *
	 * @depends	testPaymentVATCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testPaymentVATFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new PaymentVAT($db);
		$result = $localobject->fetch($id);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertEqualsWithDelta(100.0, (float) $localobject->amount, 0.00001);
		$this->assertSame('VIR', $localobject->type_code);

		return $localobject;
	}

	/**
	 * testPaymentVATUpdate
	 *
	 * @param	PaymentVAT	$localobject	VAT payment
	 * @return	PaymentVAT
	 *
	 * @depends	testPaymentVATFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testPaymentVATUpdate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// Note: update() writes the note column from $this->note, not $this->note_private, even
		// though fetch() populates both from that same column - set ->note here to avoid that trap.
		$localobject->amount = 120;
		$localobject->note = 'New note after update';
		$result = $localobject->update($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$localobject->fetch($localobject->id);
		$this->assertEqualsWithDelta(120.0, (float) $localobject->amount, 0.00001);
		$this->assertSame('New note after update', $localobject->note_private);

		return $localobject;
	}

	/**
	 * testPaymentVATDelete
	 *
	 * @param	PaymentVAT	$localobject	VAT payment
	 * @return	int
	 *
	 * @depends	testPaymentVATUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testPaymentVATDelete($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$id = $localobject->id;
		$result = $localobject->delete($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$id." result=".$result."\n";
		return $result;
	}
}
