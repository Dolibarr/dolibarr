<?php
/* Copyright (C) 2026		Quentin Vial-Gouteyron	<quentin.vial-gouteyron@atm-consulting.fr>
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
 *      \file       test/phpunit/ProductPriceCurrencyTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test for ProductPriceCurrency and Product::getSellPriceInCurrency
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/product/class/product.class.php';
require_once dirname(__FILE__).'/../../htdocs/product/class/productpricecurrency.class.php';
require_once dirname(__FILE__).'/../../htdocs/societe/class/societe.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;

/**
 * Class for PHPUnit tests of per-currency sell prices
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 *
 * @phan-file-suppress PhanUndeclaredExtendedClass
 * @phan-file-suppress PhanUndeclaredMethod
 * @phan-file-suppress PhanTypeMismatchProperty
 */
class ProductPriceCurrencyTest extends CommonClassTest
{
	/**
	 * Create a throwaway product for the tests.
	 *
	 * @param	float	$vat_tx		VAT rate to set on the product
	 * @return	Product				Created product (id > 0)
	 */
	private function createTestProduct(float $vat_tx = 20.0): Product
	{
		global $db, $user;

		$product = new Product($db);
		$product->ref = 'PPCTEST'.dol_print_date(dol_now(), '%Y%m%d%H%M%S').mt_rand(1, 9999);
		$product->label = 'Product price currency test';
		$product->type = Product::TYPE_PRODUCT;
		$product->status = 1;
		$product->status_buy = 1;
		$product->price = 50;
		$product->price_base_type = 'HT';
		$product->tva_tx = $vat_tx;

		$id = $product->create($user);
		$this->assertGreaterThan(0, $id, 'Product creation failed: '.$product->error);

		return $product;
	}

	/**
	 * Create a throwaway customer for the tests.
	 *
	 * @return	int		Societe id (>0)
	 */
	private function createTestSoc(): int
	{
		global $db, $user;

		$societe = new Societe($db);
		$societe->name = 'PPC soc '.dol_print_date(dol_now(), '%Y%m%d%H%M%S').mt_rand(1, 9999);
		$societe->client = 1;
		$societe->code_client = -1;
		$societe->country_id = 1;
		$socid = $societe->create($user);
		$this->assertGreaterThan(0, $socid, 'Societe creation failed: '.$societe->error.' '.implode(',', $societe->errors));

		return $socid;
	}

	/**
	 * Resolver returns the fixed price for the matching currency, empty otherwise, with level fallback.
	 *
	 * @return void
	 */
	public function testGetSellPriceInCurrencyResolver()
	{
		global $db;

		$product = new Product($db);
		$product->multicurrency_prices = array(
			1 => array(
				'USD' => array('price' => 100.0, 'price_ttc' => 120.0, 'price_base_type' => 'HT', 'multicurrency_tx' => 1.1),
			),
			2 => array(
				'GBP' => array('price' => 80.0, 'price_ttc' => 96.0, 'price_base_type' => 'HT', 'multicurrency_tx' => 0.85),
			),
		);

		$resUsd = $product->getSellPriceInCurrency('USD', 1);
		$this->assertEquals(100.0, $resUsd['price'], 'USD level 1 fixed price expected');

		$resUnknown = $product->getSellPriceInCurrency('CHF', 1);
		$this->assertSame(array(), $resUnknown, 'Unknown currency must return empty array (fallback to rate)');

		// Level 3 has no USD price: fall back to level 1
		$resFallback = $product->getSellPriceInCurrency('USD', 3);
		$this->assertEquals(100.0, $resFallback['price'], 'Level fallback to 1 expected for USD');

		// Level 2 GBP must not leak into level 1 GBP lookup
		$resNoLevel1Gbp = $product->getSellPriceInCurrency('GBP', 1);
		$this->assertSame(array(), $resNoLevel1Gbp, 'GBP only exists on level 2, level 1 must be empty');
	}

