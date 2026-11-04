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
 *      \file       test/phpunit/PropalTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/comm/propal/class/propal.class.php';
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
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class PropalTest extends CommonClassTest
{
	/**
	 * testPropalCreate
	 *
	 * @return	void
	 */
	public function testPropalCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Propal($db);
		$param = array('tosell' => 1);
		$localobject->initAsSpecimen($param);
		$result = $localobject->create($user);

		$this->assertLessThan($result, 0);
		print __METHOD__." result=".$result."\n";
		return $result;
	}

	/**
	 * testPropalFetch
	 *
	 * @param	int		$id		Id of object
	 * @return	Propal
	 *
	 * @depends	testPropalCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testPropalFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Propal($db);
		$result = $localobject->fetch($id);

		$this->assertLessThan($result, 0);
		print __METHOD__." id=".$id." result=".$result."\n";
		return $localobject;
	}

	/**
	 * testPropalUpdate
	 *
	 * @param	Propal		$localobject	Proposal
	 * @return	Propal
	 *
	 * @depends	testPropalFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testPropalUpdate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->note_private = 'New note private after update';
		$result = $localobject->update($user);

		$this->assertLessThan($result, 0);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		return $localobject;
	}

	/**
	 * testPropalAddLine
	 *
	 * @param	Propal		$localobject	Proposal
	 * @return	Propal
	 *
	 * @depends	testPropalUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testPropalAddLine($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->fetch_thirdparty();
		$result = $localobject->addline('Added line', 10, 2, 19.6);

		$this->assertLessThan($result, 0);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		return $localobject;
	}

	/**
	 * testPropalValid
	 *
	 * @param	Propal	$localobject	Proposal
	 * @return	Propal
	 *
	 * @depends	testPropalAddLine
	 * The depends says test is run only if previous is ok
	 */
	public function testPropalValid($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->valid($user);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $localobject;
	}

	/**
	 * testPropalOther
	 *
	 * @param	Propal	$localobject	Proposal
	 * @return	int
	 *
	 * @depends testPropalValid
	 * The depends says test is run only if previous is ok
	 */
	public function testPropalOther($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		/*$result=$localobject->setstatus(0);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		*/

		$localobject->info($localobject->id);
		print __METHOD__." localobject->date_creation=".$localobject->date_creation."\n";
		$this->assertNotEquals($localobject->date_creation, '');

		return $localobject->id;
	}

	/**
	 * testPropalDelete
	 *
	 * @param	int		$id		Id of proposal
	 * @return	void
	 *
	 * @depends	testPropalOther
	 * The depends says test is run only if previous is ok
	 */
	public function testPropalDelete($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Propal($db);
		$result = $localobject->fetch($id);
		$result = $localobject->delete($user);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $result;
	}

	/**
	 * testPropalAddlinePackaging
	 *
	 * With PRODUCT_USE_CUSTOMER_PACKAGING, a quantity already multiple of the packaging must not be rounded
	 * to the next multiple.
	 *
	 * @return  void
	 */
	public function testPropalAddlinePackaging()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$savconst = getDolGlobalString('PRODUCT_USE_CUSTOMER_PACKAGING');
		$conf->global->PRODUCT_USE_CUSTOMER_PACKAGING = 1;

		$product = new Product($db);
		$product->ref = 'PHPUNIT-PACKAGING-'.dol_print_date(dol_now(), 'dayhourlog').'-'.mt_rand(100, 999);
		$product->label = 'Product sold by packaging of 6';
		$product->type = Product::TYPE_PRODUCT;
		$product->price = 10;
		$product->tva_tx = 20;
		$product->packaging = 6;
		$productid = $product->create($user);
		$this->assertGreaterThan(0, $productid, (string) $product->error);

		$localobject = new Propal($db);
		$localobject->socid = 1;
		$localobject->date = dol_now();
		$localobject->duree_validite = 15;
		$this->assertGreaterThan(0, $localobject->create($user), (string) $localobject->error);
		$localobject->fetch_thirdparty();

		$result = array();
		foreach (array(1, 7, 12, 13) as $qty) {
			$lineid = $localobject->addline('Line', 10, $qty, 20, 0, 0, $productid);
			$this->assertGreaterThan(0, $lineid, (string) $localobject->error);
			$line = new PropaleLigne($db);
			$line->fetch($lineid);
			$result[$qty] = (float) $line->qty;
		}
		$conf->global->PRODUCT_USE_CUSTOMER_PACKAGING = $savconst;

		print __METHOD__." result=".json_encode($result)."\n";
		$this->assertEquals(array(1 => 6, 7 => 12, 12 => 12, 13 => 18), $result, 'Quantities rounded to the packaging of 6');
	}
}
