<?php
/* Copyright (C) 2023       Alexandre Janniaux      <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024-2025  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2026       Pierre Ardoin           <developpeur@lesmetiersdubatiment.fr>
 * Copyright (C) 2026       Nick Fragoulis
 * Copyright (C) 2026       Marukome0743
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

global $conf,$user,$langs,$db,$mysoc;

require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/societe/class/societe.class.php';
require_once dirname(__FILE__).'/../../htdocs/expedition/class/expedition.class.php';
require_once dirname(__FILE__).'/../../htdocs/product/class/product.class.php';
require_once dirname(__FILE__).'/../../htdocs/product/stock/class/entrepot.class.php';
require_once dirname(__FILE__).'/../../htdocs/product/stock/class/mouvementstock.class.php';
require_once dirname(__FILE__).'/../../htdocs/contact/class/contact.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/modules/expedition/doc/pdf_espadon.modules.php';

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
 */
class ExpeditionTest extends CommonClassTest
{
	/**
	 * Restore the configuration after each standalone shipment scenario.
	 *
	 * @return void
	 */
	protected function tearDown(): void
	{
		global $conf;
		$conf = $this->savconf;
		parent::tearDown();
	}

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
		global $conf;

		$conf = clone $this->savconf;
		$conf->global = clone $this->savconf->global;
		foreach (array('product' => 1, 'stock' => 1, 'productbatch' => (int) $productBatchEnabled, 'service' => 1) as $module => $enabled) {
			$conf->$module = isset($this->savconf->$module) ? clone $this->savconf->$module : new stdClass();
			$conf->$module->enabled = $enabled;
			$conf->modules[$module] = (string) $enabled;
		}

