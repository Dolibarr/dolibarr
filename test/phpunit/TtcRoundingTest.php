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
 *      All customer and supplier objects persist the TTC entry mode: customer proposal, order, invoice,
 *      contract, supplier proposal, supplier order and supplier invoice. This test guards against a
 *      regression of that behaviour. Contract lines have no subtotals; each per-object test is skipped
 *      when its module is disabled.
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
require_once dirname(__FILE__).'/../../htdocs/contrat/class/contratligne.class.php';
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
	const PU_HT = 2.79167; // 3.35 / 1.2 rounded at MAIN_MAX_DECIMALS_UNIT=5 - the stored HT unit price
	const REMISE = 10;

	// Expected per priced line, entered in TTC mode (calibrated, MAIN_MAX_DECIMALS_UNIT=5 / _TOT=2).
	const LINE_HT = 1898.33;
	const LINE_TVA = 379.67;
	const LINE_TTC = 2278.00;

	// Expected per priced line after a 10% discount, TTC mode preserved.
	const LINE_HT_REMISE = 1708.50;
	const LINE_TVA_REMISE = 341.70;
	const LINE_TTC_REMISE = 2050.20;

	// Expected per priced line entered in HT mode (pu_ht = PU_HT). subprice_ttc must be 0 (not marked as
	// TTC entry), so HT lines are never hijacked by the TTC preservation and the total keeps its HT rounding.
	const LINE_HT_HTMODE = 1898.34;
	const LINE_TVA_HTMODE = 379.66;
	const LINE_TTC_HTMODE = 2278.00;

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
		$conf->global->MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND_SUPPLIER = 0;
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
		$this->assertEquals(self::PU_HT, (float) $line->subprice, $tag.' subprice (HT unit price kept)');
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
	 * Assert both priced lines of a credit note built by inverting an invoice: the TTC entry mode
	 * is kept with the inverted sign so there is no rounding drift.
	 *
	 * @param	CommonObject	$object		Reloaded credit note (lines loaded)
	 * @param	string			$tag		Message prefix
	 * @return	void
	 */
	private function assertBothPricedLinesInverted($object, $tag)
	{
		$priced = $this->pricedLines($object);
		$this->assertCount(2, $priced, $tag.' must have 2 priced lines');
		foreach ($priced as $k => $line) {
			$this->assertEquals(-self::PU_TTC, (float) ($line->subprice_ttc ?? 0), $tag." line $k subprice_ttc (inverted TTC entry mode kept)");
			$this->assertEquals(-self::PU_HT, (float) $line->subprice, $tag." line $k subprice (inverted HT unit price)");
			$this->assertEquals(-self::LINE_HT, (float) $line->total_ht, $tag." line $k total_ht (inverted)");
			$this->assertEquals(-self::LINE_TVA, (float) $line->total_tva, $tag." line $k total_tva (inverted)");
			$this->assertEquals(-self::LINE_TTC, (float) $line->total_ttc, $tag." line $k total_ttc (inverted)");
		}
	}

	/**
	 * Assert both priced lines were entered in HT mode: subprice_ttc must be 0 (not a TTC entry marker),
	 * subprice = PU_HT and the total keeps its HT-mode rounding (never hijacked by TTC preservation).
	 *
	 * @param	CommonObject	$object		Reloaded object (lines loaded)
	 * @param	string			$tag		Message prefix
	 * @return	void
	 */
	private function assertBothHtLines($object, $tag)
	{
		$priced = $this->pricedLines($object);
		$this->assertCount(2, $priced, $tag.' must have 2 priced lines');
		foreach ($priced as $k => $line) {
			$this->assertEquals(0, (float) ($line->subprice_ttc ?? 0), $tag." line $k subprice_ttc must be 0 (HT entry mode, not marked TTC)");
			$this->assertEquals(self::PU_HT, (float) $line->subprice, $tag." line $k subprice (HT unit price)");
			$this->assertEquals(self::LINE_HT_HTMODE, (float) $line->total_ht, $tag." line $k total_ht (HT mode)");
			$this->assertEquals(self::LINE_TTC_HTMODE, (float) $line->total_ttc, $tag." line $k total_ttc (HT mode)");
		}
	}

	/**
	 * Snapshot the priced lines (subprice, subprice_ttc and totals) for later stability comparison.
	 * Used for the "rounding of total" mode where absolute totals are not deterministic, so we assert
	 * a no-op / clone does not change the stored values rather than hardcoding them.
	 *
	 * @param	CommonObject	$object		Reloaded object (lines loaded)
	 * @return	array<int,array<string,float>>
	 */
	private function snapshotPricedLines($object)
	{
		$snap = array();
		foreach ($this->pricedLines($object) as $line) {
			$snap[] = array(
				'subprice' => (float) $line->subprice,
				'subprice_ttc' => (float) ($line->subprice_ttc ?? 0),
				'total_ht' => (float) $line->total_ht,
				'total_tva' => (float) $line->total_tva,
				'total_ttc' => (float) $line->total_ttc,
			);
		}
		return $snap;
	}

	/**
	 * Assert two priced-line snapshots are identical (no rounding drift on a no-op / clone).
	 *
	 * @param	array<int,array<string,float>>	$before	Snapshot before the operation
	 * @param	array<int,array<string,float>>	$after	Snapshot after the operation
	 * @param	string							$tag	Message prefix
	 * @return	void
	 */
	private function assertLinesUnchanged($before, $after, $tag)
	{
		$this->assertCount(count($before), $after, $tag.' same number of priced lines');
		foreach ($before as $k => $b) {
			foreach (array('subprice', 'subprice_ttc', 'total_ht', 'total_tva', 'total_ttc') as $f) {
				$this->assertEquals($b[$f], $after[$k][$f], $tag." line $k $f stable");
			}
		}
	}

	/**
	 * Assert the mixed 4-line layout kept each entry mode: exactly 2 HT lines (subprice_ttc = 0, subprice =
	 * PU_HT) and 2 TTC lines (subprice_ttc = PU_TTC), whatever the round-of-total totals are.
	 *
	 * @param	CommonObject	$object		Reloaded object (lines loaded)
	 * @param	string			$tag		Message prefix
	 * @return	void
	 */
	private function assertMixedSubpriceTtc($object, $tag)
	{
		$priced = $this->pricedLines($object);
		$this->assertCount(4, $priced, $tag.' must have 4 priced lines');
		$htCount = 0;
		$ttcCount = 0;
		foreach ($priced as $k => $line) {
			$stt = (float) ($line->subprice_ttc ?? 0);
			if ($stt == 0.0) {
				$htCount++;
				$this->assertEquals(self::PU_HT, (float) $line->subprice, $tag." line $k HT subprice (PU_HT kept)");
			} else {
				$ttcCount++;
				$this->assertEquals(self::PU_TTC, $stt, $tag." line $k TTC subprice_ttc (PU_TTC kept)");
			}
		}
		$this->assertEquals(2, $htCount, $tag.' must have 2 HT-entered lines');
		$this->assertEquals(2, $ttcCount, $tag.' must have 2 TTC-entered lines');
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
		if (method_exists($reloaded, 'fetch_lines')) {
			$reloaded->fetch_lines();
		}
		$this->assertSubtotalLinePresent($reloaded, 'Customer proposal');
		$this->assertBothPricedLines($reloaded, 'Customer proposal create');

		// No-op update, preserving TTC mode (mirrors card.php using wasEnteredIncludingTax()/subprice_ttc)
		foreach ($this->pricedLines($reloaded) as $line) {
			$object->updateline($line->id, (float) $line->subprice_ttc, $line->qty, $line->remise_percent, $line->tva_tx, 0, 0, $line->desc, 'TTC');
		}
		$afterUpdate = new Propal($db);
		$afterUpdate->fetch($id);
		if (method_exists($afterUpdate, 'fetch_lines')) {
			$afterUpdate->fetch_lines();
		}
		$this->assertBothPricedLines($afterUpdate, 'Customer proposal no-op update');

		// Discount for all lines
		foreach ($this->pricedLines($afterUpdate) as $line) {
			$object->updateline($line->id, (float) $line->subprice_ttc, $line->qty, self::REMISE, $line->tva_tx, 0, 0, $line->desc, 'TTC');
		}
		$afterRemise = new Propal($db);
		$afterRemise->fetch($id);
		if (method_exists($afterRemise, 'fetch_lines')) {
			$afterRemise->fetch_lines();
		}
		$this->assertBothPricedLines($afterRemise, 'Customer proposal discount', true);
		print __METHOD__." id=".$id." total_ht=".$afterRemise->total_ht."\n";
	}

	/**
	 * Customer proposal - clone must preserve the TTC entry mode (subprice_ttc) so the cloned
	 * line keeps the same totals with no rounding drift.
	 *
	 * @return void
	 */
	public function testCustomerProposalCloneTtc()
	{
		global $user,$db;
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
		$object->ref = 'TTCCLONE'.substr(uniqid(), -8);
		$object->lines = array();
		$id = $object->create($user);
		$this->assertGreaterThan(0, $id, 'Customer proposal clone create source');

		$object->addline('Product TTC', 0, self::QTY, self::VAT, 0, 0, $pid, 0, 'TTC', self::PU_TTC);
		$object->addline('Free TTC', 0, self::QTY, self::VAT, 0, 0, 0, 0, 'TTC', self::PU_TTC);

		$source = new Propal($db);
		$source->fetch($id);
		$clonedId = $source->createFromClone($user, $socid);
		$this->assertGreaterThan(0, $clonedId, 'Customer proposal createFromClone');

		$clone = new Propal($db);
		$clone->fetch($clonedId);
		if (method_exists($clone, 'fetch_lines')) {
			$clone->fetch_lines();
		}
		$this->assertBothPricedLines($clone, 'Customer proposal clone');
		print __METHOD__." id=".$id." clone=".$clonedId."\n";
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
		if (!isModEnabled('order')) {
			$this->markTestSkipped('Module order disabled');
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
		if (method_exists($reloaded, 'fetch_lines')) {
			$reloaded->fetch_lines();
		}
		$this->assertSubtotalLinePresent($reloaded, 'Customer order');
		$this->assertBothPricedLines($reloaded, 'Customer order create');

		foreach ($this->pricedLines($reloaded) as $line) {
			$object->updateline($line->id, $line->desc, (float) $line->subprice_ttc, $line->qty, $line->remise_percent, $line->tva_tx, 0, 0, 'TTC');
		}
		$afterUpdate = new Commande($db);
		$afterUpdate->fetch($id);
		if (method_exists($afterUpdate, 'fetch_lines')) {
			$afterUpdate->fetch_lines();
		}
		$this->assertBothPricedLines($afterUpdate, 'Customer order no-op update');

		foreach ($this->pricedLines($afterUpdate) as $line) {
			$object->updateline($line->id, $line->desc, (float) $line->subprice_ttc, $line->qty, self::REMISE, $line->tva_tx, 0, 0, 'TTC');
		}
		$afterRemise = new Commande($db);
		$afterRemise->fetch($id);
		if (method_exists($afterRemise, 'fetch_lines')) {
			$afterRemise->fetch_lines();
		}
		$this->assertBothPricedLines($afterRemise, 'Customer order discount', true);
		print __METHOD__." id=".$id."\n";
	}

	/**
	 * Customer order - clone must preserve the TTC entry mode (subprice_ttc).
	 *
	 * @return void
	 */
	public function testCustomerOrderCloneTtc()
	{
		global $user,$db;
		$this->restoreGlobals();
		if (!isModEnabled('order')) {
			$this->markTestSkipped('Module order disabled');
			return;
		}

		$socid = self::$socid;
		$pid = self::$productid;

		$object = new Commande($db);
		$object->initAsSpecimen();
		$object->socid = $socid;
		$object->ref = 'TTCCLONE'.substr(uniqid(), -8);
		$object->lines = array();
		$id = $object->create($user);
		$this->assertGreaterThan(0, $id, 'Customer order clone create source');

		$object->addline('Product TTC', 0, self::QTY, self::VAT, 0, 0, $pid, 0, 0, 0, 'TTC', self::PU_TTC);
		$object->addline('Free TTC', 0, self::QTY, self::VAT, 0, 0, 0, 0, 0, 0, 'TTC', self::PU_TTC);

		$source = new Commande($db);
		$source->fetch($id);
		$clonedId = $source->createFromClone($user, $socid);
		$this->assertGreaterThan(0, $clonedId, 'Customer order createFromClone');

		$clone = new Commande($db);
		$clone->fetch($clonedId);
		if (method_exists($clone, 'fetch_lines')) {
			$clone->fetch_lines();
		}
		$this->assertBothPricedLines($clone, 'Customer order clone');
		print __METHOD__." id=".$id." clone=".$clonedId."\n";
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
		if (!isModEnabled('invoice')) {
			$this->markTestSkipped('Module invoice disabled');
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
		if (method_exists($reloaded, 'fetch_lines')) {
			$reloaded->fetch_lines();
		}
		$this->assertSubtotalLinePresent($reloaded, 'Customer invoice');
		$this->assertBothPricedLines($reloaded, 'Customer invoice create');

		foreach ($this->pricedLines($reloaded) as $line) {
			$object->updateline($line->id, $line->desc, (float) $line->subprice_ttc, $line->qty, $line->remise_percent, $line->date_start, $line->date_end, $line->tva_tx, 0, 0, 'TTC');
		}
		$afterUpdate = new Facture($db);
		$afterUpdate->fetch($id);
		if (method_exists($afterUpdate, 'fetch_lines')) {
			$afterUpdate->fetch_lines();
		}
		$this->assertBothPricedLines($afterUpdate, 'Customer invoice no-op update');

		foreach ($this->pricedLines($afterUpdate) as $line) {
			$object->updateline($line->id, $line->desc, (float) $line->subprice_ttc, $line->qty, self::REMISE, $line->date_start, $line->date_end, $line->tva_tx, 0, 0, 'TTC');
		}
		$afterRemise = new Facture($db);
		$afterRemise->fetch($id);
		if (method_exists($afterRemise, 'fetch_lines')) {
			$afterRemise->fetch_lines();
		}
		$this->assertBothPricedLines($afterRemise, 'Customer invoice discount', true);
		print __METHOD__." id=".$id."\n";
	}

	/**
	 * Customer invoice - clone must preserve the TTC entry mode (subprice_ttc).
	 *
	 * @return void
	 */
	public function testCustomerInvoiceCloneTtc()
	{
		global $user,$db;
		$this->restoreGlobals();
		if (!isModEnabled('invoice')) {
			$this->markTestSkipped('Module invoice disabled');
			return;
		}

		$socid = self::$socid;
		$pid = self::$productid;

		$object = new Facture($db);
		$object->initAsSpecimen();
		$object->socid = $socid;
		$object->ref = 'TTCCLONE'.substr(uniqid(), -8);
		$object->lines = array();
		$id = $object->create($user);
		$this->assertGreaterThan(0, $id, 'Customer invoice clone create source');

		$object->addline('Product TTC', 0, self::QTY, self::VAT, 0, 0, $pid, 0, '', '', 0, 0, 0, 'TTC', self::PU_TTC);
		$object->addline('Free TTC', 0, self::QTY, self::VAT, 0, 0, 0, 0, '', '', 0, 0, 0, 'TTC', self::PU_TTC);

		$source = new Facture($db);
		$source->fetch($id);
		$clonedId = $source->createFromClone($user, $id);
		$this->assertGreaterThan(0, $clonedId, 'Customer invoice createFromClone');

		$clone = new Facture($db);
		$clone->fetch($clonedId);
		if (method_exists($clone, 'fetch_lines')) {
			$clone->fetch_lines();
		}
		$this->assertBothPricedLines($clone, 'Customer invoice clone');
		print __METHOD__." id=".$id." clone=".$clonedId."\n";
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
		if (!isModEnabled('contract')) {
			$this->markTestSkipped('Module contract disabled');
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
		if (method_exists($reloaded, 'fetch_lines')) {
			$reloaded->fetch_lines();
		}
		$this->assertBothPricedLines($reloaded, 'Customer contract create');

		foreach ($this->pricedLines($reloaded) as $line) {
			$object->updateline($line->id, $line->desc, (float) $line->subprice_ttc, $line->qty, $line->remise_percent, $line->date_start, $line->date_end, $line->tva_tx, 0, 0, '', '', 'TTC');
		}
		$afterUpdate = new Contrat($db);
		$afterUpdate->fetch($id);
		if (method_exists($afterUpdate, 'fetch_lines')) {
			$afterUpdate->fetch_lines();
		}
		$this->assertBothPricedLines($afterUpdate, 'Customer contract no-op update');

		foreach ($this->pricedLines($afterUpdate) as $line) {
			$object->updateline($line->id, $line->desc, (float) $line->subprice_ttc, $line->qty, self::REMISE, $line->date_start, $line->date_end, $line->tva_tx, 0, 0, '', '', 'TTC');
		}
		$afterRemise = new Contrat($db);
		$afterRemise->fetch($id);
		if (method_exists($afterRemise, 'fetch_lines')) {
			$afterRemise->fetch_lines();
		}
		$this->assertBothPricedLines($afterRemise, 'Customer contract discount', true);
		print __METHOD__." id=".$id."\n";
	}

	/**
	 * Customer contract - line edit through ContratLigne::update() (the HT-only UI edit path, distinct
	 * from Contrat::updateline()). A no-op edit must keep the TTC total; changing the HT unit price
	 * drops the TTC entry mode.
	 *
	 * @return void
	 */
	public function testCustomerContractLineUpdateTtc()
	{
		global $user,$db;
		$this->restoreGlobals();
		if (!isModEnabled('contract')) {
			$this->markTestSkipped('Module contract disabled');
			return;
		}

		$object = new Contrat($db);
		$object->initAsSpecimen();
		$object->socid = self::$socid;
		$object->lines = array();
		$id = $object->create($user);
		$this->assertGreaterThan(0, $id, 'Contrat create');
		$object->addline('Product TTC', 0, self::QTY, self::VAT, 0, 0, self::$productid, 0, '', '', 'TTC', self::PU_TTC);

		$reloaded = new Contrat($db);
		$reloaded->fetch($id);
		if (method_exists($reloaded, 'fetch_lines')) {
			$reloaded->fetch_lines();
		}
		$lineid = $reloaded->lines[0]->id;

		// No-op edit (price unchanged): TTC total must be preserved, no rounding drift.
		$line = new ContratLigne($db);
		$line->fetch($lineid);
		$line->oldcopy = dol_clone($line, 2);
		$line->tva_tx = self::VAT;
		$line->update($user);

		$afterNoop = new ContratLigne($db);
		$afterNoop->fetch($lineid);
		$this->assertEquals(self::PU_TTC, (float) $afterNoop->subprice_ttc, 'Contrat line no-op: subprice_ttc kept');
		$this->assertEquals(self::LINE_HT, (float) $afterNoop->total_ht, 'Contrat line no-op: total_ht preserved (no drift)');

		// Edit that actually changes the HT unit price: TTC entry mode dropped, HT-based total.
		$newHt = 2.50;
		$line2 = new ContratLigne($db);
		$line2->fetch($lineid);
		$line2->oldcopy = dol_clone($line2, 2);
		$line2->subprice = $newHt;
		// Mirror contrat/card.php: drop the TTC mode when the HT unit price actually changed.
		if ((float) $line2->subprice != (float) $line2->oldcopy->subprice) {
			$line2->subprice_ttc = 0;
		}
		$line2->tva_tx = self::VAT;
		$line2->update($user);

		$afterHt = new ContratLigne($db);
		$afterHt->fetch($lineid);
		$this->assertEquals(0, (float) $afterHt->subprice_ttc, 'Contrat line HT edit: subprice_ttc reset');
		$this->assertEquals((float) price2num($newHt * self::QTY, 'MT'), (float) $afterHt->total_ht, 'Contrat line HT edit: HT-based total');
		print __METHOD__." id=".$id."\n";
	}

	/**
	 * Customer contract - clone must preserve the TTC entry mode (subprice_ttc).
	 *
	 * @return void
	 */
	public function testCustomerContractCloneTtc()
	{
		global $user,$db;
		$this->restoreGlobals();
		if (!isModEnabled('contract')) {
			$this->markTestSkipped('Module contract disabled');
			return;
		}

		$socid = self::$socid;
		$pid = self::$productid;

		$object = new Contrat($db);
		$object->initAsSpecimen();
		$object->socid = $socid;
		$object->ref = 'TTCCLONE'.substr(uniqid(), -8);
		$object->lines = array();
		$id = $object->create($user);
		$this->assertGreaterThan(0, $id, 'Customer contract clone create source');

		$object->addline('Product TTC', 0, self::QTY, self::VAT, 0, 0, $pid, 0, '', '', 'TTC', self::PU_TTC);
		$object->addline('Free TTC', 0, self::QTY, self::VAT, 0, 0, 0, 0, '', '', 'TTC', self::PU_TTC);

		$source = new Contrat($db);
		$source->fetch($id);
		$clonedId = $source->createFromClone($user, $socid);
		$this->assertGreaterThan(0, $clonedId, 'Customer contract createFromClone');

		$clone = new Contrat($db);
		$clone->fetch($clonedId);
		if (method_exists($clone, 'fetch_lines')) {
			$clone->fetch_lines();
		}
		$this->assertBothPricedLines($clone, 'Customer contract clone');
		print __METHOD__." id=".$id." clone=".$clonedId."\n";
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
		if (method_exists($reloaded, 'fetch_lines')) {
			$reloaded->fetch_lines();
		}
		$this->assertSubtotalLinePresent($reloaded, 'Supplier proposal');
		$this->assertBothPricedLines($reloaded, 'Supplier proposal create');

		foreach ($this->pricedLines($reloaded) as $line) {
			$object->updateline($line->id, (float) $line->subprice_ttc, $line->qty, $line->remise_percent, $line->tva_tx, 0, 0, $line->desc, 'TTC');
		}
		$afterUpdate = new SupplierProposal($db);
		$afterUpdate->fetch($id);
		if (method_exists($afterUpdate, 'fetch_lines')) {
			$afterUpdate->fetch_lines();
		}
		$this->assertBothPricedLines($afterUpdate, 'Supplier proposal no-op update');

		foreach ($this->pricedLines($afterUpdate) as $line) {
			$object->updateline($line->id, (float) $line->subprice_ttc, $line->qty, self::REMISE, $line->tva_tx, 0, 0, $line->desc, 'TTC');
		}
		$afterRemise = new SupplierProposal($db);
		$afterRemise->fetch($id);
		if (method_exists($afterRemise, 'fetch_lines')) {
			$afterRemise->fetch_lines();
		}
		$this->assertBothPricedLines($afterRemise, 'Supplier proposal discount', true);
		print __METHOD__." id=".$id."\n";
	}

	/**
	 * Supplier proposal - clone must preserve the TTC entry mode (subprice_ttc).
	 *
	 * @return void
	 */
	public function testSupplierProposalCloneTtc()
	{
		global $user,$db;
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
		$object->ref = 'TTCCLONE'.substr(uniqid(), -8);
		$object->lines = array();
		$id = $object->create($user);
		$this->assertGreaterThan(0, $id, 'Supplier proposal clone create source');

		$object->addline('Product TTC', 0, self::QTY, self::VAT, 0, 0, $pid, 0, 'TTC', self::PU_TTC);
		$object->addline('Free TTC', 0, self::QTY, self::VAT, 0, 0, 0, 0, 'TTC', self::PU_TTC);

		$source = new SupplierProposal($db);
		$source->fetch($id);
		$clonedId = $source->createFromClone($user, $socid);
		$this->assertGreaterThan(0, $clonedId, 'Supplier proposal createFromClone');

		$clone = new SupplierProposal($db);
		$clone->fetch($clonedId);
		if (method_exists($clone, 'fetch_lines')) {
			$clone->fetch_lines();
		}
		$this->assertBothPricedLines($clone, 'Supplier proposal clone');
		print __METHOD__." id=".$id." clone=".$clonedId."\n";
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
		if (method_exists($reloaded, 'fetch_lines')) {
			$reloaded->fetch_lines();
		}
		$this->assertSubtotalLinePresent($reloaded, 'Supplier order');
		$this->assertBothPricedLines($reloaded, 'Supplier order create');

		foreach ($this->pricedLines($reloaded) as $line) {
			$object->updateline($line->id, $line->desc, (float) $line->subprice_ttc, $line->qty, $line->remise_percent, $line->tva_tx, 0, 0, 'TTC');
		}
		$afterUpdate = new CommandeFournisseur($db);
		$afterUpdate->fetch($id);
		if (method_exists($afterUpdate, 'fetch_lines')) {
			$afterUpdate->fetch_lines();
		}
		$this->assertBothPricedLines($afterUpdate, 'Supplier order no-op update');

		foreach ($this->pricedLines($afterUpdate) as $line) {
			$object->updateline($line->id, $line->desc, (float) $line->subprice_ttc, $line->qty, self::REMISE, $line->tva_tx, 0, 0, 'TTC');
		}
		$afterRemise = new CommandeFournisseur($db);
		$afterRemise->fetch($id);
		if (method_exists($afterRemise, 'fetch_lines')) {
			$afterRemise->fetch_lines();
		}
		$this->assertBothPricedLines($afterRemise, 'Supplier order discount', true);
		print __METHOD__." id=".$id."\n";
	}

	/**
	 * Supplier order - clone must preserve the TTC entry mode (subprice_ttc).
	 *
	 * @return void
	 */
	public function testSupplierOrderCloneTtc()
	{
		global $user,$db;
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
		$this->assertGreaterThan(0, $id, 'Supplier order clone create source');

		$object->addline('Product TTC', 0, self::QTY, self::VAT, 0, 0, $pid, 0, '', 0, 'TTC', self::PU_TTC);
		$object->addline('Free TTC', 0, self::QTY, self::VAT, 0, 0, 0, 0, '', 0, 'TTC', self::PU_TTC);

		$source = new CommandeFournisseur($db);
		$source->fetch($id);
		$clonedId = $source->createFromClone($user, $socid);
		$this->assertGreaterThan(0, $clonedId, 'Supplier order createFromClone');

		$clone = new CommandeFournisseur($db);
		$clone->fetch($clonedId);
		if (method_exists($clone, 'fetch_lines')) {
			$clone->fetch_lines();
		}
		$this->assertBothPricedLines($clone, 'Supplier order clone');
		print __METHOD__." id=".$id." clone=".$clonedId."\n";
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
		if (method_exists($reloaded, 'fetch_lines')) {
			$reloaded->fetch_lines();
		}
		$this->assertSubtotalLinePresent($reloaded, 'Supplier invoice');
		$this->assertBothPricedLines($reloaded, 'Supplier invoice create');

		foreach ($this->pricedLines($reloaded) as $line) {
			$object->updateline($line->id, $line->desc, (float) $line->subprice_ttc, $line->tva_tx, 0, 0, $line->qty, 0, 'TTC', 0, 0, $line->remise_percent);
		}
		$afterUpdate = new FactureFournisseur($db);
		$afterUpdate->fetch($id);
		if (method_exists($afterUpdate, 'fetch_lines')) {
			$afterUpdate->fetch_lines();
		}
		$this->assertBothPricedLines($afterUpdate, 'Supplier invoice no-op update');

		foreach ($this->pricedLines($afterUpdate) as $line) {
			$object->updateline($line->id, $line->desc, (float) $line->subprice_ttc, $line->tva_tx, 0, 0, $line->qty, 0, 'TTC', 0, 0, self::REMISE);
		}
		$afterRemise = new FactureFournisseur($db);
		$afterRemise->fetch($id);
		if (method_exists($afterRemise, 'fetch_lines')) {
			$afterRemise->fetch_lines();
		}
		$this->assertBothPricedLines($afterRemise, 'Supplier invoice discount', true);
		print __METHOD__." id=".$id."\n";
	}

	/**
	 * Supplier invoice - clone must preserve the TTC entry mode (subprice_ttc).
	 *
	 * @return void
	 */
	public function testSupplierInvoiceCloneTtc()
	{
		global $user,$db;
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
		$object->ref_supplier = 'TTCCLONE'.substr(uniqid(), -8);
		$object->lines = array();
		$id = $object->create($user);
		$this->assertGreaterThan(0, $id, 'Supplier invoice clone create source');

		$object->addline('Product TTC', self::PU_TTC, self::VAT, 0, 0, self::QTY, $pid, 0, 0, 0, 0, 0, 'TTC');
		$object->addline('Free TTC', self::PU_TTC, self::VAT, 0, 0, self::QTY, 0, 0, 0, 0, 0, 0, 'TTC');

		// createFromClone() takes the new supplier ref from $this (fallback "CopyOf <ref>"): call it on a
		// fresh object so the cloned supplier invoice gets a unique ref_supplier.
		$cloner = new FactureFournisseur($db);
		$clonedId = $cloner->createFromClone($user, $id);
		$this->assertGreaterThan(0, $clonedId, 'Supplier invoice createFromClone');

		$clone = new FactureFournisseur($db);
		$clone->fetch($clonedId);
		if (method_exists($clone, 'fetch_lines')) {
			$clone->fetch_lines();
		}
		$this->assertBothPricedLines($clone, 'Supplier invoice clone');
		print __METHOD__." id=".$id." clone=".$clonedId."\n";
	}

	/**
	 * Customer proposal -> order (Commande::createFromProposal) must preserve the TTC entry mode.
	 *
	 * @return void
	 */
	public function testCustomerProposalToOrderTtc()
	{
		global $user,$db;
		$this->restoreGlobals();
		if (!isModEnabled('propal') || !isModEnabled('order')) {
			$this->markTestSkipped('Module propal or order disabled');
			return;
		}

		$socid = self::$socid;
		$pid = self::$productid;

		$object = new Propal($db);
		$object->initAsSpecimen();
		$object->socid = $socid;
		$object->ref = 'TTCORIG'.substr(uniqid(), -8);
		$object->lines = array();
		$id = $object->create($user);
		$this->assertGreaterThan(0, $id, 'Proposal create for origin');

		$object->addline('Product TTC', 0, self::QTY, self::VAT, 0, 0, $pid, 0, 'TTC', self::PU_TTC);
		$object->addline('Free TTC', 0, self::QTY, self::VAT, 0, 0, 0, 0, 'TTC', self::PU_TTC);

		$source = new Propal($db);
		$source->fetch($id);
		if (method_exists($source, 'fetch_lines')) {
			$source->fetch_lines();
		}

		$order = new Commande($db);
		$orderId = $order->createFromProposal($source, $user);
		$this->assertGreaterThan(0, $orderId, 'createFromProposal');

		$reloaded = new Commande($db);
		$reloaded->fetch($orderId);
		if (method_exists($reloaded, 'fetch_lines')) {
			$reloaded->fetch_lines();
		}
		$this->assertBothPricedLines($reloaded, 'Proposal to order');
		print __METHOD__." propal=".$id." order=".$orderId."\n";
	}

	/**
	 * Customer order -> invoice (Facture::createFromOrder) must preserve the TTC entry mode.
	 *
	 * @return void
	 */
	public function testCustomerOrderToInvoiceTtc()
	{
		global $user,$db;
		$this->restoreGlobals();
		if (!isModEnabled('order') || !isModEnabled('invoice')) {
			$this->markTestSkipped('Module order or invoice disabled');
			return;
		}

		$socid = self::$socid;
		$pid = self::$productid;

		$object = new Commande($db);
		$object->initAsSpecimen();
		$object->socid = $socid;
		$object->ref = 'TTCORIG'.substr(uniqid(), -8);
		$object->lines = array();
		$id = $object->create($user);
		$this->assertGreaterThan(0, $id, 'Order create for origin');

		$object->addline('Product TTC', 0, self::QTY, self::VAT, 0, 0, $pid, 0, 0, 0, 'TTC', self::PU_TTC);
		$object->addline('Free TTC', 0, self::QTY, self::VAT, 0, 0, 0, 0, 0, 0, 'TTC', self::PU_TTC);

		$source = new Commande($db);
		$source->fetch($id);
		if (method_exists($source, 'fetch_lines')) {
			$source->fetch_lines();
		}

		// createFromOrder() returns 1 on success; the new invoice id is in $invoice->id.
		$invoice = new Facture($db);
		$this->assertGreaterThan(0, $invoice->createFromOrder($source, $user), 'createFromOrder');
		$invoiceId = $invoice->id;

		$reloaded = new Facture($db);
		$reloaded->fetch($invoiceId);
		if (method_exists($reloaded, 'fetch_lines')) {
			$reloaded->fetch_lines();
		}
		$this->assertBothPricedLines($reloaded, 'Order to invoice');
		print __METHOD__." order=".$id." invoice=".$invoiceId."\n";
	}

	/**
	 * Customer contract -> invoice (Facture::createFromContract) must preserve the TTC entry mode.
	 *
	 * @return void
	 */
	public function testCustomerContractToInvoiceTtc()
	{
		global $user,$db;
		$this->restoreGlobals();
		if (!isModEnabled('contract') || !isModEnabled('invoice')) {
			$this->markTestSkipped('Module contract or invoice disabled');
			return;
		}

		$socid = self::$socid;
		$pid = self::$productid;

		$object = new Contrat($db);
		$object->initAsSpecimen();
		$object->socid = $socid;
		$object->ref = 'TTCORIG'.substr(uniqid(), -8);
		$object->lines = array();
		$id = $object->create($user);
		$this->assertGreaterThan(0, $id, 'Contract create for origin');

		$object->addline('Product TTC', 0, self::QTY, self::VAT, 0, 0, $pid, 0, '', '', 'TTC', self::PU_TTC);
		$object->addline('Free TTC', 0, self::QTY, self::VAT, 0, 0, 0, 0, '', '', 'TTC', self::PU_TTC);

		$source = new Contrat($db);
		$source->fetch($id);
		if (method_exists($source, 'fetch_lines')) {
			$source->fetch_lines();
		}

		// createFromContract() returns 1 on success; the new invoice id is in $invoice->id.
		$invoice = new Facture($db);
		$this->assertGreaterThan(0, $invoice->createFromContract($source, $user), 'createFromContract');
		$invoiceId = $invoice->id;

		$reloaded = new Facture($db);
		$reloaded->fetch($invoiceId);
		if (method_exists($reloaded, 'fetch_lines')) {
			$reloaded->fetch_lines();
		}
		$this->assertBothPricedLines($reloaded, 'Contract to invoice');
		print __METHOD__." contract=".$id." invoice=".$invoiceId."\n";
	}

	/**
	 * Customer invoice -> credit note (Facture::createFromCurrent with invertdetail) must keep the TTC
	 * entry mode with the inverted sign.
	 *
	 * @return void
	 */
	public function testCustomerInvoiceToCreditNoteTtc()
	{
		global $user,$db;
		$this->restoreGlobals();
		if (!isModEnabled('invoice')) {
			$this->markTestSkipped('Module invoice disabled');
			return;
		}

		$socid = self::$socid;
		$pid = self::$productid;

		$object = new Facture($db);
		$object->initAsSpecimen();
		$object->socid = $socid;
		$object->ref = 'TTCORIG'.substr(uniqid(), -8);
		$object->lines = array();
		$id = $object->create($user);
		$this->assertGreaterThan(0, $id, 'Invoice create for credit note');

		$object->addline('Product TTC', 0, self::QTY, self::VAT, 0, 0, $pid, 0, '', '', 0, 0, 0, 'TTC', self::PU_TTC);
		$object->addline('Free TTC', 0, self::QTY, self::VAT, 0, 0, 0, 0, '', '', 0, 0, 0, 'TTC', self::PU_TTC);

		$source = new Facture($db);
		$source->fetch($id);
		if (method_exists($source, 'fetch_lines')) {
			$source->fetch_lines();
		}

		// Credit note = invoice with all amounts inverted (invertdetail = 1).
		$creditId = $source->createFromCurrent($user, 1);
		$this->assertGreaterThan(0, $creditId, 'createFromCurrent invertdetail');

		$credit = new Facture($db);
		$credit->fetch($creditId);
		if (method_exists($credit, 'fetch_lines')) {
			$credit->fetch_lines();
		}
		$this->assertBothPricedLinesInverted($credit, 'Invoice to credit note');
		print __METHOD__." invoice=".$id." creditnote=".$creditId."\n";
	}

	/**
	 * Supplier order - a line entered in HT must keep subprice_ttc = 0 (not marked TTC) on create and on
	 * clone, so it is never hijacked by the TTC preservation. Guards the supplier socle gating.
	 *
	 * @return void
	 */
	public function testSupplierOrderHtLineClone()
	{
		global $user,$db;
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
		$object->ref = 'TTCHT'.substr(uniqid(), -8);
		$object->lines = array();
		$id = $object->create($user);
		$this->assertGreaterThan(0, $id, 'Supplier order HT create source');

		$object->addline('Product HT', self::PU_HT, self::QTY, self::VAT, 0, 0, $pid, 0, '', 0, 'HT', 0);
		$object->addline('Free HT', self::PU_HT, self::QTY, self::VAT, 0, 0, 0, 0, '', 0, 'HT', 0);

		$reloaded = new CommandeFournisseur($db);
		$reloaded->fetch($id);
		if (method_exists($reloaded, 'fetch_lines')) {
			$reloaded->fetch_lines();
		}
		$this->assertBothHtLines($reloaded, 'Supplier order HT create');

		$source = new CommandeFournisseur($db);
		$source->fetch($id);
		$clonedId = $source->createFromClone($user, $socid);
		$this->assertGreaterThan(0, $clonedId, 'Supplier order HT createFromClone');

		$clone = new CommandeFournisseur($db);
		$clone->fetch($clonedId);
		if (method_exists($clone, 'fetch_lines')) {
			$clone->fetch_lines();
		}
		$this->assertBothHtLines($clone, 'Supplier order HT clone');
		print __METHOD__." id=".$id." clone=".$clonedId."\n";
	}

	/**
	 * Supplier invoice - a line entered in HT must keep subprice_ttc = 0 on create (via
	 * SupplierInvoiceLine::insert) and on clone. Guards the supplier invoice socle gating.
	 *
	 * @return void
	 */
	public function testSupplierInvoiceHtLineClone()
	{
		global $user,$db;
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
		$object->ref_supplier = 'TTCHT'.substr(uniqid(), -8);
		$object->lines = array();
		$id = $object->create($user);
		$this->assertGreaterThan(0, $id, 'Supplier invoice HT create source');

		$object->addline('Product HT', self::PU_HT, self::VAT, 0, 0, self::QTY, $pid, 0, 0, 0, 0, 0, 'HT');
		$object->addline('Free HT', self::PU_HT, self::VAT, 0, 0, self::QTY, 0, 0, 0, 0, 0, 0, 'HT');

		$reloaded = new FactureFournisseur($db);
		$reloaded->fetch($id);
		if (method_exists($reloaded, 'fetch_lines')) {
			$reloaded->fetch_lines();
		}
		$this->assertBothHtLines($reloaded, 'Supplier invoice HT create');

		$cloner = new FactureFournisseur($db);
		$clonedId = $cloner->createFromClone($user, $id);
		$this->assertGreaterThan(0, $clonedId, 'Supplier invoice HT createFromClone');

		$clone = new FactureFournisseur($db);
		$clone->fetch($clonedId);
		if (method_exists($clone, 'fetch_lines')) {
			$clone->fetch_lines();
		}
		$this->assertBothHtLines($clone, 'Supplier invoice HT clone');
		print __METHOD__." id=".$id." clone=".$clonedId."\n";
	}

	/**
	 * Customer proposal - with MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND = 1 (rounding of total), a mixed order
	 * (2 lines entered HT + 2 lines entered TTC, like customer proposal id=205) must survive create /
	 * no-op / clone with no drift, and a discount keeps each entry mode.
	 *
	 * @return void
	 */
	public function testCustomerProposalRoundOfTotalStable()
	{
		global $conf,$user,$db;
		$this->restoreGlobals();
		if (!isModEnabled('propal')) {
			$this->markTestSkipped('Module propal disabled');
			return;
		}
		$conf->global->MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND = 1;

		$socid = self::$socid;
		$pid = self::$productid;

		$object = new Propal($db);
		$object->initAsSpecimen();
		$object->socid = $socid;
		$object->ref = 'TTCROT'.substr(uniqid(), -8);
		$object->lines = array();
		$id = $object->create($user);
		$this->assertGreaterThan(0, $id, 'Customer proposal ROT create');
		$object->addline('Product HT', self::PU_HT, self::QTY, self::VAT, 0, 0, $pid, 0, 'HT', 0);
		$object->addline('Free HT', self::PU_HT, self::QTY, self::VAT, 0, 0, 0, 0, 'HT', 0);
		$object->addline('Product TTC', 0, self::QTY, self::VAT, 0, 0, $pid, 0, 'TTC', self::PU_TTC);
		$object->addline('Free TTC', 0, self::QTY, self::VAT, 0, 0, 0, 0, 'TTC', self::PU_TTC);

		$reloaded = new Propal($db);
		$reloaded->fetch($id);
		$reloaded->fetch_lines();
		$this->assertMixedSubpriceTtc($reloaded, 'Customer proposal ROT create');
		$s0 = $this->snapshotPricedLines($reloaded);

		foreach ($this->pricedLines($reloaded) as $line) {
			$ttc = $line->wasEnteredIncludingTax();
			$pu = $ttc ? (float) $line->subprice_ttc : (float) $line->subprice;
			$object->updateline($line->id, $pu, $line->qty, $line->remise_percent, $line->tva_tx, 0, 0, $line->desc, $ttc ? 'TTC' : 'HT');
		}
		$afterNoop = new Propal($db);
		$afterNoop->fetch($id);
		$afterNoop->fetch_lines();
		$this->assertLinesUnchanged($s0, $this->snapshotPricedLines($afterNoop), 'Customer proposal ROT no-op');

		$source = new Propal($db);
		$source->fetch($id);
		$clonedId = $source->createFromClone($user, $socid);
		$this->assertGreaterThan(0, $clonedId, 'Customer proposal ROT clone');
		$clone = new Propal($db);
		$clone->fetch($clonedId);
		$clone->fetch_lines();
		$this->assertLinesUnchanged($s0, $this->snapshotPricedLines($clone), 'Customer proposal ROT clone');

		foreach ($this->pricedLines($afterNoop) as $line) {
			$ttc = $line->wasEnteredIncludingTax();
			$pu = $ttc ? (float) $line->subprice_ttc : (float) $line->subprice;
			$object->updateline($line->id, $pu, $line->qty, self::REMISE, $line->tva_tx, 0, 0, $line->desc, $ttc ? 'TTC' : 'HT');
		}
		$afterRemise = new Propal($db);
		$afterRemise->fetch($id);
		$afterRemise->fetch_lines();
		$this->assertMixedSubpriceTtc($afterRemise, 'Customer proposal ROT discount');
		print __METHOD__." id=".$id." clone=".$clonedId."\n";
	}

	/**
	 * Customer order - mixed HT/TTC order, MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND = 1.
	 *
	 * @return void
	 */
	public function testCustomerOrderRoundOfTotalStable()
	{
		global $conf,$user,$db;
		$this->restoreGlobals();
		if (!isModEnabled('order')) {
			$this->markTestSkipped('Module order disabled');
			return;
		}
		$conf->global->MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND = 1;

		$socid = self::$socid;
		$pid = self::$productid;

		$object = new Commande($db);
		$object->initAsSpecimen();
		$object->socid = $socid;
		$object->ref = 'TTCROT'.substr(uniqid(), -8);
		$object->lines = array();
		$id = $object->create($user);
		$this->assertGreaterThan(0, $id, 'Customer order ROT create');
		$object->addline('Product HT', self::PU_HT, self::QTY, self::VAT, 0, 0, $pid, 0, 0, 0, 'HT', 0);
		$object->addline('Free HT', self::PU_HT, self::QTY, self::VAT, 0, 0, 0, 0, 0, 0, 'HT', 0);
		$object->addline('Product TTC', 0, self::QTY, self::VAT, 0, 0, $pid, 0, 0, 0, 'TTC', self::PU_TTC);
		$object->addline('Free TTC', 0, self::QTY, self::VAT, 0, 0, 0, 0, 0, 0, 'TTC', self::PU_TTC);

		$reloaded = new Commande($db);
		$reloaded->fetch($id);
		$reloaded->fetch_lines();
		$this->assertMixedSubpriceTtc($reloaded, 'Customer order ROT create');
		$s0 = $this->snapshotPricedLines($reloaded);

		foreach ($this->pricedLines($reloaded) as $line) {
			$ttc = $line->wasEnteredIncludingTax();
			$pu = $ttc ? (float) $line->subprice_ttc : (float) $line->subprice;
			$object->updateline($line->id, $line->desc, $pu, $line->qty, $line->remise_percent, $line->tva_tx, 0, 0, $ttc ? 'TTC' : 'HT');
		}
		$afterNoop = new Commande($db);
		$afterNoop->fetch($id);
		$afterNoop->fetch_lines();
		$this->assertLinesUnchanged($s0, $this->snapshotPricedLines($afterNoop), 'Customer order ROT no-op');

		$source = new Commande($db);
		$source->fetch($id);
		$clonedId = $source->createFromClone($user, $socid);
		$this->assertGreaterThan(0, $clonedId, 'Customer order ROT clone');
		$clone = new Commande($db);
		$clone->fetch($clonedId);
		$clone->fetch_lines();
		$this->assertLinesUnchanged($s0, $this->snapshotPricedLines($clone), 'Customer order ROT clone');

		foreach ($this->pricedLines($afterNoop) as $line) {
			$ttc = $line->wasEnteredIncludingTax();
			$pu = $ttc ? (float) $line->subprice_ttc : (float) $line->subprice;
			$object->updateline($line->id, $line->desc, $pu, $line->qty, self::REMISE, $line->tva_tx, 0, 0, $ttc ? 'TTC' : 'HT');
		}
		$afterRemise = new Commande($db);
		$afterRemise->fetch($id);
		$afterRemise->fetch_lines();
		$this->assertMixedSubpriceTtc($afterRemise, 'Customer order ROT discount');
		print __METHOD__." id=".$id." clone=".$clonedId."\n";
	}

	/**
	 * Customer invoice - mixed HT/TTC order, MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND = 1.
	 *
	 * @return void
	 */
	public function testCustomerInvoiceRoundOfTotalStable()
	{
		global $conf,$user,$db;
		$this->restoreGlobals();
		if (!isModEnabled('invoice')) {
			$this->markTestSkipped('Module invoice disabled');
			return;
		}
		$conf->global->MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND = 1;

		$socid = self::$socid;
		$pid = self::$productid;

		$object = new Facture($db);
		$object->initAsSpecimen();
		$object->socid = $socid;
		$object->ref = 'TTCROT'.substr(uniqid(), -8);
		$object->lines = array();
		$id = $object->create($user);
		$this->assertGreaterThan(0, $id, 'Customer invoice ROT create');
		$object->addline('Product HT', self::PU_HT, self::QTY, self::VAT, 0, 0, $pid, 0, '', '', 0, 0, 0, 'HT', 0);
		$object->addline('Free HT', self::PU_HT, self::QTY, self::VAT, 0, 0, 0, 0, '', '', 0, 0, 0, 'HT', 0);
		$object->addline('Product TTC', 0, self::QTY, self::VAT, 0, 0, $pid, 0, '', '', 0, 0, 0, 'TTC', self::PU_TTC);
		$object->addline('Free TTC', 0, self::QTY, self::VAT, 0, 0, 0, 0, '', '', 0, 0, 0, 'TTC', self::PU_TTC);

		$reloaded = new Facture($db);
		$reloaded->fetch($id);
		$reloaded->fetch_lines();
		$this->assertMixedSubpriceTtc($reloaded, 'Customer invoice ROT create');
		$s0 = $this->snapshotPricedLines($reloaded);

		foreach ($this->pricedLines($reloaded) as $line) {
			$ttc = $line->wasEnteredIncludingTax();
			$pu = $ttc ? (float) $line->subprice_ttc : (float) $line->subprice;
			$object->updateline($line->id, $line->desc, $pu, $line->qty, $line->remise_percent, $line->date_start, $line->date_end, $line->tva_tx, 0, 0, $ttc ? 'TTC' : 'HT');
		}
		$afterNoop = new Facture($db);
		$afterNoop->fetch($id);
		$afterNoop->fetch_lines();
		$this->assertLinesUnchanged($s0, $this->snapshotPricedLines($afterNoop), 'Customer invoice ROT no-op');

		$source = new Facture($db);
		$source->fetch($id);
		$clonedId = $source->createFromClone($user, $id);
		$this->assertGreaterThan(0, $clonedId, 'Customer invoice ROT clone');
		$clone = new Facture($db);
		$clone->fetch($clonedId);
		$clone->fetch_lines();
		$this->assertLinesUnchanged($s0, $this->snapshotPricedLines($clone), 'Customer invoice ROT clone');

		foreach ($this->pricedLines($afterNoop) as $line) {
			$ttc = $line->wasEnteredIncludingTax();
			$pu = $ttc ? (float) $line->subprice_ttc : (float) $line->subprice;
			$object->updateline($line->id, $line->desc, $pu, $line->qty, self::REMISE, $line->date_start, $line->date_end, $line->tva_tx, 0, 0, $ttc ? 'TTC' : 'HT');
		}
		$afterRemise = new Facture($db);
		$afterRemise->fetch($id);
		$afterRemise->fetch_lines();
		$this->assertMixedSubpriceTtc($afterRemise, 'Customer invoice ROT discount');
		print __METHOD__." id=".$id." clone=".$clonedId."\n";
	}

	/**
	 * Supplier proposal - mixed HT/TTC order, MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND_SUPPLIER = 1.
	 *
	 * @return void
	 */
	public function testSupplierProposalRoundOfTotalStable()
	{
		global $conf,$user,$db;
		$this->restoreGlobals();
		if (!isModEnabled('supplier_proposal')) {
			$this->markTestSkipped('Module supplier_proposal disabled');
			return;
		}
		$conf->global->MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND_SUPPLIER = 1;

		$socid = self::$socid;
		$pid = self::$productid;

		$object = new SupplierProposal($db);
		$object->initAsSpecimen();
		$object->socid = $socid;
		$object->ref = 'TTCROT'.substr(uniqid(), -8);
		$object->lines = array();
		$id = $object->create($user);
		$this->assertGreaterThan(0, $id, 'Supplier proposal ROT create');
		$object->addline('Product HT', self::PU_HT, self::QTY, self::VAT, 0, 0, $pid, 0, 'HT', 0);
		$object->addline('Free HT', self::PU_HT, self::QTY, self::VAT, 0, 0, 0, 0, 'HT', 0);
		$object->addline('Product TTC', 0, self::QTY, self::VAT, 0, 0, $pid, 0, 'TTC', self::PU_TTC);
		$object->addline('Free TTC', 0, self::QTY, self::VAT, 0, 0, 0, 0, 'TTC', self::PU_TTC);

		$reloaded = new SupplierProposal($db);
		$reloaded->fetch($id);
		if (method_exists($reloaded, 'fetch_lines')) {
			$reloaded->fetch_lines();
		}
		$this->assertMixedSubpriceTtc($reloaded, 'Supplier proposal ROT create');
		$s0 = $this->snapshotPricedLines($reloaded);

		foreach ($this->pricedLines($reloaded) as $line) {
			$ttc = $line->wasEnteredIncludingTax();
			$pu = $ttc ? (float) $line->subprice_ttc : (float) $line->subprice;
			$object->updateline($line->id, $pu, $line->qty, $line->remise_percent, $line->tva_tx, 0, 0, $line->desc, $ttc ? 'TTC' : 'HT');
		}
		$afterNoop = new SupplierProposal($db);
		$afterNoop->fetch($id);
		if (method_exists($afterNoop, 'fetch_lines')) {
			$afterNoop->fetch_lines();
		}
		$this->assertLinesUnchanged($s0, $this->snapshotPricedLines($afterNoop), 'Supplier proposal ROT no-op');

		$source = new SupplierProposal($db);
		$source->fetch($id);
		$clonedId = $source->createFromClone($user, $socid);
		$this->assertGreaterThan(0, $clonedId, 'Supplier proposal ROT clone');
		$clone = new SupplierProposal($db);
		$clone->fetch($clonedId);
		if (method_exists($clone, 'fetch_lines')) {
			$clone->fetch_lines();
		}
		$this->assertLinesUnchanged($s0, $this->snapshotPricedLines($clone), 'Supplier proposal ROT clone');

		foreach ($this->pricedLines($afterNoop) as $line) {
			$ttc = $line->wasEnteredIncludingTax();
			$pu = $ttc ? (float) $line->subprice_ttc : (float) $line->subprice;
			$object->updateline($line->id, $pu, $line->qty, self::REMISE, $line->tva_tx, 0, 0, $line->desc, $ttc ? 'TTC' : 'HT');
		}
		$afterRemise = new SupplierProposal($db);
		$afterRemise->fetch($id);
		if (method_exists($afterRemise, 'fetch_lines')) {
			$afterRemise->fetch_lines();
		}
		$this->assertMixedSubpriceTtc($afterRemise, 'Supplier proposal ROT discount');
		print __METHOD__." id=".$id." clone=".$clonedId."\n";
	}

	/**
	 * Supplier order - mixed HT/TTC order, MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND_SUPPLIER = 1.
	 *
	 * @return void
	 */
	public function testSupplierOrderRoundOfTotalStable()
	{
		global $conf,$user,$db;
		$this->restoreGlobals();
		if (!isModEnabled('fournisseur') && !isModEnabled('supplier_order')) {
			$this->markTestSkipped('Module supplier order disabled');
			return;
		}
		$conf->global->MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND_SUPPLIER = 1;

		$socid = self::$socid;
		$pid = self::$productid;

		$object = new CommandeFournisseur($db);
		$object->initAsSpecimen();
		$object->socid = $socid;
		$object->ref = 'TTCROT'.substr(uniqid(), -8);
		$object->lines = array();
		$id = $object->create($user);
		$this->assertGreaterThan(0, $id, 'Supplier order ROT create');
		$object->addline('Product HT', self::PU_HT, self::QTY, self::VAT, 0, 0, $pid, 0, '', 0, 'HT', 0);
		$object->addline('Free HT', self::PU_HT, self::QTY, self::VAT, 0, 0, 0, 0, '', 0, 'HT', 0);
		$object->addline('Product TTC', 0, self::QTY, self::VAT, 0, 0, $pid, 0, '', 0, 'TTC', self::PU_TTC);
		$object->addline('Free TTC', 0, self::QTY, self::VAT, 0, 0, 0, 0, '', 0, 'TTC', self::PU_TTC);

		$reloaded = new CommandeFournisseur($db);
		$reloaded->fetch($id);
		$reloaded->fetch_lines();
		$this->assertMixedSubpriceTtc($reloaded, 'Supplier order ROT create');
		$s0 = $this->snapshotPricedLines($reloaded);

		foreach ($this->pricedLines($reloaded) as $line) {
			$ttc = $line->wasEnteredIncludingTax();
			$pu = $ttc ? (float) $line->subprice_ttc : (float) $line->subprice;
			$object->updateline($line->id, $line->desc, $pu, $line->qty, $line->remise_percent, $line->tva_tx, 0, 0, $ttc ? 'TTC' : 'HT');
		}
		$afterNoop = new CommandeFournisseur($db);
		$afterNoop->fetch($id);
		$afterNoop->fetch_lines();
		$this->assertLinesUnchanged($s0, $this->snapshotPricedLines($afterNoop), 'Supplier order ROT no-op');

		$source = new CommandeFournisseur($db);
		$source->fetch($id);
		$clonedId = $source->createFromClone($user, $socid);
		$this->assertGreaterThan(0, $clonedId, 'Supplier order ROT clone');
		$clone = new CommandeFournisseur($db);
		$clone->fetch($clonedId);
		$clone->fetch_lines();
		$this->assertLinesUnchanged($s0, $this->snapshotPricedLines($clone), 'Supplier order ROT clone');

		foreach ($this->pricedLines($afterNoop) as $line) {
			$ttc = $line->wasEnteredIncludingTax();
			$pu = $ttc ? (float) $line->subprice_ttc : (float) $line->subprice;
			$object->updateline($line->id, $line->desc, $pu, $line->qty, self::REMISE, $line->tva_tx, 0, 0, $ttc ? 'TTC' : 'HT');
		}
		$afterRemise = new CommandeFournisseur($db);
		$afterRemise->fetch($id);
		$afterRemise->fetch_lines();
		$this->assertMixedSubpriceTtc($afterRemise, 'Supplier order ROT discount');
		print __METHOD__." id=".$id." clone=".$clonedId."\n";
	}

	/**
	 * Supplier invoice - mixed HT/TTC order, MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND_SUPPLIER = 1.
	 *
	 * @return void
	 */
	public function testSupplierInvoiceRoundOfTotalStable()
	{
		global $conf,$user,$db;
		$this->restoreGlobals();
		if (!isModEnabled('fournisseur') && !isModEnabled('supplier_invoice')) {
			$this->markTestSkipped('Module supplier invoice disabled');
			return;
		}
		$conf->global->MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND_SUPPLIER = 1;

		$socid = self::$socid;
		$pid = self::$productid;

		$object = new FactureFournisseur($db);
		$object->initAsSpecimen();
		$object->socid = $socid;
		$object->ref_supplier = 'TTCROT'.substr(uniqid(), -8);
		$object->lines = array();
		$id = $object->create($user);
		$this->assertGreaterThan(0, $id, 'Supplier invoice ROT create');
		$object->addline('Product HT', self::PU_HT, self::VAT, 0, 0, self::QTY, $pid, 0, 0, 0, 0, 0, 'HT');
		$object->addline('Free HT', self::PU_HT, self::VAT, 0, 0, self::QTY, 0, 0, 0, 0, 0, 0, 'HT');
		$object->addline('Product TTC', self::PU_TTC, self::VAT, 0, 0, self::QTY, $pid, 0, 0, 0, 0, 0, 'TTC');
		$object->addline('Free TTC', self::PU_TTC, self::VAT, 0, 0, self::QTY, 0, 0, 0, 0, 0, 0, 'TTC');

		$reloaded = new FactureFournisseur($db);
		$reloaded->fetch($id);
		$reloaded->fetch_lines();
		$this->assertMixedSubpriceTtc($reloaded, 'Supplier invoice ROT create');
		$s0 = $this->snapshotPricedLines($reloaded);

		foreach ($this->pricedLines($reloaded) as $line) {
			$ttc = $line->wasEnteredIncludingTax();
			$pu = $ttc ? (float) $line->subprice_ttc : (float) $line->subprice;
			$object->updateline($line->id, $line->desc, $pu, $line->tva_tx, 0, 0, $line->qty, 0, $ttc ? 'TTC' : 'HT', 0, 0, $line->remise_percent);
		}
		$afterNoop = new FactureFournisseur($db);
		$afterNoop->fetch($id);
		$afterNoop->fetch_lines();
		$this->assertLinesUnchanged($s0, $this->snapshotPricedLines($afterNoop), 'Supplier invoice ROT no-op');

		$cloner = new FactureFournisseur($db);
		$clonedId = $cloner->createFromClone($user, $id);
		$this->assertGreaterThan(0, $clonedId, 'Supplier invoice ROT clone');
		$clone = new FactureFournisseur($db);
		$clone->fetch($clonedId);
		$clone->fetch_lines();
		$this->assertLinesUnchanged($s0, $this->snapshotPricedLines($clone), 'Supplier invoice ROT clone');

		foreach ($this->pricedLines($afterNoop) as $line) {
			$ttc = $line->wasEnteredIncludingTax();
			$pu = $ttc ? (float) $line->subprice_ttc : (float) $line->subprice;
			$object->updateline($line->id, $line->desc, $pu, $line->tva_tx, 0, 0, $line->qty, 0, $ttc ? 'TTC' : 'HT', 0, 0, self::REMISE);
		}
		$afterRemise = new FactureFournisseur($db);
		$afterRemise->fetch($id);
		$afterRemise->fetch_lines();
		$this->assertMixedSubpriceTtc($afterRemise, 'Supplier invoice ROT discount');
		print __METHOD__." id=".$id." clone=".$clonedId."\n";
	}
}
