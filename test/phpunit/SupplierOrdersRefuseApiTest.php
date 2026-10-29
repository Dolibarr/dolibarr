<?php
/* Copyright (C) 2026 William Mead <william@m34d.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file test/phpunit/SupplierOrdersRefuseApiTest.php
 * \ingroup test
 * \brief Supplier order refusal through the REST API, without database writes.
 */

global $conf,$user,$langs,$db,$mysoc;
if (!defined('NOREQUIREDB')) {
	define('NOREQUIREDB', '1');
}
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/api/class/api.class.php';
// Restler unregisters existing loaders; keep PHPUnit's Composer loader available.
call_user_func(
	/** @return void */
	static function () {
		$autoloaders = spl_autoload_functions();
		try {
			require_once DOL_DOCUMENT_ROOT.'/api/class/api_access.class.php';
			require_once DOL_DOCUMENT_ROOT.'/fourn/class/api_supplier_orders.class.php';
		} finally {
			foreach (array_reverse($autoloaders) as $autoloader) {
				if (!in_array($autoloader, spl_autoload_functions(), true)) {
					spl_autoload_register($autoloader, true, true);
				}
			}
		}
	}
);

/**
 * Exercise POST {id}/refuse using an order whose persistence is mocked.
 */
class SupplierOrdersRefuseApiTest extends CommonClassTest
{
	/** @var User|null Previous API user */
	private $previousUser;

	/** @var CommandeFournisseur Mock order */
	private $order;

	/** @var SupplierOrderRefuseApiFixture API fixture */
	private $api;

	/** @return void */
	protected function setUp(): void
	{
		parent::setUp();

		$this->previousUser = DolibarrApiAccess::$user;
		$actingUser = $this->createMock(User::class);
		$actingUser->method('hasRight')->willReturn(true);
		DolibarrApiAccess::$user = $actingUser;
		SupplierOrderRefuseApiFixture::$accessAllowed = true;
		$this->order = $this->createMock(CommandeFournisseur::class);
		$this->order->method('fetch')->willReturn(1);
		$this->order->id = 42;
		$this->order->ref = 'PO2610-0001';
		$this->order->status = CommandeFournisseur::STATUS_VALIDATED;
		$this->api = new SupplierOrderRefuseApiFixture($this->order);
	}

	/** @return void */
	protected function tearDown(): void
	{
		DolibarrApiAccess::$user = $this->previousUser;
		SupplierOrderRefuseApiFixture::$accessAllowed = true;

		parent::tearDown();
	}

	/** @return void */
	public function testRefuseRefusesAValidatedOrder()
	{
		$this->order->expects($this->once())->method('refuse')->with(DolibarrApiAccess::$user)->willReturn(1);
		$result = $this->api->refuse(42);
		$this->assertSame(200, $result['success']['code']);
		$this->assertStringContainsString('PO2610-0001', $result['success']['message']);
		$this->assertNull($this->order->refuse_note);
	}

	/** @return void */
	public function testRefuseStoresTheReason()
	{
		$this->order->expects($this->once())->method('refuse')->willReturnCallback(function () {
			$this->assertSame('Prices too high', $this->order->refuse_note);
			return 1;
		});
		$this->api->refuse(42, 'Prices too high');
	}

	/** @return void */
	public function testRefuseWithoutTriggerIsASuccess()
	{
		// CommandeFournisseur::refuse() returns the number of triggers run, which can be 0
		$this->order->expects($this->once())->method('refuse')->willReturn(0);
		$result = $this->api->refuse(42);
		$this->assertSame(200, $result['success']['code']);
	}

	/** @return void */
	public function testRefuseRequiresApprovePermission()
	{
		$actingUser = $this->createMock(User::class);
		$actingUser->method('hasRight')->willReturnCallback(function ($module, $object, $permission) {
			return $permission !== 'approuver';
		});
		DolibarrApiAccess::$user = $actingUser;
		$this->order->expects($this->never())->method('fetch');
		$this->order->expects($this->never())->method('refuse');
		$this->expectException(Luracast\Restler\RestException::class);
		$this->expectExceptionCode(403);
		$this->api->refuse(42);
	}

	/** @return void */
	public function testRefuseRequiresOrderAccess()
	{
		SupplierOrderRefuseApiFixture::$accessAllowed = false;
		$this->order->expects($this->never())->method('refuse');
		$this->expectException(Luracast\Restler\RestException::class);
		$this->expectExceptionCode(403);
		$this->api->refuse(42);
	}

	/** @return void */
	public function testRefuseRejectsAMissingOrder()
	{
		$order = $this->createMock(CommandeFournisseur::class);
		$order->method('fetch')->willReturn(0);
		$order->expects($this->never())->method('refuse');
		$api = new SupplierOrderRefuseApiFixture($order);
		$this->expectException(Luracast\Restler\RestException::class);
		$this->expectExceptionCode(404);
		$api->refuse(42);
	}

	/** @return void */
	public function testRefuseRequiresAValidatedOrder()
	{
		$this->order->expects($this->never())->method('refuse');
		$statuses = array(CommandeFournisseur::STATUS_DRAFT, CommandeFournisseur::STATUS_ACCEPTED, CommandeFournisseur::STATUS_ORDERSENT, CommandeFournisseur::STATUS_REFUSED, CommandeFournisseur::STATUS_CANCELED);
		foreach ($statuses as $status) {
			$this->order->status = $status;
			try {
				$this->api->refuse(42);
				$this->fail('An order in status '.$status.' was refused');
			} catch (Luracast\Restler\RestException $exception) {
				$this->assertSame(405, $exception->getCode());
			}
		}
	}

	/** @return void */
	public function testRefusePersistenceErrorIsReported()
	{
		$this->order->expects($this->once())->method('refuse')->willReturn(-1);
		$this->order->method('errorsToString')->willReturn('Refuse failed');
		$this->expectException(Luracast\Restler\RestException::class);
		$this->expectExceptionCode(500);
		$this->expectExceptionMessage('Refuse failed');
		$this->api->refuse(42);
	}
}

/**
 * Replace resource authorization without bypassing the order checks.
 */
class SupplierOrderRefuseApiFixture extends SupplierOrders
{
	/** @var bool Whether the resource access check succeeds */
	public static $accessAllowed = true;

	/**
	 * @param CommandeFournisseur $order Mock order
	 */
	public function __construct($order)
	{
		$this->order = $order;
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
	 * @return bool Whether access is allowed in this fixture
	 */
	protected static function _checkAccessToResource($resource, $resource_id = 0, $dbtablename = '', $feature2 = '', $dbt_keyfield = 'fk_soc', $dbt_select = 'rowid', $parenttableforentity = '')
	{
		// phpcs:enable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
		return self::$accessAllowed;
	}
}
