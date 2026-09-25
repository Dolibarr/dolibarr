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
	 * Recurring supplier invoice templates are processed before their next generation date when
	 * SUPPLIER_INVOICE_RECURRING_GENERATION_ADDDAYS is set.
	 *
	 * @return void
	 */
	public function testCreateRecurringInvoicesAheadOfTime()
	{
		global $conf, $user, $langs, $db;

		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobjectinv = new FactureFournisseur($db);
		$localobjectinv->initAsSpecimen();
		$localobjectinv->ref_supplier = 'SPECIMENADDDAYS';
		$result = $localobjectinv->create($user);
		$this->assertGreaterThan(0, $result, 'Create source supplier invoice: '.$localobjectinv->errorsToString());

		$datewhen = dol_time_plus_duree(dol_now(), 3, 'd');

		$localobject = new FactureFournisseurRec($db);
		$localobject->initAsSpecimen();
		$localobject->title = 'SPECIMENADDDAYS';	// llx_facture_fourn_rec has a unique index on (titre, entity)
		$localobject->frequency = 1;
		$localobject->unit_frequency = 'm';
		$localobject->date_when = $datewhen;
		$result = $localobject->create($user, $localobjectinv->id);
		$this->assertGreaterThan(0, $result, 'Create supplier invoice template: '.$localobject->errorsToString());

		$savoption = isset($conf->global->SUPPLIER_INVOICE_RECURRING_GENERATION_ADDDAYS) ? $conf->global->SUPPLIER_INVOICE_RECURRING_GENERATION_ADDDAYS : null;

		try {
			$conf->global->SUPPLIER_INVOICE_RECURRING_GENERATION_ADDDAYS = 0;
			$result = $localobject->createRecurringInvoices($localobject->id);
			$this->assertSame(0, $result, 'Generation must not report an error: '.$localobject->errorsToString());
			$this->assertCount(0, $this->getSupplierInvoicesGeneratedFrom($localobject->id), 'A template dated in 3 days must not be generated without the option');

			$conf->global->SUPPLIER_INVOICE_RECURRING_GENERATION_ADDDAYS = 5;
			$result = $localobject->createRecurringInvoices($localobject->id);
			$this->assertSame(0, $result, 'Generation must not report an error: '.$localobject->errorsToString());
			$generatedids = $this->getSupplierInvoicesGeneratedFrom($localobject->id);
			$this->assertCount(1, $generatedids, 'A template dated in 3 days must be generated with 5 days in advance');

			$generated = new FactureFournisseur($db);
			$result = $generated->fetch($generatedids[0]);
			$this->assertGreaterThan(0, $result, 'Fetch generated supplier invoice: '.$generated->errorsToString());
			$this->assertSame(dol_print_date($datewhen, 'day'), dol_print_date($generated->date, 'day'), 'The invoice generated in advance must keep the generation date of the template');
		} finally {
			// $conf is shared by reference with the next test suites, a failed assertion must not leak the option
			if ($savoption === null) {
				unset($conf->global->SUPPLIER_INVOICE_RECURRING_GENERATION_ADDDAYS);
			} else {
				$conf->global->SUPPLIER_INVOICE_RECURRING_GENERATION_ADDDAYS = $savoption;
			}
		}
	}

	/**
	 * A supplier template not yet due is generated in advance only once its previous occurrence is past.
	 *
	 * @return void
	 */
	public function testCreateRecurringInvoicesAtMostOneInAdvance()
	{
		global $conf, $user, $langs, $db;

		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobjectinv = new FactureFournisseur($db);
		$localobjectinv->initAsSpecimen();
		$localobjectinv->ref_supplier = 'SPECIMENADVANCEONE';
		$result = $localobjectinv->create($user);
		$this->assertGreaterThan(0, $result, 'Create source supplier invoice: '.$localobjectinv->errorsToString());

		$localobject = new FactureFournisseurRec($db);
		$localobject->initAsSpecimen();
		$localobject->title = 'SPECIMENADVANCEONE';
		$localobject->ref_supplier = 'SPECIMENADVANCEONEREC';	// Copied on each generated invoice, unique by thirdparty
		$localobject->frequency = 7;
		$localobject->unit_frequency = 'd';
		$localobject->date_when = dol_time_plus_duree(dol_now(), 2, 'd');
		$result = $localobject->create($user, $localobjectinv->id);
		$this->assertGreaterThan(0, $result, 'Create supplier invoice template: '.$localobject->errorsToString());

		$savoption = isset($conf->global->SUPPLIER_INVOICE_RECURRING_GENERATION_ADDDAYS) ? $conf->global->SUPPLIER_INVOICE_RECURRING_GENERATION_ADDDAYS : null;

		try {
			$conf->global->SUPPLIER_INVOICE_RECURRING_GENERATION_ADDDAYS = 30;
			for ($call = 0; $call < 3; $call++) {
				$result = $localobject->createRecurringInvoices($localobject->id);
				$this->assertSame(0, $result, 'Generation must not report an error: '.$localobject->errorsToString());
			}
			$this->assertCount(1, $this->getSupplierInvoicesGeneratedFrom($localobject->id), 'A weekly template with 30 days in advance must never be more than one invoice ahead');
		} finally {
			if ($savoption === null) {
				unset($conf->global->SUPPLIER_INVOICE_RECURRING_GENERATION_ADDDAYS);
			} else {
				$conf->global->SUPPLIER_INVOICE_RECURRING_GENERATION_ADDDAYS = $savoption;
			}
		}
	}

	/**
	 * Return the ids of the supplier invoices already generated from a supplier invoice template.
	 *
	 * @param	int			$id		Id of the supplier invoice template
	 * @return	int[]				Ids of the supplier invoices generated from this template
	 */
	private function getSupplierInvoicesGeneratedFrom(int $id): array
	{
		global $db;

		$ids = array();

		$sql = "SELECT rowid FROM ".$db->prefix()."facture_fourn";
		$sql .= " WHERE fk_fac_rec_source = ".((int) $id);
		$sql .= $db->order('rowid', 'ASC');

		$resql = $db->query($sql);
		$this->assertNotFalse($resql, 'Search supplier invoices generated from template '.$id.': '.$db->lasterror());

		while ($obj = $db->fetch_object($resql)) {
			$ids[] = (int) $obj->rowid;
		}
		$db->free($resql);

		return $ids;
	}
}
