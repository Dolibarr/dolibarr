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
	 * testUpdatePriceKeepsSupplierInvoiceLineEnteredIncludingTax
	 *
	 * @return	void
	 */
	public function testUpdatePriceKeepsSupplierInvoiceLineEnteredIncludingTax()
	{
		global $conf;
		$conf = $this->savconf;

		$savdecimals = getDolGlobalString('MAIN_MAX_DECIMALS_UNIT');
		$conf->global->MAIN_MAX_DECIMALS_UNIT = 2;
		try {
			$invoice = $this->createDraftSupplierInvoice();
			$lineid = $invoice->addline('Line entered including tax', 35.01, 20, 0, 0, 1, 0, 0, 0, 0, 0, 0, 'TTC');
			$this->assertGreaterThan(0, $lineid, $invoice->errorsToString());

			$this->assertGreaterThan(0, $invoice->update_price(1));

			$this->assertEquals([5.83, 35.01], $this->fetchLineVatAndTotalInclTax('facture_fourn_det', 'tva', $lineid));
		} finally {
			$conf->global->MAIN_MAX_DECIMALS_UNIT = $savdecimals;
		}
	}

	/**
	 * testUpdatePriceStillFixesCorruptedSupplierInvoiceLine
	 *
	 * @return	void
	 */
	public function testUpdatePriceStillFixesCorruptedSupplierInvoiceLine()
	{
		global $conf;
		$conf = $this->savconf;
		$db = $this->savdb;

		$savdecimals = getDolGlobalString('MAIN_MAX_DECIMALS_UNIT');
		$conf->global->MAIN_MAX_DECIMALS_UNIT = 2;
		try {
			$invoice = $this->createDraftSupplierInvoice();
			$lineid = $invoice->addline('Line entered excluding tax', 29.18, 20, 0, 0, 1);
			$this->assertGreaterThan(0, $lineid, $invoice->errorsToString());

			// Total incl. tax matches neither the unit price excl. tax nor the unit price incl. tax
			$sql = "UPDATE ".$db->prefix()."facture_fourn_det SET tva = 5.83, total_ttc = 35.01 WHERE rowid = ".((int) $lineid);
			$this->assertNotFalse($db->query($sql), (string) $db->lasterror());

			$this->assertGreaterThan(0, $invoice->update_price(1));

			$this->assertEquals([5.84, 35.02], $this->fetchLineVatAndTotalInclTax('facture_fourn_det', 'tva', $lineid));
		} finally {
			$conf->global->MAIN_MAX_DECIMALS_UNIT = $savdecimals;
		}
	}

	/**
	 * testUpdatePriceKeepsSupplierInvoiceTemplateLineEnteredIncludingTax
	 *
	 * @return	void
	 */
	public function testUpdatePriceKeepsSupplierInvoiceTemplateLineEnteredIncludingTax()
	{
		global $conf;
		$conf = $this->savconf;
		$user = $this->savuser;
		$db = $this->savdb;

		$savdecimals = getDolGlobalString('MAIN_MAX_DECIMALS_UNIT');
		$conf->global->MAIN_MAX_DECIMALS_UNIT = 2;
		try {
			$invoice = $this->createDraftSupplierInvoice();

			$template = new FactureFournisseurRec($db);
			$template->title = 'Template '.dol_print_date(dol_now(), 'dayhourlog').' '.mt_rand();
			$templateid = $template->create($user, $invoice->id);
			$this->assertGreaterThan(0, $templateid, $template->errorsToString());

			$lineid = $template->addline(0, '', '', 'Line entered including tax', 0, 35.01, 1, 0, 20, 0, 0, 'TTC');
			$this->assertGreaterThan(0, $lineid, $template->errorsToString());

			$this->assertGreaterThan(0, $template->update_price(1));

			$this->assertEquals([5.83, 35.01], $this->fetchLineVatAndTotalInclTax('facture_fourn_det_rec', 'total_tva', $lineid));
		} finally {
			$conf->global->MAIN_MAX_DECIMALS_UNIT = $savdecimals;
		}
	}

	/**
	 * Create a draft supplier invoice from the specimen
	 *
	 * @return	FactureFournisseur
	 */
	private function createDraftSupplierInvoice()
	{
		$user = $this->savuser;
		$db = $this->savdb;

		$invoice = new FactureFournisseur($db);
		$invoice->initAsSpecimen();
		$invoice->ref_supplier = 'TTC-'.dol_print_date(dol_now(), 'dayhourlog').'-'.mt_rand();
		$this->assertGreaterThan(0, $invoice->create($user), $invoice->errorsToString());

		return $invoice;
	}

	/**
	 * Read the stored VAT and total including tax of a line
	 *
	 * @param	string	$table		Line table without prefix
	 * @param	string	$fieldvat	VAT amount column of this table
	 * @param	int		$lineid		Line id
	 * @return	float[]				[VAT amount, total including tax]
	 */
	private function fetchLineVatAndTotalInclTax($table, $fieldvat, $lineid)
	{
		$db = $this->savdb;

		$sql = "SELECT ".$db->sanitize($fieldvat)." as total_tva, total_ttc FROM ".$db->prefix().$db->sanitize($table)." WHERE rowid = ".((int) $lineid);
		$resql = $db->query($sql);
		$this->assertNotFalse($resql, (string) $db->lasterror());
		$obj = $db->fetch_object($resql);
		$db->free($resql);
		$this->assertNotEmpty($obj, 'Line '.$lineid.' not found in '.$table);

		return [(float) $obj->total_tva, (float) $obj->total_ttc];
	}
}
