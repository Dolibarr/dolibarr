<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024-2026  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2026		Vincent de Grandpré		<vincent@de-grandpre.quebec>
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
 *      \file       test/phpunit/DiscountTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db,$mysoc;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/discount.class.php';
require_once dirname(__FILE__).'/../../htdocs/compta/facture/class/facture.class.php';
require_once dirname(__FILE__).'/../../htdocs/fourn/class/fournisseur.facture.class.php';
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
 * @phan-file-suppress PhanUndeclaredClass
 * @phan-file-suppress PhanUndeclaredExtendedClass
 * @phan-file-suppress PhanUndeclaredMethod
 * @phan-file-suppress PhanUndeclaredProperty
 */
class DiscountTest extends CommonClassTest
{
	/**
	 * testDiscountCreate
	 *
	 * @return	int
	 */
	public function testDiscountCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$soc = new Societe($db);
		$soc->name = "CommandeTest Unittest";
		$socid = $soc->create($user);

		// @phan-suppress-next-line PhanUndeclaredMethod
		$this->assertLessThan($socid, 0, $soc->errorsToString());

		$localobject = new DiscountAbsolute($db);
		$localobject->initAsSpecimen();
		$localobject->socid = $socid;
		$result = $localobject->create($user);

		// @phan-suppress-next-line PhanUndeclaredMethod
		$this->assertLessThan($result, 0);
		print __METHOD__." result=".$result."\n";
		return $result;
	}

	/**
	 * testDiscountFetch
	 *
	 * @param	int	$id		Id of discount
	 * @return	int
	 *
	 * @depends	testDiscountCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testDiscountFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new DiscountAbsolute($db);
		$result = $localobject->fetch($id);

		// @phan-suppress-next-line PhanUndeclaredMethod
		$this->assertLessThan($result, 0);
		print __METHOD__." id=".$id." result=".$result."\n";
		return $id;
	}

	/**
	 * testDiscountDelete
	 *
	 * @param	int		$id		Id of discount
	 * @return	int
	 *
	 * @depends	testDiscountFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testDiscountDelete($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new DiscountAbsolute($db);
		$result = $localobject->fetch($id);
		$result = $localobject->delete($user);

		print __METHOD__." id=".$id." result=".$result."\n";
		// @phan-suppress-next-line PhanUndeclaredMethod
		$this->assertLessThan($result, 0);
		return $result;
	}

	/**
	 * Provide test data for AbsoluteDiscount splitting
	 *
	 * @return array<mixed> values and expectations data
	 */
	public function providerSplitRemiseData()
	{
		// A fixed discount with a taxed total
		// array(total_amount, splitamount_1, tva_tx, localtax1_tx, localtax1_type, localtax2_tx, localtax2_type, ex_ht_amount1, ex_total_amount1, ex_ht_amount2, ex_total_amount2),
		return array(
			array(1468.15, 1000, 5, 9.975, 1, 0, 0,      869.75, 1000, 407.17, 468.15)		// Split 1468.15 ttc into 1000 + remain 468.15
		);
	}

	/**
	 * testDiscountScenarioConfirmSplit
	 *
	 * test the scenario of action 'confirm_split' in remx.php, also support 'confirm_split_more'
	 *
	 * @return	int
	 * @depends	testDiscountDelete
	 */
	public function testDiscountSplitScenarioConfirmSplit()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		foreach ($this->providerSplitRemiseData() as $case) {
			list($total_amount, $splitamount_1, $tva_tx, $localtax1_tx, $localtax1_type, $localtax2_tx, $localtax2_type, $ex_ht_amount1, $ex_total_amount1, $ex_ht_amount2, $ex_total_amount2) = $case;

			/**
			 * Create a DiscountAbsolute object from spec, split it and
			 * test results with expected.
			 */

			$localobject = new DiscountAbsolute($db);
			$localobject->tva_tx = $tva_tx;
			$localobject->localtax1_tx = $localtax1_tx;
			$localobject->localtax1_type = $localtax1_type;
			$localobject->localtax2_tx = $localtax2_tx;
			$localobject->localtax2_type = $localtax2_type;
			$newDiscounts = $localobject->splitAmount($splitamount_1, $total_amount - $splitamount_1);
			$newdiscount1 = $newDiscounts[0];
			$newdiscount2 = $newDiscounts[1];
			// @phan-suppress-next-line PhanUndeclaredMethod
			$this->assertEquals($ex_ht_amount1, $newdiscount1->amount_ht);
			// @phan-suppress-next-line PhanUndeclaredMethod
			$this->assertEquals($ex_total_amount1, $newdiscount1->amount_ttc);
			// @phan-suppress-next-line PhanUndeclaredMethod
			$this->assertEquals($ex_ht_amount2, $newdiscount2->amount_ht);
			// @phan-suppress-next-line PhanUndeclaredMethod
			$this->assertEquals($ex_total_amount2, $newdiscount2->amount_ttc);
			$result = 1;

			print __METHOD__." total_amount=".$total_amount." splitamount_1=".$splitamount_1." tva_tx=".$tva_tx." localtax1_tx=".$localtax1_tx." localtax1_type=".$localtax1_type." localtax2_tx=".$localtax2_tx." localtax2_type=".$localtax2_type." result=".$result."\n";
			print __METHOD__." discount2: amount_ht=".$newdiscount2->amount_ht." vat=".$newdiscount2->total_tva." localtax1=".$newdiscount2->total_localtax1." localtax2=".$newdiscount2->total_localtax2." ttx=".$newdiscount2->total_ttc."\n";
		}

		return $result;
	}

	/**
	 * Provide test data for AbsoluteDiscount
	 *
	 * @return array<mixed> values and expectations data
	 */
	public function providerRemiseData()
	{
		// array(amount, vat_tx, localtax1_tx, localtax1_type, localtax2_tx, localtax2_type, price_base, ex_total_tva, ex_total_localtax1, ex_total_localtax2, ex_total_ttc, ex_total_ht),
		return array(
			array(1234,    5, 9.975, 1, 0, 0, 'HT',   61.7, 123.09, 0, 1418.79, 1234),
			array(1418.79, 5, 9.975, 1, 0, 0, '',     61.7, 123.09, 0, 1418.79, 1234),
			array(1234,    5, 9.975, 1, 4, 1, 'HT',   61.7, 123.09, 49.36, 1468.15, 1234),
			array(1468.15, 5, 9.975, 1, 4, 1, '',     61.7, 123.09, 49.36, 1468.15, 1234),
			array(1234,    5, 9.975, 2, 0, 0,'HT',    61.7, 129.25, 0, 1424.95, 1234),
			array(1424.95, 5, 9.975, 2, 0, 0, '',     61.7, 129.25, 0, 1424.95, 1234),
			array(1234,    5, 9.975, 2, 4, 2,'HT',    61.7, 129.25, 51.83, 1476.78, 1234),
			array(1476.78, 5, 9.975, 2, 4, 2, '',     61.7, 129.25, 51.83, 1476.78, 1234)
		);
	}

	/**
	 * testDiscountScenarioSetRemise
	 *
	 * test the scenario of function set_remise_exept of class Societe
	 *
	 * @return	int
	 */
	public function testDiscountScenarioSetRemise()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		foreach ($this->providerRemiseData() as $case) {
			list($amount, $vat_tx, $localtax1_tx, $localtax1_type, $localtax2_tx, $localtax2_type, $price_base, $ex_total_tva, $ex_total_localtax1, $ex_total_localtax2, $ex_total_ttc, $ex_total_ht) = $case;

			/**
			 * Create a DiscountAbsolute object with spec and test with expected result
			 */
			$localobject = new DiscountAbsolute($db);

			$localobject->generateFromAmount($amount, ($price_base == 'HT' ? 0 : 1), $vat_tx, $localtax1_tx, $localtax2_tx, $localtax1_type, $localtax2_type);

			print __METHOD__." amount=".$amount." vat_tx=".$vat_tx." localtax1_tx=".$localtax1_tx." localtax1_type=".$localtax1_type." localtax2_tx=".$localtax2_tx." localtax2_type=".$localtax2_type." price_base=".$price_base."\n";

			// @phan-suppress-next-line PhanUndeclaredMethod
			$this->assertEquals($ex_total_ht, $localobject->total_ht);
			// @phan-suppress-next-line PhanUndeclaredMethod
			$this->assertEquals($ex_total_ttc, $localobject->total_ttc);
			// @phan-suppress-next-line PhanUndeclaredMethod
			$this->assertEquals($ex_total_tva, $localobject->total_tva);
			// @phan-suppress-next-line PhanUndeclaredMethod
			$this->assertEquals($ex_total_localtax1, $localobject->total_localtax1);
			// @phan-suppress-next-line PhanUndeclaredMethod
			$this->assertEquals($ex_total_localtax2, $localobject->total_localtax2);
			$result = 1;
		}

		return $result;
	}

	/**
	 * testGetSumsForIds
	 *
	 * The batch methods used by the invoice lists (CommonInvoice::getSommePaiementForIds() and
	 * DiscountAbsolute::getSumDiscountsUsedForIds()) must give, for every invoice, the same amounts as the per invoice methods
	 * getSommePaiement(), getSumCreditNotesUsed() and getSumDepositsUsed(), in both currencies. Checked on customer and on supplier
	 * invoices, with payments and with discounts coming from source invoices of every type, so that the type filter of each kind
	 * (credit notes and excess received vs deposits, which differs between customer and supplier invoices) is exercised.
	 * Everything is inserted in a transaction that is rolled back.
	 *
	 * @return void
	 */
	public function testGetSumsForIds()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$discount = new DiscountAbsolute($db);

		$db->begin();

		$now = $db->idate(dol_now());
		$cases = [
			['facture', 'Facture', 0, 'fk_facture', 'fk_facture_source', 'paiement_facture', 'fk_paiement', 'fk_facture', "ref, entity, fk_soc, type, datec, datef, total_ttc", "1, 1, %d, '".$now."', '".$now."', 100", "INSERT INTO ".$db->prefix()."paiement (entity, datec, datep, amount, fk_paiement, fk_user_creat) VALUES (1, '".$now."', '".$now."', 12.5, 4, ".((int) $user->id).")"],
			['facture_fourn', 'FactureFournisseur', 1, 'fk_invoice_supplier', 'fk_invoice_supplier_source', 'paiementfourn_facturefourn', 'fk_paiementfourn', 'fk_facturefourn', "ref, ref_supplier, entity, fk_soc, type, datec, datef, total_ttc, fk_user_author", "'SUPTEST%d', 1, 1, %d, '".$now."', '".$now."', 100, 1", "INSERT INTO ".$db->prefix()."paiementfourn (entity, datec, datep, amount, fk_paiement, fk_bank, fk_user_author) VALUES (1, '".$now."', '".$now."', 12.5, 4, 0, ".((int) $user->id).")"],
		];
		foreach ($cases as [$table, $class, $supplier, $fieldinvoice, $fieldsource, $paymenttable, $fieldpayment, $fieldpaidinvoice, $columns, $valuespattern, $paymentinsert]) {
			// One payment record, shared by the target invoices through the link table
			$this->assertTrue((bool) $db->query($paymentinsert), $class.' payment insert '.$db->lasterror());
			$paymentid = (int) $db->last_insert_id($db->prefix().($supplier ? 'paiementfourn' : 'paiement'));
			// 2 target invoices, and one source invoice of each type
			$ids = [];
			$srcids = [];
			// The deposit comes first so it gets the smallest discount amount: the credit notes sum (several types) is then always greater
			foreach ([9, 8, CommonInvoice::TYPE_DEPOSIT, CommonInvoice::TYPE_STANDARD, CommonInvoice::TYPE_REPLACEMENT, CommonInvoice::TYPE_CREDIT_NOTE, CommonInvoice::TYPE_SITUATION] as $key => $type) {
				$values = str_replace('%d', (string) $type, $valuespattern);
				$sql = "INSERT INTO ".$db->prefix().$table." (".$columns.") VALUES ('(PROVTEST".$key.")', ".$values.")";
				$this->assertTrue((bool) $db->query($sql), $class.' insert '.$db->lasterror());
				if ($key < 2) {
					$ids[] = (int) $db->last_insert_id($db->prefix().$table);
				} else {
					$srcids[$type] = (int) $db->last_insert_id($db->prefix().$table);
				}
			}
			// Discounts of a distinct amount coming from each source invoice, used on each target invoice, and one payment
			$k = 0;
			foreach ($ids as $id) {
				foreach ($srcids as $srcid) {
					$k++;
					$sql = "INSERT INTO ".$db->prefix()."societe_remise_except (entity, fk_soc, datec, amount_ht, amount_tva, amount_ttc, multicurrency_amount_ttc, ".$fieldinvoice.", ".$fieldsource.", fk_user, description)";
					$sql .= " VALUES (1, 1, '".$db->idate(dol_now())."', 10, 2, ".(10 * $k).", ".(7 * $k).", ".$id.", ".$srcid.", ".((int) $user->id).", 'test')";
					$this->assertTrue((bool) $db->query($sql), 'discount insert '.$db->lasterror());
				}
				$sql = "INSERT INTO ".$db->prefix().$paymenttable." (".$fieldpayment.", ".$fieldpaidinvoice.", amount, multicurrency_amount) VALUES (".$paymentid.", ".$id.", 12.5, 9.25)";
				$this->assertTrue((bool) $db->query($sql), 'payment link insert '.$db->lasterror());
			}

			$paid = CommonInvoice::getSommePaiementForIds($db, $ids, $supplier);
			$creditnotes = $discount->getSumDiscountsUsedForIds($ids, 'creditnotes', $supplier);
			$deposits = $discount->getSumDiscountsUsedForIds($ids, 'deposits', $supplier);

			foreach ($ids as $id) {
				$invoice = new $class($db);
				$invoice->id = $id;

				print __METHOD__." ".$class." ".$id." paid=".$paid[$id]['alreadypaid']." creditnotes=".$creditnotes[$id]['amount']." deposits=".$deposits[$id]['amount']."\n";

				$this->assertEqualsWithDelta((float) $invoice->getSommePaiement(0), $paid[$id]['alreadypaid'], 0.000001, $class.' paid');
				$this->assertEqualsWithDelta((float) $invoice->getSommePaiement(1), $paid[$id]['alreadypaid_multicurrency'], 0.000001, $class.' paid multicurrency');
				$this->assertEqualsWithDelta((float) $invoice->getSumCreditNotesUsed(0), $creditnotes[$id]['amount'], 0.000001, $class.' credit notes');
				$this->assertEqualsWithDelta((float) $invoice->getSumCreditNotesUsed(1), $creditnotes[$id]['multicurrency_amount'], 0.000001, $class.' credit notes multicurrency');
				$this->assertEqualsWithDelta((float) $invoice->getSumDepositsUsed(0), $deposits[$id]['amount'], 0.000001, $class.' deposits');
				$this->assertEqualsWithDelta((float) $invoice->getSumDepositsUsed(1), $deposits[$id]['multicurrency_amount'], 0.000001, $class.' deposits multicurrency');
				$this->assertGreaterThan(0, $deposits[$id]['amount'], $class.' the deposit type must be counted');
				$this->assertGreaterThan($deposits[$id]['amount'], $creditnotes[$id]['amount'], $class.' several types are counted as credit notes');
			}

			// An invoice without anything has no entry, unknown or empty ids give an empty array
			$this->assertArrayNotHasKey(reset($srcids), $paid);
			$this->assertSame([], CommonInvoice::getSommePaiementForIds($db, [], $supplier));
			$this->assertSame([], $discount->getSumDiscountsUsedForIds([0, -1], 'deposits', $supplier));
		}

		$db->rollback();
	}
}
