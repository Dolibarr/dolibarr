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
 *      \file       test/phpunit/MouvementStockTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db,$mysoc;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/product/stock/class/mouvementstock.class.php';
require_once dirname(__FILE__).'/../../htdocs/product/stock/class/entrepot.class.php';
require_once dirname(__FILE__).'/../../htdocs/product/class/product.class.php';

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
class MouvementStockTest extends CommonClassTest
{
	/**
	 * Init phpunit tests
	 *
	 * @return	void
	 */
	protected function setUp(): void
	{
		parent::setUp();

		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		if (!isModEnabled('productbatch')) {
			print "\n".__METHOD__." module Lot/Serial must be enabled.\n";
			die(1);
		}

		print __METHOD__."\n";
	}


	/**
	 * testMouvementCreate
	 *
	 * @return	MouvementStock
	 */
	public function testMouvementCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// We create a product for tests
		$product0 = new Product($db);
		$product0->initAsSpecimen();
		$product0->ref .= ' phpunit 0';
		$product0->label .= ' phpunit 0';
		$product0->status_batch = 1;
		$product0id = $product0->create($user);

		print __METHOD__." product0id=".$product0id."\n";
		$this->assertGreaterThan(0, $product0id, 'Failed to create product');

		$product1 = new Product($db);
		$product1->initAsSpecimen();
		$product1->ref .= ' phpunit 1';
		$product1->label .= ' phpunit 1';
		$product1id = $product1->create($user);

		$product2 = new Product($db);
		$product2->initAsSpecimen();
		$product2->ref .= ' phpunit 2';
		$product2->label .= ' phpunit 2';
		$product2id = $product2->create($user);

		// We create a product for tests
		$warehouse0 = new Entrepot($db);
		$warehouse0->initAsSpecimen();
		$warehouse0->label .= ' phpunit 0';
		$warehouse0->description .= ' phpunit 0';
		$warehouse0->statut = 0;
		$warehouse0id = $warehouse0->create($user);

		$warehouse1 = new Entrepot($db);
		$warehouse1->initAsSpecimen();
		$warehouse1->label .= ' phpunit 1';
		$warehouse1->description .= ' phpunit 1';
		$warehouse1id = $warehouse1->create($user);

		$warehouse2 = new Entrepot($db);
		$warehouse2->initAsSpecimen();
		$warehouse2->label .= ' phpunit 2';
		$warehouse2->description .= ' phpunit 2';
		$warehouse2id = $warehouse2->create($user);

		$localobject = new MouvementStock($db);

		$datetest1 = dol_mktime(0, 0, 0, 1, 1, 2000);
		$datetest2 = dol_mktime(0, 0, 0, 1, 2, 2000);

		// Create an input movement movement (type = 3) with value for eatby date and a lot $datetest1
		$result = $localobject->reception($user, $product0id, $warehouse0id, 5, 999, 'Movement for unit test with batch', $datetest1, $datetest1, 'anotyetuselotnumberA', '', 0, 'Inventory Code Test with batch');
		print __METHOD__." result=".$result."\n";
		$this->assertGreaterThan(0, $result, 'Failed to create a movement with a lot number '.$datetest1.' for product id='.$product0id.' with status_batch=1');

		$result = $localobject->reception($user, $product0id, $warehouse0id, 5, 999, 'Movement for unit test with batch', $datetest1, $datetest1, 'anotyetuselotnumberB', '', 0, 'Inventory Code Test with batch');
		print __METHOD__." result=".$result."\n";
		$this->assertGreaterThan(0, $result, 'Test to check we can create a movement a eatby dare different when lot number is different');

		// Create same input movement movement (type = 3) with same lot but a different value of eatby date
		$result = $localobject->reception($user, $product0id, $warehouse0id, 5, 999, 'Movement for unit test with batch', $datetest2, $datetest1, 'anotyetuselotnumberA', '', 0, 'Inventory Code Test with batch');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(-3, $result, 'Test to check we can t create a movement for a lot with a different eatby date');

		// Do a list of movement into warehouse 1

		// Create an input movement (type = 3) of price 9.9 -> should update PMP to 9.9
		$result = $localobject->reception($user, $product1id, $warehouse1id, 10, 9.9, 'Movement for unit test 1', '', '', '', '', 0, 'Inventory Code Test');
		print __METHOD__." result=".$result."\n";
		$this->assertGreaterThan(0, $result, 'Return code of 0 was expected for the reception test 1');

