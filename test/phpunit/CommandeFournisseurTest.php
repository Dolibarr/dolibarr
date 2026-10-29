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
 *      \file       test/phpunit/CommandeFournisseurTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db,$mysoc;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/fourn/class/fournisseur.commande.class.php';
require_once dirname(__FILE__).'/../../htdocs/fourn/class/fournisseur.product.class.php';

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
 * @phan-file-suppress PhanParamTooMany
 * @phan-file-suppress PhanPluginUnknownObjectMethodCall
 * @phan-file-suppress PhanTypeMismatchArgumentProbablyReal
 */
class CommandeFournisseurTest extends CommonClassTest
{
	/**
	 * testCommandeFournisseurCreate
	 *
	 * @return	int		ID of purchase order
	 */
	public function testCommandeFournisseurCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// Set supplier and product to use
		$socid = 1;
		$societe = new Societe($db);
		$societe->fetch($socid);
		$product = new ProductFournisseur($db);
		$product->fetch(0, 'PINKDRESS');
		if ($product->id <= 0) {
			print "\n".__METHOD__." A product with ref PINKDRESS must exists into database";
			die(1);
		}

		$quantity = 10;
		$ref_fourn = 'SUPPLIER_REF_PHPUNIT';
		$tva_tx = 19.6;

		// Delete existing supplier prices
		// TODO

		// Create 1 supplier price with min qty = 10;
		$result = $product->add_fournisseur($user, $societe->id, $ref_fourn, $quantity);    // This insert record with no value for price. Values are update later with update_buyprice
		print __METHOD__." add_fournisseur result=".$result."\n";
		$this->assertGreaterThanOrEqual(0, $result, 'Create 1 supplier price with min qty = 10 if not exists');
		$result = $product->update_buyprice($quantity, 20, $user, 'HT', $societe, '', $ref_fourn, $tva_tx, 0, 0);
		print __METHOD__." update_buyprice result=".$result."\n";
		$this->assertGreaterThanOrEqual(0, $result, 'Update buyprice');

		// Create purchase order with a too low quantity and option SUPPLIER_ORDER_WITH_PREDEFINED_PRICES_ONLY is on
		$conf->global->SUPPLIER_ORDER_WITH_PREDEFINED_PRICES_ONLY = 1;

		$localobject = new CommandeFournisseur($db);
		$param = array('tobuy' => 1);
		$localobject->initAsSpecimen($param);
		$localobject->lines = array();    // Overwrite lines of order
		$line = new CommandeFournisseurLigne($db);
		$line->desc = $langs->trans("Description")." specimen line with qty too low";
		$line->qty = 1;                   // So lower than $quantity
		$line->subprice = 100;
		$line->fk_product = $product->id;
		$line->ref_fourn = $ref_fourn;
		$line->ref_supplier = $ref_fourn;
		$localobject->lines[] = $line;

		$result = $localobject->create($user);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(-1, $result, 'Creation of too low quantity');   // must be -1 because quantity is lower than minimum of supplier price

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."commande_fournisseur where ref IN ('', '(PROV)')";
		$db->query($sql);

		// Create purchase order
		$localobject2 = new CommandeFournisseur($db);
		$param = array('tobuy' => 1);
		$localobject2->initAsSpecimen($param);    // This create 5 lines of first product found for socid 1
		$localobject2->lines = array();       // Overwrite lines of order
		$line = new CommandeFournisseurLigne($db);
		$line->desc = $langs->trans("Description")." specimen line ok";
		$line->qty = 10;                      // So enough quantity
		$line->subprice = 100;
		$line->fk_product = $product->id;
		$line->ref_supplier = $ref_fourn;
		$localobject2->lines[] = $line;

		$result = $localobject2->create($user);
		print __METHOD__." result=".$result."\n";
		$this->assertGreaterThan(0, $result);


		// Create purchase order with a too low quantity but option SUPPLIER_ORDER_WITH_PREDEFINED_PRICES_ONLY is off
		$conf->global->SUPPLIER_ORDER_WITH_PREDEFINED_PRICES_ONLY = 0;

		$localobject3 = new CommandeFournisseur($db);
		$param = array('tobuy' => 1);
		$localobject3->initAsSpecimen($param);
		$localobject3->lines = array();    // Overwrite lines of order
		$line = new CommandeFournisseurLigne($db);
		$line->desc = $langs->trans("Description")." specimen line with qty too low";
		$line->qty = 1;                   // So lower than $quantity
		$line->subprice = 100;
		$line->fk_product = $product->id;
		$line->ref_supplier = $ref_fourn;
		$localobject3->lines[] = $line;

		$result = $localobject3->create($user);
		print __METHOD__." result=".$result."\n";
		$this->assertGreaterThan(0, $result, 'Creation of too low quantity should be ok');   // must be id of line because there is no test on minimum quantity

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."commande_fournisseur where ref=''";
		$db->query($sql);

