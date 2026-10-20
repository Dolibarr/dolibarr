<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file test/phpunit/ExpeditionStockPreviewTest.php
 * \ingroup test
 * \brief Shipment warehouse suggestions with database fixtures.
 */

if (!defined('DOL_DOCUMENT_ROOT')) {
	define('DOL_DOCUMENT_ROOT', dirname(__FILE__).'/../../htdocs');
}
if (!defined('DOL_DATA_ROOT')) {
	define('DOL_DATA_ROOT', sys_get_temp_dir());
}
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/conf.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/db/Database.interface.php';
require_once DOL_DOCUMENT_ROOT.'/core/db/DoliDB.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/db/mysqli.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/sendings.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class ExpeditionStockPreviewTest extends \PHPUnit\Framework\TestCase
{
	/** @var array<string,mixed> Globals to restore */
	private $savedGlobals = array();

	/** @return void */
	protected function setUp(): void
	{
		parent::setUp();
		foreach (array('conf', 'user') as $name) {
			$this->savedGlobals[$name] = $GLOBALS[$name] ?? null;
		}
		global $conf, $user;
		$conf = new Conf();
		$conf->entity = 1;
		$conf->modules = array('stock' => 1);
		$user = new User(null);
	}

	/** @return void */
	protected function tearDown(): void
	{
		foreach ($this->savedGlobals as $name => $value) {
			$GLOBALS[$name] = $value;
		}
		parent::tearDown();
	}

	/**
	 * Build an accessible draft with warehouse stock, using the native selector query.
	 *
	 * @param array<int,float> $stocks Physical quantities by warehouse
	 * @return array{Expedition,Product}
	 */
	private function fixtures($stocks)
	{
		$db = $this->createMock(DoliDBMysqli::class);
		$db->method('prefix')->willReturn('llx_');
		$db->method('sanitize')->willReturnArgument(0);
		$db->method('query')->willReturnCallback(function ($sql) {
			$this->assertStringContainsString('e.entity IN (1)', $sql);
			$this->assertStringContainsString('e.statut IN (1)', $sql);
			$this->assertStringContainsString('ps.fk_product = 42', $sql);
			return true;
		});
		$db->method('num_rows')->willReturn(count($stocks));
		$rows = array();
		foreach ($stocks as $id => $stock) {
			$rows[] = (object) array('rowid' => $id, 'label' => 'Warehouse '.$id, 'description' => '', 'fk_parent' => 0, 'stock' => $stock);
		}
		$db->method('fetch_object')->willReturnCallback(function () use (&$rows) {
			return array_shift($rows);
		});
		$shipment = new Expedition($db);
		$shipment->id = 12;
		$shipment->status = Expedition::STATUS_DRAFT;
		$product = new Product($db);
		$product->id = 42;
		$product->type = Product::TYPE_PRODUCT;
		$product->stockable_product = Product::ENABLED_STOCK;
		return array($shipment, $product);
	}

	/**
	 * Prefer the native default only when it can supply the requested quantity.
	 *
	 * @return void
	 */
	public function testDefaultAndAvailableStock()
	{
		global $conf;
		$conf->global->MAIN_DEFAULT_WAREHOUSE = 1;
		foreach (array(array(2.5, 1), array(7.0, 2)) as $case) {
			list($shipment, $product) = $this->fixtures(array(1 => 5.0, 2 => 10.0));
			$result = shippingGetStockPreview($shipment, $product, $case[0]);
			$this->assertSame($case[1], $result['selected']);
		}
	}

	/** @return void */
	public function testDraftAllocationsAndDecimalQuantities()
	{
		list($shipment, $product) = $this->fixtures(array(1 => 5.0, 2 => 10.0));
		$line = new ExpeditionLigne($shipment->db);
		$line->fk_product = 42;
		$line->entrepot_id = 2;
		$line->qty = 9;
		$shipment->lines = array($line);
		$result = shippingGetStockPreview($shipment, $product, 2.5);
		$this->assertSame(1, $result['selected']);
		$this->assertSame(5.0, $result['stock_available']);
		$this->assertSame(2.5, $result['stock_after']);
		$this->assertFalse($result['insufficient']);
	}

	/** @return void */
	public function testManualSelectionAndUnavailableWarehouse()
	{
		foreach (array(1, 99, 0) as $warehouse) {
			list($shipment, $product) = $this->fixtures(array(1 => 1.0, 2 => 10.0));
			$result = shippingGetStockPreview($shipment, $product, 2.5, $warehouse, true);
			$this->assertSame($warehouse == 1 ? 1 : 0, $result['selected']);
			$this->assertSame($warehouse == 1 ? -1.5 : null, $result['stock_after']);
			$this->assertSame($warehouse == 1, $result['insufficient']);
		}
	}

	/** @return void */
	public function testUserDefaultAndSingleWarehouseFallback()
	{
		global $conf, $user;
		$conf->global->MAIN_DEFAULT_WAREHOUSE = 1;
		$conf->global->MAIN_DEFAULT_WAREHOUSE_USER = 1;
		$user->fk_warehouse = 2;
		list($shipment, $product) = $this->fixtures(array(1 => 10.0, 2 => 5.0));
		$this->assertSame(2, shippingGetStockPreview($shipment, $product, 2.5)['selected']);
		list($shipment, $product) = $this->fixtures(array(3 => 0.0));
		$result = shippingGetStockPreview($shipment, $product, 2.5);
		$this->assertSame(3, $result['selected']);
		$this->assertTrue($result['insufficient']);
	}

	/** @return void */
	public function testInvalidDefaultsAndNoStock()
	{
		global $conf;
		$conf->global->MAIN_DEFAULT_WAREHOUSE = 99;
		foreach (array(array(), array(1 => 0.0, 2 => 1.0)) as $stocks) {
			list($shipment, $product) = $this->fixtures($stocks);
			$result = shippingGetStockPreview($shipment, $product, 2.5);
			$this->assertSame(0, $result['selected']);
			$this->assertNull($result['stock_available']);
		}
	}

	/** @return void */
	public function testServicesAndInvalidQuantitiesHaveNoPreview()
	{
		foreach (array(0, -1, 2.5) as $qty) {
			list($shipment, $product) = $this->fixtures(array());
			$product->type = Product::TYPE_SERVICE;
			$this->assertNull(shippingGetStockPreview($shipment, $product, (float) $qty)['stock_after']);
			$product->type = Product::TYPE_PRODUCT;
			$product->stockable_product = Product::DISABLED_STOCK;
			$this->assertNull(shippingGetStockPreview($shipment, $product, (float) $qty)['stock_after']);
		}
	}

	/** @return void */
	public function testStockMovedAtDispatchHasNoDraftProjection()
	{
		global $conf;
		$conf->global->STOCK_CALCULATE_ON_SHIPMENT_DISPATCH_ORDER = 1;
		list($shipment, $product) = $this->fixtures(array());
		$shipment->db->expects($this->never())->method('query');
		$this->assertNull(shippingGetStockPreview($shipment, $product, 2.5)['stock_after']);
	}
}
