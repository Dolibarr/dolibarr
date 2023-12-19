<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
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
 *      \file       test/phpunit/CommandeFournisseurTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/fourn/class/fournisseur.commande.class.php';
require_once dirname(__FILE__).'/../../htdocs/fourn/class/fournisseur.product.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->getrights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS=1;



/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class CommandeFournisseurTest extends PHPUnit\Framework\TestCase
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @param 	string	$name		Name
	 * @return CommandeFournisseurTest
	 */
	public function __construct($name = '')
	{
		parent::__construct($name);

		//$this->sharedFixture
		global $conf,$user,$langs,$db;
		$this->savconf=$conf;
		$this->savuser=$user;
		$this->savlangs=$langs;
		$this->savdb=$db;

		print __METHOD__." db->type=".$db->type." user->id=".$user->id;
		//print " - db ".$db->db;
		print "\n";
	}

	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		global $conf,$user,$langs,$db;
		$db->begin(); // This is to have all actions inside a transaction even if test launched without suite.

		print __METHOD__."\n";
	}

	/**
	 * tearDownAfterClass
	 *
	 * @return	void
	 */
	public static function tearDownAfterClass(): void
	{
		global $conf,$user,$langs,$db;
		$db->rollback();

		print __METHOD__."\n";
	}

	/**
	 * Init phpunit tests
	 *
	 * @return  void
	 */
	protected function setUp(): void
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		print __METHOD__."\n";
		//print $db->getVersion()."\n";
	}
	/**
	 * End phpunit tests
	 *
	 * @return	void
	 */
	protected function tearDown(): void
	{
		print __METHOD__."\n";
	}

	/**
	 * testCommandeFournisseurCreate
	 *
	 * @return	int		ID of purchase order
	 */
	public function testCommandeFournisseurCreate()
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		// Set supplier and product to use
		$socid=1;
		$societe=new Societe($db);
		$societe->fetch($socid);
		$product=new ProductFournisseur($db);
		$product->fetch(0, 'PINKDRESS');
		if ($product->id <= 0) {
			print "\n".__METHOD__." A product with ref PINKDRESS must exists into database";
			die(1);
		}

		$quantity=10;
		$ref_fourn='SUPPLIER_REF_PHPUNIT';
		$tva_tx=19.6;

		// Delete existing supplier prices
		// TODO

		// Create 1 supplier price with min qty = 10;
		$result=$product->add_fournisseur($user, $societe->id, $ref_fourn, $quantity);    // This insert record with no value for price. Values are update later with update_buyprice
		print __METHOD__." add_fournisseur result=".$result."\n";
		$this->assertGreaterThanOrEqual(0, $result, 'Create 1 supplier price with min qty = 10 if not exists');
		$result=$product->update_buyprice($quantity, 20, $user, 'HT', $societe, '', $ref_fourn, $tva_tx, 0, 0);
		print __METHOD__." update_buyprice result=".$result."\n";
		$this->assertGreaterThanOrEqual(0, $result, 'Update buyprice');

		// Create purchase order with a too low quantity and option SUPPLIER_ORDER_WITH_PREDEFINED_PRICES_ONLY is on
		$conf->global->SUPPLIER_ORDER_WITH_PREDEFINED_PRICES_ONLY = 1;

		$localobject=new CommandeFournisseur($db);
		$localobject->initAsSpecimen();
		$localobject->lines=array();    // Overwrite lines of order
		$line=new CommandeFournisseurLigne($db);
		$line->desc=$langs->trans("Description")." specimen line with qty too low";
		$line->qty=1;                   // So lower than $quantity
		$line->subprice=100;
		$line->fk_product=$product->id;
		$line->ref_fourn=$ref_fourn;
		$localobject->lines[]=$line;

		$result=$localobject->create($user);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(-1, $result, 'Creation of too low quantity');   // must be -1 because quantity is lower than minimum of supplier price

		$sql="DELETE FROM ".MAIN_DB_PREFIX."commande_fournisseur where ref IN ('', '(PROV)')";
		$db->query($sql);

		// Create purchase order
		$localobject2=new CommandeFournisseur($db);
		$localobject2->initAsSpecimen();    // This create 5 lines of first product found for socid 1
		$localobject2->lines=array();       // Overwrite lines of order
		$line=new CommandeFournisseurLigne($db);
		$line->desc=$langs->trans("Description")." specimen line ok";
		$line->qty=10;                      // So enough quantity
		$line->subprice=100;
		$line->fk_product=$product->id;
		$line->ref_fourn=$ref_fourn;
		$localobject2->lines[]=$line;

		$result=$localobject2->create($user);
		print __METHOD__." result=".$result."\n";
		$this->assertGreaterThan(0, $result);


		// Create purchase order with a too low quantity but option SUPPLIER_ORDER_WITH_PREDEFINED_PRICES_ONLY is off
		$conf->global->SUPPLIER_ORDER_WITH_PREDEFINED_PRICES_ONLY = 0;

		$localobject3=new CommandeFournisseur($db);
		$localobject3->initAsSpecimen();
		$localobject3->lines=array();    // Overwrite lines of order
		$line=new CommandeFournisseurLigne($db);
		$line->desc=$langs->trans("Description")." specimen line with qty too low";
		$line->qty=1;                   // So lower than $quantity
		$line->subprice=100;
		$line->fk_product=$product->id;
		$line->ref_fourn=$ref_fourn;
		$localobject3->lines[]=$line;

		$result=$localobject3->create($user);
		print __METHOD__." result=".$result."\n";
		$this->assertGreaterThan(0, $result, 'Creation of too low quantity should be ok');   // must be id of line because there is no test on minimum quantity

		$sql="DELETE FROM ".MAIN_DB_PREFIX."commande_fournisseur where ref=''";
		$db->query($sql);

		// Create purchase order
		$localobject4=new CommandeFournisseur($db);
		$localobject4->initAsSpecimen();    // This create 5 lines of first product found for socid 1
		$localobject4->lines=array();       // Overwrite lines of order
		$line=new CommandeFournisseurLigne($db);
		$line->desc=$langs->trans("Description")." specimen line ok";
		$line->qty=10;                      // So enough quantity
		$line->subprice=100;
		$line->fk_product=$product->id;
		$line->ref_fourn=$ref_fourn;
		$localobject4->lines[]=$line;

		$result=$localobject4->create($user);
		print __METHOD__." id for purchase order created by testCommandeFournisseurCreate = ".$result."\n";
		$this->assertGreaterThan(0, $result, 'Test to create a purchase order by testCommandeFournisseurCreate');

		return $result;
	}


	/**
	 * testCommandeFournisseurFetch
	 *
	 * @param   int 		$id     			Id of purchase order
	 * @return  CommandeFournisseur				Purchase order
	 *
	 * @depends testCommandeFournisseurCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testCommandeFournisseurFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$localobject=new CommandeFournisseur($db);
		$result=$localobject->fetch($id);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0, 'Failed to fetch supplier order with id '.$id);
		return $localobject;
	}

	/**
	 * testCommandeFournisseurValid
	 *
	 * @param   CommandeFournisseur $localobject     Supplier order
	 * @return  CommandeFournisseur		Supplier order
	 *
	 * @depends testCommandeFournisseurFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testCommandeFournisseurValid($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$result=$localobject->valid($user);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $localobject;
	}

	/**
	 * testCommandeFournisseurApprove
	 *
	 * @param   CommandeFournisseur $localobject 	Supplier order
	 * @return  CommandeFournisseur		Supplier order
	 *
	 * @depends testCommandeFournisseurValid
	 * The depends says test is run only if previous is ok
	 */
	public function testCommandeFournisseurApprove($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$result=$localobject->approve($user);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $localobject;
	}

	/**
	 * testCommandeFournisseurCancel
	 *
	 * @param   CommandeFournisseur  $localobject    Supplier order
	 * @return  CommandeFournisseur		Supplier order
	 *
	 * @depends testCommandeFournisseurApprove
	 * The depends says test is run only if previous is ok
	 */
	public function testCommandeFournisseurCancel($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$result=$localobject->cancel($user);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $localobject;
	}

	/**
	 * testCommandeFournisseurOther
	 *
	 * @param   CommandeFournisseur $localobject     Supplier order
	 * @return  int						Id of purchase order
	 *
	 * @depends testCommandeFournisseurCancel
	 * The depends says test is run only if previous is ok
	 */
	public function testCommandeFournisseurOther($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		/*$result=$localobject->setstatus(0);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		*/

		/*$localobject->info($localobject->id);
		print __METHOD__." localobject->date_creation=".$localobject->date_creation."\n";
		$this->assertNotEquals($localobject->date_creation, '');
		*/
		$this->assertEquals(1, 1);

		return $localobject->id;
	}

	/**
	 * testCommandeFournisseurDelete
	 *
	 * @param   int $id     Id of order
	 * @return  int			Result of delete
	 *
	 * @depends testCommandeFournisseurOther
	 * The depends says test is run only if previous is ok
	 */
	public function testCommandeFournisseurDelete($id)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$localobject=new CommandeFournisseur($db);
		$result=$localobject->fetch($id);
		$result=$localobject->delete($user);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $result;
	}
}
