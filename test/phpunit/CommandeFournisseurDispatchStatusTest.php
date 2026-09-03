<?php
/* Copyright (C) 2026 Nicolas Vidal <nicolas.vidal@atm-consulting.fr>
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
 *      \file       test/phpunit/CommandeFournisseurDispatchStatusTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/fourn/class/fournisseur.commande.class.php';
require_once dirname(__FILE__).'/../../htdocs/product/class/product.class.php';
require_once dirname(__FILE__).'/../../htdocs/product/stock/class/entrepot.class.php';
require_once dirname(__FILE__).'/../../htdocs/societe/class/societe.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class for PHPUnit tests of the reception status computed from dispatched quantities
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class CommandeFournisseurDispatchStatusTest extends CommonClassTest
{
	/**
	 * @var int Warehouse used to dispatch
	 */
	private $warehouseid = 0;

	/**
	 * @var int Product ordered
	 */
	private $productid = 0;

	/**
	 * Ordered quantity of every purchase order built by the tests
	 */
	const ORDERED_QTY = 10;

	/**
	 * Build a purchase order of ORDERED_QTY, sent to the supplier (status STATUS_ORDERSENT)
	 *
	 * @return	CommandeFournisseur		Purchase order ready to be dispatched
	 */
	private function buildOrderedPurchaseOrder()
	{
		global $user, $db;

		if (empty($this->productid)) {
			$product = new Product($db);
			$product->ref = uniqid('PHPUNIT_DISPATCHSTATUS_');
			$product->label = 'Product for dispatch status test';
			$product->type = Product::TYPE_PRODUCT;
			$product->status = 1;
			$product->status_buy = 1;
			$result = $product->create($user);
			$this->assertGreaterThan(0, $result, 'Create product: '.$product->error);
			$this->productid = $product->id;
		}

		if (empty($this->warehouseid)) {
			$warehouse = new Entrepot($db);
			$warehouse->ref = uniqid('PHPUNIT_DISPATCHSTATUS_');
			$warehouse->label = $warehouse->ref;
			$warehouse->statut = 1;
			$result = $warehouse->create($user);
			$this->assertGreaterThan(0, $result, 'Create warehouse: '.$warehouse->error);
			$this->warehouseid = $warehouse->id;
		}

		$socid = 0;
		$resql = $db->query('SELECT rowid FROM '.$db->prefix()."societe WHERE fournisseur = 1 AND entity IN (".getEntity('societe').') ORDER BY rowid ASC');
		if ($resql) {
			$obj = $db->fetch_object($resql);
			$socid = empty($obj) ? 0 : (int) $obj->rowid;
			$db->free($resql);
		}
		if (empty($socid)) {
			$societe = new Societe($db);
			$societe->name = 'PHPUNIT dispatch status supplier';
			$societe->fournisseur = 1;
			$result = $societe->create($user);
			$this->assertGreaterThan(0, $result, 'Create supplier: '.$societe->error);
			$socid = $societe->id;
		}

		$order = new CommandeFournisseur($db);
		$order->socid = $socid;
		$result = $order->create($user);
		$this->assertGreaterThan(0, $result, 'Create purchase order: '.$order->error);

		$result = $order->addline('Line for dispatch status test', 100, self::ORDERED_QTY, 0, 0, 0, $this->productid);
		$this->assertGreaterThan(0, $result, 'Add line: '.$order->error);

		// The validation workflow is out of the scope of these tests, only the status the dispatch needs matters
		$result = $order->setStatus($user, CommandeFournisseur::STATUS_ORDERSENT);
		$this->assertGreaterThan(0, $result, 'Set order as sent: '.$order->error);

		$order->fetch($order->id);
		$this->assertEquals(CommandeFournisseur::STATUS_ORDERSENT, $order->status, 'Order must be sent before dispatching');

		return $order;
	}

	/**
	 * An order with no dispatch line at all is neither partially nor completely received
	 *
	 * @return void
	 */
	public function testStatusFromDispatchedQtyWithoutAnyDispatch()
	{
		global $user;

		$order = $this->buildOrderedPurchaseOrder();

		$this->assertEquals(0, $order->getStatusFromDispatchedQty());

		$this->assertEquals(1, $order->calcAndSetStatusDispatch($user), 'Nothing dispatched: '.$order->error);
		$order->fetch($order->id);
		$this->assertEquals(CommandeFournisseur::STATUS_ORDERSENT, $order->status, 'Nothing dispatched must leave the status untouched');
	}

	/**
	 * Dispatching less than ordered gives STATUS_RECEIVED_PARTIALLY, dispatching the rest gives STATUS_RECEIVED_COMPLETELY
	 *
	 * @return void
	 */
	public function testStatusFromDispatchedQtyFollowsTheDispatchedQuantities()
	{
		global $user;

		$order = $this->buildOrderedPurchaseOrder();

		$result = $order->dispatchProduct($user, $this->productid, self::ORDERED_QTY - 4, $this->warehouseid);
		$this->assertGreaterThan(0, $result, 'Dispatch part of the order: '.$order->error);
		$this->assertEquals(CommandeFournisseur::STATUS_RECEIVED_PARTIALLY, $order->getStatusFromDispatchedQty());

		$this->assertEquals(CommandeFournisseur::STATUS_RECEIVED_PARTIALLY, $order->calcAndSetStatusDispatch($user), 'Partial dispatch: '.$order->error);
		$order->fetch($order->id);
		$this->assertEquals(CommandeFournisseur::STATUS_RECEIVED_PARTIALLY, $order->status);

		$result = $order->dispatchProduct($user, $this->productid, 4, $this->warehouseid);
		$this->assertGreaterThan(0, $result, 'Dispatch the remaining quantity: '.$order->error);
		$this->assertEquals(CommandeFournisseur::STATUS_RECEIVED_COMPLETELY, $order->getStatusFromDispatchedQty());

		$this->assertEquals(CommandeFournisseur::STATUS_RECEIVED_COMPLETELY, $order->calcAndSetStatusDispatch($user), 'Complete dispatch: '.$order->error);
		$order->fetch($order->id);
		$this->assertEquals(CommandeFournisseur::STATUS_RECEIVED_COMPLETELY, $order->status);
	}

	/**
	 * Reopening an order whose quantities are all dispatched must not claim a partial reception
	 *
	 * @return void
	 */
	public function testReopenOfACompletelyReceivedOrderGoesBackToOrderSent()
	{
		global $user;

		$order = $this->buildOrderedPurchaseOrder();

		$result = $order->dispatchProduct($user, $this->productid, self::ORDERED_QTY, $this->warehouseid);
		$this->assertGreaterThan(0, $result, 'Dispatch the whole order: '.$order->error);

		$result = $order->calcAndSetStatusDispatch($user);
		$this->assertEquals(CommandeFournisseur::STATUS_RECEIVED_COMPLETELY, $result, 'Order must be received completely: '.$order->error);

		$order->fetch($order->id);
		$this->assertEquals(CommandeFournisseur::STATUS_RECEIVED_COMPLETELY, $order->status);

		$result = $order->setReopen($user);
		$this->assertGreaterThan(0, $result, 'Reopen: '.$order->error);

		$order->fetch($order->id);
		$this->assertEquals(CommandeFournisseur::STATUS_ORDERSENT, $order->status);
	}

	/**
	 * Reopening an order closed while incomplete keeps the partial reception it really has
	 *
	 * @return void
	 */
	public function testReopenOfAPartiallyReceivedOrderStaysPartiallyReceived()
	{
		global $user;

		$order = $this->buildOrderedPurchaseOrder();

		$result = $order->dispatchProduct($user, $this->productid, self::ORDERED_QTY - 1, $this->warehouseid);
		$this->assertGreaterThan(0, $result, 'Dispatch part of the order: '.$order->error);

		// Closing an incomplete order: no more reception is expected
		$result = $order->Livraison($user, dol_now(), 'tot', '');
		$this->assertGreaterThan(0, $result, 'Close as received: '.$order->error);

		$order->fetch($order->id);
		$this->assertEquals(CommandeFournisseur::STATUS_RECEIVED_COMPLETELY, $order->status);

		$result = $order->setReopen($user);
		$this->assertGreaterThan(0, $result, 'Reopen: '.$order->error);

		$order->fetch($order->id);
		$this->assertEquals(CommandeFournisseur::STATUS_RECEIVED_PARTIALLY, $order->status);
	}
}
