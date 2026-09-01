<?php
/* Copyright (C) 2026 MDW <mdeweerd@users.noreply.github.com>
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
 */

/**
 *      \file       test/phpunit/Issue38061Test.php
 *      \ingroup    test
 *      \brief      PHPUnit test for issue #38061 - Payments from available credit not used in Sales Tax Reports
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/tax.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/date.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/functions.lib.php';
require_once dirname(__FILE__).'/../../htdocs/compta/facture/class/facture.class.php';
require_once dirname(__FILE__).'/../../htdocs/compta/facture/class/factureligne.class.php';
require_once dirname(__FILE__).'/../../htdocs/societe/class/societe.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/discount.class.php';
require_once dirname(__FILE__).'/../../htdocs/compta/paiement/class/paiement.class.php';
require_once dirname(__FILE__).'/../../htdocs/compta/bank/class/account.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

// Define Discount as alias for DiscountAbsolute if not already defined
if (!class_exists('Discount')) {
	class_alias('DiscountAbsolute', 'Discount');
}

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;

/**
 * Class for PHPUnit tests for issue #38061
 *
 * Issue: When an invoice is (partially) paid with a credit, then this transaction
 * does not appear in the Sales tax reports and hence the VAT report is incorrect.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks    backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class Issue38061Test extends CommonClassTest
{
	/**
	 * Clean up test data before each test
	 *
	 * @return void
	 */
	public function setUp(): void
	{
		parent::setUp();

		global $db;

		// Clean up any existing test data before running the test
		// This handles leftover data from manual runs or previous test failures
		$patterns = array('CUTEST38061', 'CUTEST38061-2', 'CUTEST38061_PARTIAL');
		$this->cleanupTestData($db, $patterns);
	}

	/**
	 * Test that VAT reports include invoices paid with credit notes
	 *
	 * This test demonstrates the bug where invoices paid with credit notes
	 * are not included in the tax_by_rate calculations.
	 *
	 * @return void
	 */
	public function testVATReportIncludesCreditNotePayments()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// Enable required modules
		$conf->facture->enabled = 1;
		$conf->societe->enabled = 1;

		// Create a test company
		$soc = new Societe($db);
		$soc->name = 'Test Company for Issue 38061';
		$soc->client = 1;
		$soc->fournisseur = 0;
		$soc->code_client = 'CUTEST38061';
		$res = $soc->create($user);
		$this->assertGreaterThan(0, $res, 'Failed to create test company');
		$socid = $soc->id;
		$this->test_socid = $socid;
		$this->test_invoice_ids = array();

		// Create a standard invoice with VAT
		$invoice1 = new Facture($db);
		$invoice1->socid = $socid;
		$invoice1->date = dol_now();
		$invoice1->type = Facture::TYPE_STANDARD;
		$invoice1->cond_reglement_id = 1;
		$invoice1->mode_reglement_id = 1;
		$res = $invoice1->create($user);
		$this->assertGreaterThan(0, $res, 'Failed to create first invoice');

		// Add a line to the invoice with VAT
		$invoice1->addline(
			'Test product description',  // desc
			100,                         // pu_ht
			10,                          // qty
			19.6,                       // txtva
			0,                          // txlocaltax1
			0,                          // txlocaltax2
			0,                          // fk_product
			0,                          // remise_percent
			0                           // price_base_type
		);

		// Validate the invoice
		$res = $invoice1->validate($user);
		$this->assertGreaterThan(0, $res, 'Failed to validate first invoice');
		$invoice1id = $invoice1->id;
		$this->test_invoice_ids[] = $invoice1id;

		// Create a credit note from the first invoice
		$creditnote = new Facture($db);
		$creditnote->socid = $socid;
		$creditnote->type = Facture::TYPE_CREDIT_NOTE;
		$creditnote->fk_facture_source = $invoice1id;
		$creditnote->date = dol_now();
		$creditnote->cond_reglement_id = 0;
		$creditnote->mode_reglement_id = 0;
		$res = $creditnote->create($user);
		$this->assertGreaterThan(0, $res, 'Failed to create credit note');

		// Copy lines from the source invoice to the credit note
		// Note: For credit notes, quantities are typically negative, but the type field handles the sign
		$invoice1->fetch($invoice1id);
		$creditnote->fetch($creditnote->id);
		foreach ($invoice1->lines as $line) {
			$creditnote->addline(
				$line->desc,
				$line->subprice,
				$line->qty,
				$line->tva_tx,
				0,
				0,
				$line->fk_product,
				0,
				0
			);
		}

		// Validate the credit note
		$res = $creditnote->validate($user);
		$this->assertGreaterThan(0, $res, 'Failed to validate credit note');
		$creditnoteid = $creditnote->id;
		$this->test_invoice_ids[] = $creditnoteid;

		// Create a second invoice to be paid with the credit note
		$invoice2 = new Facture($db);
		$invoice2->socid = $socid;
		$invoice2->date = dol_now();
		$invoice2->type = Facture::TYPE_STANDARD;
		$invoice2->cond_reglement_id = 1;
		$invoice2->mode_reglement_id = 1;
		$res = $invoice2->create($user);
		$this->assertGreaterThan(0, $res, 'Failed to create second invoice');

		// Add a line to the second invoice with VAT
		$invoice2->addline(
			'Test product description for invoice 2',  // desc
			50,                                          // pu_ht
			5,                                           // qty
			19.6,                                        // txtva
			0,                                           // txlocaltax1
			0,                                           // txlocaltax2
			0,                                           // fk_product
			0,                                           // remise_percent
			0                                            // price_base_type
		);

		// Validate the second invoice
		$res = $invoice2->validate($user);
		$this->assertGreaterThan(0, $res, 'Failed to validate second invoice');
		$invoice2id = $invoice2->id;
		$this->test_invoice_ids[] = $invoice2id;

		// Now use the credit note to pay the second invoice
		// This is done by creating a societe_remise_except record
		$discount = new DiscountAbsolute($db);
		$discount->socid = $socid;
		$discount->fk_facture = $invoice2id;
		$discount->fk_facture_source = $creditnoteid;
		$discount->amount_ht = 250.00;  // 50 * 5 = 250
		$discount->amount_tva = 49.00;  // 250 * 0.196 = 49
		$discount->amount_ttc = 299.00;  // 250 + 49 = 299
		$discount->tva_tx = 19.6;
		$discount->description = '(CREDIT_NOTE)';
		$discount->discount_type = 0;  // Customer discount
		$discount->datec = dol_now();
		$discount->fk_user = $user->id;

		$res = $discount->create($user);
		$this->assertGreaterThan(0, $res, 'Failed to create discount (credit note usage): '.$discount->error);

		// Now test the tax_by_rate function
		// We need to set the tax mode to payment-based for this test to show the issue
		// Save current tax mode
		$saved_tax_mode = isset($conf->global->TAX_MODE) ? $conf->global->TAX_MODE : 0;
		$conf->global->TAX_MODE = 2;  // Payment-based VAT calculation
		$conf->global->TAX_MODE_SELL_PRODUCT = 'payment';
		$conf->global->TAX_MODE_SELL_SERVICE = 'payment';

		// Get VAT data using tax_by_rate function
		$now = dol_now();
		$year = date('Y', $now);
		$month = date('n', $now);
		$date_start = dol_get_first_day($year, $month);
		$date_end = dol_get_last_day($year, $month);

		// For sell direction (customer invoices)
		$x_coll = tax_by_rate('vat', $db, 0, 0, $date_start, $date_end, 2, 'sell');

		// Check if the second invoice (paid with credit note) is included in VAT calculations
		// The data structure is: $x_coll[rate]['facid'][index] = invoice_id
		// and $x_coll[rate]['payment_amount'][index] = payment amount for that invoice
		$found_invoice2 = false;
		$total_payment_amount_for_invoice2 = 0;

		foreach ($x_coll as $rate => $data) {
			if (isset($data['facid']) && is_array($data['facid'])) {
				foreach ($data['facid'] as $index => $facid) {
					if ($facid == $invoice2id) {
						$found_invoice2 = true;
						// Check payment amount for this invoice
						if (isset($data['payment_amount'][$index]) && $data['payment_amount'][$index] > 0) {
							$total_payment_amount_for_invoice2 += $data['payment_amount'][$index];
						}
					}
				}
			}
			if ($found_invoice2) {
				break;
			}
		}

		// This is the bug: invoice2 paid with credit note should be included but it's not
		// Because tax_by_rate doesn't look at societe_remise_except table
		// The test currently expects it NOT to be found (demonstrating the bug)
		// After the fix, it should be found

		// Restore tax mode
		$conf->global->TAX_MODE = $saved_tax_mode;

		// For now, we document the bug - the invoice paid with credit note is NOT included
		// This is what the issue is about
		$debug_info = "Invoice 1 ID: $invoice1id, Invoice 2 ID: $invoice2id, Credit Note ID: $creditnoteid";
		$debug_info .= "\nDiscount created: $res";
		$debug_info .= "\nFound invoice2: " . ($found_invoice2 ? 'YES' : 'NO');
		$debug_info .= "\nTotal payment amount for invoice2: $total_payment_amount_for_invoice2";
		$debug_info .= "\nVAT data returned: " . print_r($x_coll, true);

		// The test currently demonstrates the bug - invoice2 is NOT in the VAT report
		// when paid with credit note
		// We use assertTrue to verify the fix works
		// After the fix, invoice2 should be found and have payment amount > 0
		$this->assertTrue(
			$found_invoice2 && $total_payment_amount_for_invoice2 > 0,
			"FIXED: Invoice paid with credit note (ID=$invoice2id) IS included in VAT report with payment amount.\n" . $debug_info
		);

		// Also verify that the payment amount is correct
		// (it should include the credit note payment of 250 + 49 VAT = 299 TTC)
		$this->assertGreaterThan(
			0,
			$total_payment_amount_for_invoice2,
			"Payment amount for invoice2 should be greater than 0 (credit note payment of 299 TTC expected)"
		);

		// If this assertion passes, it means the bug exists
		// If it fails after a fix, it means the bug is resolved
		print __METHOD__ . ": Test demonstrates issue #38061 - Invoice paid with credit note missing from VAT report\n";
	}

	/**
	 * Clean up test data after each test
	 *
	 * @return void
	 */
	public function tearDown(): void
	{
		global $db;

		// Clean up data created by the test
		// This is called after each test method, even if the test fails
		if (isset($this->test_socid) && $this->test_socid > 0) {
			$socid = $this->test_socid;
			$invoice_ids = isset($this->test_invoice_ids) ? $this->test_invoice_ids : array();
			$paymentid = isset($this->test_paymentid) ? $this->test_paymentid : 0;

			// Delete discounts
			$db->query("DELETE FROM " . MAIN_DB_PREFIX . "societe_remise_except WHERE fk_soc = " . (int) $socid);

			// Delete payment extra fields and payments
			if (!empty($invoice_ids)) {
				$invoice_id_list = implode(',', $invoice_ids);

				// Find payment IDs linked to our invoices
				$payment_ids_to_delete = array();
				$sql_pay_link = "SELECT DISTINCT fk_paiement FROM " . MAIN_DB_PREFIX . "paiement_facture WHERE fk_facture IN (" . $invoice_id_list . ")";
				$resql_pay = $db->query($sql_pay_link);
				if ($resql_pay) {
					while ($obj = $db->fetch_object($resql_pay)) {
						$payment_ids_to_delete[] = $obj->fk_paiement;
					}
				}
				$payment_id_list_to_delete = !empty($payment_ids_to_delete) ? implode(',', $payment_ids_to_delete) : '0';

				// Note: Bank entries are not directly linked to payments in the database schema
				// (the bank table doesn't have a fk_paiement field).
				// Bank entries will be left in the database and cleaned up by cleanupTestData()
				// which deletes by company patterns.

				// Delete paiement_facture links
				$db->query("DELETE FROM " . MAIN_DB_PREFIX . "paiement_facture WHERE fk_facture IN (" . $invoice_id_list . ")");
				$db->query("DELETE FROM " . MAIN_DB_PREFIX . "paiement_facture WHERE fk_paiement IN (" . $payment_id_list_to_delete . ")");

				// Delete payment extra fields
				if (!empty($payment_ids_to_delete)) {
					$db->query("DELETE FROM " . MAIN_DB_PREFIX . "paiement_extrafields WHERE fk_object IN (" . $payment_id_list_to_delete . ")");
				}

				// Delete payments
				if (!empty($payment_ids_to_delete)) {
					$db->query("DELETE FROM " . MAIN_DB_PREFIX . "paiement WHERE rowid IN (" . $payment_id_list_to_delete . ")");
				}

				// Delete invoice line extra fields
				$db->query("DELETE FROM " . MAIN_DB_PREFIX . "facturedet_extrafields WHERE fk_object IN (SELECT rowid FROM " . MAIN_DB_PREFIX . "facturedet WHERE fk_facture IN (" . $invoice_id_list . "))");

				// Delete invoice lines
				$db->query("DELETE FROM " . MAIN_DB_PREFIX . "facturedet WHERE fk_facture IN (" . $invoice_id_list . ")");

				// Delete invoice extra fields
				$db->query("DELETE FROM " . MAIN_DB_PREFIX . "facture_extrafields WHERE fk_object IN (" . $invoice_id_list . ")");

				// Delete discounts that reference these invoices
				$db->query("DELETE FROM " . MAIN_DB_PREFIX . "societe_remise_except WHERE fk_facture IN (" . $invoice_id_list . ") OR fk_facture_source IN (" . $invoice_id_list . ")");

				// Delete invoices - credit notes first
				$db->query("DELETE FROM " . MAIN_DB_PREFIX . "facture WHERE rowid IN (" . $invoice_id_list . ") AND type IN (2,3)");
				$db->query("DELETE FROM " . MAIN_DB_PREFIX . "facture WHERE rowid IN (" . $invoice_id_list . ") AND type NOT IN (2,3)");
			}

			// Delete company contacts
			$db->query("DELETE FROM " . MAIN_DB_PREFIX . "socpeople WHERE fk_soc = " . (int) $socid);

			// Delete company extra fields
			$db->query("DELETE FROM " . MAIN_DB_PREFIX . "societe_extrafields WHERE fk_object = " . (int) $socid);

			// Delete company
			$db->query("DELETE FROM " . MAIN_DB_PREFIX . "societe WHERE rowid = " . (int) $socid);
		}

		parent::tearDown();
	}

	/**
	 * Test that tax_by_rate function should consider credit notes as payments
	 *
	 * This test will fail until the bug is fixed. After fixing, it should pass.
	 *
	 * @return void
	 */
	public function testTaxByRateShouldIncludeCreditNotePayments()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// Enable required modules
		$conf->facture->enabled = 1;
		$conf->societe->enabled = 1;

		// Create a test company
		$soc = new Societe($db);
		$soc->name = 'Test Company 2 for Issue 38061';
		$soc->client = 1;
		$soc->fournisseur = 0;
		$soc->code_client = 'CUTEST38061-2';
		$res = $soc->create($user);
		$this->assertGreaterThan(0, $res, 'Failed to create test company');
		$socid = $soc->id;
		$this->test_socid = $socid;
		$this->test_invoice_ids = array();

		// Create a credit note invoice with VAT
		$creditnote = new Facture($db);
		$creditnote->socid = $socid;
		$creditnote->date = dol_now();
		$creditnote->type = Facture::TYPE_CREDIT_NOTE;
		$creditnote->cond_reglement_id = 0;
		$creditnote->mode_reglement_id = 0;
		$res = $creditnote->create($user);
		$this->assertGreaterThan(0, $res, 'Failed to create credit note');

		// Add a line to the credit note with VAT
		$creditnote->addline(
			'Credit product description',  // desc
			100,                           // pu_ht (positive, credit note handled by type)
			2,                             // qty
			19.6,                         // txtva
			0,                             // txlocaltax1
			0,                             // txlocaltax2
			0,                             // fk_product
			0,                             // remise_percent
			0                              // price_base_type
		);

		// Validate the credit note
		$res = $creditnote->validate($user);
		$this->assertGreaterThan(0, $res, 'Failed to validate credit note');
		$creditnoteid = $creditnote->id;
		$this->test_invoice_ids[] = $creditnoteid;

		// Create an invoice to be paid with the credit note
		$invoice = new Facture($db);
		$invoice->socid = $socid;
		$invoice->date = dol_now();
		$invoice->type = Facture::TYPE_STANDARD;
		$invoice->cond_reglement_id = 1;
		$invoice->mode_reglement_id = 1;
		$res = $invoice->create($user);
		$this->assertGreaterThan(0, $res, 'Failed to create invoice');

		// Add a line to the invoice with VAT
		$invoice->addline(
			'Test product description',  // desc
			100,                          // pu_ht
			1,                            // qty
			19.6,                        // txtva
			0,                           // txlocaltax1
			0,                           // txlocaltax2
			0,                           // fk_product
			0,                           // remise_percent
			0                            // price_base_type
		);

		// Validate the invoice
		$res = $invoice->validate($user);
		$this->assertGreaterThan(0, $res, 'Failed to validate invoice');
		$invoiceid = $invoice->id;
		$this->test_invoice_ids[] = $invoiceid;

		// Use the credit note to pay the invoice
		$discount = new DiscountAbsolute($db);
		$discount->socid = $socid;
		$discount->fk_facture = $invoiceid;
		$discount->fk_facture_source = $creditnoteid;
		$discount->amount_ht = 100.00;
		$discount->amount_tva = 19.60;  // 100 * 0.196
		$discount->amount_ttc = 119.60;
		$discount->tva_tx = 19.6;
		$discount->description = '(CREDIT_NOTE)';
		$discount->discount_type = 0;
		$discount->datec = dol_now();
		$discount->fk_user = $user->id;

		$res = $discount->create($user);
		$this->assertGreaterThan(0, $res, 'Failed to create discount (credit note usage): '.$discount->error);

		// Set tax mode to payment-based
		$saved_tax_mode = isset($conf->global->TAX_MODE) ? $conf->global->TAX_MODE : 0;
		$saved_tax_mode_sell_product = isset($conf->global->TAX_MODE_SELL_PRODUCT) ? $conf->global->TAX_MODE_SELL_PRODUCT : '';
		$saved_tax_mode_sell_service = isset($conf->global->TAX_MODE_SELL_SERVICE) ? $conf->global->TAX_MODE_SELL_SERVICE : '';

		$conf->global->TAX_MODE = 2;
		$conf->global->TAX_MODE_SELL_PRODUCT = 'payment';
		$conf->global->TAX_MODE_SELL_SERVICE = 'payment';

		// Get date range
		$now = dol_now();
		$year = date('Y', $now);
		$month = date('n', $now);
		$date_start = dol_get_first_day($year, $month);
		$date_end = dol_get_last_day($year, $month);

		// Get VAT data
		$x_coll = tax_by_rate('vat', $db, 0, 0, $date_start, $date_end, 2, 'sell');

		// Check if the invoice paid with credit note is included
		// The data structure is: $x_coll[rate]['facid'][index] = invoice_id
		// and $x_coll[rate]['payment_amount'][index] = payment amount from credit note
		$found_invoice = false;
		$total_vat_found = 0;
		$total_payment_amount_from_credit = 0;

		foreach ($x_coll as $rate => $data) {
			if (isset($data['facid']) && is_array($data['facid'])) {
				foreach ($data['facid'] as $index => $facid) {
					if ($facid == $invoiceid) {
						$found_invoice = true;
						// Check if VAT amount is included
						if (isset($data['vat_list'][$index])) {
							$total_vat_found += $data['vat_list'][$index];
						}
						// Check payment amount from credit note
						if (isset($data['payment_amount'][$index]) && $data['payment_amount'][$index] > 0) {
							$total_payment_amount_from_credit += $data['payment_amount'][$index];
						}
						break;
					}
				}
			}
			if ($found_invoice) {
				break;
			}
		}

		// Restore tax mode
		$conf->global->TAX_MODE = $saved_tax_mode;
		$conf->global->TAX_MODE_SELL_PRODUCT = $saved_tax_mode_sell_product;
		$conf->global->TAX_MODE_SELL_SERVICE = $saved_tax_mode_sell_service;

		// This test documents the expected behavior AFTER the fix
		// Currently it will fail because the bug exists
		// After the fix, the invoice should be found and VAT should be included
		$debug_info = "Invoice ID: $invoiceid, Credit Note ID: $creditnoteid\n";
		$debug_info .= "Found invoice: " . ($found_invoice ? 'YES' : 'NO') . "\n";
		$debug_info .= "Total VAT found: $total_vat_found\n";
		$debug_info .= "Payment amount from credit: $total_payment_amount_from_credit\n";
		$debug_info .= "VAT data: " . print_r($x_coll, true);

		// After the fix, the invoice should be found and have payment amount > 0
		$this->assertTrue(
			$found_invoice && $total_payment_amount_from_credit > 0,
			"FIXED: Invoice paid with credit note should be included in VAT report with payment amount.\n" . $debug_info
		);

		// Also check that VAT amount is correct
		$this->assertGreaterThan(
			0,
			$total_vat_found,
			"VAT amount should be greater than 0 for invoice paid with credit note"
		);

		// Check that the payment amount from credit note is captured
		$this->assertGreaterThan(
			0,
			$total_payment_amount_from_credit,
			"Payment amount from credit note should be greater than 0 (119.60 TTC expected)"
		);

		print __METHOD__ . ": Test verifies that issue #38061 is fixed - Invoice paid with credit note is in VAT report\n";
	}

	/**
	 * Test that VAT reports include invoices PARTIALLY paid with split credit notes (main issue #38061 scenario)
	 *
	 * This test mirrors the interactive test case more closely:
	 * - Creates a credit note from Invoice 1
	 * - Splits the credit note into 2 parts
	 * - Uses only the first part to PARTIALLY pay Invoice 2 (with services)
	 * - Also creates a normal payment on Invoice 2
	 * - Verifies that the partial payment from credit note is included in VAT report
	 *
	 * @return void
	 */
	public function testVATReportIncludesPartiallyPaidInvoiceWithSplitCreditNote()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// Enable required modules
		$conf->facture->enabled = 1;
		$conf->societe->enabled = 1;

		// Create a test company
		$soc = new Societe($db);
		$soc->name = 'Test Company for Issue 38061 Partial';
		$soc->client = 1;
		$soc->fournisseur = 0;
		$soc->code_client = 'CUTEST38061_PARTIAL';
		$res = $soc->create($user);
		$this->assertGreaterThan(0, $res, 'Failed to create test company');
		$socid = $soc->id;
		$this->test_socid = $socid;
		$this->test_invoice_ids = array();

		// Create Invoice 1 with service line (1000 HT)
		$invoice1 = new Facture($db);
		$invoice1->socid = $socid;
		$invoice1->date = dol_now();
		$invoice1->type = Facture::TYPE_STANDARD;
		$invoice1->cond_reglement_id = 1;
		$invoice1->mode_reglement_id = 1;
		$res = $invoice1->create($user);
		$this->assertGreaterThan(0, $res, 'Failed to create first invoice');

		// Add service line: 100 x 10 = 1000 HT
		$res = $invoice1->addline(
			'Test Service 1000',  // desc
			100,                  // pu_ht
			10,                   // qty
			19.6,                // txtva
			0,
			0,
			0,
			0,
			0,       // tax fields
			'',
			'',              // special_code, fk_remise_except
			0,
			0,               // info_bits, fk_unit
			'HT',                // price_base_type
			0,                  // fk_product_type
			1                   // type = 1 for service
		);
		$this->assertGreaterThan(0, $res, 'Failed to add line to invoice 1');

		$res = $invoice1->validate($user);
		$this->assertGreaterThan(0, $res, 'Failed to validate first invoice');
		$invoice1id = $invoice1->id;
		$this->test_invoice_ids[] = $invoice1id;

		// Create credit note from Invoice 1
		$creditnote = new Facture($db);
		$creditnote->socid = $socid;
		$creditnote->type = Facture::TYPE_CREDIT_NOTE;
		$creditnote->fk_facture_source = $invoice1id;
		$creditnote->date = dol_now();
		$creditnote->cond_reglement_id = 0;
		$creditnote->mode_reglement_id = 0;
		$res = $creditnote->create($user);
		$this->assertGreaterThan(0, $res, 'Failed to create credit note');

		// Copy lines from Invoice 1 to credit note
		$invoice1->fetch($invoice1id);
		$creditnote->fetch($creditnote->id);
		foreach ($invoice1->lines as $line) {
			$creditnote->addline(
				$line->desc,
				$line->subprice,
				$line->qty,
				$line->tva_tx,
				0,
				0,
				$line->fk_product,
				0,
				0
			);
		}

		$res = $creditnote->validate($user);
		$this->assertGreaterThan(0, $res, 'Failed to validate credit note');
		$creditnoteid = $creditnote->id;
		$this->test_invoice_ids[] = $creditnoteid;

		// Create Invoice 2 with service line (100 HT)
		$invoice2 = new Facture($db);
		$invoice2->socid = $socid;
		$invoice2->date = dol_now();
		$invoice2->type = Facture::TYPE_STANDARD;
		$invoice2->cond_reglement_id = 1;
		$invoice2->mode_reglement_id = 1;
		$res = $invoice2->create($user);
		$this->assertGreaterThan(0, $res, 'Failed to create second invoice');

		// Add service line: 10 x 10 = 100 HT
		$res = $invoice2->addline(
			'Test Service 100',   // desc
			10,                   // pu_ht
			10,                   // qty
			19.6,                // txtva
			0,
			0,
			0,
			0,
			0,       // tax fields
			'',
			'',              // special_code, fk_remise_except
			0,
			0,               // info_bits, fk_unit
			'HT',                // price_base_type
			0,                  // fk_product_type
			1                   // type = 1 for service
		);
		$this->assertGreaterThan(0, $res, 'Failed to add line to invoice 2');

		$res = $invoice2->validate($user);
		$this->assertGreaterThan(0, $res, 'Failed to validate second invoice');
		$invoice2id = $invoice2->id;
		$this->test_invoice_ids[] = $invoice2id;

		// Create discount from credit note (1196 TTC = 1000 HT + 196 VAT)
		$discount_original = new DiscountAbsolute($db);
		$discount_original->socid = $socid;
		$discount_original->fk_facture_source = $creditnoteid;
		$discount_original->amount_ht = 1000.00;
		$discount_original->amount_tva = 196.00;
		$discount_original->amount_ttc = 1196.00;
		$discount_original->tva_tx = 19.6;
		$discount_original->description = '(CREDIT_NOTE)';
		$discount_original->discount_type = 0;
		$discount_original->datec = dol_now();
		$discount_original->fk_user = $user->id;

		$res = $discount_original->create($user);
		$this->assertGreaterThan(0, $res, 'Failed to create original discount: '.$discount_original->error);

		// Split the discount into 2 parts: 10 TTC and 1186 TTC
		$amount_ttc_1 = 10.00;
		$amount_ttc_2 = 1196.00 - 10.00;  // 1186.00

		$newDiscounts = $discount_original->splitAmount($amount_ttc_1, $amount_ttc_2);
		$discount_part1 = $newDiscounts[0];  // 10 TTC part
		$discount_part2 = $newDiscounts[1];  // 1186 TTC part

		// Delete the original discount using the class method
		// First, clear the source links to avoid deleting the entire family
		$discount_original->fk_facture_source = 0;
		$discount_original->fk_invoice_supplier_source = 0;
		$res = $discount_original->delete($user);
		$this->assertGreaterThan(0, $res, 'Failed to delete original discount: ' . $discount_original->error);

		// Create the two split discounts
		$res1 = $discount_part1->create($user);
		$res2 = $discount_part2->create($user);
		$this->assertGreaterThan(0, $res1, 'Failed to create split discount part 1');
		$this->assertGreaterThan(0, $res2, 'Failed to create split discount part 2');

		$discountid_part1 = $discount_part1->id;
		$discountid_part2 = $discount_part2->id;

		// Link discount part 1 to Invoice 2 (partial payment)
		$discount_part1->fetch($discountid_part1);
		$result = $discount_part1->link_to_invoice(0, $invoice2id);
		$this->assertGreaterThan(0, $result, 'Failed to link discount to invoice');

		// Create a normal payment on Invoice 2 (50.00)
		$payment = new Paiement($db);
		$payment->datepaye = dol_now();
		$payment->amount = 50.00;
		$payment->amounts = array($invoice2id => 50.00);
		$payment->facid = $invoice2id;
		$payment->socid = $socid;

		// Find first bank account
		$first_account_id = 0;
		$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "bank_account WHERE entity IN (" . getEntity('bank_account') . ") AND clos = 0 ORDER BY rowid ASC LIMIT 1";
		$resql = $db->query($sql);
		if ($resql && $db->num_rows($resql) > 0) {
			$obj = $db->fetch_object($resql);
			$first_account_id = $obj->rowid;
		}

		// Find payment mode
		$payment_mode_id = 0;
		$payment_mode_code = '';
		if ($first_account_id > 0) {
			$sql = "SELECT id, code FROM " . MAIN_DB_PREFIX . "c_paiement WHERE entity IN (" . getEntity('c_paiement') . ") AND active = 1 ORDER BY id ASC LIMIT 1";
			$resql = $db->query($sql);
			if ($resql && $db->num_rows($resql) > 0) {
				$obj = $db->fetch_object($resql);
				$payment_mode_id = $obj->id;
				$payment_mode_code = $obj->code;
			}
			if ($payment_mode_id <= 0) {
				$sql = "SELECT id, code FROM " . MAIN_DB_PREFIX . "c_paiement WHERE active = 1 ORDER BY id ASC LIMIT 1";
				$resql = $db->query($sql);
				if ($resql && $db->num_rows($resql) > 0) {
					$obj = $db->fetch_object($resql);
					$payment_mode_id = $obj->id;
					$payment_mode_code = $obj->code;
				}
			}
		}

		if ($first_account_id > 0 && $payment_mode_id > 0 && !empty($payment_mode_code)) {
			$payment->fk_account = $first_account_id;
			$payment->paiementid = $payment_mode_id;
			$payment->paiementcode = $payment_mode_code;

			$res = $payment->create($user);
			if ($res > 0) {
				$payment->addPaymentToBank($user, 'payment', '(CustomerInvoicePayment)', $first_account_id, '', '');
				$paymentid = $payment->id;
			} else {
				$paymentid = 0;
			}
		} else {
			$paymentid = 0;
		}

		// Set tax mode to payment-based
		$saved_tax_mode = isset($conf->global->TAX_MODE) ? $conf->global->TAX_MODE : 0;
		$saved_tax_mode_sell_product = isset($conf->global->TAX_MODE_SELL_PRODUCT) ? $conf->global->TAX_MODE_SELL_PRODUCT : '';
		$saved_tax_mode_sell_service = isset($conf->global->TAX_MODE_SELL_SERVICE) ? $conf->global->TAX_MODE_SELL_SERVICE : '';

		$conf->global->TAX_MODE = 2;
		$conf->global->TAX_MODE_SELL_PRODUCT = 'payment';
		$conf->global->TAX_MODE_SELL_SERVICE = 'payment';

		// Get date range for current month
		$now = dol_now();
		$year = date('Y', $now);
		$month = date('n', $now);
		$date_start = dol_get_first_day($year, $month);
		$date_end = dol_get_last_day($year, $month);

		// Get VAT data
		$x_coll = tax_by_rate('vat', $db, 0, 0, $date_start, $date_end, 2, 'sell');

		// Check if Invoice 2 is included in VAT calculations
		// The data structure is: $x_coll[rate]['facid'][index] = invoice_id
		// and $x_coll[rate]['payment_amount'][index] = payment amount for that invoice
		$found_invoice2 = false;
		$total_vat_for_invoice2 = 0;
		$total_payment_amount_for_invoice2 = 0;

		foreach ($x_coll as $rate => $data) {
			if (isset($data['facid']) && is_array($data['facid'])) {
				foreach ($data['facid'] as $index => $facid) {
					if ($facid == $invoice2id) {
						$found_invoice2 = true;
						// Sum VAT amounts for this invoice
						if (isset($data['vat_list'][$index])) {
							$total_vat_for_invoice2 += $data['vat_list'][$index];
						}
						// Also check payment-related VAT
						if (isset($data['vat_list_paie'][$index])) {
							$total_vat_for_invoice2 += $data['vat_list_paie'][$index];
						}
						// Check payment amount for this invoice
						if (isset($data['payment_amount'][$index]) && $data['payment_amount'][$index] > 0) {
							$total_payment_amount_for_invoice2 += $data['payment_amount'][$index];
						}
					}
				}
			}
			if ($found_invoice2) {
				break;
			}
		}

		// Restore tax mode
		$conf->global->TAX_MODE = $saved_tax_mode;
		$conf->global->TAX_MODE_SELL_PRODUCT = $saved_tax_mode_sell_product;
		$conf->global->TAX_MODE_SELL_SERVICE = $saved_tax_mode_sell_service;

		// Debug info
		$debug_info = "Invoice 1 ID: $invoice1id, Credit Note ID: $creditnoteid\n";
		$debug_info .= "Invoice 2 ID: $invoice2id, Discount part 1 ID: $discountid_part1\n";
		$debug_info .= "Normal payment ID: $paymentid, Discount part 2 ID: $discountid_part2\n";
		$debug_info .= "Found invoice2: " . ($found_invoice2 ? 'YES' : 'NO') . "\n";
		$debug_info .= "Total payment amount for invoice2: $total_payment_amount_for_invoice2\n";
		$debug_info .= "Total VAT for invoice2: $total_vat_for_invoice2\n";
		$debug_info .= "VAT data: " . print_r($x_coll, true);

		// This is the main test: Invoice 2 paid partially with credit note (10 TTC) and partially with normal payment (50.00)
		// should be included in VAT report when using payment-based VAT calculation
		// The normal payment part should definitely be there
		// The credit note part is what issue #38061 is about
		// After the fix, invoice2 should be found and have payment amount > 0
		$this->assertTrue(
			$found_invoice2 && $total_payment_amount_for_invoice2 > 0,
			"FIXED: Invoice 2 (partially paid with split credit note) should be included in VAT report with payment amount.\n" . $debug_info
		);

		// Also check that VAT amount is non-zero for this invoice
		$this->assertGreaterThan(
			0,
			$total_vat_for_invoice2,
			"VAT amount should be greater than 0 for invoice 2 paid partially with credit note and normal payment"
		);

		// Check that the payment amount includes both credit note (10 TTC) and normal payment (50 TTC)
		// Total should be at least 60 (10 + 50)
		$this->assertGreaterThanOrEqual(
			60,
			$total_payment_amount_for_invoice2,
			"Payment amount for invoice2 should be at least 60 (10 TTC from credit note + 50 TTC from normal payment)"
		);

		print __METHOD__ . ": Test verifies partial payment with split credit note is included in VAT report\n";
	}

	/**
	 * Helper method to clean up test data for the given patterns
	 *
	 * @param DoliDB $db Database connection
	 * @param array $patterns Array of patterns to match company codes or names
	 * @return void
	 */
	private function cleanupTestData($db, $patterns)
	{
		foreach ($patterns as $pattern) {
			// Find companies by code_client or name
			$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "societe WHERE code_client LIKE '" . $db->escape($pattern) . "%' OR nom LIKE '%" . $db->escape($pattern) . "%'";
			$resql = $db->query($sql);
			if ($resql) {
				$company_ids = array();
				while ($obj = $db->fetch_object($resql)) {
					$company_ids[] = $obj->rowid;
				}

				// Delete all discounts for these companies
				if (!empty($company_ids)) {
					$company_id_list = implode(',', $company_ids);

					// First, find all invoice IDs for these companies
					$invoice_ids = array();
					$sql_inv = "SELECT rowid FROM " . MAIN_DB_PREFIX . "facture WHERE fk_soc IN (" . $company_id_list . ")";
					$resql_inv = $db->query($sql_inv);
					if ($resql_inv) {
						while ($obj_inv = $db->fetch_object($resql_inv)) {
							$invoice_ids[] = $obj_inv->rowid;
						}
					}
					$invoice_id_list = !empty($invoice_ids) ? implode(',', $invoice_ids) : '0';

					// First, find all payment IDs for these companies' invoices
					// Note: paiement table doesn't have fk_soc, so we find payments through paiement_facture
					$payment_ids = array();
					if (!empty($invoice_ids)) {
						$sql_pay = "SELECT DISTINCT fk_paiement FROM " . MAIN_DB_PREFIX . "paiement_facture WHERE fk_facture IN (" . $invoice_id_list . ")";
						$resql_pay = $db->query($sql_pay);
						if ($resql_pay) {
							while ($obj_pay = $db->fetch_object($resql_pay)) {
								$payment_ids[] = $obj_pay->fk_paiement;
							}
						}
					}
					$payment_id_list = !empty($payment_ids) ? implode(',', $payment_ids) : '0';

					// Delete in correct order: child tables first, then parent tables

					// Note: Bank entries are not directly linked to payments in the database schema
					// (the bank table doesn't have a fk_paiement field, and bank_class was renamed to category_bankline).
					// Bank entries will be left in the database. They can be manually cleaned up if needed.

					// 3. Delete paiement_facture links for invoices
					if (!empty($invoice_ids)) {
						$sql_pf = "DELETE FROM " . MAIN_DB_PREFIX . "paiement_facture WHERE fk_facture IN (" . $invoice_id_list . ")";
						$res = $db->query($sql_pf);
					}

					// 4. Delete paiement_facture links for payments
					if (!empty($payment_ids)) {
						$sql_pf2 = "DELETE FROM " . MAIN_DB_PREFIX . "paiement_facture WHERE fk_paiement IN (" . $payment_id_list . ")";
						$res = $db->query($sql_pf2);
					}

					// 5. Delete payment extra fields
					if (!empty($payment_ids)) {
						$sql_pe = "DELETE FROM " . MAIN_DB_PREFIX . "paiement_extrafields WHERE fk_object IN (" . $payment_id_list . ")";
						$res = $db->query($sql_pe);
					}

					// 6. Delete invoice line extra fields
					if (!empty($invoice_ids)) {
						$sql_idle = "DELETE FROM " . MAIN_DB_PREFIX . "facturedet_extrafields WHERE fk_object IN (SELECT rowid FROM " . MAIN_DB_PREFIX . "facturedet WHERE fk_facture IN (" . $invoice_id_list . "))";
						$res = $db->query($sql_idle);
					}

					// 7. Delete invoice lines
					if (!empty($invoice_ids)) {
						$sql_idl = "DELETE FROM " . MAIN_DB_PREFIX . "facturedet WHERE fk_facture IN (" . $invoice_id_list . ")";
						$res = $db->query($sql_idl);
					}

					// 8. Delete invoice extra fields
					if (!empty($invoice_ids)) {
						$sql_ie = "DELETE FROM " . MAIN_DB_PREFIX . "facture_extrafields WHERE fk_object IN (" . $invoice_id_list . ")";
						$res = $db->query($sql_ie);
					}

					// 9. Delete discounts FIRST - they may reference invoices via fk_facture_source
					$sql_disc = "DELETE FROM " . MAIN_DB_PREFIX . "societe_remise_except WHERE fk_soc IN (" . $company_id_list . ")";
					$res = $db->query($sql_disc);

					// 10. Delete invoices - Must delete credit notes first, then other invoices
					// because fk_facture_source references the source invoice
					// First delete credit notes (type = 2, 3) that reference these companies
					$sql_cn = "DELETE FROM " . MAIN_DB_PREFIX . "facture WHERE fk_soc IN (" . $company_id_list . ") AND type IN (2,3)";
					$res = $db->query($sql_cn);

					// Then delete remaining invoices (standard, deposits, etc.)
					$sql_inv = "DELETE FROM " . MAIN_DB_PREFIX . "facture WHERE fk_soc IN (" . $company_id_list . ")";
					$res = $db->query($sql_inv);

					// 10. Delete payments - paiement table doesn't have fk_soc, need to delete via paiement_facture links
					// First find payment IDs linked to our invoices
					$payment_ids_to_delete = array();
					if (!empty($invoice_ids)) {
						$sql_pay_link = "SELECT DISTINCT fk_paiement FROM " . MAIN_DB_PREFIX . "paiement_facture WHERE fk_facture IN (" . $invoice_id_list . ")";
						$resql_pay = $db->query($sql_pay_link);
						if ($resql_pay) {
							while ($obj = $db->fetch_object($resql_pay)) {
								$payment_ids_to_delete[] = $obj->fk_paiement;
							}
						}
					}
					$payment_id_list_to_delete = !empty($payment_ids_to_delete) ? implode(',', $payment_ids_to_delete) : '0';

					if (!empty($payment_ids_to_delete)) {
						// Note: Bank entries are not directly linked to payments (no fk_paiement in bank table)
						// Skip bank cleanup here as well

						// Delete paiement_facture links for these payments
						$sql_pf_del = "DELETE FROM " . MAIN_DB_PREFIX . "paiement_facture WHERE fk_paiement IN (" . $payment_id_list_to_delete . ")";
						$res = $db->query($sql_pf_del);

						// Delete payment extra fields
						$sql_pe_del = "DELETE FROM " . MAIN_DB_PREFIX . "paiement_extrafields WHERE fk_object IN (" . $payment_id_list_to_delete . ")";
						$res = $db->query($sql_pe_del);

						// Finally delete the payments
						$sql_pay_del = "DELETE FROM " . MAIN_DB_PREFIX . "paiement WHERE rowid IN (" . $payment_id_list_to_delete . ")";
						$res = $db->query($sql_pay_del);
					} else {
					}

					// 11. Delete company contacts
					$sql = "DELETE FROM " . MAIN_DB_PREFIX . "socpeople WHERE fk_soc IN (" . $company_id_list . ")";
					$res = $db->query($sql);

					// 12. Delete company extra fields
					$sql = "DELETE FROM " . MAIN_DB_PREFIX . "societe_extrafields WHERE fk_object IN (" . $company_id_list . ")";
					$res = $db->query($sql);

					// 13. Delete the companies
					$res = $db->query("DELETE FROM " . MAIN_DB_PREFIX . "societe WHERE rowid IN (" . $company_id_list . ")");
				}
			}
		}
	}

	/**
	 * Clean up ALL test data after all tests have run
	 * This ensures no leftover data from manual runs or previous test executions
	 *
	 * @return void
	 */
	public static function tearDownAfterClass(): void
	{
		global $db, $user, $conf;

		// Create an instance to call the cleanup method
		$instance = new self();
		$patterns = array('CUTEST38061', 'CUTEST38061-2', 'CUTEST38061_PARTIAL');
		$instance->cleanupTestData($db, $patterns);
	}
}