	/**
	 * Setting a HT price computes the TTC counterpart and round-trips through fetch.
	 *
	 * @return void
	 */
	public function testSetPriceCurrencyHtComputesTtc()
	{
		global $db, $user;

		$product = $this->createTestProduct(20.0);

		$ppc = new ProductPriceCurrency($db);
		$res = $ppc->setPriceCurrency($product->id, 'USD', 100.0, 'HT', 20.0, $user, 1, 1.1);
		$this->assertGreaterThan(0, $res, 'setPriceCurrency (HT) failed: '.$ppc->error);

		$fetched = new ProductPriceCurrency($db);
		$found = $fetched->fetchByKey($product->id, 1, 'USD');
		$this->assertSame(1, $found, 'fetchByKey must find the row');
		$this->assertEquals(100.0, $fetched->price, 'HT price must be stored as is');
		$this->assertEquals(120.0, $fetched->price_ttc, 'TTC must be 100 * 1.20');
		$this->assertSame('HT', $fetched->price_base_type);
	}

	/**
	 * Setting a TTC price computes the HT counterpart.
	 *
	 * @return void
	 */
	public function testSetPriceCurrencyTtcComputesHt()
	{
		global $db, $user;

		$product = $this->createTestProduct(20.0);

		$ppc = new ProductPriceCurrency($db);
		$res = $ppc->setPriceCurrency($product->id, 'USD', 120.0, 'TTC', 20.0, $user, 1, 1.1);
		$this->assertGreaterThan(0, $res, 'setPriceCurrency (TTC) failed: '.$ppc->error);

		$fetched = new ProductPriceCurrency($db);
		$found = $fetched->fetchByKey($product->id, 1, 'USD');
		$this->assertSame(1, $found, 'fetchByKey must find the row');
		$this->assertEquals(100.0, $fetched->price, 'HT must be 120 / 1.20');
		$this->assertEquals(120.0, $fetched->price_ttc, 'TTC stored as is');
		$this->assertSame('TTC', $fetched->price_base_type);
	}

	/**
	 * Calling setPriceCurrency twice on the same business key updates the same row (upsert).
	 *
	 * @return void
	 */
	public function testSetPriceCurrencyUpsertKeepsSingleRow()
	{
		global $db, $user;

		$product = $this->createTestProduct(20.0);

		$ppc = new ProductPriceCurrency($db);
		$this->assertGreaterThan(0, $ppc->setPriceCurrency($product->id, 'USD', 100.0, 'HT', 20.0, $user, 1, 1.1));
		$this->assertGreaterThan(0, $ppc->setPriceCurrency($product->id, 'USD', 150.0, 'HT', 20.0, $user, 1, 1.2));

		$all = $ppc->fetchAllForProduct($product->id);
		$this->assertArrayHasKey(1, $all, 'Level 1 must be present');
		$this->assertArrayHasKey('USD', $all[1], 'USD must be present on level 1');
		$this->assertCount(1, $all[1], 'Upsert must keep a single USD row on level 1');
		$this->assertEquals(150.0, $all[1]['USD']['price'], 'Second set must overwrite the price');
	}

	/**
	 * fetchAllForProduct groups results by level then currency code.
	 *
	 * @return void
	 */
	public function testFetchAllForProductGroupsByLevelAndCode()
	{
		global $db, $user;

		$product = $this->createTestProduct(20.0);

		$ppc = new ProductPriceCurrency($db);
		$ppc->setPriceCurrency($product->id, 'USD', 100.0, 'HT', 20.0, $user, 1, 1.1);
		$ppc->setPriceCurrency($product->id, 'GBP', 80.0, 'HT', 20.0, $user, 2, 0.85);

		$all = $ppc->fetchAllForProduct($product->id);
		$this->assertEquals(100.0, $all[1]['USD']['price']);
		$this->assertEquals(80.0, $all[2]['GBP']['price']);
	}

