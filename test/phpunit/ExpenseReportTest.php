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

	/**
	 * testExpenseReportPaymentInCurrency
	 *
	 * A payment settles the expense report in its currency. From an account in that currency or in the company one the bank gets the amount
	 * of the payment, from an account in another currency it gets the amount really debited, that is required.
	 *
	 * @return	void
	 */
	public function testExpenseReportPaymentInCurrency()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		require_once DOL_DOCUMENT_ROOT.'/expensereport/class/paymentexpensereport.class.php';
		require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

		$expensereport = new ExpenseReport($db);
		$expensereport->initAsSpecimen();
		$expensereport->lines = array();
		$expensereport->status = 0;
		$expensereport->fk_statut = 0;
		$expensereport->multicurrency_code = 'USD';
		$expensereport->multicurrency_tx = 0.8;
		$this->assertGreaterThan(0, $expensereport->create($user), (string) $expensereport->error);

		$accountusd = $this->createBankAccount('USD');
		$accountcompany = $this->createBankAccount($conf->currency);

		$payment = new PaymentExpenseReport($db);
		$payment->fk_expensereport = $expensereport->id;
		$payment->datep = dol_now();
		$payment->amounts = array($user->id => 50);
		$payment->multicurrency_amount = 40;
		$payment->multicurrency_code = 'USD';
		$payment->fk_typepayment = 2;
		$this->assertGreaterThan(0, $payment->create($user), (string) $payment->error);
		$this->assertGreaterThan(0, $payment->addPaymentToBank($user, 'payment_expensereport', '(ExpenseReportPayment)', $accountusd, '', ''), (string) $payment->error);

		$resql = $db->query("SELECT b.amount, b.amount_main_currency FROM ".MAIN_DB_PREFIX."bank as b, ".MAIN_DB_PREFIX."payment_expensereport as p WHERE p.fk_bank = b.rowid AND p.rowid = ".((int) $payment->id));
		$bankline = $db->fetch_object($resql);
		$this->assertEquals(-40, (float) $bankline->amount, 'The USD account is debited of the USD of the payment');
		$this->assertEquals(-50, (float) $bankline->amount_main_currency);
		$this->assertEquals(40, (float) $expensereport->getSumPayments(1));
		$this->assertEquals(50, (float) $expensereport->getSumPayments());

		$payment2 = new PaymentExpenseReport($db);
		$payment2->fk_expensereport = $expensereport->id;
		$payment2->datep = dol_now();
		$payment2->amounts = array($user->id => 25);
		$payment2->multicurrency_amount = 20;
		$payment2->multicurrency_code = 'USD';
		$payment2->fk_typepayment = 2;
		$payment2->bank_amount = 26.5;
		$this->assertGreaterThan(0, $payment2->create($user), (string) $payment2->error);
		$this->assertGreaterThan(0, $payment2->addPaymentToBank($user, 'payment_expensereport', '(ExpenseReportPayment)', $accountcompany, '', ''), (string) $payment2->error);
		$resql = $db->query("SELECT b.amount, b.amount_main_currency FROM ".MAIN_DB_PREFIX."bank as b, ".MAIN_DB_PREFIX."payment_expensereport as p WHERE p.fk_bank = b.rowid AND p.rowid = ".((int) $payment2->id));
		$bankline = $db->fetch_object($resql);
		$this->assertEquals(-25, (float) $bankline->amount, 'The company currency account is debited of the payment at the rate of the report');
		$this->assertNull($bankline->amount_main_currency);

		$payment3 = new PaymentExpenseReport($db);
		$payment3->fk_expensereport = $expensereport->id;
		$payment3->datep = dol_now();
		$payment3->amounts = array($user->id => 5);
		$payment3->multicurrency_amount = 4;
		$payment3->multicurrency_code = 'USD';
		$payment3->fk_typepayment = 2;
		$this->assertGreaterThan(0, $payment3->create($user), (string) $payment3->error);
		$accountother = $this->createBankAccount('XAG');
		$this->assertLessThan(0, $payment3->addPaymentToBank($user, 'payment_expensereport', '(ExpenseReportPayment)', $accountother, '', ''), 'The amount debited is required from an account in another currency');
		$payment3->bank_amount = 0.2;
		$this->assertGreaterThan(0, $payment3->addPaymentToBank($user, 'payment_expensereport', '(ExpenseReportPayment)', $accountother, '', ''), (string) $payment3->error);
		$resql = $db->query("SELECT b.amount FROM ".MAIN_DB_PREFIX."bank as b, ".MAIN_DB_PREFIX."payment_expensereport as p WHERE p.fk_bank = b.rowid AND p.rowid = ".((int) $payment3->id));
		$this->assertEquals(-0.2, (float) $db->fetch_object($resql)->amount, 'An account in another currency is debited of the amount really debited');
	}

	/**
	 * testExpenseReportSetMulticurrencyRateAtPayment
	 *
	 * The first payment of an approved expense report sets its rate: the amounts in company currency are recalculated
	 * and the ones in its currency are kept. Once paid or transferred to accounting, the rate is kept.
	 *
	 * @return	void
	 */
	public function testExpenseReportSetMulticurrencyRateAtPayment()
	{
		global $conf,$user,$langs,$db,$mysoc;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		require_once DOL_DOCUMENT_ROOT.'/expensereport/class/paymentexpensereport.class.php';

		$resql = $db->query("SELECT id FROM ".MAIN_DB_PREFIX."c_type_fees WHERE active = 1 ORDER BY id LIMIT 1");
		$typefeesid = (int) $db->fetch_object($resql)->id;
		$resql = $db->query("SELECT t.taux FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_country as c WHERE t.fk_pays = c.rowid AND c.code = '".$db->escape($mysoc->country_code)."' AND t.taux > 0 AND t.active = 1 AND t.localtax1 = '0' AND t.localtax2 = '0' ORDER BY t.taux DESC LIMIT 1");
		$vatrate = (float) $db->fetch_object($resql)->taux;
		$hotelht = (float) price2num(120 / (1 + $vatrate / 100), 'MT');

		$expensereport = new ExpenseReport($db);
		$expensereport->initAsSpecimen();
		$expensereport->lines = array();
		$expensereport->status = 0;
		$expensereport->fk_statut = 0;
		$expensereport->multicurrency_code = 'USD';
		$expensereport->multicurrency_tx = 0.8;
		$this->assertGreaterThan(0, $expensereport->create($user), (string) $expensereport->error);
		$expensereport->fetch($expensereport->id);
		$this->assertGreaterThan(0, $expensereport->addline(1, 0, $typefeesid, 0, dol_now(), 'Taxi', 0, 0, 0, 0, 100), (string) $expensereport->error);
		$this->assertGreaterThan(0, $expensereport->addline(1, 0, $typefeesid, $vatrate, dol_now(), 'Hotel', 0, 0, 0, 0, 60), (string) $expensereport->error);
		$expensereport->fetch($expensereport->id);
		$this->assertLessThan(0, $expensereport->setMulticurrencyRateAtPayment(0.5), 'The rate of a draft is not set by a payment');

		$db->query("UPDATE ".MAIN_DB_PREFIX."expensereport SET fk_statut = ".ExpenseReport::STATUS_APPROVED." WHERE rowid = ".((int) $expensereport->id));
		$expensereport->fetch($expensereport->id);
		$this->assertTrue($expensereport->canSetMulticurrencyRateAtPayment());
		$this->assertGreaterThan(0, $expensereport->setMulticurrencyRateAtPayment(0.5), (string) $expensereport->error);

		$expensereport->fetch($expensereport->id);
		$this->assertEquals(0.5, (float) $expensereport->multicurrency_tx);
		$this->assertEquals(160, (float) $expensereport->multicurrency_total_ttc, 'The amounts in the currency of the report are kept');
		$this->assertEquals(320, (float) $expensereport->total_ttc, 'The amounts in company currency follow the rate of the payment');
		$this->assertEquals(200 + $hotelht, (float) $expensereport->total_ht);
		$this->assertEquals(120 - $hotelht, (float) $expensereport->total_tva);
		$this->assertEquals(100, (float) $expensereport->lines[0]->multicurrency_total_ttc);
		$this->assertEquals(200, (float) $expensereport->lines[0]->total_ttc);
		$this->assertEquals(60, (float) $expensereport->lines[1]->multicurrency_total_ttc);
		$this->assertEquals(120, (float) $expensereport->lines[1]->total_ttc);

		$payment = new PaymentExpenseReport($db);
		$payment->fk_expensereport = $expensereport->id;
		$payment->datep = dol_now();
		$payment->amounts = array($user->id => 20);
		$payment->multicurrency_amount = 10;
		$payment->multicurrency_code = 'USD';
		$payment->fk_typepayment = 2;
		$this->assertGreaterThan(0, $payment->create($user), (string) $payment->error);
		$this->assertFalse($expensereport->canSetMulticurrencyRateAtPayment());
		$this->assertLessThan(0, $expensereport->setMulticurrencyRateAtPayment(0.4), 'The rate of a report already paid is kept');
		$expensereport->fetch($expensereport->id);
		$this->assertEquals(0.5, (float) $expensereport->multicurrency_tx);
		$this->assertEquals(320, (float) $expensereport->total_ttc);

		$expensereport2 = new ExpenseReport($db);
		$expensereport2->initAsSpecimen();
		$expensereport2->lines = array();
		$expensereport2->status = 0;
		$expensereport2->fk_statut = 0;
		$expensereport2->multicurrency_code = 'USD';
		$expensereport2->multicurrency_tx = 0.8;
		$this->assertGreaterThan(0, $expensereport2->create($user), (string) $expensereport2->error);
		$expensereport2->fetch($expensereport2->id);
		$this->assertGreaterThan(0, $expensereport2->addline(1, 0, $typefeesid, 0, dol_now(), 'Taxi', 0, 0, 0, 0, 100), (string) $expensereport2->error);
		$db->query("UPDATE ".MAIN_DB_PREFIX."expensereport SET fk_statut = ".ExpenseReport::STATUS_APPROVED." WHERE rowid = ".((int) $expensereport2->id));
		$db->query("INSERT INTO ".MAIN_DB_PREFIX."accounting_bookkeeping (piece_num, doc_date, doc_type, doc_ref, fk_doc, fk_docdet, numero_compte, label_compte, debit, credit, fk_user_author, code_journal) VALUES (1, '".$db->idate(dol_now())."', 'expense_report', 'ER', ".((int) $expensereport2->id).", 0, '625', 'Travel', 125, 0, ".((int) $user->id).", 'ER')");
		$expensereport2->fetch($expensereport2->id);
		$this->assertLessThan(0, $expensereport2->setMulticurrencyRateAtPayment(0.5), 'The rate of a report transferred to accounting is kept');
		$this->assertSame($langs->trans('ErrorExpenseReportRateChangeOnlyAtFirstPayment'), $expensereport2->error);
		$expensereport2->fetch($expensereport2->id);
		$this->assertEquals(0.8, (float) $expensereport2->multicurrency_tx);
		$this->assertEquals(125, (float) $expensereport2->total_ttc);
	}

	/**
	 * testExpenseReportSetMulticurrencyCodeUsesRateOfStartDate
	 *
	 * Changing the currency of a draft takes the same rate as at creation, and the currency and the rate are only changed on a draft.
	 *
	 * @return	void
	 */
	public function testExpenseReportSetMulticurrencyCodeUsesRateOfStartDate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		require_once DOL_DOCUMENT_ROOT.'/multicurrency/class/multicurrency.class.php';
		require_once DOL_DOCUMENT_ROOT.'/multicurrency/class/currencyrate.class.php';

		$savoption = getDolGlobalString('MULTICURRENCY_USE_RATE_ON_DOCUMENT_DATE');
		$conf->global->MULTICURRENCY_USE_RATE_ON_DOCUMENT_DATE = 1;

		$currency = new MultiCurrency($db);
		$currency->code = 'XTS';
		$currency->name = 'ExpenseReportTest';
		$this->assertGreaterThan(0, $currency->create($user), $currency->errorsToString());
		foreach (array('2021-03-15' => 2, '2021-05-15' => 4) as $day => $value) {
			$rate = new CurrencyRate($db);
			$rate->rate = $value;
			$rate->date_sync = dol_stringtotime($day.' 12:00:00');
			$this->assertGreaterThan(0, $rate->create($user, $currency->id));
		}

		$expensereport = new ExpenseReport($db);
		$expensereport->initAsSpecimen();
		$expensereport->lines = array();
		$expensereport->status = 0;
		$expensereport->fk_statut = 0;
		$expensereport->date_debut = dol_stringtotime('2021-03-20 12:00:00');
		$expensereport->date_fin = $expensereport->date_debut;
		$this->assertGreaterThan(0, $expensereport->create($user), (string) $expensereport->error);
		$resql = $db->query("SELECT id FROM ".MAIN_DB_PREFIX."c_type_fees WHERE active = 1 ORDER BY id LIMIT 1");
		$typefeesid = (int) $db->fetch_object($resql)->id;
		$expensereport->fetch($expensereport->id);
		$this->assertGreaterThan(0, $expensereport->addline(1, 100, $typefeesid, 0, $expensereport->date_debut, 'Taxi'), (string) $expensereport->error);
		$expensereport->fetch($expensereport->id);

		$this->assertGreaterThan(0, $expensereport->setMulticurrencyCode('XTS'), (string) $expensereport->error);
		$expensereport->fetch($expensereport->id);
		$conf->global->MULTICURRENCY_USE_RATE_ON_DOCUMENT_DATE = $savoption;
		$this->assertEquals(2, (float) $expensereport->multicurrency_tx, 'The rate of the start date of the report is used');
		$this->assertEquals($currency->id, (int) $expensereport->fk_multicurrency);
		$this->assertEquals(100, (float) $expensereport->total_ttc);
		$this->assertEquals(200, (float) $expensereport->multicurrency_total_ttc);

		$db->query("UPDATE ".MAIN_DB_PREFIX."expensereport SET fk_statut = ".ExpenseReport::STATUS_VALIDATED." WHERE rowid = ".((int) $expensereport->id));
		$expensereport->fetch($expensereport->id);
		$this->assertLessThan(0, $expensereport->setMulticurrencyRate(3, 1), 'The rate of a validated report is not changed');
		$this->assertLessThan(0, $expensereport->setMulticurrencyCode('USD'), 'The currency of a validated report is not changed');
		$expensereport->fetch($expensereport->id);
		$this->assertEquals('XTS', $expensereport->multicurrency_code);
		$this->assertEquals(2, (float) $expensereport->multicurrency_tx);
	}

	/**
	 * testExpenseReportApiPaymentInCurrency
	 *
	 * A payment created through the API takes its amounts in the currency of the expense report, like the payment page.
	 *
	 * @return	void
	 */
	public function testExpenseReportApiPaymentInCurrency()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		require_once DOL_DOCUMENT_ROOT.'/api/class/api_access.class.php';
		require_once DOL_DOCUMENT_ROOT.'/api/class/api.class.php';
		require_once DOL_DOCUMENT_ROOT.'/expensereport/class/api_expensereports.class.php';
		require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
		$apiuser = clone $user;
		$apiuser->rights = clone $user->rights;
		$apiuser->rights->expensereport = new stdClass();
		$apiuser->rights->expensereport->to_paid = 1;
		DolibarrApiAccess::$user = $apiuser;

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
		$this->assertGreaterThan(0, $expensereport->addline(1, 0, $typefeesid, 0, dol_now(), 'Taxi', 0, 0, 0, 0, 100), (string) $expensereport->error);
		$db->query("UPDATE ".MAIN_DB_PREFIX."expensereport SET fk_statut = ".ExpenseReport::STATUS_APPROVED." WHERE rowid = ".((int) $expensereport->id));

		$accountusd = $this->createBankAccount('USD');
		$accountcompany = $this->createBankAccount($conf->currency);
		$api = new ExpenseReports();

		try {
			$api->addPayment($expensereport->id, array('fk_typepayment' => 2, 'datep' => dol_now(), 'amounts' => array($user->id => 101), 'accountid' => $accountusd));
			$this->fail('A payment higher than the remainder in the currency of the report is refused');
		} catch (Luracast\Restler\RestException $e) {
			$this->assertStringContainsString('remainder to pay (100 USD)', $e->getMessage());
		}
		try {
			$api->addPayment($expensereport->id, array('fk_typepayment' => 2, 'datep' => dol_now(), 'amounts' => array($user->id => 20), 'accountid' => $accountcompany, 'bank_amount' => 'abc'));
			$this->fail('A bank_amount that is not a positive number is refused');
		} catch (Luracast\Restler\RestException $e) {
			$this->assertEquals(400, $e->getCode());
		}

		try {
			$api->addPayment($expensereport->id, array('fk_typepayment' => 2, 'datep' => dol_now(), 'amounts' => array($user->id => 40), 'accountid' => $accountusd, 'multicurrency_tx' => '0'));
			$this->fail('A rate that is not a positive number is refused');
		} catch (Luracast\Restler\RestException $e) {
			$this->assertEquals(400, $e->getCode());
		}

		$paymentid = $api->addPayment($expensereport->id, array('fk_typepayment' => 2, 'datep' => dol_now(), 'amounts' => array($user->id => 40), 'accountid' => $accountusd, 'multicurrency_tx' => '0.5'));
		$payment = new PaymentExpenseReport($db);
		$payment->fetch($paymentid);
		$this->assertEquals(40, (float) $payment->multicurrency_amount);
		$this->assertEquals(80, (float) $payment->amount, 'The first payment converts at its rate');
		$this->assertSame('USD', $payment->multicurrency_code);
		$expensereport->fetch($expensereport->id);
		$this->assertEquals(200, (float) $expensereport->total_ttc, 'The report takes the rate of its first payment');

		try {
			$api->addPayment($expensereport->id, array('fk_typepayment' => 2, 'datep' => dol_now(), 'amounts' => array($user->id => 60), 'accountid' => $accountcompany, 'multicurrency_tx' => '0.4'));
			$this->fail('The rate of a report already paid is not changed');
		} catch (Luracast\Restler\RestException $e) {
			$this->assertEquals(400, $e->getCode());
		}

		$paymentid = $api->addPayment($expensereport->id, array('fk_typepayment' => 2, 'datep' => dol_now(), 'amounts' => array($user->id => 60), 'accountid' => $accountcompany, 'bank_amount' => '76.5', 'multicurrency_tx' => '0.5'));
		$payment = new PaymentExpenseReport($db);
		$payment->fetch($paymentid);
		$this->assertEquals(60, (float) $payment->multicurrency_amount);
		$this->assertEquals(120, (float) $payment->amount, 'The last payment settles the remainder in company currency');
		$resql = $db->query("SELECT b.amount FROM ".MAIN_DB_PREFIX."bank as b WHERE b.rowid = ".((int) $payment->fk_bank));
		$this->assertEquals(-120, (float) $db->fetch_object($resql)->amount, 'The company currency account is debited of the payment, bank_amount is not used');
		$expensereport->fetch($expensereport->id);
		$this->assertEquals(ExpenseReport::STATUS_CLOSED, $expensereport->status);
	}

	/**
	 * Create a bank account in a currency
	 *
	 * @param	string	$currency	Currency code
	 * @return	int					Id of the bank account
	 */
	private function createBankAccount(string $currency): int
	{
		global $user, $db;

		$account = new Account($db);
		$account->ref = 'ER'.$currency.substr(uniqid(), -6);
		$account->label = $account->ref;
		$account->type = Account::TYPE_CURRENT;
		$account->country_id = 1;
		$account->date_solde = dol_now();
		$account->currency_code = $currency;
		$id = $account->create($user);
		$this->assertGreaterThan(0, $id, $account->errorsToString());

		return $id;
	}
}