		// Create an input movement (type = 3) of price 9.7 -> should update PMP to 9.9/9.7 = 9.8
		$result = $localobject->reception($user, $product1id, $warehouse1id, 10, 9.7, 'Movement for unit test 2', '', '', '', '', 0, 'Inventory Code Test');
		print __METHOD__." result=".$result."\n";
		$this->assertGreaterThan(0, $result);

		// Create an output movement (type = 2) of price 9.7 -> should update PMP to 9.9/9.7 = 9.8
		$result = $localobject->livraison($user, $product1id, $warehouse1id, 5, 999, 'Movement for unit test 3', '', '', '', '', 0, 'Inventory Code Test');
		print __METHOD__." result=".$result."\n";
		$this->assertGreaterThan(0, $result);

		// Create an output movement (type = 1) of price 9.7 -> should update PMP to 9.9/9.7 = 9.8
		$result = $localobject->_create($user, $product1id, $warehouse1id, 1, 0, 0, 'Input from transfer', 'Transfert X');
		print __METHOD__." result=".$result."\n";
		$this->assertGreaterThan(0, $result);

		// Create an output movement (type = 1) of price 9.7 -> should update PMP to 9.9/9.7 = 9.8
		$result = $localobject->_create($user, $product1id, $warehouse1id, -2, 1, 0, 'Output from transfer', 'Transfert Y');
		print __METHOD__." result=".$result."\n";
		$this->assertGreaterThan(0, $result);


		// Do same but into warehouse 2

		// Create an input movement (type = 3) of price 9.9 -> should update PMP to 9.9
		$result = $localobject->reception($user, $product1id, $warehouse2id, 10, 9.9, 'Movement for unit test 1 wh 2', '', '', '', '', 0, 'Inventory Code Test 2');
		print __METHOD__." result=".$result."\n";
		$this->assertGreaterThan(0, $result);

		// Create an input movement (type = 3) of price 9.7 -> should update PMP to 9.9/9.7 = 9.8
		$result = $localobject->reception($user, $product1id, $warehouse2id, 10, 9.7, 'Movement for unit test 2 wh 2', '', '', '', '', 0, 'Inventory Code Test 2');
		print __METHOD__." result=".$result."\n";
		$this->assertGreaterThan(0, $result);

		// Create an output movement (type = 2) of price 9.7 -> should update PMP to 9.9/9.7 = 9.8
		$result = $localobject->livraison($user, $product1id, $warehouse2id, 5, 999, 'Movement for unit test 3 wh 2', '', '', '', '', 0, 'Inventory Code Test 2');
		print __METHOD__." result=".$result."\n";
		$this->assertGreaterThan(0, $result);

		// Create an output movement (type = 1) of price 9.7 -> should update PMP to 9.9/9.7 = 9.8
		$result = $localobject->_create($user, $product1id, $warehouse2id, 1, 0, 0, 'Input from transfer wh 2', 'Transfert X 2');
		print __METHOD__." result=".$result."\n";
		$this->assertGreaterThan(0, $result, 'Test create A');

		// Create an output movement (type = 1) of price 9.7 -> should update PMP to 9.9/9.7 = 9.8
		$result = $localobject->_create($user, $product1id, $warehouse2id, -2, 1, 0, 'Output from transfer wh 2', 'Transfert Y 2');
		print __METHOD__." result=".$result."\n";
		$this->assertGreaterThan(0, $result, 'Test create B');