	/**
	 * A per-customer currency price (fk_soc) takes precedence over the catalog one; other customers fall back to catalog.
	 *
	 * @return void
	 */
	public function testPerCustomerCurrencyPriceTakesPrecedence()
	{
		global $db, $user;

		$product = $this->createTestProduct(20.0);
		$socid = $this->createTestSoc();

		$ppc = new ProductPriceCurrency($db);
		// Catalog price (fk_soc = 0)
		$this->assertGreaterThan(0, $ppc->setPriceCurrency($product->id, 'USD', 100.0, 'HT', 20.0, $user, 1, 1.1, 0), 'Catalog set failed: '.$ppc->error);
		// Customer-specific price (fk_soc = socid)
		$this->assertGreaterThan(0, $ppc->setPriceCurrency($product->id, 'USD', 70.0, 'HT', 20.0, $user, 1, 1.1, $socid), 'Customer set failed: '.$ppc->error);

		// Simulate the catalog cache the same way Product::fetch() would (without depending on the
		// multicurrency module being enabled in the test environment). The per-customer price is
		// resolved on demand from the database, the catalog one from this cache.
		$resolver = new Product($db);
		$resolver->id = $product->id;
		$resolver->multicurrency_prices = array(
			1 => array('USD' => array('price' => 100.0, 'price_ttc' => 120.0, 'price_base_type' => 'HT', 'multicurrency_tx' => 1.1)),
		);

		$resCustomer = $resolver->getSellPriceInCurrency('USD', 1, $socid);
		$this->assertEquals(70.0, $resCustomer['price'], 'Customer-specific currency price must win over catalog');

		$resCatalog = $resolver->getSellPriceInCurrency('USD', 1, 0);
		$this->assertEquals(100.0, $resCatalog['price'], 'Catalog price expected when no customer');

		$resOther = $resolver->getSellPriceInCurrency('USD', 1, $socid + 999999);
		$this->assertEquals(100.0, $resOther['price'], 'Unknown customer must fall back to catalog price');
	}

	/**
	 * fetchAllForProduct (catalog cache) must not include per-customer currency prices.
	 *
	 * @return void
	 */
	public function testFetchAllForProductExcludesCustomerPrices()
	{
		global $db, $user;

		$product = $this->createTestProduct(20.0);
		$socid = $this->createTestSoc();

		$ppc = new ProductPriceCurrency($db);
		$ppc->setPriceCurrency($product->id, 'USD', 100.0, 'HT', 20.0, $user, 1, 1.1, 0);
		$ppc->setPriceCurrency($product->id, 'USD', 70.0, 'HT', 20.0, $user, 1, 1.1, $socid);

		$all = $ppc->fetchAllForProduct($product->id);
		$this->assertCount(1, $all[1], 'Only the catalog USD price must be in the catalog cache');
		$this->assertEquals(100.0, $all[1]['USD']['price'], 'Catalog price expected in cache, not the customer one');
	}

	/**
	 * End-to-end: when the multicurrency module is enabled, Product::fetch() populates the catalog cache
	 * and getSellPriceInCurrency() resolves the stored price from it.
	 *
	 * @return void
	 */
	public function testFetchPopulatesMulticurrencyPricesWhenModuleEnabled()
	{
		global $db, $user, $conf;

		$product = $this->createTestProduct(20.0);
		$ppc = new ProductPriceCurrency($db);
		$this->assertGreaterThan(0, $ppc->setPriceCurrency($product->id, 'USD', 123.0, 'HT', 20.0, $user, 1, 1.1, 0), 'Set failed: '.$ppc->error);

		// Force the multicurrency module on so fetch() loads the per-currency cache (it is off in the test env).
		// isModEnabled() reads $conf->modules[<module>].
		$savmodule = isset($conf->modules['multicurrency']) ? $conf->modules['multicurrency'] : null;
		$conf->modules['multicurrency'] = 'multicurrency';

		$reloaded = new Product($db);
		$reloaded->fetch($product->id);

		// Restore the module flag immediately to avoid leaking into other tests
		if ($savmodule === null) {
			unset($conf->modules['multicurrency']);
		} else {
			$conf->modules['multicurrency'] = $savmodule;
		}

		$this->assertArrayHasKey(1, $reloaded->multicurrency_prices, 'fetch() must populate price level 1');
		$this->assertArrayHasKey('USD', $reloaded->multicurrency_prices[1], 'fetch() must populate the USD price');
		$this->assertEquals(123.0, $reloaded->multicurrency_prices[1]['USD']['price'], 'Cache must hold the stored catalog price');

		$res = $reloaded->getSellPriceInCurrency('USD', 1, 0);
		$this->assertEquals(123.0, $res['price'], 'Resolver must return the catalog price from the populated cache');
	}

