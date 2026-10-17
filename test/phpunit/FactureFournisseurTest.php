<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2017 Juanjo Menent        <jmenent@2byte.es>
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
 *      \file       test/phpunit/FactureFournisseurTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/fourn/class/fournisseur.facture.class.php';
require_once dirname(__FILE__).'/../../htdocs/fourn/class/fournisseur.facture-rec.class.php';
require_once dirname(__FILE__).'/../../htdocs/fourn/class/paiementfourn.class.php';
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
class FactureFournisseurTest extends CommonClassTest
{
	/**
	 * testFactureFournisseurCreate
	 *
	 * @return int
	 */
	public function testFactureFournisseurCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new FactureFournisseur($db);
		$localobject->initAsSpecimen();
		$result = $localobject->create($user);

		$this->assertLessThan($result, 0, $localobject->errorsToString());
		print __METHOD__." result=".$result."\n";
		return $result;
	}

	/**
	 * testFactureFournisseurFetch
	 *
	 * @param	int		$id		If supplier invoice
	 * @return	void
	 *
	 * @depends	testFactureFournisseurCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testFactureFournisseurFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new FactureFournisseur($db);
		$result = $localobject->fetch($id);

		$this->assertLessThan($result, 0, $localobject->errorsToString());
		print __METHOD__." id=".$id." result=".$result."\n";
		return $localobject;
	}

	/**
	 * testFactureFournisseurUpdate
	 *
	 * @param	FactureFournisseur	$localobject	Supplier invoice
	 * @return	int
	 *
	 * @depends	testFactureFournisseurFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testFactureFournisseurUpdate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->note = 'New note after update';
		$result = $localobject->update($user);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0, $localobject->errorsToString());
		return $localobject;
	}

	/**
	 * testFactureFournisseurValid
	 *
	 * @param	FactureFournisseur	$localobject	Supplier invoice
	 * @return	void
	 *
	 * @depends	testFactureFournisseurUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testFactureFournisseurValid($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->validate($user);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$this->assertLessThan($result, 0, $localobject->errorsToString());
		return $localobject;
	}

	/**
	 * testFactureFournisseurOther
	 *
	 * @param	FactureFournisseur	$localobject		Supplier invoice
	 * @return	void
	 *
	 * @depends testFactureFournisseurValid
	 * The depends says test is run only if previous is ok
	 */
	public function testFactureFournisseurOther($localobject)
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
	 * testFactureFournisseurDelete
	 *
	 * @param	int		$id		Id of supplier invoice
	 * @return	void
	 *
	 * @depends	testFactureFournisseurOther
	 * The depends says test is run only if previous is ok
	 */
	public function testFactureFournisseurDelete($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new FactureFournisseur($db);
		$result = $localobject->fetch($id);
		$result = $localobject->delete($user);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0, $localobject->errorsToString());
		return $result;
	}

	/**
	 * testFactureFournisseurRecGetNextDate
	 *
	 * @return	void
	 */
	public function testFactureFournisseurRecGetNextDate()
	{
		global $db;

		$savtz = date_default_timezone_get();
		date_default_timezone_set('Europe/Paris');

		try {
			$localobject = new FactureFournisseurRec($db);
			$localobject->frequency = 1;
			$localobject->unit_frequency = 'm';

			// Midnight of the 1st after a month of 30 days is the last day of the previous month in UTC
			foreach (array('2026-10-01' => '2026-11-01', '2026-12-01' => '2027-01-01', '2026-03-01' => '2026-04-01') as $day => $expected) {
				$localobject->date_when = dol_stringtotime($day.' 00:00:00', 0);
				$result = dol_print_date($localobject->getNextDate(), '%Y-%m-%d %H:%M:%S', 'tzserver');
				print __METHOD__." day=".$day." result=".$result."\n";
				$this->assertEquals($expected.' 00:00:00', $result);
			}
		} finally {
			date_default_timezone_set($savtz);
		}
	}

	/**
	 * A payment in the invoice currency that settles its whole remaining amount closes the supplier invoice,
	 * even when converting that amount gives one cent less than the sum of the converted lines.
	 *
	 * @return	void
	 */
	public function testFactureFournisseurPaidInInvoiceCurrencyIsClosed()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$rate = 1.0862009;
		$unitpriceininvoicecurrency = 100.10;

		$invoice = new FactureFournisseur($db);
		$invoice->initAsSpecimen();
		$invoice->lines = array();
		$invoice->ref_supplier = 'PAIDININVOICECURRENCY'.dol_now();
		$invoice->multicurrency_code = 'USD';
		$invoice->multicurrency_tx = $rate;
		$this->assertLessThan($invoice->create($user), 0, 'FactureFournisseur::create failed: '.$invoice->error);
		for ($i = 0; $i < 2; $i++) {
			$result = $invoice->addline('Line in invoice currency', 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 'HT', 0, -1, 0, array(), null, 0, $unitpriceininvoicecurrency);
			$this->assertLessThan($result, 0, 'FactureFournisseur::addline failed: '.$invoice->error);
		}
		$this->assertLessThan($invoice->validate($user), 0, 'FactureFournisseur::validate failed: '.$invoice->error);
		$invoice->fetch($invoice->id);

		$amountininvoicecurrency = (float) price2num($invoice->multicurrency_total_ttc, 'MT');
		$this->assertNotEquals((float) price2num($amountininvoicecurrency / $rate, 'MT'), (float) price2num($invoice->total_ttc, 'MT'), 'Test needs lines whose converted sum differs from the converted total');

		$payment = new PaiementFourn($db);
		$payment->datepaye = dol_now();
		$payment->multicurrency_amounts = array($invoice->id => $amountininvoicecurrency);
		$payment->multicurrency_code = array($invoice->id => 'USD');
		$payment->multicurrency_tx = array($invoice->id => $rate);
		$payment->paiementcode = 'LIQ';
		$payment->paiementid = dol_getIdFromCode($db, $payment->paiementcode, 'c_paiement', 'code', 'id', 1);
		$paymentid = $payment->create($user, 1);
		$this->assertLessThan($paymentid, 0, 'PaiementFourn::create failed: '.$payment->error);

		$invoice->fetch($invoice->id);
		$this->assertEquals((float) price2num($invoice->total_ttc, 'MT'), (float) price2num($payment->amount, 'MT'), 'Payment amount in company currency differs from the invoice total');
		$this->assertEquals(FactureFournisseur::STATUS_CLOSED, $invoice->status, 'Supplier invoice fully paid in its currency is not closed');
	}
}
