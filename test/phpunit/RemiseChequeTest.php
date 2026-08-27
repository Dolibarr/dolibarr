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
 *      \file       test/phpunit/RemiseChequeTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/compta/paiement/cheque/class/remisecheque.class.php';
require_once dirname(__FILE__).'/../../htdocs/compta/paiement/class/paiement.class.php';
require_once dirname(__FILE__).'/../../htdocs/compta/facture/class/facture.class.php';
require_once dirname(__FILE__).'/../../htdocs/compta/bank/class/account.class.php';
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
 * RemiseCheque (chequereceipt) does not create a standalone
 * record: it gathers existing not-yet-remitted check payments (llx_bank rows with fk_type='CHQ' and
 * fk_bordereau=0) for a given bank account into a receipt. So the fixture here goes through the same
 * real path as an actual check payment: create+validate an invoice, pay it with a CHQ-coded payment,
 * reconcile it to a bank account (this is what produces the llx_bank row RemiseCheque::create() picks
 * up), then remit that check.
 *
 * Note: unlike most other classes in this suite, RemiseCheque::delete()/updateAmount() return an
 * "errno" convention (0=success, <0=error) rather than the usual ">0=success" - see their own
 * assertions below.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class RemiseChequeTest extends CommonClassTest
{
	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		self::assertTrue(isModEnabled('invoice'), 'module customer invoice must be enabled');
		self::assertTrue(isModEnabled('bank'), 'module bank must be enabled');
		parent::setUpBeforeClass();
	}

	/**
	 * Create a validated invoice, pay it with a check and reconcile that payment to a bank account.
	 *
	 * @return array{accountid:int,banklineid:int,amount:float}
	 */
	public function testRemiseChequePrepareCheckPayment()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$conf->global->FAC_FORCE_DATE_VALIDATION = 0;
		$conf->global->INVOICE_CHECK_POSTERIOR_DATE = 0;

		$invoice = new Facture($db);
		$invoice->initAsSpecimen();
		$result = $invoice->create($user);
		$this->assertGreaterThan(0, $result, $invoice->errorsToString());
		$result = $invoice->validate($user);
		$this->assertGreaterThan(0, $result, $invoice->errorsToString());

		$amount = (float) price2num($invoice->total_ttc, 'MT');

		$payment = new Paiement($db);
		$payment->datepaye = dol_now();
		$payment->amounts = array($invoice->id => $amount);
		$payment->paiementcode = 'CHQ';
		$payment->paiementid = dol_getIdFromCode($db, 'CHQ', 'c_paiement', 'code', 'id', 1);
		$payment->num_payment = 'CHK-PHPUNIT-001';
		$paymentid = $payment->create($user, 0);
		$this->assertGreaterThan(0, $paymentid, $payment->errorsToString());

		$account = new Account($db);
		$account->initAsSpecimen();
		$account->date_solde = dol_now(); // required by Account::create
		$accountid = $account->create($user);
		$this->assertGreaterThan(0, $accountid, $account->errorsToString());

		$payment->fetch($paymentid);
		$payment->paiementcode = $payment->type_code;
		$payment->amounts = $payment->getAmountsArray();
		$banklineid = $payment->addPaymentToBank($user, 'payment', '(CustomerInvoicePayment)', $accountid, '', '');
		$this->assertGreaterThan(0, $banklineid, $payment->errorsToString());

		print __METHOD__." accountid=".$accountid." banklineid=".$banklineid." amount=".$amount."\n";
		return array('accountid' => $accountid, 'banklineid' => $banklineid, 'amount' => $amount);
	}

	/**
	 * testRemiseChequeCreate
	 *
	 * @param	array{accountid:int,banklineid:int,amount:float}	$data	Data from previous test
	 * @return	array{id:int,amount:float}	Id of the created receipt and the amount it must carry
	 *
	 * @depends	testRemiseChequePrepareCheckPayment
	 * The depends says test is run only if previous is ok
	 */
	public function testRemiseChequeCreate($data)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new RemiseCheque($db);
		$localobject->type = 'CHQ';
		$result = $localobject->create($user, $data['accountid'], 0, array($data['banklineid']));

		$this->assertGreaterThan(0, $result, 'RemiseCheque::create failed, errno='.$localobject->errno.' '.$localobject->error);
		print __METHOD__." result=".$result."\n";

		// create() does not update ->amount/->nbcheque in memory (only updateAmount() writes them, to
		// the DB only) - fetch to see the real values.
		$localobject->fetch($result);
		$this->assertEqualsWithDelta($data['amount'], (float) $localobject->amount, 0.00001);
		$this->assertEquals(1, $localobject->nbcheque);

		return array('id' => $result, 'amount' => $data['amount']);
	}

	/**
	 * testRemiseChequeFetch
	 *
	 * @param	array{id:int,amount:float}	$data	Id of the receipt and the amount it must carry
	 * @return	RemiseCheque
	 *
	 * @depends	testRemiseChequeCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testRemiseChequeFetch($data)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new RemiseCheque($db);
		$result = $localobject->fetch($data['id']);

		$this->assertGreaterThan(0, $result, (string) $localobject->error);
		print __METHOD__." id=".$data['id']." result=".$result."\n";
		$this->assertEqualsWithDelta($data['amount'], (float) $localobject->amount, 0.00001);
		$this->assertEquals(1, $localobject->nbcheque);
		$this->assertEquals(RemiseCheque::STATUS_DRAFT, $localobject->status);
		$this->assertSame('(PROV'.$data['id'].')', $localobject->ref);

		return $localobject;
	}

	/**
	 * testRemiseChequeValidate
	 *
	 * @param	RemiseCheque	$localobject	Cheque receipt
	 * @return	RemiseCheque
	 *
	 * @depends	testRemiseChequeFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testRemiseChequeValidate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$oldref = $localobject->ref;
		$result = $localobject->validate($user);

		$this->assertEquals(1, $result, 'RemiseCheque::validate failed, errno='.$localobject->errno.' '.$localobject->error);
		print __METHOD__." id=".$localobject->id." result=".$result." ref=".$localobject->ref."\n";

		$localobject->fetch($localobject->id);
		$this->assertEquals(RemiseCheque::STATUS_VALIDATED, $localobject->status);
		$this->assertNotEquals($oldref, $localobject->ref, 'validate() must replace the provisional ref with a definitive one');

		return $localobject;
	}

	/**
	 * testRemiseChequeDelete
	 *
	 * Unlike most other classes in this suite, delete() returns an "errno" convention: 0 on success.
	 *
	 * @param	RemiseCheque	$localobject	Cheque receipt
	 * @return	void
	 *
	 * @depends	testRemiseChequeValidate
	 * The depends says test is run only if previous is ok
	 */
	public function testRemiseChequeDelete($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$id = $localobject->id;
		$result = $localobject->delete($user);

		$this->assertEquals(0, $result, 'RemiseCheque::delete failed, errno='.$result.' '.$localobject->error);
		print __METHOD__." id=".$id." result=".$result."\n";
	}
}
