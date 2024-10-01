<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
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

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/product/stock/class/mouvementstock.class.php';
require_once dirname(__FILE__).'/../../htdocs/product/stock/class/entrepot.class.php';
require_once dirname(__FILE__).'/../../htdocs/product/class/product.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->getrights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
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
}
