<?php
/* Copyright (C) 2023       Alexandre Janniaux      <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024-2025  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2026		Pierre Ardoin			<developpeur@lesmetiersdubatiment.fr>
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
 *      \file       test/phpunit/ExpeditionTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test for the Expedition code
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;

require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/societe/class/societe.class.php';
require_once dirname(__FILE__).'/../../htdocs/expedition/class/expedition.class.php';
require_once dirname(__FILE__).'/../../htdocs/product/class/product.class.php';
require_once dirname(__FILE__).'/../../htdocs/product/stock/class/entrepot.class.php';
require_once dirname(__FILE__).'/../../htdocs/product/stock/class/mouvementstock.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

$langs->load("dict");

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
class ExpeditionTest extends CommonClassTest
{
	/**
	 * Enable the modules and constants needed by standalone shipment stock tests.
	 *
	 * @param	bool	$calculateOnShipment	Stock decrease on shipment validation
	 * @param	bool	$calculateOnClose		Stock decrease on shipment closing
	 * @param	bool	$productBatchEnabled	Enable lot/serial module
	 * @return	void
	 */
	private function configureStandaloneShipmentStock($calculateOnShipment, $calculateOnClose, $productBatchEnabled = false)
	{
		global $conf, $user;

		if (empty($conf->product)) {
			$conf->product = new stdClass();
		}
		if (empty($conf->stock)) {
			$conf->stock = new stdClass();
		}
		if (empty($conf->productbatch)) {
			$conf->productbatch = new stdClass();
		}
		if (empty($conf->service)) {
			$conf->service = new stdClass();
		}
		if (empty($conf->modules) || !is_array($conf->modules)) {
			$conf->modules = array();
		}

		$conf->product->enabled = 1;
		$conf->stock->enabled = 1;
		$conf->productbatch->enabled = $productBatchEnabled ? 1 : 0;
		$conf->service->enabled = 1;
		$conf->modules['product'] = '1';
		$conf->modules['stock'] = '1';
		$conf->modules['productbatch'] = $productBatchEnabled ? '1' : '0';
		$conf->modules['service'] = '1';

		$conf->global->MAIN_USE_ADVANCED_PERMS = '';
		$conf->global->SHIPMENT_STANDALONE = 1;
		$conf->global->STOCK_CALCULATE_ON_SHIPMENT = $calculateOnShipment ? 1 : '';
		$conf->global->STOCK_CALCULATE_ON_SHIPMENT_CLOSE = $calculateOnClose ? 1 : '';
		$conf->global->STOCK_WAREHOUSE_NOT_REQUIRED_FOR_SHIPMENTS = '';
		$conf->global->STOCK_MUST_BE_ENOUGH_FOR_SHIPMENT = 1;
		$conf->global->STOCK_SUPPORTS_SERVICES = '';
		$conf->global->SHIPMENT_SUPPORTS_SERVICES = '';

		$user->rights->expedition = new stdClass();
		$user->rights->expedition->creer = 1;
	}

	/**
	 * Create a product for standalone shipment stock tests.
	 *
	 * @param	int		$statusBatch		Lot/serial status
	 * @param	int		$productType			Product type
	 * @param	int		$stockableProduct		Stockable product flag
	 * @return	int							Product id
	 */
	private function createShipmentStockProduct($statusBatch = 0, $productType = Product::TYPE_PRODUCT, $stockableProduct = Product::ENABLED_STOCK)
	{
		global $db, $user;

		$product = new Product($db);
		$product->initAsSpecimen();
		$product->ref = 'EXPSTANDALONE-'.dol_now().'-'.mt_rand(1000, 9999);
		$product->label = 'Expedition standalone stock phpunit';
		$product->type = $productType;
		$product->status = 1;
		$product->status_buy = 1;
		$product->status_batch = $statusBatch;
		$product->stockable_product = $stockableProduct;

		$productId = $product->create($user);
		$this->assertGreaterThan(0, $productId, $product->errorsToString());

		return $productId;
	}

	/**
	 * Create an open warehouse for standalone shipment stock tests.
	 *
	 * @return	int		Warehouse id
	 */
	private function createShipmentStockWarehouse()
	{
		global $db, $user;

		$warehouse = new Entrepot($db);
		$warehouse->initAsSpecimen();
		$warehouse->ref = 'EXPSTANDALONE-'.dol_now().'-'.mt_rand(1000, 9999);
		$warehouse->label = 'Expedition standalone stock phpunit '.$warehouse->ref;
		$warehouse->statut = Entrepot::STATUS_OPEN_ALL;

		$warehouseId = $warehouse->create($user);
		$this->assertGreaterThan(0, $warehouseId, $warehouse->errorsToString());

		return $warehouseId;
	}

	/**
	 * Create a standalone shipment for stock tests.
	 *
	 * @return	Expedition	Shipment object
	 */
	private function createStandaloneShipment()
	{
		global $db, $user;

		$soc = new Societe($db);
		$soc->name = 'Expedition standalone stock phpunit';
		$socId = $soc->create($user);
		$this->assertGreaterThan(0, $socId, $soc->errorsToString());

		$shipment = new Expedition($db);
		$shipment->socid = $socId;
		$result = $shipment->create($user);
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());

		return $shipment;
	}

	/**
	 * Add stock for a product into a warehouse.
	 *
	 * @param	int		$productId		Product id
	 * @param	int		$warehouseId	Warehouse id
	 * @param	float	$qty			Quantity to add
	 * @return	void
	 */
	private function addStockForShipmentProduct($productId, $warehouseId, $qty)
	{
		global $db, $user;

		$mouvS = new MouvementStock($db);
		$result = $mouvS->reception($user, $productId, $warehouseId, $qty, 0, 'Stock for standalone shipment phpunit');
		$this->assertGreaterThan(0, $result, $mouvS->errorsToString());
	}

	/**
	 * Get real product stock in a warehouse.
	 *
	 * @param	int	$productId		Product id
	 * @param	int	$warehouseId	Warehouse id
	 * @return	float				Real stock
	 */
	private function getWarehouseRealStock($productId, $warehouseId)
	{
		global $db;

		$product = new Product($db);
		$result = $product->fetch($productId);
		$this->assertGreaterThan(0, $result, $product->errorsToString());
		$product->load_stock('warehouseopen');

		return empty($product->stock_warehouse[$warehouseId]) ? 0 : (float) $product->stock_warehouse[$warehouseId]->real;
	}

	/**
	 * testExpeditionCreate
	 *
	 * @return int
	 */
	public function testExpeditionCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$soc = new Societe($db);
		$soc->name = "ExpeditionTest Unittest";
		$soc_id = $soc->create($user);
		$this->assertLessThanOrEqual(
			$soc_id,
			0,
			"Cannot create Societe object: ".
			$soc->errorsToString()
		);

		$localobject = new Expedition($db);
		$localobject->socid = $soc_id;
		$result = $localobject->create($user);
		$this->assertLessThanOrEqual($result, 0, "Cannot create Reception object:\n".
									 $localobject->errorsToString());
		return $result;
	}

	/**
	 * testExpeditionFetch
	 *
	 * Check that a Expedition object can be fetched from database.
	 *
	 * @param 	int			$id 		The id of an existing Expedition object to fetch.
	 * @return 	Expedition 	$localobject
	 *
	 * @depends testExpeditionCreate
	 */
	public function testExpeditionFetch($id)
	{
		global $db;

		$localobject = new Expedition($db);
		$result = $localobject->fetch($id);
		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $localobject;
	}

	/**
	 * testExpeditionUpdate
	 *
	 * Check that an Expedition object can be updated.
	 *
	 * @param 	Expedition	$localobject 	An existing Expedition object to update.
	 * @return 	Expedition 					An Expedition object with data fetched and name changed
	 *
	 * @depends testExpeditionFetch
	 */
	public function testExpeditionUpdate($localobject)
	{
		global $user;

		$localobject->name = "foobar";

		$result = $localobject->update($user);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0, $localobject->errorsToString());

		return $localobject;
	}

	/**
	 * testExpeditionValid
	 *
	 * Check that an Expedition with status == Expedition::STATUS_DRAFT can be
	 * re-opened with the Expedition::reOpen() function.
	 *
	 * @param Expedition	$localobject 	An existing Expedition object to validate.
	 * @return Expedition					An Expedition object with data fetched and STATUS_VALIDATED
	 *
	 * @depends testExpeditionUpdate
	 */
	public function testExpeditionValid($localobject)
	{
		global $db, $user, $conf;

		$conf->global->MAIN_USE_ADVANCED_PERMS = '';
		$user->rights->expedition = new stdClass();
		$user->rights->expedition->creer = 1;

		$result = $user->fetch($user->id);
		$this->assertLessThan($result, 0, $user->errorsToString());

		$result = $localobject->fetch($localobject->id);
		$this->assertLessThan($result, 0, $localobject->errorsToString());
		$this->assertEquals(Expedition::STATUS_DRAFT, $localobject->statut);
		$this->assertEquals(Expedition::STATUS_DRAFT, $localobject->status);

		$result = $localobject->valid($user);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0, $localobject->errorsToString());
		$this->assertEquals(Expedition::STATUS_VALIDATED, $localobject->statut);
		$this->assertEquals(Expedition::STATUS_VALIDATED, $localobject->status);

		$obj = new Expedition($db);
		$obj->fetch($localobject->id);
		$this->assertEquals(Expedition::STATUS_VALIDATED, $obj->statut);
		$this->assertEquals(Expedition::STATUS_VALIDATED, $obj->status);
		return $obj;
	}

	/**
	 * testExpeditionSetClosed
	 *
	 * Check that a Expedition can be closed with the Reception::setClosed()
	 * function, after it has been validated.
	 *
	 * @param 	Expedition	$localobject 	An existing validated Expedition object to close.
	 * @return 	Expedition 					An Expedition object with data fetched and STATUS_CLOSED
	 *
	 * @depends testExpeditionValid
	 */
	public function testExpeditionSetClosed($localobject)
	{
		global $db, $user;

		$result = $localobject->fetch($localobject->id);
		$this->assertLessThanOrEqual($result, 0, "Cannot fetch Expedition object:\n".
									 $localobject->errorsToString());
		$this->assertEquals(Expedition::STATUS_VALIDATED, $localobject->statut);
		$this->assertEquals(Expedition::STATUS_VALIDATED, $localobject->status);

		$result = $localobject->setClosed();
		$this->assertLessThanOrEqual($result, 0, "Cannot close Expedition object:\n".
									 $localobject->errorsToString());
		$this->assertEquals(
			Expedition::STATUS_CLOSED,
			$localobject->status,
			"Checking that \$localobject->status is STATUS_CLOSED"
		);
		$this->assertEquals(
			Expedition::STATUS_CLOSED,
			$localobject->statut,
			"Checking that \$localobject->statut is STATUS_CLOSED"
		);

		$obj = new Expedition($db);
		$result = $obj->fetch($localobject->id);
		$this->assertLessThanOrEqual($result, 0, "Cannot fetch Expedition object:\n".
									 $obj->errorsToString());
		$this->assertEquals(
			Expedition::STATUS_CLOSED,
			$obj->status,
			"Checking that \$obj->status is STATUS_CLOSED"
		);
		$this->assertEquals(
			Expedition::STATUS_CLOSED,
			$obj->statut,
			"Checking that \$obj->statut is STATUS_CLOSED"
		);

		return $obj;
	}

	/**
	 * testExpeditionReOpen
	 *
	 * Check that a Expedition with status == Reception::STATUS_CLOSED can be
	 * re-opened with the Expedition::reOpen() function.
	 *
	 * @param 	Expedition	$localobject 	An existing closed Reception object to re-open.
	 * @return 	Expedition 					An Expedition object with data fetched and STATUS_VALIDATED
	 *
	 * @depends testExpeditionSetClosed
	 */
	public function testExpeditionReOpen($localobject)
	{
		global $db;

		$result = $localobject->fetch($localobject->id);
		$this->assertLessThanOrEqual($result, 0, "Cannot fetch Expedition object:\n".
									 $localobject->errorsToString());

		$this->assertEquals(Expedition::STATUS_CLOSED, $localobject->status);
		$this->assertEquals(Expedition::STATUS_CLOSED, $localobject->statut);

		$result = $localobject->reOpen();
		$this->assertLessThanOrEqual($result, 0, "Cannot reOpen Expedition object:\n".
									 $localobject->errorsToString());
		$this->assertEquals(Expedition::STATUS_VALIDATED, $localobject->statut);
		$this->assertEquals(Expedition::STATUS_VALIDATED, $localobject->status);

		$obj = new Expedition($db);
		$obj->fetch($localobject->id);
		$this->assertEquals(Expedition::STATUS_VALIDATED, $obj->statut);
		$this->assertEquals(Expedition::STATUS_VALIDATED, $obj->status);

		return $obj;
	}

	/**
	 * testExpeditionSetDraft
	 *
	 * Check that a Expedition with status == Reception::STATUS_CLOSED can be
	 * re-opened with the Expedition::reOpen() function.
	 *
	 * @param 	Expedition	$localobject 	An existing validated Expedition object to mark as Draft.
	 * @return 	Expedition	 				An Expeditionn object with data fetched and STATUS_DRAFT
	 *
	 * @depends testExpeditionReOpen
	 */
	public function testExpeditionSetDraft($localobject)
	{
		global $db, $user, $conf;

		//$conf->global->MAIN_USE_ADVANCED_PERMS = 1;
		//$user->rights->reception->creer = 1;
		//$user->rights->reception_advance->validate = 1;

		$result = $localobject->fetch($localobject->id);
		$this->assertLessThan($result, 0);
		$this->assertEquals(Expedition::STATUS_VALIDATED, $localobject->statut);
		$this->assertEquals(Expedition::STATUS_VALIDATED, $localobject->status);

		$result = $localobject->setDraft($user);
		$this->assertLessThanOrEqual($result, 0, "Cannot setDraft on Expedition object:\n".
									 $localobject->errorsToString());
		$this->assertEquals(Expedition::STATUS_DRAFT, $localobject->statut);
		$this->assertEquals(Expedition::STATUS_DRAFT, $localobject->status);

		$obj = new Expedition($db);
		$obj->fetch($localobject->id);
		$this->assertEquals(Expedition::STATUS_DRAFT, $obj->statut);
		$this->assertEquals(Expedition::STATUS_DRAFT, $obj->status);

		return $obj;
	}

	/**
	 * testStandaloneShipmentLineStoresWarehouse
	 *
	 * @return	void
	 */
	public function testStandaloneShipmentLineStoresWarehouse()
	{
		global $db;

		$this->configureStandaloneShipmentStock(false, false);

		$productId = $this->createShipmentStockProduct();
		$warehouseId = $this->createShipmentStockWarehouse();
		$this->addStockForShipmentProduct($productId, $warehouseId, 10);

		$shipment = $this->createStandaloneShipment();
		$lineId = $shipment->addlinefree(2, 'shipping', $productId, 0, -1, 'Standalone product', 0, array(), $warehouseId);
		$this->assertGreaterThan(0, $lineId, $shipment->errorsToString());

		$line = new ExpeditionLigne($db);
		$result = $line->fetch($lineId);
		$this->assertGreaterThan(0, $result, $line->errorsToString());
		$this->assertEquals($productId, $line->fk_product);
		$this->assertEquals($warehouseId, $line->entrepot_id);
	}

	/**
	 * testStandaloneShipmentDecreasesStockOnValidation
	 *
	 * @return	void
	 */
	public function testStandaloneShipmentDecreasesStockOnValidation()
	{
		global $user;

		$this->configureStandaloneShipmentStock(true, false);

		$productId = $this->createShipmentStockProduct();
		$warehouseId = $this->createShipmentStockWarehouse();
		$this->addStockForShipmentProduct($productId, $warehouseId, 10);

		$shipment = $this->createStandaloneShipment();
		$lineId = $shipment->addlinefree(3, 'shipping', $productId, 0, -1, 'Standalone product', 0, array(), $warehouseId);
		$this->assertGreaterThan(0, $lineId, $shipment->errorsToString());

		$result = $shipment->valid($user);
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());
		$this->assertEquals(7, $this->getWarehouseRealStock($productId, $warehouseId));
	}

	/**
	 * testStandaloneShipmentDecreasesStockOnClose
	 *
	 * @return	void
	 */
	public function testStandaloneShipmentDecreasesStockOnClose()
	{
		global $user;

		$this->configureStandaloneShipmentStock(false, true);

		$productId = $this->createShipmentStockProduct();
		$warehouseId = $this->createShipmentStockWarehouse();
		$this->addStockForShipmentProduct($productId, $warehouseId, 10);

		$shipment = $this->createStandaloneShipment();
		$lineId = $shipment->addlinefree(4, 'shipping', $productId, 0, -1, 'Standalone product', 0, array(), $warehouseId);
		$this->assertGreaterThan(0, $lineId, $shipment->errorsToString());

		$result = $shipment->valid($user);
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());
		$this->assertEquals(10, $this->getWarehouseRealStock($productId, $warehouseId));

		$result = $shipment->setClosed();
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());
		$this->assertEquals(6, $this->getWarehouseRealStock($productId, $warehouseId));
	}

	/**
	 * testStandaloneShipmentRequiresWarehouse
	 *
	 * @return	void
	 */
	public function testStandaloneShipmentRequiresWarehouse()
	{
		$this->configureStandaloneShipmentStock(true, false);

		$productId = $this->createShipmentStockProduct();
		$shipment = $this->createStandaloneShipment();

		$result = $shipment->addlinefree(1, 'shipping', $productId, 0, -1, 'Standalone product', 0, array(), 0);
		$this->assertLessThan(0, $result);
		$this->assertNotEmpty($shipment->error);
	}

	/**
	 * testStandaloneShipmentAllowsMissingWarehouseWhenConfigured
	 *
	 * @return	void
	 */
	public function testStandaloneShipmentAllowsMissingWarehouseWhenConfigured()
	{
		global $conf;

		$this->configureStandaloneShipmentStock(true, false);
		$conf->global->STOCK_WAREHOUSE_NOT_REQUIRED_FOR_SHIPMENTS = 1;

		$productId = $this->createShipmentStockProduct();
		$warehouseId = $this->createShipmentStockWarehouse();
		$this->addStockForShipmentProduct($productId, $warehouseId, 10);

		$shipment = $this->createStandaloneShipment();
		$result = $shipment->addlinefree(1, 'shipping', $productId, 0, -1, 'Standalone product', 0, array(), 0);
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());
	}

	/**
	 * testStandaloneShipmentSupportsServicesWithoutStock
	 *
	 * @return	void
	 */
	public function testStandaloneShipmentSupportsServicesWithoutStock()
	{
		global $conf;

		$this->configureStandaloneShipmentStock(true, false);
		$conf->global->SHIPMENT_SUPPORTS_SERVICES = 1;

		$productId = $this->createShipmentStockProduct(0, Product::TYPE_SERVICE, Product::ENABLED_STOCK);
		$shipment = $this->createStandaloneShipment();

		$result = $shipment->addlinefree(1, 'shipping', $productId, 0, -1, 'Standalone service', 0, array(), 0);
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());
	}

	/**
	 * testStandaloneShipmentSupportsServicesWithStock
	 *
	 * @return	void
	 */
	public function testStandaloneShipmentSupportsServicesWithStock()
	{
		global $conf;

		$this->configureStandaloneShipmentStock(true, false);
		$conf->global->STOCK_SUPPORTS_SERVICES = 1;

		$productId = $this->createShipmentStockProduct(0, Product::TYPE_SERVICE, Product::ENABLED_STOCK);
		$warehouseId = $this->createShipmentStockWarehouse();
		$this->addStockForShipmentProduct($productId, $warehouseId, 10);

		$shipment = $this->createStandaloneShipment();
		$result = $shipment->addlinefree(1, 'shipping', $productId, 0, -1, 'Standalone service', 0, array(), $warehouseId);
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());
	}

	/**
	 * testStandaloneShipmentRejectsBatchProduct
	 *
	 * @return	void
	 */
	public function testStandaloneShipmentRejectsBatchProduct()
	{
		$this->configureStandaloneShipmentStock(true, false, true);

		$productId = $this->createShipmentStockProduct(1);
		$warehouseId = $this->createShipmentStockWarehouse();
		$shipment = $this->createStandaloneShipment();

		$result = $shipment->addlinefree(1, 'shipping', $productId, 0, -1, 'Standalone product', 0, array(), $warehouseId);
		$this->assertLessThan(0, $result);
		$this->assertEquals('ErrorTryToMakeMoveOnProductRequiringBatchData', $shipment->errorhidden);
	}

	/**
	 * testExpeditionDelete
	 *
	 * Check that an Expedition object can be deleted.
	 *
	 * @param 	Expedition 	$localobject 	An existing Expedition object to delete.
	 * @return 	int 						The result of the delete operation
	 *
	 * @depends testExpeditionReOpen
	 */
	public function testExpeditionDelete($localobject)
	{
		global $db, $user;

		$result = $localobject->delete($user);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThanOrEqual($result, 0);

		$soc = new Societe($db);
		$result = $soc->delete($localobject->socid, $user);
		$this->assertLessThanOrEqual($result, 0);

		return $result;
	}
}
