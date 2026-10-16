<?php
/* Copyright (C) 2026 Braito <braito4@hotmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file test/phpunit/SupplierOrdersHeaderApiTest.php
 * \ingroup test
 * \brief Supplier order header update results without database writes.
 */

if (!defined('DOL_DOCUMENT_ROOT')) {
	define('DOL_DOCUMENT_ROOT', __DIR__.'/../../htdocs');
}
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/api/class/api.class.php';
require_once DOL_DOCUMENT_ROOT.'/api/class/api_access.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/api_supplier_orders.class.php';

/**
 * Refresh the response only when the core reports a successful update.
 */
class SupplierOrdersHeaderApiTest extends PHPUnit\Framework\TestCase
{
	/** @var User|null Previous API user */
	private $previousUser;

	/** @return void */
	protected function setUp(): void
	{
		$this->previousUser = DolibarrApiAccess::$user;
		$user = $this->createMock(User::class);
		$user->method('hasRight')->willReturn(true);
		DolibarrApiAccess::$user = $user;
	}

	/** @return void */
	protected function tearDown(): void
	{
		DolibarrApiAccess::$user = $this->previousUser;
	}

	/** @return void */
	public function testSuccessfulUpdateReturnsTheRefreshedOrder()
	{
		foreach (array(1, 2) as $result) {
			$order = $this->createMock(CommandeFournisseur::class);
			$order->id = 42;
			$order->method('fetch')->willReturn(1);
			$order->expects($this->once())->method('update')->with(DolibarrApiAccess::$user)->willReturn($result);
			$api = new SupplierOrdersHeaderApiFixture($order);
			$this->assertSame(array('id' => 42), $api->put(42, array('note_private' => 'Updated note')));
			$this->assertSame(1, $api->getCalls);
			$this->assertSame('Updated note', $order->note_private);
		}
	}

	/** @return void */
	public function testFailedUpdateDoesNotReturnARefreshedOrder()
	{
		foreach (array(0, -1, -2) as $result) {
			$order = $this->createMock(CommandeFournisseur::class);
			$order->id = 42;
			$order->method('fetch')->willReturn(1);
			$order->expects($this->once())->method('update')->with(DolibarrApiAccess::$user)->willReturn($result);
			$api = new SupplierOrdersHeaderApiFixture($order);
			try {
				$api->put(42, array('note_private' => 'Updated note'));
				$this->fail('A failed update was reported as successful');
			} catch (Luracast\Restler\RestException $exception) {
				$this->assertSame(500, $exception->getCode());
				$this->assertStringContainsString('Error updating supplier order', $exception->getMessage());
			}
			$this->assertSame(0, $api->getCalls);
		}
	}
}

/**
 * Replace resource authorization and capture response refreshes.
 */
class SupplierOrdersHeaderApiFixture extends SupplierOrders
{
	/** @var int Number of response refreshes */
	public $getCalls = 0;

	/**
	 * @param CommandeFournisseur $order Mock order
	 */
	public function __construct($order)
	{
		$this->order = $order;
	}

	/**
	 * @param int $id Order ID
	 * @return array{id:int} Refreshed response
	 */
	public function get($id)
	{
		$this->getCalls++;
		return array('id' => $id);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * @param string $resource Resource
	 * @param mixed $resource_id ID
	 * @param string $dbtablename Table
	 * @param string $feature2 Permission
	 * @param string $dbt_keyfield Foreign key
	 * @param string $dbt_select ID field
	 * @param string $parenttableforentity Parent entity
	 * @return bool Access allowed in this fixture
	 */
	protected static function _checkAccessToResource($resource, $resource_id = 0, $dbtablename = '', $feature2 = '', $dbt_keyfield = 'fk_soc', $dbt_select = 'rowid', $parenttableforentity = '')
	{
		// phpcs:enable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
		return true;
	}
}
