<?php
/* Copyright (C) 2026 Lionel Vessiller <lvessiller@open-dsi.fr>
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
 *      \file       test/phpunit/TtcRoundingTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test for unit price entered including tax (price_base_type='TTC').
 *      \remarks    To run this script as CLI:  phpunit filename.php
 *
 *      When a line is entered with the unit price including tax, the line total AND the persisted
 *      "TTC entry mode" (subprice_ttc) must be kept consistent so that no 0.01 rounding drift appears,
 *      including after a no-op edit or a bulk action (discount on all lines).
 *
 *      Full workflow covered per object (like a real quote): a subtotal title line, a product line and
 *      a free line, all entered in TTC mode, then a no-op update, then a global discount on all lines.
 *
 *      Scenario per priced line: qty = 680, VAT = 20%, unit price TTC = 3.35
 *          - on create:        total_ht = 1898.33, total_tva = 379.67, total_ttc = 2278.00
 *          - after 10% discount: total_ht = 1708.50, total_tva = 341.70, total_ttc = 2050.20
 *          - the line always keeps subprice_ttc = 3.35 (the value typed by the user)
 *
 *      The principle is already in place for the customer proposal (reference) and for the supplier
 *      order/invoice: those run the whole workflow green. It must still be replicated on the customer
 *      order/invoice/contract: the subprice_ttc assertions are the expected red signal until then
 *      (contract lines have no subtotals; the contract test is skipped when the module is disabled).
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/societe/class/societe.class.php';
require_once dirname(__FILE__).'/../../htdocs/product/class/product.class.php';
require_once dirname(__FILE__).'/../../htdocs/comm/propal/class/propal.class.php';
require_once dirname(__FILE__).'/../../htdocs/commande/class/commande.class.php';
require_once dirname(__FILE__).'/../../htdocs/compta/facture/class/facture.class.php';
require_once dirname(__FILE__).'/../../htdocs/contrat/class/contrat.class.php';
require_once dirname(__FILE__).'/../../htdocs/fourn/class/fournisseur.commande.class.php';
require_once dirname(__FILE__).'/../../htdocs/fourn/class/fournisseur.facture.class.php';
require_once dirname(__FILE__).'/../../htdocs/supplier_proposal/class/supplier_proposal.class.php';
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
class TtcRoundingTest extends CommonClassTest
{
	// Common scenario: a unit price typed including tax that does not divide evenly once converted to HT.
	const QTY = 680;
	const VAT = 20;
	const PU_TTC = 3.35;
	const REMISE = 10;

	// Expected per priced line, entered in TTC mode (calibrated, MAIN_MAX_DECIMALS_UNIT=5 / _TOT=2).
	const LINE_HT = 1898.33;
	const LINE_TVA = 379.67;
	const LINE_TTC = 2278.00;

	// Expected per priced line after a 10% discount, TTC mode preserved.
	const LINE_HT_REMISE = 1708.50;
	const LINE_TVA_REMISE = 341.70;
	const LINE_TTC_REMISE = 2050.20;

	/** @var int Shared thirdparty (customer and supplier) created once for the whole class */
	protected static $socid;

	/** @var int Shared product created once for the whole class */
	protected static $productid;

	/**
	 * Create the shared fixtures (one thirdparty and one product) once for the whole class.
	 * They live inside the class transaction and are rolled back in tearDownAfterClass().
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		global $user,$db;

		parent::setUpBeforeClass();

		$soc = new Societe($db);
		$soc->name = 'Test TTC rounding '.uniqid();
		$soc->client = 1;
		$soc->fournisseur = 1;
		$soc->country_id = 1;
		$soc->code_client = -1;
		$soc->code_fournisseur = -1;
		self::$socid = $soc->create($user);
		self::assertGreaterThan(0, self::$socid, 'Create shared thirdparty: '.$soc->error);

		// A dedicated simple product (no packaging) so a product line behaves deterministically.
		$prod = new Product($db);
		$prod->ref = 'TTCTEST'.substr(uniqid(), -8);
		$prod->label = 'TTC test product';
		$prod->type = 0;
		$prod->status = 1;
		$prod->status_buy = 1;
		$prod->price_base_type = 'HT';
		$prod->price = 10;
		$prod->tva_tx = self::VAT;
		self::$productid = $prod->create($user);
		self::assertGreaterThan(0, self::$productid, 'Create shared product: '.$prod->error);
	}

	/**
	 * Restore globals saved by CommonClassTest and force a deterministic rounding mode.
	 *
	 * @return void
	 */
	private function restoreGlobals()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$conf->global->MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND = 0;
	}

	/**
	 * Return only the priced lines (exclude subtotal title/section lines).
	 *
	 * @param	CommonObject	$object		Object with lines loaded
	 * @return	CommonObjectLine[]
	 */
	private function pricedLines($object)
	{
		$out = array();
		foreach ($object->lines as $line) {
			if (defined('SUBTOTALS_SPECIAL_CODE') && $line->special_code == SUBTOTALS_SPECIAL_CODE) {
				continue;
			}
			$out[] = $line;
		}
		return $out;
	}

	/**
	 * Assert a priced line entered in TTC keeps its TTC value and has the expected totals.
	 *
	 * @param	CommonObjectLine	$line		Reloaded line
	 * @param	string				$tag		Message prefix
	 * @param	bool				$discounted	true to check the post-discount totals
	 * @return	void
	 */
	private function assertPricedLine($line, $tag, $discounted = false)
	{
		$this->assertEquals(self::PU_TTC, (float) ($line->subprice_ttc ?? 0), $tag.' subprice_ttc (TTC entry mode kept)');
		if ($discounted) {
			$this->assertEquals(self::LINE_HT_REMISE, (float) $line->total_ht, $tag.' total_ht (after discount)');
			$this->assertEquals(self::LINE_TVA_REMISE, (float) $line->total_tva, $tag.' total_tva (after discount)');
			$this->assertEquals(self::LINE_TTC_REMISE, (float) $line->total_ttc, $tag.' total_ttc (after discount)');
		} else {
			$this->assertEquals(self::LINE_HT, (float) $line->total_ht, $tag.' total_ht');
			$this->assertEquals(self::LINE_TVA, (float) $line->total_tva, $tag.' total_tva');
			$this->assertEquals(self::LINE_TTC, (float) $line->total_ttc, $tag.' total_ttc');
		}
	}

	/**
	 * Assert both priced lines (product + free) of a reloaded object.
	 *
	 * @param	CommonObject	$object		Reloaded object (lines loaded)
	 * @param	string			$tag		Message prefix
	 * @param	bool			$discounted	true to check post-discount totals
	 * @return	void
	 */
	private function assertBothPricedLines($object, $tag, $discounted = false)
	{
		$priced = $this->pricedLines($object);
		$this->assertCount(2, $priced, $tag.' must have 2 priced lines');
		$this->assertPricedLine($priced[0], $tag.' product line', $discounted);
		$this->assertPricedLine($priced[1], $tag.' free line', $discounted);
	}

	/**
	 * Whether the object supports subtotal lines (subtotals module enabled and object type wired).
	 * Kept portable across versions: supplier objects gained subtotals only in later Dolibarr releases.
	 *
	 * @param	CommonObject	$object		Object to test
	 * @return	bool
	 */
	private function subtotalsSupported($object)
	{
		return defined('SUBTOTALS_SPECIAL_CODE') && method_exists($object, 'addSubtotalLine');
	}

	/**
	 * Add a subtotal title line when the object supports it (no-op otherwise, for portability).
	 *
	 * @param	CommonObject	$object		Object to add the title to
	 * @param	Translate		$langs		Language object
	 * @return	void
	 */
	private function addSubtotalTitle($object, $langs)
	{
		if ($this->subtotalsSupported($object)) {
			$object->addSubtotalLine($langs, 'Section 1', 1);
		}
	}

	/**
	 * Assert that a subtotal title line is present, when the object supports subtotals.
	 *
	 * @param	CommonObject	$object		Reloaded object (lines loaded)
	 * @param	string			$tag		Message prefix
	 * @return	void
	 */
	private function assertSubtotalLinePresent($object, $tag)
	{
		if (!$this->subtotalsSupported($object)) {
			return;
		}
		$found = false;
		foreach ($object->lines as $line) {
			if ($line->special_code == SUBTOTALS_SPECIAL_CODE) {
				$found = true;
				break;
			}
		}
		$this->assertTrue($found, $tag.' must have a subtotal line');
	}

	/**
	 * Customer proposal - reference object, fix already applied. Full workflow, expected green.
	 *
	 * @return void
	 */
	public function testCustomerProposalTtcWorkflow()
	{
		global $user,$langs,$db;
		$this->restoreGlobals();
		if (!isModEnabled('propal')) {
			$this->markTestSkipped('Module propal disabled');
			return;
		}

		$socid = self::$socid;
		$pid = self::$productid;

		$object = new Propal($db);
		$object->initAsSpecimen();
		$object->socid = $socid;
		$object->lines = array();
		$id = $object->create($user);
		$this->assertGreaterThan(0, $id, 'Customer proposal create');

		$this->addSubtotalTitle($object, $langs);
		$object->addline('Product TTC', 0, self::QTY, self::VAT, 0, 0, $pid, 0, 'TTC', self::PU_TTC);
		$object->addline('Free TTC', 0, self::QTY, self::VAT, 0, 0, 0, 0, 'TTC', self::PU_TTC);

		// Create state
		$reloaded = new Propal($db);
		$reloaded->fetch($id);
		$reloaded->fetch_lines();
		$this->assertSubtotalLinePresent($reloaded, 'Customer proposal');
		$this->assertBothPricedLines($reloaded, 'Customer proposal create');

		// No-op update, preserving TTC mode (mirrors card.php using wasEnteredIncludingTax()/subprice_ttc)
		foreach ($this->pricedLines($reloaded) as $line) {
			$object->updateline($line->id, (float) $line->subprice_ttc, $line->qty, $line->remise_percent, $line->tva_tx, 0, 0, $line->desc, 'TTC');
		}
		$afterUpdate = new Propal($db);
		$afterUpdate->fetch($id);
		$afterUpdate->fetch_lines();
		$this->assertBothPricedLines($afterUpdate, 'Customer proposal no-op update');

		// Discount for all lines
		foreach ($this->pricedLines($afterUpdate) as $line) {
			$object->updateline($line->id, (float) $line->subprice_ttc, $line->qty, self::REMISE, $line->tva_tx, 0, 0, $line->desc, 'TTC');
		}
		$afterRemise = new Propal($db);
		$afterRemise->fetch($id);
		$afterRemise->fetch_lines();
		$this->assertBothPricedLines($afterRemise, 'Customer proposal discount', true);
		print __METHOD__." id=".$id." total_ht=".$afterRemise->total_ht."\n";
	}

	/**
	 * Customer order - full workflow. subprice_ttc persistence to be added -> red.
	 *
	 * @return void
	 */
	public function testCustomerOrderTtcWorkflow()
	{
		global $user,$langs,$db;
		$this->restoreGlobals();
		if (!isModEnabled('commande')) {
			$this->markTestSkipped('Module commande disabled');
			return;
		}

		$socid = self::$socid;
		$pid = self::$productid;

		$object = new Commande($db);
		$object->initAsSpecimen();
		$object->socid = $socid;
		$object->lines = array();
		$id = $object->create($user);
		$this->assertGreaterThan(0, $id, 'Customer order create');

		$this->addSubtotalTitle($object, $langs);
		$object->addline('Product TTC', 0, self::QTY, self::VAT, 0, 0, $pid, 0, 0, 0, 'TTC', self::PU_TTC);
		$object->addline('Free TTC', 0, self::QTY, self::VAT, 0, 0, 0, 0, 0, 0, 'TTC', self::PU_TTC);

		$reloaded = new Commande($db);
		$reloaded->fetch($id);
		$reloaded->fetch_lines();
		$this->assertSubtotalLinePresent($reloaded, 'Customer order');
		$this->assertBothPricedLines($reloaded, 'Customer order create');

		foreach ($this->pricedLines($reloaded) as $line) {
			$object->updateline($line->id, $line->desc, (float) $line->subprice_ttc, $line->qty, $line->remise_percent, $line->tva_tx, 0, 0, 'TTC');
		}
		$afterUpdate = new Commande($db);
		$afterUpdate->fetch($id);
		$afterUpdate->fetch_lines();
		$this->assertBothPricedLines($afterUpdate, 'Customer order no-op update');

		foreach ($this->pricedLines($afterUpdate) as $line) {
			$object->updateline($line->id, $line->desc, (float) $line->subprice_ttc, $line->qty, self::REMISE, $line->tva_tx, 0, 0, 'TTC');
		}
		$afterRemise = new Commande($db);
		$afterRemise->fetch($id);
		$afterRemise->fetch_lines();
		$this->assertBothPricedLines($afterRemise, 'Customer order discount', true);
		print __METHOD__." id=".$id."\n";
	}

	/**
	 * Customer invoice - full workflow. subprice_ttc persistence to be added -> red.
	 *
	 * @return void
	 */
	public function testCustomerInvoiceTtcWorkflow()
	{
		global $user,$langs,$db;
		$this->restoreGlobals();
		if (!isModEnabled('facture')) {
			$this->markTestSkipped('Module facture disabled');
			return;
		}

		$socid = self::$socid;
		$pid = self::$productid;

		$object = new Facture($db);
		$object->initAsSpecimen();
		$object->socid = $socid;
		$object->lines = array();
		$id = $object->create($user);
		$this->assertGreaterThan(0, $id, 'Customer invoice create');

		$this->addSubtotalTitle($object, $langs);
		$object->addline('Product TTC', 0, self::QTY, self::VAT, 0, 0, $pid, 0, '', '', 0, 0, 0, 'TTC', self::PU_TTC);
		$object->addline('Free TTC', 0, self::QTY, self::VAT, 0, 0, 0, 0, '', '', 0, 0, 0, 'TTC', self::PU_TTC);

		$reloaded = new Facture($db);
		$reloaded->fetch($id);
		$reloaded->fetch_lines();
		$this->assertSubtotalLinePresent($reloaded, 'Customer invoice');
		$this->assertBothPricedLines($reloaded, 'Customer invoice create');

		foreach ($this->pricedLines($reloaded) as $line) {
			$object->updateline($line->id, $line->desc, (float) $line->subprice_ttc, $line->qty, $line->remise_percent, $line->date_start, $line->date_end, $line->tva_tx, 0, 0, 'TTC');
		}
		$afterUpdate = new Facture($db);
		$afterUpdate->fetch($id);
		$afterUpdate->fetch_lines();
		$this->assertBothPricedLines($afterUpdate, 'Customer invoice no-op update');

		foreach ($this->pricedLines($afterUpdate) as $line) {
			$object->updateline($line->id, $line->desc, (float) $line->subprice_ttc, $line->qty, self::REMISE, $line->date_start, $line->date_end, $line->tva_tx, 0, 0, 'TTC');
		}
		$afterRemise = new Facture($db);
		$afterRemise->fetch($id);
		$afterRemise->fetch_lines();
		$this->assertBothPricedLines($afterRemise, 'Customer invoice discount', true);
		print __METHOD__." id=".$id."\n";
	}

	/**
	 * Customer contract - full workflow (no subtotals on contracts) -> red.
	 *
	 * @return void
	 */
	public function testCustomerContractTtcWorkflow()
	{
		global $user,$db;
		$this->restoreGlobals();
		if (!isModEnabled('contrat')) {
			$this->markTestSkipped('Module contrat disabled');
			return;
		}

		$socid = self::$socid;
		$pid = self::$productid;

		$object = new Contrat($db);
		$object->initAsSpecimen();
		$object->socid = $socid;
		$object->lines = array();
		$id = $object->create($user);
		$this->assertGreaterThan(0, $id, 'Customer contract create');

		$object->addline('Product TTC', 0, self::QTY, self::VAT, 0, 0, $pid, 0, '', '', 'TTC', self::PU_TTC);
		$object->addline('Free TTC', 0, self::QTY, self::VAT, 0, 0, 0, 0, '', '', 'TTC', self::PU_TTC);

		$reloaded = new Contrat($db);
		$reloaded->fetch($id);
		$reloaded->fetch_lines();
		$this->assertBothPricedLines($reloaded, 'Customer contract create');

		foreach ($this->pricedLines($reloaded) as $line) {
			$object->updateline($line->id, $line->desc, (float) $line->subprice_ttc, $line->qty, $line->remise_percent, $line->date_start, $line->date_end, $line->tva_tx, 0, 0, '', '', 'TTC');
		}
		$afterUpdate = new Contrat($db);
		$afterUpdate->fetch($id);
		$afterUpdate->fetch_lines();
		$this->assertBothPricedLines($afterUpdate, 'Customer contract no-op update');

		foreach ($this->pricedLines($afterUpdate) as $line) {
			$object->updateline($line->id, $line->desc, (float) $line->subprice_ttc, $line->qty, self::REMISE, $line->date_start, $line->date_end, $line->tva_tx, 0, 0, '', '', 'TTC');
		}
		$afterRemise = new Contrat($db);
		$afterRemise->fetch($id);
		$afterRemise->fetch_lines();
		$this->assertBothPricedLines($afterRemise, 'Customer contract discount', true);
		print __METHOD__." id=".$id."\n";
	}

	/**
	 * Supplier proposal - full workflow. subprice_ttc already handled upstream.
	 * Same addline()/updateline() signatures as the customer proposal.
	 *
	 * @return void
	 */
	public function testSupplierProposalTtcWorkflow()
	{
		global $user,$langs,$db;
		$this->restoreGlobals();
		if (!isModEnabled('supplier_proposal')) {
			$this->markTestSkipped('Module supplier_proposal disabled');
			return;
		}

		$socid = self::$socid;
		$pid = self::$productid;

		$object = new SupplierProposal($db);
		$object->initAsSpecimen();
		$object->socid = $socid;
		$object->lines = array();
		$id = $object->create($user);
		$this->assertGreaterThan(0, $id, 'Supplier proposal create');

		$this->addSubtotalTitle($object, $langs);
		$object->addline('Product TTC', 0, self::QTY, self::VAT, 0, 0, $pid, 0, 'TTC', self::PU_TTC);
		$object->addline('Free TTC', 0, self::QTY, self::VAT, 0, 0, 0, 0, 'TTC', self::PU_TTC);

		$reloaded = new SupplierProposal($db);
		$reloaded->fetch($id);
		$reloaded->fetch_lines();
		$this->assertSubtotalLinePresent($reloaded, 'Supplier proposal');
		$this->assertBothPricedLines($reloaded, 'Supplier proposal create');

		foreach ($this->pricedLines($reloaded) as $line) {
			$object->updateline($line->id, (float) $line->subprice_ttc, $line->qty, $line->remise_percent, $line->tva_tx, 0, 0, $line->desc, 'TTC');
		}
		$afterUpdate = new SupplierProposal($db);
		$afterUpdate->fetch($id);
		$afterUpdate->fetch_lines();
		$this->assertBothPricedLines($afterUpdate, 'Supplier proposal no-op update');

		foreach ($this->pricedLines($afterUpdate) as $line) {
			$object->updateline($line->id, (float) $line->subprice_ttc, $line->qty, self::REMISE, $line->tva_tx, 0, 0, $line->desc, 'TTC');
		}
		$afterRemise = new SupplierProposal($db);
		$afterRemise->fetch($id);
		$afterRemise->fetch_lines();
		$this->assertBothPricedLines($afterRemise, 'Supplier proposal discount', true);
		print __METHOD__." id=".$id."\n";
	}

	/**
	 * Supplier order - full workflow. subprice_ttc already handled upstream.
	 *
	 * @return void
	 */
	public function testSupplierOrderTtcWorkflow()
	{
		global $user,$langs,$db;
		$this->restoreGlobals();
		if (!isModEnabled('fournisseur') && !isModEnabled('supplier_order')) {
			$this->markTestSkipped('Module supplier order disabled');
			return;
		}

		$socid = self::$socid;
		$pid = self::$productid;

		$object = new CommandeFournisseur($db);
		$object->initAsSpecimen();
		$object->socid = $socid;
		$object->lines = array();
		$id = $object->create($user);
		$this->assertGreaterThan(0, $id, 'Supplier order create');

		$this->addSubtotalTitle($object, $langs);
		$object->addline('Product TTC', 0, self::QTY, self::VAT, 0, 0, $pid, 0, '', 0, 'TTC', self::PU_TTC);
		$object->addline('Free TTC', 0, self::QTY, self::VAT, 0, 0, 0, 0, '', 0, 'TTC', self::PU_TTC);

		$reloaded = new CommandeFournisseur($db);
		$reloaded->fetch($id);
		$reloaded->fetch_lines();
		$this->assertSubtotalLinePresent($reloaded, 'Supplier order');
		$this->assertBothPricedLines($reloaded, 'Supplier order create');

		foreach ($this->pricedLines($reloaded) as $line) {
			$object->updateline($line->id, $line->desc, (float) $line->subprice_ttc, $line->qty, $line->remise_percent, $line->tva_tx, 0, 0, 'TTC');
		}
		$afterUpdate = new CommandeFournisseur($db);
		$afterUpdate->fetch($id);
		$afterUpdate->fetch_lines();
		$this->assertBothPricedLines($afterUpdate, 'Supplier order no-op update');

		foreach ($this->pricedLines($afterUpdate) as $line) {
			$object->updateline($line->id, $line->desc, (float) $line->subprice_ttc, $line->qty, self::REMISE, $line->tva_tx, 0, 0, 'TTC');
		}
		$afterRemise = new CommandeFournisseur($db);
		$afterRemise->fetch($id);
		$afterRemise->fetch_lines();
		$this->assertBothPricedLines($afterRemise, 'Supplier order discount', true);
		print __METHOD__." id=".$id."\n";
	}

	/**
	 * Supplier invoice - full workflow. subprice_ttc already handled upstream.
	 * Note the different addline()/updateline() parameter order (pu in position 2, no pu_ttc: in TTC
	 * mode the $pu argument carries the price including tax).
	 *
	 * @return void
	 */
	public function testSupplierInvoiceTtcWorkflow()
	{
		global $user,$langs,$db;
		$this->restoreGlobals();
		if (!isModEnabled('fournisseur') && !isModEnabled('supplier_invoice')) {
			$this->markTestSkipped('Module supplier invoice disabled');
			return;
		}

		$socid = self::$socid;
		$pid = self::$productid;

		$object = new FactureFournisseur($db);
		$object->initAsSpecimen();
		$object->socid = $socid;
		$object->lines = array();
		$id = $object->create($user);
		$this->assertGreaterThan(0, $id, 'Supplier invoice create');

		$this->addSubtotalTitle($object, $langs);
		$object->addline('Product TTC', self::PU_TTC, self::VAT, 0, 0, self::QTY, $pid, 0, 0, 0, 0, 0, 'TTC');
		$object->addline('Free TTC', self::PU_TTC, self::VAT, 0, 0, self::QTY, 0, 0, 0, 0, 0, 0, 'TTC');

		$reloaded = new FactureFournisseur($db);
		$reloaded->fetch($id);
		$reloaded->fetch_lines();
		$this->assertSubtotalLinePresent($reloaded, 'Supplier invoice');
		$this->assertBothPricedLines($reloaded, 'Supplier invoice create');

		foreach ($this->pricedLines($reloaded) as $line) {
			$object->updateline($line->id, $line->desc, (float) $line->subprice_ttc, $line->tva_tx, 0, 0, $line->qty, 0, 'TTC', 0, 0, $line->remise_percent);
		}
		$afterUpdate = new FactureFournisseur($db);
		$afterUpdate->fetch($id);
		$afterUpdate->fetch_lines();
		$this->assertBothPricedLines($afterUpdate, 'Supplier invoice no-op update');

		foreach ($this->pricedLines($afterUpdate) as $line) {
			$object->updateline($line->id, $line->desc, (float) $line->subprice_ttc, $line->tva_tx, 0, 0, $line->qty, 0, 'TTC', 0, 0, self::REMISE);
		}
		$afterRemise = new FactureFournisseur($db);
		$afterRemise->fetch($id);
		$afterRemise->fetch_lines();
		$this->assertBothPricedLines($afterRemise, 'Supplier invoice discount', true);
		print __METHOD__." id=".$id."\n";
	}
}
