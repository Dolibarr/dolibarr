<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
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
 *      \file       test/phpunit/ProductTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db,$mysoc;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/product/class/product.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

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
 * @remarks backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class ProductTest extends CommonClassTest
{
	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		global $conf,$user,$langs,$db;

		if (!isModEnabled('product')) {
			print __METHOD__." Module Product must be enabled.\n";
			die(1);
		}

		$db->begin(); // This is to have all actions inside a transaction even if test launched without suite.

		print __METHOD__."\n";
	}


	/**
	 * testProductCreate
	 *
	 * @return  void
	 */
	public function testProductCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Product($db);
		$localobject->initAsSpecimen();
		$result = $localobject->create($user);

		print __METHOD__." result=".$result."\n";
		$this->assertLessThanOrEqual($result, 0, "Creation of product");

		return $result;
	}

	/**
	 * testProductFetch
	 *
	 * @param   int $id     Id product
	 * @return  Product
	 *
	 * @depends testProductCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testProductFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Product($db);
		$result = $localobject->fetch($id);
		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $localobject;
	}

	/**
	 * testProductUpdate
	 *
	 * @param   Product $localobject    Product
	 * @return  void
	 *
	 * @depends testProductFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testProductUpdate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->note_public = 'New public note after update';
		$localobject->note_private = 'New private note after update';
		$result = $localobject->update($localobject->id, $user);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0, 'Error '.$localobject->error);

		return $localobject;
	}

	/**
	 * testProductOther
	 *
	 * @param   Product $localobject    Product
	 * @return  void
	 *
	 * @depends	testProductUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testProductOther($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$this->assertEquals(0, 0);

		return $localobject->id;
	}

	/**
	 * testLoadStatsWithoutRightToSeeAllThirdparties
	 *
	 * Statistics of a product must not generate an SQL error for a user who is not allowed to see all thirdparties.
	 *
	 * @param   int $id     Id of product
	 * @return  void
	 *
	 * @depends testProductOther
	 * The depends says test is run only if previous is ok
	 */
	public function testLoadStatsWithoutRightToSeeAllThirdparties($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Product($db);
		$localobject->fetch($id);

		$limiteduser = new User($db);
		$limiteduser->id = 999999;
		$this->assertEquals(0, $limiteduser->hasRight('societe', 'client', 'voir'));

		$adminuser = $user;
		$user = $limiteduser;
		try {
			foreach (array('propale', 'proposal_supplier', 'commande', 'commande_fournisseur', 'sending', 'reception', 'inproduction', 'contrat', 'facture', 'facturerec', 'facture_fournisseur') as $stat) {
				$method = 'load_stats_'.$stat;
				$result = $localobject->$method();
				print __METHOD__." ".$method." result=".$result."\n";
				$this->assertGreaterThanOrEqual(0, $result, $method." failed: ".$localobject->error);
			}
		} finally {
			$user = $adminuser;
		}
	}

	/**
	 * testProductDelete
	 *
	 * @param       int $id     Id of product
	 * @return      void
	 *
	 * @depends testProductOther
	 * The depends says test is run only if previous is ok
	 */
	public function testProductDelete($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Product($db);
		$result = $localobject->fetch($id);

		$result = $localobject->delete($user);
		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $result;
	}

	/**
	 * testProductMerge
	 * Merge an origin product into a target one: empty fields of target are filled, notes are concatenated and origin is deleted.
	 *
	 * @return      void
	 */
	public function testProductMerge()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$target = new Product($db);
		$target->initAsSpecimen();
		$target->ref = 'PRODUCT_SPEC_MERGE_TARGET';
		$target->barcode = '';
		$target->description = '';
		$target->note_private = 'Note target';
		$targetid = $target->create($user);
		$this->assertGreaterThan(0, $targetid, "Creation of target product: ".$target->error);

		$origin = new Product($db);
		$origin->initAsSpecimen();
		$origin->ref = 'PRODUCT_SPEC_MERGE_ORIGIN';
		$origin->barcode = '';
		$origin->description = 'Description origin';
		$origin->note_private = 'Note origin';
		$originid = $origin->create($user);
		$this->assertGreaterThan(0, $originid, "Creation of origin product: ".$origin->error);

		$target = new Product($db);
		$target->fetch($targetid);
		$result = $target->mergeProduct($originid);
		print __METHOD__." targetid=".$targetid." originid=".$originid." result=".$result."\n";
		$this->assertEquals(0, $result, "Merge of products: ".$target->error);

		// Origin product must be deleted
		$check = new Product($db);
		$this->assertEquals(0, $check->fetch($originid), "Origin product must be deleted");

		// Empty fields of target are filled with origin values and notes are concatenated
		$check = new Product($db);
		$check->fetch($targetid);
		$this->assertEquals('PRODUCT_SPEC_MERGE_TARGET', $check->ref);
		$this->assertEquals('Description origin', $check->description);
		$this->assertEquals(1, preg_match('/Note target.*Note origin/s', $check->note_private), "Notes must be concatenated");

		$result = $check->delete($user);
		$this->assertGreaterThan(0, $result, "Deletion of target product: ".$check->error);
	}
}