		$conf->global->MAIN_USE_ADVANCED_PERMS = '';
		$conf->global->SHIPMENT_STANDALONE = 1;
		$conf->global->STOCK_CALCULATE_ON_SHIPMENT = $calculateOnShipment ? 1 : '';
		$conf->global->STOCK_CALCULATE_ON_SHIPMENT_CLOSE = $calculateOnClose ? 1 : '';
		$conf->global->STOCK_WAREHOUSE_NOT_REQUIRED_FOR_SHIPMENTS = '';
		$conf->global->STOCK_MUST_BE_ENOUGH_FOR_SHIPMENT = 1;
		$conf->global->STOCK_SUPPORTS_SERVICES = '';
		$conf->global->SHIPMENT_SUPPORTS_SERVICES = '';
		$conf->global->PRODUIT_SOUSPRODUITS = 0;
	}

	/**
	 * Create a product for standalone shipment stock tests.
	 *
	 * @param	string	$scenario		Test scenario
	 * @param	int		$statusBatch		Lot/serial status
	 * @param	int		$productType			Product type
	 * @param	int		$stockableProduct		Stockable product flag
	 * @return	int							Product id
	 */
	private function createShipmentStockProduct($scenario, $statusBatch = 0, $productType = Product::TYPE_PRODUCT, $stockableProduct = Product::ENABLED_STOCK)
	{
		global $db, $user;

		$product = new Product($db);
		$product->initAsSpecimen();
		$product->ref = 'EXPSTANDALONE-'.$scenario;
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
	 * @param	string	$scenario		Test scenario
	 * @return	int		Warehouse id
	 */
	private function createShipmentStockWarehouse($scenario)
	{
		global $db, $user;

		$warehouse = new Entrepot($db);
		$warehouse->initAsSpecimen();
		$warehouse->ref = 'EXPSTANDALONE-'.$scenario;
		$warehouse->label = 'Expedition standalone stock phpunit '.$warehouse->ref;
		$warehouse->statut = $warehouse->status = Entrepot::STATUS_OPEN_ALL;

		$warehouseId = $warehouse->create($user);
		$this->assertGreaterThan(0, $warehouseId, $warehouse->errorsToString());
		$result = $warehouse->fetch($warehouseId);
		$this->assertGreaterThan(0, $result, $warehouse->errorsToString());
		$this->assertEquals(Entrepot::STATUS_OPEN_ALL, $warehouse->status);

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
		$result = $product->load_stock('warehouseopen');
		$this->assertGreaterThan(0, $result, $product->errorsToString());

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

		$result = $localobject->setClosed($user);
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

		$productId = $this->createShipmentStockProduct(__FUNCTION__);
		$warehouseId = $this->createShipmentStockWarehouse(__FUNCTION__);
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

		$productId = $this->createShipmentStockProduct(__FUNCTION__);
		$warehouseId = $this->createShipmentStockWarehouse(__FUNCTION__);
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

		$productId = $this->createShipmentStockProduct(__FUNCTION__);
		$warehouseId = $this->createShipmentStockWarehouse(__FUNCTION__);
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
	 * testStandaloneShipmentRestoresStockOnReopen
	 *
	 * @return	void
	 */
	public function testStandaloneShipmentRestoresStockOnReopen()
	{
		global $user;

		$this->configureStandaloneShipmentStock(false, true);

		$productId = $this->createShipmentStockProduct(__FUNCTION__);
		$warehouseId = $this->createShipmentStockWarehouse(__FUNCTION__);
		$this->addStockForShipmentProduct($productId, $warehouseId, 10);

		$shipment = $this->createStandaloneShipment();
		$lineId = $shipment->addlinefree(4, 'shipping', $productId, 0, -1, 'Standalone product', 0, array(), $warehouseId);
		$this->assertGreaterThan(0, $lineId, $shipment->errorsToString());

		$result = $shipment->valid($user);
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());

		$result = $shipment->setClosed();
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());
		$this->assertEquals(6, $this->getWarehouseRealStock($productId, $warehouseId));

		$result = $shipment->reOpen();
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());
		$this->assertEquals(10, $this->getWarehouseRealStock($productId, $warehouseId));

		// Closing again must remove the stock only once
		$result = $shipment->setClosed();
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());
		$this->assertEquals(6, $this->getWarehouseRealStock($productId, $warehouseId));
	}

	/**
	 * testStandaloneShipmentRestoresStockOnSetDraft
	 *
	 * @return	void
	 */
	public function testStandaloneShipmentRestoresStockOnSetDraft()
	{
		global $user;

		$this->configureStandaloneShipmentStock(true, false);

		$productId = $this->createShipmentStockProduct(__FUNCTION__);
		$warehouseId = $this->createShipmentStockWarehouse(__FUNCTION__);
		$this->addStockForShipmentProduct($productId, $warehouseId, 10);

		$shipment = $this->createStandaloneShipment();
		$lineId = $shipment->addlinefree(3, 'shipping', $productId, 0, -1, 'Standalone product', 0, array(), $warehouseId);
		$this->assertGreaterThan(0, $lineId, $shipment->errorsToString());

		$result = $shipment->valid($user);
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());
		$this->assertEquals(7, $this->getWarehouseRealStock($productId, $warehouseId));

		$result = $shipment->setDraft($user);
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());
		$this->assertEquals(10, $this->getWarehouseRealStock($productId, $warehouseId));

		// Validating again must remove the stock only once
		$result = $shipment->valid($user);
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());
		$this->assertEquals(7, $this->getWarehouseRealStock($productId, $warehouseId));
	}

	/**
	 * testStandaloneShipmentRestoresStockOnCancel
	 *
	 * @return	void
	 */
	public function testStandaloneShipmentRestoresStockOnCancel()
	{
		global $user;

		$this->configureStandaloneShipmentStock(true, false);

		$productId = $this->createShipmentStockProduct(__FUNCTION__);
		$warehouseId = $this->createShipmentStockWarehouse(__FUNCTION__);
		$this->addStockForShipmentProduct($productId, $warehouseId, 10);

		$shipment = $this->createStandaloneShipment();
		$lineId = $shipment->addlinefree(3, 'shipping', $productId, 0, -1, 'Standalone product', 0, array(), $warehouseId);
		$this->assertGreaterThan(0, $lineId, $shipment->errorsToString());

		$result = $shipment->valid($user);
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());
		$this->assertEquals(7, $this->getWarehouseRealStock($productId, $warehouseId));

		$result = $shipment->cancel($user, 0, true);
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());
		$this->assertEquals(10, $this->getWarehouseRealStock($productId, $warehouseId));
	}

	/**
	 * testStandaloneShipmentRestoresStockOnDelete
	 *
	 * @return	void
	 */
	public function testStandaloneShipmentRestoresStockOnDelete()
	{
		global $user;

		$this->configureStandaloneShipmentStock(false, true);

		$productId = $this->createShipmentStockProduct(__FUNCTION__);
		$warehouseId = $this->createShipmentStockWarehouse(__FUNCTION__);
		$this->addStockForShipmentProduct($productId, $warehouseId, 10);

		$shipment = $this->createStandaloneShipment();
		$lineId = $shipment->addlinefree(4, 'shipping', $productId, 0, -1, 'Standalone product', 0, array(), $warehouseId);
		$this->assertGreaterThan(0, $lineId, $shipment->errorsToString());

		$result = $shipment->valid($user);
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());
		$result = $shipment->setClosed();
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());
		$this->assertEquals(6, $this->getWarehouseRealStock($productId, $warehouseId));

		$result = $shipment->delete($user, 0, true);
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());
		$this->assertEquals(10, $this->getWarehouseRealStock($productId, $warehouseId));
	}

	/**
	 * testStandaloneShipmentDeliveryReceiptHasLines
	 *
	 * @return	void
	 */
	public function testStandaloneShipmentDeliveryReceiptHasLines()
	{
		global $conf, $db, $user;

		require_once dirname(__FILE__).'/../../htdocs/delivery/class/delivery.class.php';

		$this->configureStandaloneShipmentStock(false, false);
		$conf->global->MAIN_SUBMODULE_DELIVERY = 1;

		$productId = $this->createShipmentStockProduct(__FUNCTION__);
		$shipment = $this->createStandaloneShipment();
		$result = $shipment->addlinefree(2, 'shipping', $productId, 0, 1, 'Standalone product', 0);
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());
		$result = $shipment->valid($user);
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());

		$deliveryId = $shipment->create_delivery($user);
		$this->assertGreaterThan(0, $deliveryId, $shipment->errorsToString());

		$delivery = new Delivery($db);
		$result = $delivery->fetch($deliveryId);
		$this->assertGreaterThan(0, $result, $delivery->errorsToString());
		$this->assertCount(1, $delivery->lines);
		$this->assertEquals($productId, $delivery->lines[0]->fk_product);
		$this->assertEquals(2, $delivery->lines[0]->qty_shipped);
	}

	/**
	 * The lines of a standalone shipment must have the same properties as the lines of a shipment
	 * created from an order: the PDF models and getTotalWeightVolume() read qty_shipped, the product
	 * data, the weight and the volume.
	 *
	 * @return	void
	 */
	public function testStandaloneShipmentLinesHaveShippedQtyAndProductData()
	{
		global $db, $user;

		$this->configureStandaloneShipmentStock(false, false);

		$productId = $this->createShipmentStockProduct(__FUNCTION__);
		$product = new Product($db);
		$this->assertGreaterThan(0, $product->fetch($productId), $product->errorsToString());
		$product->weight = 2;
		$product->weight_units = 0;	// kg
		$product->volume = 0.5;
		$product->volume_units = 0;	// m3
		$this->assertGreaterThan(0, $product->update($productId, $user), $product->errorsToString());

		$shipment = $this->createStandaloneShipment();
		$result = $shipment->addlinefree(3, 'shipping', $productId, 0, 1, 'Standalone product', 0);
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());
		$result = $shipment->addlinefree(1, 'shipping', 0, 0, 2, 'Free text', 0);
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());

		$result = $shipment->fetch($shipment->id);
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());
		$this->assertCount(2, $shipment->lines);

		$line = $shipment->lines[0];
		$this->assertEquals(3, $line->qty_shipped);
		$this->assertSame(Product::TYPE_PRODUCT, $line->product_type);
		$this->assertSame($product->ref, $line->product_ref);
		$this->assertSame($product->label, $line->product_label);
		$this->assertEquals(2, $line->weight);
		$this->assertEquals(0.5, $line->volume);

		$this->assertEquals(1, $shipment->lines[1]->qty_shipped);
		$this->assertEmpty($shipment->lines[1]->product_ref);

		$totals = $shipment->getTotalWeightVolume();
		$this->assertEquals(4, $totals['toship']);
		$this->assertEquals(6, $totals['weight']);
		$this->assertEquals(1.5, $totals['volume']);
	}

	/**
	 * testStandaloneShipmentLineGetsSellPriceForInvoice
	 *
	 * @return	void
	 */
	public function testStandaloneShipmentLineGetsSellPriceForInvoice()
	{
		global $db, $user, $mysoc;

		require_once dirname(__FILE__).'/../../htdocs/core/lib/sendings.lib.php';

		$this->configureStandaloneShipmentStock(false, false);

		$productId = $this->createShipmentStockProduct(__FUNCTION__);
		$product = new Product($db);
		$result = $product->fetch($productId);
		$this->assertGreaterThan(0, $result, $product->errorsToString());
		$result = $product->updatePrice(50, 'HT', $user, 20);
		$this->assertGreaterThan(0, $result, $product->errorsToString());

		$shipment = $this->createStandaloneShipment();
		$result = $shipment->addlinefree(2, 'shipping', $productId, 0, 1, 'Standalone product', 0);
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());
		$result = $shipment->addlinefree(1, 'shipping', 0, 0, 2, 'Free text', 0);
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());

		$result = $shipment->fetch($shipment->id);
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());
		$shipment->fetch_thirdparty();
		$this->assertCount(2, $shipment->lines);

		foreach ($shipment->lines as $line) {
			shippingSetStandaloneLineSellPrice($line, $shipment->thirdparty);
			if ($line->fk_product > 0) {
				$this->assertEquals(50, $line->subprice);
				$this->assertEquals(get_default_tva($mysoc, $shipment->thirdparty, $productId), $line->tva_tx);
				$this->assertEquals(Product::TYPE_PRODUCT, $line->product_type);
			} else {
				$this->assertEmpty($line->subprice);
				$this->assertEquals('Free text', $line->desc);
			}
		}
	}

	/**
	 * testStandaloneShipmentAcceptsMissingWarehouse
	 *
	 * @return	void
	 */
	public function testStandaloneShipmentAcceptsMissingWarehouse()
	{
		$this->configureStandaloneShipmentStock(true, false);

		$productId = $this->createShipmentStockProduct(__FUNCTION__);
		$shipment = $this->createStandaloneShipment();

		// A line with no origin order line may deliberately carry no warehouse:
		// a sample, goods sent out for servicing, or a delivery note issued for
		// e-reporting with no stock effect. Empty warehouse = no stock movement.
		$result = $shipment->addlinefree(1, 'shipping', $productId, 0, -1, 'Standalone product', 0, array(), 0);
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());
	}

	/**
	 * testStandaloneShipmentWithoutWarehouseMovesNoStock
	 *
	 * @return	void
	 */
	public function testStandaloneShipmentWithoutWarehouseMovesNoStock()
	{
		global $user;

		$this->configureStandaloneShipmentStock(true, true);

		$productId = $this->createShipmentStockProduct(__FUNCTION__);
		$warehouseId = $this->createShipmentStockWarehouse(__FUNCTION__);
		$this->addStockForShipmentProduct($productId, $warehouseId, 10);

		$shipment = $this->createStandaloneShipment();
		$lineId = $shipment->addlinefree(3, 'shipping', $productId, 0, -1, 'Standalone product', 0, array(), 0);
		$this->assertGreaterThan(0, $lineId, $shipment->errorsToString());

		$result = $shipment->valid($user);
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());
		$this->assertEquals(10, $this->getWarehouseRealStock($productId, $warehouseId));

		$result = $shipment->setClosed();
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());
		$this->assertEquals(10, $this->getWarehouseRealStock($productId, $warehouseId));
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

		$productId = $this->createShipmentStockProduct(__FUNCTION__);
		$warehouseId = $this->createShipmentStockWarehouse(__FUNCTION__);
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

		$productId = $this->createShipmentStockProduct(__FUNCTION__, 0, Product::TYPE_SERVICE, Product::ENABLED_STOCK);
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

		$productId = $this->createShipmentStockProduct(__FUNCTION__, 0, Product::TYPE_SERVICE, Product::ENABLED_STOCK);
		$warehouseId = $this->createShipmentStockWarehouse(__FUNCTION__);
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

		$productId = $this->createShipmentStockProduct(__FUNCTION__, 1);
		$warehouseId = $this->createShipmentStockWarehouse(__FUNCTION__);
		$shipment = $this->createStandaloneShipment();

		$result = $shipment->addlinefree(1, 'shipping', $productId, 0, -1, 'Standalone product', 0, array(), $warehouseId);
		$this->assertLessThan(0, $result);
		$this->assertEquals('ErrorTryToMakeMoveOnProductRequiringBatchData', $shipment->errorhidden);
	}

	/**
	 * Stock checks also apply to edits, using the stored product and warehouse.
	 *
	 * @return void
	 */
	public function testStandaloneShipmentChecksStockWhenUpdatingLine()
	{
		global $db;
		$this->configureStandaloneShipmentStock(false, false);
		$productId = $this->createShipmentStockProduct(__FUNCTION__);
		$warehouseId = $this->createShipmentStockWarehouse(__FUNCTION__);
		$this->addStockForShipmentProduct($productId, $warehouseId, 10);
		$shipment = $this->createStandaloneShipment();
		$lineId = $shipment->addlinefree(2, 'shipping', $productId, 0, 1, 'Standalone product', 0, array(), $warehouseId);
		$this->assertGreaterThan(0, $lineId, $shipment->errorsToString());

		$result = $shipment->updatelinefree($lineId, 11, 'shipping', 0, 0, 1, 'Standalone product', 0, 1);
		$this->assertSame(-3, $result);
		$this->assertSame('ErrorStockIsNotEnoughToAddProductOnShipment', $shipment->errorhidden);
		$line = new ExpeditionLigne($db);
		$this->assertGreaterThan(0, $line->fetch($lineId));
		$this->assertEquals(2, $line->qty);
		$this->assertEquals($productId, $line->fk_product);
		$this->assertEquals($warehouseId, $line->entrepot_id);

		$result = $shipment->updatelinefree($lineId, 3, 'shipping', 0, 0, 1, 'Standalone product', 0, 1);
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());
		$this->assertGreaterThan(0, $line->fetch($lineId));
		$this->assertEquals(3, $line->qty);
		$this->assertEquals($warehouseId, $line->entrepot_id);

		$otherWarehouseId = $this->createShipmentStockWarehouse(__FUNCTION__.'-other');
		// A warehouse without a product_stock row has zero available stock.
		$result = $shipment->updatelinefree($lineId, 3, 'shipping', 0, 0, 1, 'Standalone product', 0, 1, array(), $otherWarehouseId);
		$this->assertSame(-3, $result);
		$this->assertGreaterThan(0, $line->fetch($lineId));
		$this->assertEquals($warehouseId, $line->entrepot_id);

		$this->addStockForShipmentProduct($productId, $otherWarehouseId, 5);
		$result = $shipment->updatelinefree($lineId, 3, 'shipping', 0, 0, 1, 'Standalone product', 0, 1, array(), $otherWarehouseId);
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());
		$this->assertGreaterThan(0, $line->fetch($lineId));
		$this->assertEquals($otherWarehouseId, $line->entrepot_id);
		$this->assertEquals($productId, $line->fk_product);
	}

	/**
	 * Insufficient stock is rejected, while text-only lines need no warehouse.
	 *
	 * @return void
	 */
	public function testStandaloneShipmentChecksStockOnlyForProductLines()
	{
		$this->configureStandaloneShipmentStock(false, false);
		$productId = $this->createShipmentStockProduct(__FUNCTION__);
		$warehouseId = $this->createShipmentStockWarehouse(__FUNCTION__);
		$this->addStockForShipmentProduct($productId, $warehouseId, 1);
		$shipment = $this->createStandaloneShipment();
		$result = $shipment->addlinefree(2, 'shipping', $productId, 0, 1, 'Standalone product', 0, array(), $warehouseId);
		$this->assertSame(-3, $result);
		$this->assertSame('ErrorStockIsNotEnoughToAddProductOnShipment', $shipment->errorhidden);
		$result = $shipment->addlinefree(2, 'shipping', 0, 0, 1, 'Free text', 0);
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());
	}

	/**
	 * Get the REST API class for shipments, called as the current user.
	 *
	 * @return	Shipments	API object
	 */
	private function getShipmentsApi()
	{
		global $user;

		require_once DOL_DOCUMENT_ROOT.'/api/class/api_access.class.php';
		require_once DOL_DOCUMENT_ROOT.'/api/class/api.class.php';
		require_once DOL_DOCUMENT_ROOT.'/expedition/class/api_shipments.class.php';

		DolibarrApiAccess::$user = $user;

		return new Shipments();
	}

	/**
	 * The REST API must allow to add free lines (product or text) to a standalone shipment.
	 *
	 * @return	void
	 */
	public function testStandaloneShipmentApiAddsFreeLine()
	{
		global $conf, $db;

		$this->configureStandaloneShipmentStock(false, false);
		$productId = $this->createShipmentStockProduct(__FUNCTION__);
		$warehouseId = $this->createShipmentStockWarehouse(__FUNCTION__);
		$this->addStockForShipmentProduct($productId, $warehouseId, 10);
		$shipment = $this->createStandaloneShipment();

		// Product line with warehouse (each REST request gets a new API object)
		$lineId = $this->getShipmentsApi()->postLine($shipment->id, 0, 2, $warehouseId, $productId);
		$this->assertGreaterThan(0, $lineId);
		$line = new ExpeditionLigne($db);
		$this->assertGreaterThan(0, $line->fetch($lineId), $line->errorsToString());
		$this->assertEquals($shipment->id, $line->fk_expedition);
		$this->assertEquals($productId, $line->fk_product);
		$this->assertEquals($warehouseId, $line->entrepot_id);
		$this->assertEquals(2, $line->qty);
		$this->assertSame('shipping', $line->element_type);

		// Free text line
		$lineId = $this->getShipmentsApi()->postLine($shipment->id, 0, 1, 0, 0, 'Free text line');
		$this->assertGreaterThan(0, $lineId);
		$line = new ExpeditionLigne($db);
		$this->assertGreaterThan(0, $line->fetch($lineId), $line->errorsToString());
		$this->assertSame('Free text line', $line->description);

		// Neither a product nor a description: bad request
		try {
			$this->getShipmentsApi()->postLine($shipment->id, 0, 1);
			$this->fail('A free line without product and description must be refused');
		} catch (Luracast\Restler\RestException $e) {
			$this->assertSame(400, $e->getCode(), $e->getMessage());
		}

		// Without SHIPMENT_STANDALONE, an order line stays mandatory
		$conf->global->SHIPMENT_STANDALONE = 0;
		try {
			$this->getShipmentsApi()->postLine($shipment->id, 0, 1, $warehouseId, $productId);
			$this->fail('Without SHIPMENT_STANDALONE, a line without order line must be refused');
		} catch (Luracast\Restler\RestException $e) {
			$this->assertSame(400, $e->getCode());
			$this->assertSame('Field fk_origin_line is mandatory', $e->getMessage());
		}
	}

	/**
	 * Only shipments without linked source object are counted as standalone (used to lock the setup option).
	 *
	 * @return void
	 */
	public function testCountStandaloneShipments()
	{
		global $db, $user;

		$this->configureStandaloneShipmentStock(false, false);
		$staticshipment = new Expedition($db);
		$nbbefore = $staticshipment->countStandalone();
		$this->assertGreaterThanOrEqual(0, $nbbefore, $staticshipment->errorsToString());

		$this->createStandaloneShipment();
		$this->assertSame($nbbefore + 1, $staticshipment->countStandalone());

		// A shipment with a source object (here a sales order) is not standalone
		$shipment = $this->createStandaloneShipment();
		$result = $shipment->add_object_linked('commande', 1);
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());
		$this->assertSame($nbbefore + 1, $staticshipment->countStandalone());
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

	/**
	 * testStandaloneShipmentPdfReadsItsOwnContacts
	 *
	 * @return void
	 */
	public function testStandaloneShipmentPdfReadsItsOwnContacts()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// The contact type may be missing on a database upgraded from an old dump (insert without rowid fails on pgsql)
		$sql = "SELECT rowid FROM ".$db->prefix()."c_type_contact WHERE element = 'shipping' AND source = 'external' AND code = 'SHIPPING' AND active = 1";
		$resql = $db->query($sql);
		if (!$resql || !$db->num_rows($resql)) {
			$this->markTestSkipped('No active contact type SHIPPING found in c_type_contact for element shipping.');
			return;
		}

		$soc = new Societe($db);
		$soc->name = "ExpeditionTest Standalone Unittest";
		$soc_id = $soc->create($user);
		$this->assertGreaterThan(0, $soc_id, $soc->errorsToString());

		$contact = new Contact($db);
		$contact->socid = $soc_id;
		$contact->lastname = "ExpeditionTest Standalone Contact";
		$contact_id = $contact->create($user);
		$this->assertGreaterThan(0, $contact_id, $contact->errorsToString());

		$shipment = new Expedition($db);
		$shipment->socid = $soc_id;
		$shipment_id = $shipment->create($user);
		$this->assertGreaterThan(0, $shipment_id, $shipment->errorsToString());
		$shipment->fetch($shipment_id);
		$this->assertGreaterThan(0, $shipment->add_contact($contact_id, 'SHIPPING', 'external'));
		$shipment->fetch_origin();

		$pdf = new pdf_espadon($db);
		$this->assertEquals(array($contact_id), self::callMethod($pdf, 'getIdContactOfShipment', array($shipment, 'external', 'SHIPPING')));
	}

	/**
	 * POST /shipments/{id}/lines cannot expand a kit into its components, so it must refuse a kit.
	 * Otherwise the kit is stored with a warehouse and no child lines, and deleting the validated
	 * shipment does not give back the stock of its components.
	 *
	 * @return void
	 */
	public function testApiPostLineRefusesKit()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// Loading the Restler autoloader unregisters all the other ones, including the one PHPUnit needs: restore them.
		$autoloaders = spl_autoload_functions();
		require_once DOL_DOCUMENT_ROOT.'/api/class/api.class.php';
		require_once DOL_DOCUMENT_ROOT.'/api/class/api_access.class.php';
		spl_autoload_unregister(Luracast\Restler\AutoLoader::instance());
		foreach ($autoloaders as $autoloader) {
			spl_autoload_register($autoloader);
		}
		require_once DOL_DOCUMENT_ROOT.'/expedition/class/api_shipments.class.php';
		require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
		require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';

		$saved = array();
		foreach (array('PRODUIT_SOUSPRODUITS' => 1, 'PRODUIT_SOUSPRODUITS_ALSO_ENABLE_PARENT_STOCK_MOVE' => 0, 'STOCK_MUST_BE_ENOUGH_FOR_SHIPMENT' => 0) as $key => $value) {
			$saved[$key] = getDolGlobalString($key);
			$conf->global->$key = $value;
		}

		try {
			$suffix = 'ApiLineKit'.mt_rand(100000, 999999);

			$soc = new Societe($db);
			$soc->name = 'ExpeditionTest '.$suffix;
			$socId = $soc->create($user);
			$this->assertGreaterThan(0, $socId, $soc->errorsToString());

			$warehouse = new Entrepot($db);
			$warehouse->ref = 'WH'.$suffix;
			$warehouse->label = 'WH'.$suffix;
			$warehouse->statut = 1;
			$warehouseId = $warehouse->create($user);
			$this->assertGreaterThan(0, $warehouseId, $warehouse->errorsToString());

			$productIds = array();
			foreach (array('kit', 'component', 'plain') as $code) {
				$product = new Product($db);
				$product->ref = $code.$suffix;
				$product->label = $code.$suffix;
				$product->type = Product::TYPE_PRODUCT;
				$productIds[$code] = $product->create($user);
				$this->assertGreaterThan(0, $productIds[$code], $product->errorsToString());
			}

			$kit = new Product($db);
			$this->assertGreaterThan(0, $kit->add_sousproduit($productIds['kit'], $productIds['component'], 3, 1));

			$order = new Commande($db);
			$order->socid = $socId;
			$order->date = dol_now();
			$orderId = $order->create($user);
			$this->assertGreaterThan(0, $orderId, $order->errorsToString());
			$kitLineId = $order->addline('Kit', 0, 2, 0, 0, 0, $productIds['kit']);
			$this->assertGreaterThan(0, $kitLineId, $order->errorsToString());
			$plainLineId = $order->addline('Plain', 0, 1, 0, 0, 0, $productIds['plain']);
			$this->assertGreaterThan(0, $plainLineId, $order->errorsToString());

			$shipment = new Expedition($db);
			$shipment->socid = $socId;
			$shipment->origin_id = $orderId;
			$shipment->origin = 'commande';
			$shipmentId = $shipment->create($user);
			$this->assertGreaterThan(0, $shipmentId, $shipment->errorsToString());

			DolibarrApiAccess::$user = $user;

			$code = 0;
			try {
				$api = new Shipments();
				$api->postLine($shipmentId, $kitLineId, 2, $warehouseId);
			} catch (Luracast\Restler\RestException $e) {
				$code = $e->getCode();
			}
			$this->assertEquals(405, $code);
			$this->assertEquals(0, $this->countShipmentLines($shipmentId));

			// The product of the line can be given by the caller: a kit is also refused this way
			$code = 0;
			try {
				$api = new Shipments();
				$api->postLine($shipmentId, $plainLineId, 1, $warehouseId, $productIds['kit']);
			} catch (Luracast\Restler\RestException $e) {
				$code = $e->getCode();
			}
			$this->assertEquals(405, $code);
			$this->assertEquals(0, $this->countShipmentLines($shipmentId));

			// A line that is not a kit is still added
			$api = new Shipments();
			$this->assertGreaterThan(0, $api->postLine($shipmentId, $plainLineId, 1, $warehouseId));
			$this->assertEquals(1, $this->countShipmentLines($shipmentId));

			// With this option, a kit moves its own stock and create() stores it with its warehouse: the line is allowed
			$conf->global->PRODUIT_SOUSPRODUITS_ALSO_ENABLE_PARENT_STOCK_MOVE = 1;
			$api = new Shipments();
			$this->assertGreaterThan(0, $api->postLine($shipmentId, $kitLineId, 2, $warehouseId));
			$this->assertEquals(2, $this->countShipmentLines($shipmentId));
		} finally {
			foreach ($saved as $key => $value) {
				$conf->global->$key = $value;
			}
		}
	}

	/**
	 * Return the number of lines of a shipment
	 *
	 * @param	int		$shipmentId		Id of shipment
	 * @return	int						Number of lines
	 */
	private function countShipmentLines($shipmentId)
	{
		global $db;

		$resql = $db->query("SELECT COUNT(*) as nb FROM ".$db->prefix()."expeditiondet WHERE fk_expedition = ".((int) $shipmentId));
		$this->assertNotFalse($resql, (string) $db->lasterror());

		return (int) $db->fetch_object($resql)->nb;
	}

	/**
	 * A shipment created by the REST API must expand a kit into its components, like the shipment card does,
	 * so that the stock consumed on validation is restored on deletion.
	 *
	 * @return void
	 */
	public function testApiPostExpandsKitAndDeleteRestoresComponentStock()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// Loading the Restler autoloader unregisters all the other ones, including the one PHPUnit needs: restore them.
		$autoloaders = spl_autoload_functions();
		require_once DOL_DOCUMENT_ROOT.'/api/class/api.class.php';
		require_once DOL_DOCUMENT_ROOT.'/api/class/api_access.class.php';
		spl_autoload_unregister(Luracast\Restler\AutoLoader::instance());
		foreach ($autoloaders as $autoloader) {
			spl_autoload_register($autoloader);
		}
		require_once DOL_DOCUMENT_ROOT.'/expedition/class/api_shipments.class.php';
		require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
		require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
		require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';

		$saved = array();
		foreach (array('PRODUIT_SOUSPRODUITS' => 1, 'STOCK_CALCULATE_ON_SHIPMENT' => 1, 'PRODUIT_SOUSPRODUITS_ALSO_ENABLE_PARENT_STOCK_MOVE' => 0, 'STOCK_SUPPORTS_SERVICES' => 0, 'SHIPMENT_SUPPORTS_SERVICES' => 0) as $key => $value) {
			$saved[$key] = getDolGlobalString($key);
			$conf->global->$key = $value;
		}

		try {
			$suffix = 'ApiKit'.mt_rand(100000, 999999);

			$soc = new Societe($db);
			$soc->name = 'ExpeditionTest '.$suffix;
			$socId = $soc->create($user);
			$this->assertGreaterThan(0, $socId, $soc->errorsToString());

			$warehouse = new Entrepot($db);
			$warehouse->ref = 'WH'.$suffix;
			$warehouse->label = 'WH'.$suffix;
			$warehouse->statut = 1;
			$warehouseId = $warehouse->create($user);
			$this->assertGreaterThan(0, $warehouseId, $warehouse->errorsToString());

			$productIds = array();
			foreach (array('kit' => Product::TYPE_PRODUCT, 'component' => Product::TYPE_PRODUCT, 'service' => Product::TYPE_SERVICE) as $code => $type) {
				$product = new Product($db);
				$product->ref = $code.$suffix;
				$product->label = $code.$suffix;
				$product->type = $type;
				$productIds[$code] = $product->create($user);
				$this->assertGreaterThan(0, $productIds[$code], $product->errorsToString());
			}

			$kit = new Product($db);
			$this->assertGreaterThan(0, $kit->add_sousproduit($productIds['kit'], $productIds['component'], 3, 1));

			$movement = new MouvementStock($db);
			$this->assertGreaterThan(0, $movement->reception($user, $productIds['component'], $warehouseId, 10, 0, 'ExpeditionTest initial stock'));
			$this->assertEquals(10.0, $this->getRealStock($productIds['component'], $warehouseId));

			$order = new Commande($db);
			$order->socid = $socId;
			$order->date = dol_now();
			$orderId = $order->create($user);
			$this->assertGreaterThan(0, $orderId, $order->errorsToString());
			$kitLineId = $order->addline('Kit', 0, 2, 0, 0, 0, $productIds['kit']);
			$this->assertGreaterThan(0, $kitLineId, $order->errorsToString());
			$serviceLineId = $order->addline('Service', 0, 1, 0, 0, 0, $productIds['service'], 0, 0, 0, 'HT', 0, '', '', Product::TYPE_SERVICE);
			$this->assertGreaterThan(0, $serviceLineId, $order->errorsToString());

			DolibarrApiAccess::$user = $user;
			$api = new Shipments();
			$shipmentId = $api->post(array(
				'socid' => $socId,
				'origin_id' => $orderId,
				'origin_type' => 'commande',
				'lines' => array(
					array('origin_id' => $orderId, 'fk_elementdet' => $kitLineId, 'element_type' => 'commande', 'entrepot_id' => $warehouseId, 'qty' => 2, 'rang' => 1, 'array_options' => null, 'detail_batch' => null),
					array('origin_id' => $orderId, 'fk_elementdet' => $serviceLineId, 'element_type' => 'commande', 'entrepot_id' => $warehouseId, 'qty' => 1, 'rang' => 2, 'array_options' => null, 'detail_batch' => null),
				),
			));
			$this->assertGreaterThan(0, $shipmentId);

			$lines = array();
			$resql = $db->query("SELECT rowid, fk_product, fk_parent, fk_entrepot, qty FROM ".$db->prefix()."expeditiondet WHERE fk_expedition = ".((int) $shipmentId)." ORDER BY rowid");
			$this->assertNotFalse($resql, (string) $db->lasterror());
			while ($obj = $db->fetch_object($resql)) {
				$lines[(int) $obj->fk_product] = $obj;
			}
			$this->assertCount(3, $lines);

			// The kit is stored without warehouse and its component carries the warehouse, as with the shipment card
			$this->assertEmpty($lines[$productIds['kit']]->fk_parent);
			$this->assertEmpty($lines[$productIds['kit']]->fk_entrepot);
			$this->assertEquals(2.0, (float) $lines[$productIds['kit']]->qty);
			$this->assertEquals($lines[$productIds['kit']]->rowid, $lines[$productIds['component']]->fk_parent);
			$this->assertEquals($warehouseId, $lines[$productIds['component']]->fk_entrepot);
			$this->assertEquals(6.0, (float) $lines[$productIds['component']]->qty);

			// A line that is not a kit keeps its warehouse. A service line stays on the shipment as before.
			$this->assertEmpty($lines[$productIds['service']]->fk_parent);
			$this->assertEquals($warehouseId, $lines[$productIds['service']]->fk_entrepot);

			$shipment = new Expedition($db);
			$this->assertGreaterThan(0, $shipment->fetch($shipmentId), $shipment->errorsToString());
			$this->assertGreaterThan(0, $shipment->valid($user), $shipment->errorsToString());
			$this->assertEquals(4.0, $this->getRealStock($productIds['component'], $warehouseId));

			$this->assertGreaterThan(0, $shipment->delete($user, 0, true), $shipment->errorsToString());
			$this->assertEquals(10.0, $this->getRealStock($productIds['component'], $warehouseId));
		} finally {
			foreach ($saved as $key => $value) {
				$conf->global->$key = $value;
			}
		}
	}

	/**
	 * A line of a product with lots posted to POST /shipments must keep its lots, as with the shipment card:
	 * the lots arrive as plain arrays and were silently dropped with the line.
	 *
	 * @return void
	 */
	public function testApiPostRecordsBatchLines()
	{
		global $user, $db;

		// Loading the Restler autoloader unregisters all the other ones, including the one PHPUnit needs: restore them.
		$autoloaders = spl_autoload_functions();
		require_once DOL_DOCUMENT_ROOT.'/api/class/api.class.php';
		require_once DOL_DOCUMENT_ROOT.'/api/class/api_access.class.php';
		spl_autoload_unregister(Luracast\Restler\AutoLoader::instance());
		foreach ($autoloaders as $autoloader) {
			spl_autoload_register($autoloader);
		}
		require_once DOL_DOCUMENT_ROOT.'/expedition/class/api_shipments.class.php';
		require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
		require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';

		$this->configureStandaloneShipmentStock(true, false, true);
		$productId = $this->createShipmentStockProduct(__FUNCTION__, 1);
		$warehouseId = $this->createShipmentStockWarehouse(__FUNCTION__);
		$batch = 'LOT'.mt_rand(100000, 999999);

		$movement = new MouvementStock($db);
		$this->assertGreaterThan(0, $movement->reception($user, $productId, $warehouseId, 10, 0, 'ExpeditionTest lot stock', '', '', $batch));
		$resql = $db->query("SELECT pb.rowid FROM ".$db->prefix()."product_batch as pb INNER JOIN ".$db->prefix()."product_stock as ps ON ps.rowid = pb.fk_product_stock WHERE ps.fk_product = ".((int) $productId)." AND ps.fk_entrepot = ".((int) $warehouseId)." AND pb.batch = '".$db->escape($batch)."'");
		$this->assertNotFalse($resql, (string) $db->lasterror());
		$obj = $db->fetch_object($resql);
		$this->assertNotEmpty($obj, 'The lot must be in stock');
		$stockBatchId = (int) $obj->rowid;

		$soc = new Societe($db);
		$soc->name = 'ExpeditionTest '.$batch;
		$socId = $soc->create($user);
		$this->assertGreaterThan(0, $socId, $soc->errorsToString());
		$order = new Commande($db);
		$order->socid = $socId;
		$order->date = dol_now();
		$orderId = $order->create($user);
		$this->assertGreaterThan(0, $orderId, $order->errorsToString());
		$lineId = $order->addline('Product with lots', 0, 3, 0, 0, 0, $productId);
		$this->assertGreaterThan(0, $lineId, $order->errorsToString());

		DolibarrApiAccess::$user = $user;
		$api = new Shipments();
		$shipmentId = $api->post(array(
			'socid' => $socId,
			'origin_id' => $orderId,
			'origin_type' => 'commande',
			'lines' => array(
				array('origin_id' => $orderId, 'fk_elementdet' => $lineId, 'element_type' => 'commande', 'qty' => 3, 'rang' => 1,
					'detail_batch' => array(array('fk_origin_stock' => $stockBatchId, 'qty' => 3))),
			),
		));
		$this->assertGreaterThan(0, $shipmentId);

		$resql = $db->query("SELECT ed.fk_entrepot, ed.qty, eb.batch, eb.qty as batch_qty, eb.fk_origin_stock FROM ".$db->prefix()."expeditiondet as ed INNER JOIN ".$db->prefix()."expeditiondet_batch as eb ON eb.fk_expeditiondet = ed.rowid WHERE ed.fk_expedition = ".((int) $shipmentId));
		$this->assertNotFalse($resql, (string) $db->lasterror());
		$obj = $db->fetch_object($resql);
		$this->assertNotEmpty($obj, 'The line and its lot must be recorded');
		$this->assertEquals($warehouseId, (int) $obj->fk_entrepot, 'The warehouse comes from the lot');
		$this->assertEquals(3.0, (float) $obj->qty);
		$this->assertEquals($batch, $obj->batch);
		$this->assertEquals(3.0, (float) $obj->batch_qty);
		$this->assertEquals($stockBatchId, (int) $obj->fk_origin_stock);

		$shipment = new Expedition($db);
		$this->assertGreaterThan(0, $shipment->fetch($shipmentId), $shipment->errorsToString());
		$this->assertGreaterThan(0, $shipment->valid($user), $shipment->errorsToString());
		$this->assertEquals(7.0, $this->getRealStock($productId, $warehouseId));

		// An unknown stock record is refused instead of creating a shipment without the line
		try {
			$api = new Shipments();
			$api->post(array(
				'socid' => $socId,
				'origin_id' => $orderId,
				'origin_type' => 'commande',
				'lines' => array(
					array('origin_id' => $orderId, 'fk_elementdet' => $lineId, 'element_type' => 'commande', 'qty' => 1, 'rang' => 1,
						'detail_batch' => array(array('fk_origin_stock' => 999999999, 'qty' => 1))),
				),
			));
			$this->fail('A lot that is not in stock must be refused');
		} catch (Luracast\Restler\RestException $e) {
			$this->assertEquals(400, $e->getCode());
		}
	}

	/**
	 * A kit shipped through POST /orders/{id}/shipment/{warehouse_id} must be stored as the shipment card stores it,
	 * so that validating then deleting the shipment gives back the stock of its components.
	 *
	 * @return void
	 */
	public function testApiCreateOrderShipmentExpandsKitAndDeleteRestoresComponentStock()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// Loading the Restler autoloader unregisters all the other ones, including the one PHPUnit needs: restore them.
		$autoloaders = spl_autoload_functions();
		require_once DOL_DOCUMENT_ROOT.'/api/class/api.class.php';
		require_once DOL_DOCUMENT_ROOT.'/api/class/api_access.class.php';
		spl_autoload_unregister(Luracast\Restler\AutoLoader::instance());
		foreach ($autoloaders as $autoloader) {
			spl_autoload_register($autoloader);
		}
		require_once DOL_DOCUMENT_ROOT.'/commande/class/api_orders.class.php';
		require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
		require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
		require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
		require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';

		$saved = array();
		foreach (array('PRODUIT_SOUSPRODUITS' => 1, 'STOCK_CALCULATE_ON_SHIPMENT' => 1, 'PRODUIT_SOUSPRODUITS_ALSO_ENABLE_PARENT_STOCK_MOVE' => 0, 'STOCK_SUPPORTS_SERVICES' => 0, 'SHIPMENT_SUPPORTS_SERVICES' => 0) as $key => $value) {
			$saved[$key] = getDolGlobalString($key);
			$conf->global->$key = $value;
		}

		try {
			$suffix = 'ApiOrderKit'.mt_rand(100000, 999999);

			$soc = new Societe($db);
			$soc->name = 'ExpeditionTest '.$suffix;
			$socId = $soc->create($user);
			$this->assertGreaterThan(0, $socId, $soc->errorsToString());

			$warehouse = new Entrepot($db);
			$warehouse->ref = 'WH'.$suffix;
			$warehouse->label = 'WH'.$suffix;
			$warehouse->statut = 1;
			$warehouseId = $warehouse->create($user);
			$this->assertGreaterThan(0, $warehouseId, $warehouse->errorsToString());

			$productIds = array();
			foreach (array('kit' => Product::TYPE_PRODUCT, 'component' => Product::TYPE_PRODUCT, 'plain' => Product::TYPE_PRODUCT, 'service' => Product::TYPE_SERVICE) as $code => $type) {
				$product = new Product($db);
				$product->ref = $code.$suffix;
				$product->label = $code.$suffix;
				$product->type = $type;
				$productIds[$code] = $product->create($user);
				$this->assertGreaterThan(0, $productIds[$code], $product->errorsToString());
			}

			$kit = new Product($db);
			$this->assertGreaterThan(0, $kit->add_sousproduit($productIds['kit'], $productIds['component'], 3, 1));

			$movement = new MouvementStock($db);
			$this->assertGreaterThan(0, $movement->reception($user, $productIds['component'], $warehouseId, 10, 0, 'ExpeditionTest initial stock'));
			$this->assertGreaterThan(0, $movement->reception($user, $productIds['plain'], $warehouseId, 10, 0, 'ExpeditionTest initial stock'));

			$order = new Commande($db);
			$order->socid = $socId;
			$order->date = dol_now();
			$orderId = $order->create($user);
			$this->assertGreaterThan(0, $orderId, $order->errorsToString());
			$this->assertGreaterThan(0, $order->addline('Kit', 0, 2, 0, 0, 0, $productIds['kit']), $order->errorsToString());
			$this->assertGreaterThan(0, $order->addline('Plain', 0, 1, 0, 0, 0, $productIds['plain']), $order->errorsToString());
			$this->assertGreaterThan(0, $order->addline('Service', 0, 1, 0, 0, 0, $productIds['service'], 0, 0, 0, 'HT', 0, '', '', Product::TYPE_SERVICE), $order->errorsToString());

			DolibarrApiAccess::$user = $user;
			$api = new Orders();
			$shipmentId = $api->createOrderShipment($orderId, $warehouseId);
			$this->assertGreaterThan(0, $shipmentId);

			$lines = array();
			$resql = $db->query("SELECT rowid, fk_product, fk_parent, fk_entrepot, qty FROM ".$db->prefix()."expeditiondet WHERE fk_expedition = ".((int) $shipmentId)." ORDER BY rowid");
			$this->assertNotFalse($resql, (string) $db->lasterror());
			while ($obj = $db->fetch_object($resql)) {
				$lines[(int) $obj->fk_product] = $obj;
			}
			$this->assertCount(4, $lines);

			// The kit is stored without warehouse and its component carries the warehouse, as with the shipment card
			$this->assertEmpty($lines[$productIds['kit']]->fk_parent);
			$this->assertEmpty($lines[$productIds['kit']]->fk_entrepot);
			$this->assertEquals(2.0, (float) $lines[$productIds['kit']]->qty);
			$this->assertEquals($lines[$productIds['kit']]->rowid, $lines[$productIds['component']]->fk_parent);
			$this->assertEquals($warehouseId, $lines[$productIds['component']]->fk_entrepot);
			$this->assertEquals(6.0, (float) $lines[$productIds['component']]->qty);

			// The other lines keep their warehouse. A service line stays on the shipment as before.
			$this->assertEquals($warehouseId, $lines[$productIds['plain']]->fk_entrepot);
			$this->assertEquals(1.0, (float) $lines[$productIds['plain']]->qty);
			$this->assertEmpty($lines[$productIds['service']]->fk_parent);
			$this->assertEquals($warehouseId, $lines[$productIds['service']]->fk_entrepot);

			$shipment = new Expedition($db);
			$this->assertGreaterThan(0, $shipment->fetch($shipmentId), $shipment->errorsToString());
			$this->assertGreaterThan(0, $shipment->valid($user), $shipment->errorsToString());
			$this->assertEquals(4.0, $this->getRealStock($productIds['component'], $warehouseId));
			$this->assertEquals(9.0, $this->getRealStock($productIds['plain'], $warehouseId));

			$this->assertGreaterThan(0, $shipment->delete($user, 0, true), $shipment->errorsToString());
			$this->assertEquals(10.0, $this->getRealStock($productIds['component'], $warehouseId));
			$this->assertEquals(10.0, $this->getRealStock($productIds['plain'], $warehouseId));
		} finally {
			foreach ($saved as $key => $value) {
				$conf->global->$key = $value;
			}
		}
	}

	/**
	 * Return the real stock of a product in a warehouse
	 *
	 * @param	int		$productId		Id of product
	 * @param	int		$warehouseId	Id of warehouse
	 * @return	float					Real stock
	 */
	private function getRealStock($productId, $warehouseId)
	{
		global $db;

		$resql = $db->query("SELECT reel FROM ".$db->prefix()."product_stock WHERE fk_product = ".((int) $productId)." AND fk_entrepot = ".((int) $warehouseId));
		$this->assertNotFalse($resql, (string) $db->lasterror());
		$obj = $db->fetch_object($resql);

		return $obj ? (float) $obj->reel : 0.0;
	}
}