		return $localobject;
	}

	/**
	 * testMouvementCheck
	 *
	 * @param	MouvementStock		$localobject	Movement object we created
	 * @return	MouvementStock
	 *
	 * @depends	testMouvementCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testMouvementCheck($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$productid = $localobject->product_id;
		$warehouseid = $localobject->entrepot_id;

		print __METHOD__." productid=".$productid."\n";

		$producttotest = new Product($db);
		$producttotest->fetch($productid);

		print __METHOD__." producttotest->stock_reel=".$producttotest->stock_reel."\n";
		$this->assertEquals($producttotest->stock_reel, 28);	// 28 is result of stock movement defined into testMouvementCreate

		print __METHOD__." producttotest->pmp=".$producttotest->pmp."\n";
		$this->assertEquals($producttotest->pmp, 9.8);

		return $localobject;
	}

	/**
	 * testCalculateBalanceForProductBefore
	 *
	 * @return	void
	 */
	public function testCalculateBalanceForProductBefore()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$product = new Product($db);
		$product->initAsSpecimen();
		$product->ref .= ' phpunit balance';
		$product->label .= ' phpunit balance';
		$productid = $product->create($user);
		$this->assertGreaterThan(0, $productid, 'Failed to create product');

		$warehouse = new Entrepot($db);
		$warehouse->initAsSpecimen();
		$warehouse->label .= ' phpunit balance';
		$warehouseid = $warehouse->create($user);
		$this->assertGreaterThan(0, $warehouseid, 'Failed to create warehouse');

		$movement = new MouvementStock($db);
		$movementdate = dol_mktime(0, 0, 0, 1, 1, 2020);
		$result = $movement->reception($user, $productid, $warehouseid, 2.5, 0, 'Fractional qty for unit test', '', '', '', $movementdate, 0, 'Inventory Code Balance');
		$this->assertGreaterThan(0, $result, 'Failed to create fractional stock movement');

		$balance = $movement->calculateBalanceForProductBefore($productid, dol_now());
		print __METHOD__." balance=".$balance."\n";
		$this->assertEquals(2.5, $balance, 'Fractional stock balance must not be truncated to an integer');
	}

	/**
	 * testVariantParentStockIsSumOfVariants
	 *
	 * @return	void
	 */
	public function testVariantParentStockIsSumOfVariants()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		require_once dirname(__FILE__).'/../../htdocs/variants/class/ProductCombination.class.php';

		// $conf is shared by every test suite of the run, so the module and options set here are restored at the end
		$savmodules = $conf->modules;
		$savparentstock = getDolGlobalString('VARIANT_ALLOW_STOCK_MOVEMENT_ON_VARIANT_PARENT');
		$savnegative = getDolGlobalString('STOCK_DISALLOW_NEGATIVE_TRANSFER');
		$conf->modules['variants'] = 'variants';

		try {
			// A parent followed by batch, a red variant followed by batch and a blue variant without batch
			$parent = new Product($db);
			$parent->initAsSpecimen();
			$parent->ref .= ' phpunit variant parent';
			$parent->label .= ' phpunit variant parent';
			$parent->status_batch = 1;
			$parentid = $parent->create($user);
			$this->assertGreaterThan(0, $parentid, 'Failed to create parent product');

			$variantids = array();
			foreach (array('red' => 1, 'blue' => 0) as $color => $status_batch) {
				$variant = new Product($db);
				$variant->initAsSpecimen();
				$variant->ref .= ' phpunit variant '.$color;
				$variant->label .= ' phpunit variant '.$color;
				$variant->status_batch = $status_batch;
				$variantids[$color] = $variant->create($user);
				$this->assertGreaterThan(0, $variantids[$color], 'Failed to create variant product');

				$combination = new ProductCombination($db);
				$combination->fk_product_parent = $parentid;
				$combination->fk_product_child = $variantids[$color];
				$this->assertGreaterThan(0, $combination->create($user), 'Failed to link the variant to its parent');
			}

			$warehouse = new Entrepot($db);
			$warehouse->initAsSpecimen();
			$warehouse->label .= ' phpunit variant';
			$warehouseid = $warehouse->create($user);
			$this->assertGreaterThan(0, $warehouseid, 'Failed to create warehouse');

			// Stock of parent is the sum of its variants: after each movement of a variant, its parent is recalculated
			$conf->global->VARIANT_ALLOW_STOCK_MOVEMENT_ON_VARIANT_PARENT = '2';
			$movement = new MouvementStock($db);
			$this->assertGreaterThan(0, $movement->reception($user, $variantids['red'], $warehouseid, 50, 0, 'phpunit variant', '', '', 'phpunit-lot'));
			$this->assertGreaterThan(0, $movement->reception($user, $variantids['blue'], $warehouseid, 50, 0, 'phpunit variant'));
			$this->assertGreaterThan(0, $movement->livraison($user, $variantids['red'], $warehouseid, 10, 0, 'phpunit variant', '', '', '', 'phpunit-lot'));
			$this->assertEquals(90, $this->getStockInWarehouse($parentid, $warehouseid), 'Stock of the parent must be the sum of its variants');
			$this->assertEmpty($this->getBatchesInWarehouse($parentid, $warehouseid), 'The parent must receive quantities only, without batch number');
			$this->assertEquals(0, $this->getNbMovements($parentid), 'The parent is recalculated, no movement is recorded on it');

			// The parent is not counted twice in the warehouse total
			$calcproducts = $warehouse->nb_products();
			$this->assertEquals(90, $calcproducts['nb'], 'The parent must not be counted in the warehouse total');
			$calcproductsunique = $warehouse->nb_different_products();
			$this->assertEquals(2, $calcproductsunique['nb'], 'The parent must not be counted in the number of products of the warehouse');

			// The parent can't move by itself
			$this->assertLessThan(0, $movement->livraison($user, $parentid, $warehouseid, 1, 0, 'phpunit variant'), 'A movement on the parent itself must be refused');

			// A difference on the parent, whatever its cause, is corrected at the next movement of a variant: 40 red + 60 blue
			$resql = $db->query("UPDATE ".$db->prefix()."product_stock SET reel = 999 WHERE fk_product = ".((int) $parentid)." AND fk_entrepot = ".((int) $warehouseid));
			$this->assertNotFalse($resql, 'Failed to put a difference on the parent');
			$this->assertGreaterThan(0, $movement->reception($user, $variantids['blue'], $warehouseid, 10, 0, 'phpunit variant'));
			$this->assertEquals(100, $this->getStockInWarehouse($parentid, $warehouseid), 'A difference on the parent must be corrected at the next movement of a variant');

			// Stock managed manually: the parent is not recalculated, and may move by itself
			$conf->global->VARIANT_ALLOW_STOCK_MOVEMENT_ON_VARIANT_PARENT = '1';
			$this->assertGreaterThan(0, $movement->reception($user, $variantids['blue'], $warehouseid, 100, 0, 'phpunit variant'));
			$this->assertEquals(100, $this->getStockInWarehouse($parentid, $warehouseid), 'The parent must not be recalculated when its stock is managed manually');

			// A parent with less stock never blocks a variant, even when negative stock is not allowed: 40 red + 40 blue
			$conf->global->VARIANT_ALLOW_STOCK_MOVEMENT_ON_VARIANT_PARENT = '2';
			$conf->global->STOCK_DISALLOW_NEGATIVE_TRANSFER = '1';
			$this->assertGreaterThan(0, $movement->livraison($user, $variantids['blue'], $warehouseid, 120, 0, 'phpunit variant'), 'A parent with less stock must not block its variant');
			$this->assertEquals(80, $this->getStockInWarehouse($parentid, $warehouseid));

			// Choosing the sum of variants recalculates every parent, here corrected by hand while its stock was managed manually
			$resql = $db->query("UPDATE ".$db->prefix()."product_stock SET reel = 75 WHERE fk_product = ".((int) $parentid)." AND fk_entrepot = ".((int) $warehouseid));
			$this->assertNotFalse($resql, 'Failed to put a difference on the parent');
			$this->assertEquals(75, $this->getStockInWarehouse($parentid, $warehouseid));
			$this->assertGreaterThan(0, $movement->recalculateVariantParentStocks(), 'Failed to recalculate stock of parents');
			$this->assertEquals(80, $this->getStockInWarehouse($parentid, $warehouseid), 'Recalculation must set the parent to the sum of its variants');
		} finally {
			$conf->modules = $savmodules;
			$conf->global->VARIANT_ALLOW_STOCK_MOVEMENT_ON_VARIANT_PARENT = $savparentstock;
			$conf->global->STOCK_DISALLOW_NEGATIVE_TRANSFER = $savnegative;
		}
	}

	/**
	 * Return the stock of a product in a warehouse
	 *
	 * @param	int		$productid		Id of product
	 * @param	int		$warehouseid	Id of warehouse
	 * @return	float					Stock of the product in the warehouse
	 */
	private function getStockInWarehouse($productid, $warehouseid)
	{
		$product = new Product($this->savdb);
		$product->fetch($productid);
		$product->load_stock('novirtual');

		return isset($product->stock_warehouse[$warehouseid]) ? (float) $product->stock_warehouse[$warehouseid]->real : 0;
	}

	/**
	 * Return the number of stock movements recorded for a product
	 *
	 * @param	int		$productid		Id of product
	 * @return	int						Number of stock movements
	 */
	private function getNbMovements($productid)
	{
		$resql = $this->savdb->query("SELECT COUNT(rowid) as nb FROM ".$this->savdb->prefix()."stock_mouvement WHERE fk_product = ".((int) $productid));
		$obj = $resql ? $this->savdb->fetch_object($resql) : null;

		return $obj ? (int) $obj->nb : -1;
	}

	/**
	 * Return the batch details of a product in a warehouse
	 *
	 * @param	int		$productid		Id of product
	 * @param	int		$warehouseid	Id of warehouse
	 * @return	array<mixed>			Batch details of the product in the warehouse
	 */
	private function getBatchesInWarehouse($productid, $warehouseid)
	{
		$product = new Product($this->savdb);
		$product->fetch($productid);
		$product->load_stock('novirtual');

		return isset($product->stock_warehouse[$warehouseid]->detail_batch) ? (array) $product->stock_warehouse[$warehouseid]->detail_batch : array();
	}
}
