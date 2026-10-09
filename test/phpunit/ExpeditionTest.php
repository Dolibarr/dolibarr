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
 */
class ExpeditionTest extends CommonClassTest
{
	/**
	 * Check that deleting a shipment restores the stock of sub-products.
	 *
	 * @return void
	 */
	public function testDeleteRestoresSubProductStock()
	{
		global $conf, $db, $user;

		$oldStockEnabled = $conf->stock->enabled;
		$oldSubProducts = getDolGlobalString('PRODUIT_SOUSPRODUITS');
		$oldIndependentStock = getDolGlobalString('INDEPENDANT_SUBPRODUCT_STOCK');
		$oldCalculateOnShipment = getDolGlobalString('STOCK_CALCULATE_ON_SHIPMENT');
		$oldParentStockMove = getDolGlobalString('PRODUIT_SOUSPRODUITS_ALSO_ENABLE_PARENT_STOCK_MOVE');

		$conf->stock->enabled = 1;
		$conf->global->PRODUIT_SOUSPRODUITS = 1;
		$conf->global->INDEPENDANT_SUBPRODUCT_STOCK = 0;
		$conf->global->STOCK_CALCULATE_ON_SHIPMENT = 1;
		$conf->global->PRODUIT_SOUSPRODUITS_ALSO_ENABLE_PARENT_STOCK_MOVE = 0;

		$suffix = dol_print_date(dol_now(), '%Y%m%d%H%M%S').'-'.mt_rand(1000, 9999);
		$parentId = 0;
		$childId = 0;
		$warehouseId = 0;
		$shipmentId = 0;
		$thirdpartyId = 0;

		try {
			$thirdparty = new Societe($db);
			$thirdparty->name = 'Expedition stock restore '.$suffix;
			$thirdpartyId = $thirdparty->create($user);
			$this->assertGreaterThan(0, $thirdpartyId, $thirdparty->errorsToString());

			$sql = "INSERT INTO ".$db->prefix()."entrepot (ref, datec, entity, statut)";
			$sql .= " VALUES ('".$db->escape('WH-'.$suffix)."', '".$db->idate(dol_now())."', ".((int) $conf->entity).", 1)";
			$this->assertTrue((bool) $db->query($sql), (string) $db->lasterror());
			$warehouseId = $db->last_insert_id($db->prefix().'entrepot');

			$sql = "INSERT INTO ".$db->prefix()."product (ref, entity, label, fk_product_type, tosell, tobuy, datec)";
			$sql .= " VALUES ('".$db->escape('PARENT-'.$suffix)."', ".((int) $conf->entity).", 'Parent', 0, 1, 0, '".$db->idate(dol_now())."')";
			$this->assertTrue((bool) $db->query($sql), (string) $db->lasterror());
			$parentId = $db->last_insert_id($db->prefix().'product');

			$sql = "INSERT INTO ".$db->prefix()."product (ref, entity, label, fk_product_type, tosell, tobuy, datec)";
			$sql .= " VALUES ('".$db->escape('CHILD-'.$suffix)."', ".((int) $conf->entity).", 'Child', 0, 1, 0, '".$db->idate(dol_now())."')";
			$this->assertTrue((bool) $db->query($sql), (string) $db->lasterror());
			$childId = $db->last_insert_id($db->prefix().'product');

			$sql = "INSERT INTO ".$db->prefix()."product_association (fk_product_pere, fk_product_fils, qty, incdec, rang)";
			$sql .= " VALUES (".((int) $parentId).", ".((int) $childId).", 3, 1, 1)";
			$this->assertTrue((bool) $db->query($sql), (string) $db->lasterror());

			$sql = "INSERT INTO ".$db->prefix()."product_stock (fk_product, fk_entrepot, reel) VALUES";
			$sql .= " (".((int) $parentId).", ".((int) $warehouseId).", 10),";
			$sql .= " (".((int) $childId).", ".((int) $warehouseId).", 10)";
			$this->assertTrue((bool) $db->query($sql), (string) $db->lasterror());

			$sql = "INSERT INTO ".$db->prefix()."expedition";
			$sql .= " (ref, entity, fk_soc, date_creation, date_valid, fk_user_author, fk_user_valid, fk_statut)";
			$sql .= " VALUES ('".$db->escape('SHIP-'.$suffix)."', ".((int) $conf->entity).", ".((int) $thirdpartyId).",";
			$sql .= " '".$db->idate(dol_now())."', '".$db->idate(dol_now())."', ".((int) $user->id).", ".((int) $user->id).", 1)";
			$this->assertTrue((bool) $db->query($sql), (string) $db->lasterror());
			$shipmentId = $db->last_insert_id($db->prefix().'expedition');

			$sql = "INSERT INTO ".$db->prefix()."expeditiondet";
			$sql .= " (fk_expedition, element_type, fk_product, qty, fk_entrepot, rang)";
			$sql .= " VALUES (".((int) $shipmentId).", 'commande', ".((int) $parentId).", 2, ".((int) $warehouseId).", 1)";
			$this->assertTrue((bool) $db->query($sql), (string) $db->lasterror());

			$movement = new MouvementStock($db);
			$result = $movement->livraison($user, $parentId, $warehouseId, 2, 0, 'ExpeditionTest validation');
			$this->assertGreaterThanOrEqual(0, $result, $movement->errorsToString());
			$this->assertEquals(4.0, $this->getProductStock($childId, $warehouseId));

			$shipment = new Expedition($db);
			$this->assertGreaterThan(0, $shipment->fetch($shipmentId));
			$result = $shipment->delete($user);
			$this->assertGreaterThan(0, $result, $shipment->errorsToString());
			$this->assertEquals(10.0, $this->getProductStock($childId, $warehouseId));
		} finally {
			if ($shipmentId > 0) {
				$db->query("DELETE FROM ".$db->prefix()."expeditiondet WHERE fk_expedition = ".((int) $shipmentId));
				$db->query("DELETE FROM ".$db->prefix()."expedition WHERE rowid = ".((int) $shipmentId));
			}
			if ($parentId > 0 || $childId > 0) {
				$db->query("DELETE FROM ".$db->prefix()."product_stock WHERE fk_product IN (".((int) $parentId).", ".((int) $childId).")");
				$db->query("DELETE FROM ".$db->prefix()."product_association WHERE fk_product_pere = ".((int) $parentId));
				$db->query("DELETE FROM ".$db->prefix()."product WHERE rowid IN (".((int) $parentId).", ".((int) $childId).")");
			}
			if ($warehouseId > 0) {
				$db->query("DELETE FROM ".$db->prefix()."entrepot WHERE rowid = ".((int) $warehouseId));
			}
			if ($thirdpartyId > 0) {
				$thirdparty = new Societe($db);
				$thirdparty->fetch($thirdpartyId);
				$thirdparty->delete($thirdpartyId, $user);
			}

			$conf->stock->enabled = $oldStockEnabled;
			$conf->global->PRODUIT_SOUSPRODUITS = $oldSubProducts;
			$conf->global->INDEPENDANT_SUBPRODUCT_STOCK = $oldIndependentStock;
			$conf->global->STOCK_CALCULATE_ON_SHIPMENT = $oldCalculateOnShipment;
			$conf->global->PRODUIT_SOUSPRODUITS_ALSO_ENABLE_PARENT_STOCK_MOVE = $oldParentStockMove;
		}
	}

	/**
	 * Return the real stock for a product in a warehouse.
	 *
	 * @param int $productId Product ID
	 * @param int $warehouseId Warehouse ID
	 * @return float
	 */
	private function getProductStock($productId, $warehouseId)
	{
		global $db;

		$sql = "SELECT reel FROM ".$db->prefix()."product_stock";
		$sql .= " WHERE fk_product = ".((int) $productId)." AND fk_entrepot = ".((int) $warehouseId);
		$result = $db->query($sql);
		$this->assertTrue((bool) $result, (string) $db->lasterror());
		$row = $db->fetch_object($result);

		return (float) $row->reel;
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
