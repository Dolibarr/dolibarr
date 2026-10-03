<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       test/phpunit/ExpeditionCatalogLineTest.php
 * \ingroup    test
 * \brief      Unit tests for additional catalog lines on order shipments.
 */

// These unit tests need no configured Dolibarr installation or database connection.
if (!defined('DOL_DOCUMENT_ROOT')) {
	define('DOL_DOCUMENT_ROOT', dirname(__FILE__).'/../../htdocs');
}
if (!defined('DOL_DATA_ROOT')) {
	define('DOL_DATA_ROOT', sys_get_temp_dir());
}
if (!defined('MAIN_DB_PREFIX')) {
	define('MAIN_DB_PREFIX', 'llx_');
}
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/conf.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/db/Database.interface.php';
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';

require_once DOL_DOCUMENT_ROOT.'/core/lib/sendings.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/translate.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

/**
 * Catalog lines must coexist with grouped order lines without changing the order.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class ExpeditionCatalogLineTest extends \PHPUnit\Framework\TestCase
{
	/** @var array<string,mixed> Globals restored after each test */
	private $savedGlobals = array();

	/** @return void */
	protected function setUp(): void
	{
		parent::setUp();
		foreach (array('conf', 'extrafields', 'mysoc', 'langs', 'hookmanager', 'db') as $name) {
			$this->savedGlobals[$name] = $GLOBALS[$name] ?? null;
		}
		global $conf, $langs, $hookmanager;
		$conf = new Conf();
		$conf->currency = 'EUR';
		$conf->file->dol_document_root = array(DOL_DOCUMENT_ROOT);
		$langs = new Translate('', $conf);
		$langs->setDefaultLang('en_US');
		$hookmanager = new HookManager($this->createMock(Database::class));
	}

	/** @return void */
	protected function tearDown(): void
	{
		foreach ($this->savedGlobals as $name => $value) {
			$GLOBALS[$name] = $value;
		}
		parent::tearDown();
	}

	/** @return void */
	public function testCatalogAvailability()
	{
		global $conf;
		$shipment = new Expedition($this->createMock(Database::class));
		$shipment->id = 12;
		$shipment->status = Expedition::STATUS_DRAFT;
		$shipment->origin = 'commande';
		$shipment->origin_id = 23;
		$conf->global->SHIPMENT_STANDALONE = 1;
		$this->assertFalse(shippingCanAddCatalogLine($shipment));
		$conf->global->SHIPMENT_FROM_ORDER_CAN_ADD_LINE = 1;
		$this->assertTrue(shippingCanAddCatalogLine($shipment));
		$conf->global->STOCK_CALCULATE_ON_SHIPMENT_DISPATCH_ORDER = 1;
		$this->assertFalse(shippingCanAddCatalogLine($shipment));
		$conf->global->STOCK_CALCULATE_ON_SHIPMENT_DISPATCH_ORDER = 0;
		$shipment->origin = 'propal';
		$this->assertFalse(shippingCanAddCatalogLine($shipment));
		$shipment->origin = 'order';
		$this->assertTrue(shippingCanAddCatalogLine($shipment));
		$shipment->status = Expedition::STATUS_VALIDATED;
		$this->assertFalse(shippingCanAddCatalogLine($shipment));
		$shipment->status = Expedition::STATUS_DRAFT;
		$shipment->origin_id = 0;
		$this->assertTrue(shippingCanAddCatalogLine($shipment));
		$conf->global->SHIPMENT_STANDALONE = 0;
		$this->assertFalse(shippingCanAddCatalogLine($shipment));
		$shipment->id = 0;
		$this->assertFalse(shippingCanAddCatalogLine($shipment));
	}

	/**
	 * Build the row shared by the order and catalog query fixtures.
	 *
	 * @return stdClass
	 */
	private function row()
	{
		$row = (object) array_fill_keys(array(
			'product_type', 'fk_product_type', 'product_tobatch', 'weight_units', 'length_units', 'width_units', 'height_units', 'surface_units', 'volume_units',
			'total_ht', 'total_localtax1', 'total_localtax2', 'total_ttc', 'total_tva', 'fk_remise_except', 'fk_fournprice',
			'vat_src_code', 'tva_tx', 'localtax1_tx', 'localtax2_tx', 'localtax1_type', 'localtax2_type', 'info_bits', 'price', 'remise_percent', 'pa_ht',
			'fk_multicurrency', 'multicurrency_subprice', 'multicurrency_total_ht', 'multicurrency_total_tva', 'multicurrency_total_ttc', 'special_code',
		), 0);
		foreach (array('custom_label', 'product_barcode', 'multicurrency_code', 'extraparams', 'date_start', 'date_end') as $name) {
			$row->$name = '';
		}
		$row->rowid = $row->line_id = 42;
		$row->fk_expedition = 12;
		$row->fk_product = 7;
		$row->fk_entrepot = 3;
		$row->fk_parent = $row->fk_elementdet = $row->fk_element = null;
		$row->element_type = 'shipping';
		$row->qty = $row->qty_shipped = 2.5;
		$row->qty_asked = 8;
		$row->subprice = 10;
		$row->fk_unit = 2;
		$row->rang = 1;
		$row->product_ref = 'EXTRA-7';
		$row->product_label = 'Extra product';
		$row->product_desc = 'Catalog description';
		$row->description = 'Stored shipment description';
		$row->product_tosell = $row->product_tobuy = $row->stockable_product = 1;
		$row->weight = 2.0;
		$row->length = $row->width = $row->height = $row->surface = $row->volume = 1.0;
		return $row;
	}

	/**
	 * Load real grouped order lines followed by catalog lines using SELECT-only fixtures.
	 *
	 * @return void
	 */
	public function testMixedLinesPreserveOrderQuantitiesAndPdfData()
	{
		global $db, $extrafields, $mysoc, $langs;
		$ordered = $this->row();
		$ordered->rowid = $ordered->fk_elementdet = 31;
		$ordered->line_id = 40;
		$ordered->fk_element = 23;
		$ordered->element_type = 'commande';
		$ordered->qty_shipped = 1.0;
		$secondAllocation = clone $ordered;
		$secondAllocation->line_id = 41;
		$secondAllocation->fk_entrepot = 4;
		$secondAllocation->qty_shipped = 2.0;
		$extra = $this->row();
		$db = $this->createMock(Database::class);
		$queryCount = 0;
		$db->expects($this->exactly(2))->method('query')->willReturnCallback(function ($sql) use (&$queryCount) {
			$this->assertStringStartsWith('SELECT ', $sql);
			$this->assertStringContainsString('ed.fk_expedition = 12', $sql);
			if (++$queryCount == 2) {
				$this->assertStringContainsString('ed.fk_elementdet IS NULL OR ed.fk_elementdet = 0', $sql);
				$this->assertStringContainsString("ed.element_type = 'shipping'", $sql);
				$this->assertStringContainsString('ed.fk_parent IS NULL OR ed.fk_parent = 0', $sql);
			}
			return true;
		});
		$db->method('num_rows')->willReturnOnConsecutiveCalls(2, 1);
		$db->method('fetch_object')->willReturnOnConsecutiveCalls($ordered, $secondAllocation, $extra);
		$extrafields = new ExtraFields($db);
		$extrafields->attributes['expeditiondet'] = array('loaded' => 1, 'label' => array());
		$mysoc = new Societe($db);
		$mysoc->country_code = 'FR';
		$mysoc->country_id = 1;
		$shipment = new Expedition($db);
		$shipment->id = 12;
		$shipment->origin_id = 23;
		$shipment->thirdparty = new Societe($db);
		$this->assertSame(1, $shipment->fetch_lines());
		$this->assertCount(2, $shipment->lines);
		$this->assertEquals(8, $shipment->lines[0]->qty_asked);
		$this->assertEquals(3, $shipment->lines[0]->qty_shipped);
		$this->assertCount(2, $shipment->lines[0]->details_entrepot);
		$this->assertEquals(30, $shipment->total_ht);
		$line = $shipment->lines[1];
		$this->assertSame(42, $line->id);
		$this->assertSame('EXTRA-7', $line->product_ref);
		$this->assertSame('Extra product', $line->product_label);
		$this->assertSame('Stored shipment description', $line->desc);
		$this->assertSame(3, $line->entrepot_id);
		$this->assertSame(2, $line->fk_unit);
		$this->assertSame(2.0, $line->weight);
		$this->assertSame(0, $line->subprice);
		$this->assertSame(0, $line->total_ht);
		$this->assertNull($line->fk_elementdet);
		$this->assertSame('0', pdf_getlineqty_asked($shipment, 1, $langs));
		$this->assertSame('2.5', pdf_getlineqty_shipped($shipment, 1, $langs));
		$totals = $shipment->getTotalWeightVolume();
		$this->assertEquals(8, $totals['ordered']);
		$this->assertEquals(5.5, $totals['toship']);
		$this->assertEquals(11, $totals['weight']);
		$this->assertEquals(5.5, $totals['volume']);
	}

	/** @return void */
	public function testCatalogReadFailureIsNotReportedAsCompleteShipment()
	{
		$db = $this->createMock(Database::class);
		$db->expects($this->exactly(2))->method('query')->willReturnOnConsecutiveCalls(true, false);
		$db->method('num_rows')->willReturn(0);
		$db->method('error')->willReturn('Catalog read failed');
		$shipment = new Expedition($db);
		$shipment->id = 12;
		$this->assertSame(-3, $shipment->fetch_lines());
		$this->assertSame('Catalog read failed', $shipment->error);
		$this->assertSame(array(), $shipment->lines);
	}
}
