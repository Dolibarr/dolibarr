<?php
/* Copyright (C) 2023       Alexandre Janniaux      <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024-2025  Frédéric France         <frederic.france@free.fr>
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

global $conf,$user,$langs,$db;

require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/societe/class/societe.class.php';
require_once dirname(__FILE__).'/../../htdocs/expedition/class/expedition.class.php';
require_once dirname(__FILE__).'/../../htdocs/contact/class/contact.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/modules/expedition/doc/pdf_espadon.modules.php';
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
 */
class ExpeditionTest extends CommonClassTest
{
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