	/**
	 * Bulk init creates the missing catalog price and never overwrites an existing one.
	 *
	 * @return void
	 */
	public function testInitCatalogCurrencyPricesIsNonDestructive()
	{
		global $db, $user;

		$product = $this->createTestProduct(20.0);

		$ppc = new ProductPriceCurrency($db);
		// Pre-existing USD catalog price that must survive the bulk init untouched
		$this->assertGreaterThan(0, $ppc->setPriceCurrency($product->id, 'USD', 999.0, 'HT', 20.0, $user, 1, 1.0, 0), 'Pre-set failed: '.$ppc->error);

		// Entity-wide bulk init for USD and GBP, price level 1 (rate is 1 when none is configured in the test env)
		$created = $ppc->initCatalogCurrencyPrices($user, array('USD', 'GBP'), 1);
		$this->assertGreaterThanOrEqual(1, $created, 'At least the missing GBP price of the test product must be created');

		// The pre-existing USD price of the test product must be left untouched
		$checkUsd = new ProductPriceCurrency($db);
		$this->assertSame(1, $checkUsd->fetchByKey($product->id, 1, 'USD', 0), 'USD price must still exist');
		$this->assertEquals(999.0, $checkUsd->price, 'Existing USD price must not be overwritten by the bulk init');

		// The missing GBP price must have been created from the company price (50) and the current rate (1)
		$checkGbp = new ProductPriceCurrency($db);
		$this->assertSame(1, $checkGbp->fetchByKey($product->id, 1, 'GBP', 0), 'GBP price must now exist');
		$this->assertEquals(50.0, $checkGbp->price, 'GBP price must equal company price 50 * rate 1');
	}

	/**
	 * Merging two thirdparties must drop the origin per-currency rows that would collide with an existing
	 * dest row on the unique key (the dest price wins), remap the non-colliding ones, and never raise a
	 * duplicate-key error. Guards the portable dedup in Product::replaceThirdparty(). (issue #32379)
	 *
	 * @return void
	 */
	public function testReplaceThirdpartyDedupCurrencyPrices()
	{
		global $db, $user;

		$product = $this->createTestProduct(20.0);
		$socOrigin = $this->createTestSoc();
		$socDest = $this->createTestSoc();

		$ppc = new ProductPriceCurrency($db);
		// Dest already has a USD price: it must survive the merge untouched (the kept row).
		$this->assertGreaterThan(0, $ppc->setPriceCurrency($product->id, 'USD', 100.0, 'HT', 20.0, $user, 1, 1.1, $socDest), 'Dest USD set failed: '.$ppc->error);
		// Origin has a colliding USD price (same product/level/currency/entity) -> must be deleted, not remapped.
		$this->assertGreaterThan(0, $ppc->setPriceCurrency($product->id, 'USD', 90.0, 'HT', 20.0, $user, 1, 1.1, $socOrigin), 'Origin USD set failed: '.$ppc->error);
		// Origin has a GBP price with no dest counterpart -> must be remapped to dest.
		$this->assertGreaterThan(0, $ppc->setPriceCurrency($product->id, 'GBP', 80.0, 'HT', 20.0, $user, 1, 0.85, $socOrigin), 'Origin GBP set failed: '.$ppc->error);

		$res = Product::replaceThirdparty($db, $socOrigin, $socDest);
		$this->assertTrue($res, 'replaceThirdparty must succeed without a duplicate-key error');

		// Dest keeps its own USD price (100), not the origin one (90).
		$destUsd = new ProductPriceCurrency($db);
		$this->assertSame(1, $destUsd->fetchByKey($product->id, 1, 'USD', $socDest), 'Dest USD price must still exist');
		$this->assertEquals(100.0, $destUsd->price, 'Dest USD price must be kept (origin colliding row dropped)');

		// The non-colliding origin GBP price is remapped to dest.
		$destGbp = new ProductPriceCurrency($db);
		$this->assertSame(1, $destGbp->fetchByKey($product->id, 1, 'GBP', $socDest), 'Origin GBP price must be remapped to dest');
		$this->assertEquals(80.0, $destGbp->price, 'Remapped GBP price value must be preserved');

		// Origin keeps no per-currency price after the merge.
		$origUsd = new ProductPriceCurrency($db);
		$this->assertSame(0, $origUsd->fetchByKey($product->id, 1, 'USD', $socOrigin), 'Origin USD price must be gone (deleted as duplicate)');
		$origGbp = new ProductPriceCurrency($db);
		$this->assertSame(0, $origGbp->fetchByKey($product->id, 1, 'GBP', $socOrigin), 'Origin GBP price must be gone (remapped away)');
	}
}
