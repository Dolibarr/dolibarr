<?php
/* Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
	 * Check that a Reception object can be fetched from database.
	 *
	 * @param 	int		$id 	The id of an existing Reception object to fetch.
	 * @return 					Reception $localobject
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
	 * Check that a Expedition object can be updated.
	 *
	 * @param 	Object	$localobject 	An existing Expedition object to update.
	 * @return 							Reception a Expedition object with data fetched and name changed
	 *
	 * @depends testExpeditionFetch
	 */
	public function testExpeditionUpdate($localobject)
	{
		global $user;

		$localobject->name = "foobar";

		$result = $localobject->update($localobject->id, $user);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0, $localobject->errorsToString());

		return $localobject;
	}

	/**
	 * testExpeditionValid
	 *
	 * Check that a Reception with status == Reception::STATUS_DRAFT can be
	 * re-opened with the Reception::reOpen() function.
	 *
	 * @param Object	$localobject 	An existing Reception object to validate.
	 * @return Reception a Reception object with data fetched and STATUS_VALIDATED
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
	 * Check that a Reception can be closed with the Reception::setClosed()
	 * function, after it has been validated.
	 *
	 * @param 	Object		$localobject 	An existing validated Reception object to close.
	 * @return 	Expedition 					An Expedition object with data fetched and STATUS_CLOSED
	 *
	 * @depends testExpeditionValid
	 */
	public function testExpeditionSetClosed($localobject)
	{
		global $db, $user;

		$result = $localobject->fetch($localobject->id);
		$this->assertLessThanOrEqual($result, 0, "Cannot fetch Reception object:\n".
									 $localobject->errorsToString());
		$this->assertEquals(Expedition::STATUS_VALIDATED, $localobject->statut);
		$this->assertEquals(Expedition::STATUS_VALIDATED, $localobject->status);

		$result = $localobject->setClosed($user);
		$this->assertLessThanOrEqual($result, 0, "Cannot close Reception object:\n".
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
		$this->assertLessThanOrEqual($result, 0, "Cannot fetch Reception object:\n".
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
	 * re-opened with the Reception::reOpen() function.
	 *
	 * @param 	Object	$localobject 	An existing closed Reception object to re-open.
	 * @return 	Expedition 				A Expedition object with data fetched and STATUS_VALIDATED
	 *
	 * @depends testExpeditionSetClosed
	 */
	public function testExpeditionReOpen($localobject)
	{
		global $db;

		$result = $localobject->fetch($localobject->id);
		$this->assertLessThanOrEqual($result, 0, "Cannot fetch Reception object:\n".
									 $localobject->errorsToString());

		$this->assertEquals(Expedition::STATUS_CLOSED, $localobject->status);
		$this->assertEquals(Expedition::STATUS_CLOSED, $localobject->statut);

		$result = $localobject->reOpen();
		$this->assertLessThanOrEqual($result, 0, "Cannot reOpen Reception object:\n".
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
	 * Check that a Reception with status == Reception::STATUS_CLOSED can be
	 * re-opened with the Reception::reOpen() function.
	 *
	 * @param 	Object	$localobject 	An existing validated Reception object to mark as Draft.
	 * @return 	Reception 				A Reception object with data fetched and STATUS_DRAFT
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
	 * Check that a Reception object can be deleted.
	 *
	 * @param 	Object 	$localobject 	An existing Reception object to delete.
	 * @return 	int 					The result of the delete operation
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
