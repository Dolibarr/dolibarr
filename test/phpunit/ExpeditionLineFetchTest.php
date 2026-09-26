<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       test/phpunit/ExpeditionLineFetchTest.php
 * \ingroup    test
 * \brief      Unit tests for shipment line loading.
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

/**
 * Shipment line loading tests using database result fixtures.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class ExpeditionLineFetchTest extends \PHPUnit\Framework\TestCase
{
	/** @var Conf|null Original configuration */
	private $savedConf;

	/** @var ExtraFields|null Original extrafields cache */
	private $savedExtrafields;

	/**
	 * Isolate configuration and extrafields from the rest of the suite.
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setUp();
		global $conf, $extrafields;
		$this->savedConf = $conf;
		$this->savedExtrafields = $extrafields;
		$conf = new Conf();
	}

	/**
	 * Restore the suite configuration and extrafields cache.
	 *
	 * @return void
	 */
	protected function tearDown(): void
	{
		global $conf, $extrafields;
		$conf = $this->savedConf;
		$extrafields = $this->savedExtrafields;
		parent::tearDown();
	}

	/**
	 * Build a stored shipment line, including nullable relationships.
	 *
	 * @param int|null $warehouseId Warehouse id
	 * @return stdClass Database result row
	 */
	private function createLineRow($warehouseId)
	{
		return (object) array(
			'rowid' => 42,
			'fk_expedition' => 12,
			'fk_entrepot' => $warehouseId,
			'fk_product' => 7,
			'fk_parent' => 41,
			'description' => 'Shipment line',
			'fk_unit' => null,
			'fk_element' => 23,
			'fk_elementdet' => 24,
			'element_type' => 'commande',
			'qty' => 2,
			'rang' => 1,
			'extraparams' => '{"source":"test"}',
		);
	}

	/**
	 * Direct loading restores product, parent and both warehouse properties.
	 * A subsequent fetch must also clear relationships stored as NULL.
	 *
	 * @return void
	 */
	public function testFetchRestoresLineRelationships()
	{
		$row = $this->createLineRow(3);
		$unlinkedRow = $this->createLineRow(null);
		$unlinkedRow->fk_product = null;
		$unlinkedRow->fk_parent = null;
		$unlinkedRow->extraparams = null;

		$db = $this->createMock(Database::class);
		$db->expects($this->exactly(2))->method('query')->willReturnCallback(function ($sql) {
			$this->assertStringContainsString('ed.fk_product', $sql);
			$this->assertStringContainsString('ed.fk_parent', $sql);
			$this->assertStringContainsString('WHERE ed.rowid = 42', $sql);
			return true;
		});
		$db->expects($this->exactly(2))->method('fetch_object')->willReturnOnConsecutiveCalls($row, $unlinkedRow);
		$db->expects($this->exactly(2))->method('free');

		$line = new ExpeditionLigne($db);
		foreach (array($row, $unlinkedRow) as $expected) {
			$this->assertSame(1, $line->fetch(42));
			$this->assertSame($expected->fk_product, $line->fk_product);
			$this->assertSame($expected->fk_parent, $line->fk_parent);
			$this->assertSame($expected->fk_entrepot, $line->entrepot_id);
			$this->assertSame($expected->fk_entrepot, $line->fk_entrepot);
			$this->assertSame($expected->element_type, $line->element_type);
			$this->assertSame(42, $line->id);
			$this->assertSame(12, $line->fk_expedition);
			$this->assertSame(2, $line->qty);
			$this->assertSame($expected->extraparams ? array('source' => 'test') : array(), $line->extraparams);
		}
	}

	/**
	 * Simple shipments expose the native origin type and warehouse properties.
	 *
	 * @return void
	 */
	public function testFetchLinesFreeRestoresWarehouseAndOriginType()
	{
		global $extrafields;
		$row = $this->createLineRow(3);
		$unassignedRow = $this->createLineRow(null);
		$unassignedRow->rowid = 43;
		$unassignedRow->fk_parent = null;
		$unassignedRow->element_type = 'shipping';
		$unassignedRow->fk_element = null;
		$unassignedRow->fk_elementdet = null;

		$db = $this->createMock(Database::class);
		$db->expects($this->once())->method('query')->with($this->stringContains('WHERE ed.fk_expedition = 12'))->willReturn(true);
		$db->expects($this->once())->method('num_rows')->willReturn(2);
		$db->expects($this->exactly(2))->method('fetch_object')->willReturnOnConsecutiveCalls($row, $unassignedRow);
		$db->expects($this->once())->method('free');
		$extrafields = new ExtraFields($db);
		$extrafields->attributes['expeditiondet'] = array('loaded' => 1, 'label' => array());

		$shipment = new Expedition($db);
		$shipment->id = 12;
		$this->assertSame(1, $shipment->fetch_lines_free());
		$this->assertCount(2, $shipment->lines);
		foreach (array($row, $unassignedRow) as $index => $expected) {
			$line = $shipment->lines[$index];
			$this->assertSame($expected->rowid, $line->id);
			$this->assertSame($expected->fk_product, $line->fk_product);
			$this->assertSame($expected->fk_parent, $line->fk_parent);
			$this->assertSame($expected->fk_entrepot, $line->entrepot_id);
			$this->assertSame($expected->fk_entrepot, $line->fk_entrepot);
			$this->assertSame($expected->element_type, $line->element_type);
			$this->assertSame($expected->fk_elementdet, $line->fk_elementdet);
		}
	}

	/**
	 * Editing keeps the stored product and defaults to the stored warehouse.
	 * Explicit warehouse changes and removals are written by the native line method.
	 *
	 * @return void
	 */
	public function testUpdateFreeLinePreservesProductAndHandlesWarehouse()
	{
		global $extrafields;
		foreach (array(null, 5, 0) as $warehouse) {
			$row = $this->createLineRow(3);
			$row->element_type = 'shipping';
			$row->fk_elementdet = null;
			$row->fk_parent = null;
			$expectedWarehouse = $warehouse === null ? 3 : $warehouse;
			$db = $this->createMock(Database::class);
			$db->method('escape')->willReturnArgument(0);
			$db->expects($this->exactly(3))->method('query')->willReturnCallback(function ($sql) use ($expectedWarehouse) {
				if (strpos($sql, 'UPDATE ') === 0) {
					$this->assertStringContainsString('fk_entrepot = '.($expectedWarehouse ?: 'null'), $sql);
					$this->assertStringContainsString('qty = 4', $sql);
					$this->assertStringContainsString('WHERE rowid = 42', $sql);
				} else {
					$this->assertStringContainsString('SELECT ', $sql);
					$this->assertStringContainsString('expeditiondet', $sql);
				}
				return true;
			});
			$db->expects($this->exactly(2))->method('fetch_object')->willReturn($row);
			$db->expects($this->exactly(2))->method('begin');
			$db->expects($this->exactly(2))->method('commit');
			$db->expects($this->never())->method('rollback');
			$extrafields = new ExtraFields($db);
			$extrafields->attributes['expeditiondet'] = array('loaded' => 1, 'label' => array());

			$shipment = new Expedition($db);
			$shipment->id = 12;
			$shipment->status = Expedition::STATUS_DRAFT;
			if ($warehouse === null) {
				$result = $shipment->updatelinefree(42, 4, 'shipping', 0, 0, 1, 'Updated line', 0, 1);
			} else {
				// Even a different supplied product must not change which product is checked.
				$result = $shipment->updatelinefree(42, 4, 'shipping', 99, 0, 1, 'Updated line', 0, 1, array(), $warehouse);
			}
			$this->assertSame(1, $result);
			$this->assertSame(7, $shipment->line->fk_product);
			$this->assertSame($expectedWarehouse, $shipment->line->entrepot_id);
			$this->assertSame($expectedWarehouse, $shipment->line->fk_entrepot);
			$this->assertSame(3, $shipment->line->oldline->entrepot_id);
		}
	}

	/**
	 * A failed read must not reach the update or lose the database error.
	 *
	 * @return void
	 */
	public function testUpdateFreeLineStopsOnFetchFailure()
	{
		$db = $this->createMock(Database::class);
		$db->expects($this->once())->method('query')->willReturn(false);
		$db->method('lasterror')->willReturn('Shipment line read failed');
		$db->expects($this->once())->method('begin');
		$db->expects($this->once())->method('rollback');
		$db->expects($this->never())->method('commit');
		$shipment = new Expedition($db);
		$shipment->id = 12;
		$shipment->status = Expedition::STATUS_DRAFT;
		$this->assertSame(-1, $shipment->updatelinefree(42, 4, 'shipping', 0, 0, 1, 'Updated line', 0, 1));
		$this->assertSame('Shipment line read failed', $shipment->error);
	}
}