		// Create purchase order
		$localobject4 = new CommandeFournisseur($db);
		$localobject4->initAsSpecimen();    // This create 5 lines of first product found for socid 1
		$localobject4->lines = array();       // Overwrite lines of order
		$line = new CommandeFournisseurLigne($db);
		$line->desc = $langs->trans("Description")." specimen line ok";
		$line->qty = 10;                      // So enough quantity
		$line->subprice = 100;
		$line->fk_product = $product->id;
		$localobject4->lines[] = $line;

		$result = $localobject4->create($user);
		print __METHOD__." id for purchase order created by testCommandeFournisseurCreate = ".$result."\n";
		$this->assertGreaterThan(0, $result, 'Test to create a purchase order by testCommandeFournisseurCreate');

		return $result;
	}


	/**
	 * testCommandeFournisseurFetch
	 *
	 * @param   int 		$id     			Id of purchase order
	 * @return  CommandeFournisseur				Purchase order
	 *
	 * @depends testCommandeFournisseurCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testCommandeFournisseurFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new CommandeFournisseur($db);
		$result = $localobject->fetch($id);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0, 'Failed to fetch supplier order with id '.$id);
		return $localobject;
	}

	/**
	 * testCommandeFournisseurValid
	 *
	 * @param   CommandeFournisseur $localobject     Supplier order
	 * @return  CommandeFournisseur		Supplier order
	 *
	 * @depends testCommandeFournisseurFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testCommandeFournisseurValid($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->valid($user);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $localobject;
	}

	/**
	 * testCommandeFournisseurApprove
	 *
	 * @param   CommandeFournisseur $localobject 	Supplier order
	 * @return  CommandeFournisseur		Supplier order
	 *
	 * @depends testCommandeFournisseurValid
	 * The depends says test is run only if previous is ok
	 */
	public function testCommandeFournisseurApprove($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->approve($user);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $localobject;
	}

	/**
	 * testCommandeFournisseurCancel
	 *
	 * @param   CommandeFournisseur  $localobject    Supplier order
	 * @return  CommandeFournisseur		Supplier order
	 *
	 * @depends testCommandeFournisseurApprove
	 * The depends says test is run only if previous is ok
	 */
	public function testCommandeFournisseurCancel($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->cancel($user);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $localobject;
	}

	/**
	 * testCommandeFournisseurOther
	 *
	 * @param   CommandeFournisseur $localobject     Supplier order
	 * @return  int						Id of purchase order
	 *
	 * @depends testCommandeFournisseurCancel
	 * The depends says test is run only if previous is ok
	 */
	public function testCommandeFournisseurOther($localobject)
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

		/*$localobject->info($localobject->id);
		print __METHOD__." localobject->date_creation=".$localobject->date_creation."\n";
		$this->assertNotEquals($localobject->date_creation, '');
		*/
		$this->assertEquals(1, 1);

		return $localobject->id;
	}

	/**
	 * testCommandeFournisseurDelete
	 *
	 * @param   int $id     Id of order
	 * @return  int			Result of delete
	 *
	 * @depends testCommandeFournisseurOther
	 * The depends says test is run only if previous is ok
	 */
	public function testCommandeFournisseurDelete($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new CommandeFournisseur($db);
		$result = $localobject->fetch($id);
		$result = $localobject->delete($user);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $result;
	}

	/**
	 * testSupplierOrderLineInsertMulticurrencySubpriceTtc
	 *
	 * @return	void
	 */
	public function testSupplierOrderLineInsertMulticurrencySubpriceTtc()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$order = new CommandeFournisseur($db);
		$order->initAsSpecimen();
		$orderid = $order->create($user);
		$this->assertGreaterThan(0, $orderid, 'Failed to create supplier order: '.$order->errorsToString());

		$line = new CommandeFournisseurLigne($db);
		$line->fk_commande = $orderid;
		$line->desc = 'phpunit multicurrency line';
		$line->qty = 1;
		$line->product_type = 0;
		$line->special_code = 0;
		$line->rang = 0;
		$line->vat_src_code = '';
		$line->tva_tx = 20;
		$line->localtax1_tx = 0;
		$line->localtax2_tx = 0;
		$line->localtax1_type = '';
		$line->localtax2_type = '';
		$line->remise_percent = 0;
		$line->subprice = 100;
		$line->subprice_ttc = 120;
		$line->ref_supplier = '';
		$line->total_ht = 100;
		$line->total_tva = 20;
		$line->total_localtax1 = 0;
		$line->total_localtax2 = 0;
		$line->total_ttc = 120;
		$line->fk_multicurrency = 0;
		$line->multicurrency_code = $conf->currency;
		$line->multicurrency_subprice = 110;
		$line->multicurrency_subprice_ttc = 132;
		$line->multicurrency_total_ht = 110;
		$line->multicurrency_total_tva = 22;
		$line->multicurrency_total_ttc = 132;
		$result = $line->insert(1);
		$this->assertGreaterThan(0, $result, 'Failed to insert supplier order line: '.$line->errorsToString());

		$reloaded = new CommandeFournisseurLigne($db);
		$reloaded->fetch($line->id);
		print __METHOD__." multicurrency_subprice_ttc=".$reloaded->multicurrency_subprice_ttc."\n";
		$this->assertEquals(132, $reloaded->multicurrency_subprice_ttc, 'multicurrency_subprice_ttc must be persisted on insert');
	}

	/**
	 * testUpdateProductBuyPrices
	 *
	 * @return	void
	 */
	public function testUpdateProductBuyPrices()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$advancedperms = getDolGlobalString('MAIN_USE_ADVANCED_PERMS');
		$conf->global->MAIN_USE_ADVANCED_PERMS = 0;	// Test the default rights, create on products and services

		$societe = new Societe($db);
		$societe->fetch(1);

		// Product 1 has a supplier price of 20 (min qty 1), product 2 has no supplier price
		$product1 = new ProductFournisseur($db);
		$product1->initAsSpecimen();
		$product1->ref = 'PHPUNIT_BUYPRICES_1_'.dol_now();
		$productid1 = $product1->create($user);
		$this->assertGreaterThan(0, $productid1, 'Failed to create product 1: '.$product1->errorsToString());
		$product2 = new Product($db);
		$product2->initAsSpecimen();
		$product2->ref = 'PHPUNIT_BUYPRICES_2_'.dol_now();
		$productid2 = $product2->create($user);
		$this->assertGreaterThan(0, $productid2, 'Failed to create product 2: '.$product2->errorsToString());

		$ref_fourn = 'SUPPLIER_REF_PHPUNIT_BUYPRICES_'.dol_now();
		$result = $product1->add_fournisseur($user, $societe->id, $ref_fourn, 1);
		$this->assertGreaterThan(0, $result, 'Failed to create the supplier price line');
		$result = $product1->update_buyprice(1, 20, $user, 'HT', $societe, 0, $ref_fourn, 0, 0, 0, 0, 0, 0, '', array(), '', 20, 'HT', 1, $conf->currency);
		$this->assertGreaterThan(0, $result, 'Failed to set the supplier price');
		$pricerowid = $product1->product_fourn_price_id;

		// Purchase order with a line at 30 with 10% discount (net 27) on product 1, and a line at 50 on product 2
		$order = new CommandeFournisseur($db);
		$order->initAsSpecimen();
		$order->socid = $societe->id;
		$order->lines = array();
		$line = new CommandeFournisseurLigne($db);
		$line->desc = 'phpunit line with a supplier price';
		$line->qty = 1;
		$line->subprice = 30;
		$line->remise_percent = 10;
		$line->fk_product = $productid1;
		$line->ref_supplier = $ref_fourn;
		$order->lines[] = $line;
		$line = new CommandeFournisseurLigne($db);
		$line->desc = 'phpunit line without supplier price';
		$line->qty = 2;
		$line->subprice = 50;
		$line->fk_product = $productid2;
		$order->lines[] = $line;
		$line = new CommandeFournisseurLigne($db);
		$line->desc = 'phpunit free of charge line, must be ignored';
		$line->qty = 1;
		$line->subprice = 0;
		$line->fk_product = $productid2;
		$order->lines[] = $line;
		$orderid = $order->create($user);
		$this->assertGreaterThan(0, $orderid, 'Failed to create supplier order: '.$order->errorsToString());
		$order->fetch($orderid);

		$this->assertEquals(0, $order->updateProductBuyPrices(new User($db)), 'A user without rights must not update any price');

		$result = $order->updateProductBuyPrices($user);
		$this->assertEquals(3, $result, 'Supplier price and cost price of product 1, cost price of product 2');

		// Supplier price of product 1 is the price of the line, and the previous price is kept in the history
		$productsupplier = new ProductFournisseur($db);
		$productsupplier->fetch_product_fournisseur_price($pricerowid);
		$this->assertEquals(30, (float) $productsupplier->fourn_unitprice, 'Supplier price must be the price of the line');
		$this->assertEquals(10, (float) $productsupplier->fourn_remise_percent, 'Supplier discount must be the discount of the line');
		$this->assertCount(2, $productsupplier->listProductFournisseurPriceLog($pricerowid), 'History must contain the old and the new price');

		// Cost price is the net unit price of the line, and no supplier price is created for product 2
		$product1->fetch($productid1);
		$this->assertEquals(27, (float) $product1->cost_price, 'Cost price must be the net unit price of the line');
		$product2->fetch($productid2);
		$this->assertEquals(50, (float) $product2->cost_price, 'Cost price must be the net unit price of the line');
		$this->assertCount(0, $productsupplier->list_product_fournisseur_price($productid2), 'No supplier price must be created');

		// Prices are already the ones of the order
		$this->assertEquals(0, $order->updateProductBuyPrices($user), 'Nothing must be updated a second time');

		$conf->global->MAIN_USE_ADVANCED_PERMS = $advancedperms;

		$order->delete($user);
		$product1->delete($user);
		$product2->delete($user);
	}
}
