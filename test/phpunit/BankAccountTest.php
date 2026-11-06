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
 *      \file       test/phpunit/BankAccounrTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db,$mysoc;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/compta/bank/class/account.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/bank.lib.php';
require_once dirname(__FILE__).'/../../htdocs/fourn/class/fournisseur.facture.class.php';
require_once dirname(__FILE__).'/../../htdocs/fourn/class/paiementfourn.class.php';
require_once dirname(__FILE__).'/../../htdocs/expensereport/class/expensereport.class.php';
require_once dirname(__FILE__).'/../../htdocs/expensereport/class/paymentexpensereport.class.php';
require_once dirname(__FILE__).'/../../htdocs/multicurrency/class/multicurrency.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;

$langs->load("main");


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class BankAccountTest extends CommonClassTest
{
	/**
	 * Test country-specific bank account fields and labels.
	 *
	 * @return void
	 */
	public function testNorthAmericanBankAccountFieldsAndLabels()
	{
		global $db;

		$account = new Account($db);
		$account->country_code = 'CA';

		$this->assertSame(3, $account->useDetailedBBAN());
		$this->assertSame(array('BankCode', 'DeskCode', 'BankAccountNumber'), $account->getFieldsToShow());
		$this->assertSame('InstitutionNumber', $account->getBankFieldLabel('BankCode'));
		$this->assertSame('TransitNumber', $account->getBankFieldLabel('DeskCode'));

		$account->country_code = 'FR';
		$this->assertSame(1, $account->useDetailedBBAN());

		$account->country_code = 'US';
		$this->assertSame(2, $account->useDetailedBBAN());
		$this->assertSame(array('BankCode', 'BankAccountNumber'), $account->getFieldsToShow());
		$this->assertSame('ABARoutingNumber', $account->getBankFieldLabel('BankCode'));
	}

	/**
	 * testBankAccountCreate
	 *
	 * @return  int
	 */
	public function testBankAccountCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Account($db);
		$localobject->initAsSpecimen();
		$localobject->date_solde = dol_now();
		$result = $localobject->create($user);

		print __METHOD__." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $result;
	}

	/**
	 * testBankAccountFetch
	 *
	 * @param   int $id     Id of contract
	 * @return  int
	 *
	 * @depends testBankAccountCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testBankAccountFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Account($db);
		$result = $localobject->fetch($id);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $localobject;
	}

	/**
	 * testBankAccountOther
	 *
	 * @param   Account  $localobject    Account
	 * @return  int
	 *
	 * @depends testBankAccountFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testBankAccountOther($localobject)
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

		$result = $localobject->needIBAN();
		//print __METHOD__." localobject->date_creation=".$localobject->date_creation."\n";
		$this->assertEquals(1, $result);

		// Test checkIbanForAccount for FR account
		$result = checkIbanForAccount($localobject);
		print __METHOD__." checkIbanForAccount(".$localobject->iban.") = ".$result."\n";
		$this->assertTrue($result);

		// Test checkIbanForAccount for CI account
		$localobject2 = new Account($db);
		$localobject2->country = 'CI';
		$localobject2->iban = 'CI77A12312341234123412341234';
		$result = checkIbanForAccount($localobject2);
		print __METHOD__." checkIbanForAccount(".$localobject2->iban.") = ".$result."\n";
		$this->assertTrue($result);

		return $localobject;
	}

	/**
	 * testCheckSwiftForAccount
	 *
	 * @param   Account  $localobject    Account
	 * @return  int
	 *
	 * @depends testBankAccountOther
	 * The depends says test is run only if previous is ok
	 */
	public function testCheckSwiftForAccount($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->bic = 'PSSTFRPPMARBIDON';

		$result = checkSwiftForAccount($localobject);
		print __METHOD__." checkSwiftForAccount ".$localobject->bic." = ".$result."\n";
		$this->assertFalse($result);


		$localobject->bic = 'PSSTFRPPMAR';

		$result = checkSwiftForAccount($localobject);
		print __METHOD__." checkSwiftForAccount ".$localobject->bic." = ".$result."\n";
		$this->assertTrue($result);

		return $localobject->id;
	}

	/**
	 * testBankAccountDelete
	 *
	 * @param   int $id     Id of contract
	 * @return  int
	 *
	 * @depends testCheckSwiftForAccount
	 * The depends says test is run only if previous is ok
	 */
	public function testBankAccountDelete($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Account($db);
		$result = $localobject->fetch($id);
		$result = $localobject->delete($user);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $result;
	}

	/**
	 * testBankLineExchangeDifference
	 *
	 * An invoice of 700 in its currency booked 610 is paid by a bank line worth 600 in main currency: the exchange gain is 10.
	 * An expense report payment of 610 debited 620 is a loss of 10, and a bank line that pays no document has no difference.
	 *
	 * @return void
	 */
	public function testBankLineExchangeDifference()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$account = new Account($db);
		$account->initAsSpecimen();
		$account->ref = 'XDIFF'.substr(uniqid(), -6);
		$account->date_solde = dol_now();
		$this->assertGreaterThan(0, $account->create($user), $account->errorsToString());

		$currency = new MultiCurrency($db);
		$currency->code = 'XBB';
		$currency->name = 'BankLineExchangeDifferenceTest';
		$this->assertGreaterThan(0, $currency->create($user), $currency->errorsToString());

		$invoice = new FactureFournisseur($db);
		$invoice->initAsSpecimen();
		$invoice->lines = array();
		$invoice->ref_supplier = 'XDIFF'.uniqid();
		$invoice->multicurrency_code = 'XBB';
		$invoice->multicurrency_tx = 700 / 610;
		$this->assertGreaterThan(0, $invoice->create($user), (string) $invoice->error);
		$this->assertGreaterThan(0, $invoice->addline('Line in invoice currency', 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 'HT', 0, -1, 0, array(), null, 0, 700), (string) $invoice->error);
		$this->assertGreaterThan(0, $invoice->validate($user), (string) $invoice->error);

		$payment = new PaiementFourn($db);
		$payment->datepaye = dol_now();
		$payment->multicurrency_amounts = array($invoice->id => 700);
		$payment->paiementid = dol_getIdFromCode($db, 'VIR', 'c_paiement', 'code', 'id', 1);
		$this->assertGreaterThan(0, $payment->create($user, 1), (string) $payment->error);
		$this->assertEquals(610, (float) $payment->amount);

		$banklineid = $account->addline(dol_now(), 'VIR', '(SupplierInvoicePayment)', -650, '', 0, $user, '', '', '', null, '', -600);
		$this->assertGreaterThan(0, $banklineid, (string) $account->error);
		$this->assertGreaterThan(0, $payment->update_fk_bank($banklineid));

		$bankline = new AccountLine($db);
		$bankline->id = $banklineid;
		$this->assertEquals(10, $bankline->getExchangeDifference(), 'The supplier account is settled at 610 and the bank gives 600, so 10 of gain');

		$expensereport = new ExpenseReport($db);
		$expensereport->initAsSpecimen();
		$expensereport->lines = array();
		$expensereport->status = 0;
		$expensereport->fk_statut = 0;
		$this->assertGreaterThan(0, $expensereport->create($user), (string) $expensereport->error);

		$paymentexpensereport = new PaymentExpenseReport($db);
		$paymentexpensereport->fk_expensereport = $expensereport->id;
		$paymentexpensereport->datep = dol_now();
		$paymentexpensereport->amounts = array($user->id => 610);
		$paymentexpensereport->fk_typepayment = 2;
		$this->assertGreaterThan(0, $paymentexpensereport->create($user), (string) $paymentexpensereport->error);

		$banklineid = $account->addline(dol_now(), 'VIR', '(ExpenseReportPayment)', -620, '', 0, $user);
		$this->assertGreaterThan(0, $paymentexpensereport->update_fk_bank($banklineid));
		$bankline->id = $banklineid;
		$this->assertEquals(-10, $bankline->getExchangeDifference(), 'The expense report is settled at 610 and the bank gives 620, so 10 of loss');

		$bankline->id = $account->addline(dol_now(), 'VIR', 'Not a payment', -620, '', 0, $user);
		$this->assertEquals(0, $bankline->getExchangeDifference());
	}
}
