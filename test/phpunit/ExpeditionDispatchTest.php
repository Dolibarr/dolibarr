<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 * Copyright (C) 2026       Frédéric France         <frederic.france@free.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       test/phpunit/ExpeditionDispatchTest.php
 * \ingroup    test
 * \brief      Unit tests for standalone shipment dispatch groups.
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
require_once DOL_DOCUMENT_ROOT.'/core/db/Database.interface.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/sendings.lib.php';

/**
 * Dispatch group validation using loaded shipment line fixtures.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class ExpeditionDispatchTest extends \PHPUnit\Framework\TestCase
{
	/** @var Conf|null Original configuration */
	private $savedConf;

	/**
	 * Enable standalone shipments without changing the suite configuration.
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setUp();
		global $conf;
		$this->savedConf = $conf;
		$conf = new Conf();
		$conf->global->SHIPMENT_STANDALONE = 1;
	}

	/**
	 * Restore the suite configuration.
	 *
	 * @return void
	 */
	protected function tearDown(): void
	{
		global $conf;
		$conf = $this->savedConf;
		parent::tearDown();
	}

	/**
	 * Build two independent groups for the same product and invalid allocations.
	 *
	 * @return Expedition Loaded standalone shipment
	 */
	private function createShipment()
	{
		$db = $this->createMock(Database::class);
		$db->expects($this->never())->method('query');
		$shipment = new Expedition($db);
		$shipment->id = 12;
		$shipment->origin_id = 0;
		$shipment->status = Expedition::STATUS_DRAFT;
		foreach (array(
			array(42, 12, 7, null),
			array(43, 12, 7, 42),
			array(44, 12, 7, 0),
			array(45, 12, 7, 44),
			array(46, 13, 7, 0),
			array(47, 12, 8, 42),
		) as $row) {
			$line = new ExpeditionLigne($db);
			$line->id = $row[0];
			$line->fk_expedition = $row[1];
			$line->fk_product = $row[2];
			$line->fk_parent = $row[3];
			$shipment->lines[] = $line;
		}
		return $shipment;
	}

	/**
	 * Existing and new allocations resolve to their own source line.
	 *
	 * @return void
	 */
	public function testResolveAllocationsWithinTheirSourceGroup()
	{
		$shipment = $this->createShipment();
		foreach (array(42, 43, -1, 0) as $lineId) {
			$this->assertSame($shipment->lines[0], shippingGetStandaloneDispatchSourceLine($shipment, 42, $lineId, 7));
		}
		foreach (array(44, 45, -1) as $lineId) {
			$this->assertSame($shipment->lines[2], shippingGetStandaloneDispatchSourceLine($shipment, 44, $lineId, 7));
		}
		// Moving the entire quantity to children must retain the empty source line.
		$shipment->lines[0]->qty = 0;
		$this->assertSame($shipment->lines[0], shippingGetStandaloneDispatchSourceLine($shipment, 42, 43, 7));
	}

	/**
	 * Reject foreign lines, other groups of the same product and kit components.
	 *
	 * @return void
	 */
	public function testRejectAllocationsOutsideTheirSourceGroup()
	{
		$shipment = $this->createShipment();
		foreach (array(
			array(42, 44, 7),
			array(42, 45, 7),
			array(42, 46, 7),
			array(42, 47, 7),
			array(42, 999, 7),
			array(42, -1, 8),
			array(42, -1, 0),
			array(43, -1, 7),
			array(46, -1, 7),
			array(999, -1, 7),
		) as $dispatch) {
			$this->assertNull(shippingGetStandaloneDispatchSourceLine($shipment, $dispatch[0], $dispatch[1], $dispatch[2]));
		}
	}

	/**
	 * Only an existing standalone draft shipment can be dispatched.
	 *
	 * @return void
	 */
	public function testRejectUnavailableStandaloneDispatch()
	{
		global $conf;
		foreach (array(
			array('id', 0),
			array('origin_id', 15),
			array('status', Expedition::STATUS_VALIDATED),
			array('status', Expedition::STATUS_CLOSED),
		) as $state) {
			$shipment = $this->createShipment();
			$shipment->{$state[0]} = $state[1];
			$this->assertNull(shippingGetStandaloneDispatchSourceLine($shipment, 42, 43, 7));
		}
		$shipment = $this->createShipment();
		$conf->global->SHIPMENT_STANDALONE = 0;
		$this->assertNull(shippingGetStandaloneDispatchSourceLine($shipment, 42, 43, 7));
	}

	/**
	 * Deleting the source removes its allocations without requiring virtual products.
	 * A failed allocation deletion must roll back and retain the source.
	 *
	 * @return void
	 */
	public function testDeleteSourceWithStandaloneAllocations()
	{
		global $conf;
		$conf->global->PRODUIT_SOUSPRODUITS = 0;
		foreach (array(false, true) as $fail) {
			// setMethods() and not onlyMethods()/addMethods(): Travis still runs the test suite with PHPUnit 7.5
			$db = $this->getMockBuilder(Database::class)
				->setMethods(array_merge(get_class_methods(Database::class), array('prefix')))
				->getMock();
			$db->method('prefix')->willReturn(MAIN_DB_PREFIX);
			$db->method('lasterror')->willReturn('Test allocation deletion failure');
			$db->expects($this->once())->method('begin');
			$db->expects($fail ? $this->never() : $this->once())->method('commit');
			$db->expects($fail ? $this->once() : $this->never())->method('rollback');
			$deletedIds = array();
			$db->expects($this->exactly($fail ? 1 : 3))->method('query')->willReturnCallback(function ($sql) use ($fail, &$deletedIds) {
				$this->assertSame(1, preg_match('/^DELETE FROM '.MAIN_DB_PREFIX.'expeditiondet WHERE rowid = ([0-9]+)$/', $sql, $matches));
				$deletedIds[] = (int) $matches[1];
				return !$fail;
			});
			$line = $this->getMockBuilder(ExpeditionLigne::class)
				->setConstructorArgs(array($db))
				->setMethods(array('findAllChild', 'deleteExtraFields'))
				->getMock();
			$line->id = 42;
			$line->fk_expedition = 12;
			$line->element_type = 'shipping';
			$line->expects($this->once())->method('findAllChild')->willReturnCallback(function ($lineId, &$list) {
				$this->assertSame(42, $lineId);
				$list = array(42 => array(43, 44));
				return 1;
			});
			$line->expects($fail ? $this->never() : $this->once())->method('deleteExtraFields')->willReturn(1);
			$this->assertSame($fail ? -1 : 1, $line->delete(null, 1));
			$this->assertSame($fail ? array(43) : array(43, 44, 42), $deletedIds);
		}
	}
}
