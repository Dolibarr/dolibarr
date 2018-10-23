<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *      \file       test/phpunit/MouvementStockTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/product/stock/class/mouvementstock.class.php';
require_once dirname(__FILE__).'/../../htdocs/product/stock/class/entrepot.class.php';
require_once dirname(__FILE__).'/../../htdocs/product/class/product.class.php';

if (empty($user->id))
{
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
class MouvementStockTest extends PHPUnit_Framework_TestCase
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @return ContratTest
	 */
	function __construct()
	{
		parent::__construct();

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

	// Static methods
  	public static function setUpBeforeClass()
    {
    	global $conf,$user,$langs,$db;
		$db->begin();	// This is to have all actions inside a transaction even if test launched without suite.

    	print __METHOD__."\n";
    }

    // tear down after class
    public static function tearDownAfterClass()
    {
    	global $conf,$user,$langs,$db;
		$db->rollback();

		print __METHOD__."\n";
    }

	/**
	 * Init phpunit tests
	 *
	 * @return	void
	 */
    protected function setUp()
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		print __METHOD__."\n";
    }
	/**
	 * End phpunit tests
	 *
	 * @return	void
	 */
    protected function tearDown()
    {
    	print __METHOD__."\n";
    }

    /**
     * testMouvementCreate
     *
     * @return	int
     */
    public function testMouvementCreate()
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		// We create a product for tests
		$product1=new Product($db);
		$product1->initAsSpecimen();
		$product1->ref.=' 1';
		$product1->label.=' 1';
		$product1id=$product1->create($user);

		$product2=new Product($db);
		$product2->initAsSpecimen();
		$product2->ref.=' 2';
		$product2->label.=' 2';
		$product2id=$product2->create($user);

		// We create a product for tests
		$warehouse1=new Entrepot($db);
		$warehouse1->initAsSpecimen();
		$warehouse1->libelle.=' 1';
		$warehouse1->description.=' 1';
		$warehouse1id=$warehouse1->create($user);

		$warehouse2=new Entrepot($db);
		$warehouse2->initAsSpecimen();
		$warehouse2->libelle.=' 2';
		$warehouse2->description.=' 2';
		$warehouse2id=$warehouse2->create($user);

		$localobject=new MouvementStock($this->savdb);

    	// Do a list of movement into warehouse 1

    	// Create an input movement (type = 3) of price 9.9 -> shoul dupdate PMP to 9.9
    	$result=$localobject->_create($user, $product1id, $warehouse1id, 10, 3, 9.9, 'Movement for unit test 1', 'Inventory Code Test');
    	print __METHOD__." result=".$result."\n";
    	$this->assertLessThan($result, 0);

    	// Create an input movement (type = 3) of price 9.7 -> shoul dupdate PMP to 9.9/9.7 = 9.8
    	$result=$localobject->_create($user, $product1id, $warehouse1id, 10, 3, 9.7, 'Movement for unit test 2', 'Inventory Code Test');
    	print __METHOD__." result=".$result."\n";
    	$this->assertLessThan($result, 0);

    	// Create an output movement (type = 2) of price 9.7 -> shoul dupdate PMP to 9.9/9.7 = 9.8
    	$result=$localobject->_create($user, $product1id, $warehouse1id, -5, 2, 999, 'Movement for unit test 3', 'Inventory Code Test');
    	print __METHOD__." result=".$result."\n";
    	$this->assertLessThan($result, 0);

    	// Create an output movement (type = 1) of price 9.7 -> shoul dupdate PMP to 9.9/9.7 = 9.8
    	$result=$localobject->_create($user, $product1id, $warehouse1id, 1, 0, 0, 'Input from transfer', 'Transfert X');
    	print __METHOD__." result=".$result."\n";
    	$this->assertLessThan($result, 0);

    	// Create an output movement (type = 1) of price 9.7 -> shoul dupdate PMP to 9.9/9.7 = 9.8
    	$result=$localobject->_create($user, $product1id, $warehouse1id, -2, 1, 0, 'Output from transfer', 'Transfert Y');
    	print __METHOD__." result=".$result."\n";
    	$this->assertLessThan($result, 0);


    	// Do same but into warehouse 2

    	// Create an input movement (type = 3) of price 9.9 -> shoul dupdate PMP to 9.9
    	$result=$localobject->_create($user, $product1id, $warehouse2id, 10, 3, 9.9, 'Movement for unit test 1 wh 2', 'Inventory Code Test 2');
    	print __METHOD__." result=".$result."\n";
    	$this->assertLessThan($result, 0);

    	// Create an input movement (type = 3) of price 9.7 -> shoul dupdate PMP to 9.9/9.7 = 9.8
    	$result=$localobject->_create($user, $product1id, $warehouse2id, 10, 3, 9.7, 'Movement for unit test 2 wh 2', 'Inventory Code Test 2');
    	print __METHOD__." result=".$result."\n";
    	$this->assertLessThan($result, 0);

    	// Create an output movement (type = 2) of price 9.7 -> shoul dupdate PMP to 9.9/9.7 = 9.8
    	$result=$localobject->_create($user, $product1id, $warehouse2id, -5, 2, 999, 'Movement for unit test 3 wh 2', 'Inventory Code Test 2');
    	print __METHOD__." result=".$result."\n";
    	$this->assertLessThan($result, 0);

    	// Create an output movement (type = 1) of price 9.7 -> shoul dupdate PMP to 9.9/9.7 = 9.8
    	$result=$localobject->_create($user, $product1id, $warehouse2id, 1, 0, 0, 'Input from transfer wh 2', 'Transfert X 2');
    	print __METHOD__." result=".$result."\n";
    	$this->assertLessThan($result, 0);

    	// Create an output movement (type = 1) of price 9.7 -> shoul dupdate PMP to 9.9/9.7 = 9.8
    	$result=$localobject->_create($user, $product1id, $warehouse2id, -2, 1, 0, 'Output from transfer wh 2', 'Transfert Y 2');
    	print __METHOD__." result=".$result."\n";
    	$this->assertLessThan($result, 0);


    	return $localobject;
    }

    /**
     * testMouvementCheck
     *
     * @param	MouvementStock		$localobject	Movement object we created
     * @return	int
     *
     * @depends	testMouvementCreate
     * The depends says test is run only if previous is ok
     */
    public function testMouvementCheck($localobject)
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$productid = $localobject->product_id;
		$warehouseid = $localobject->entrepot_id;

		print __METHOD__." productid=".$productid."\n";

		$producttotest = new Product($db);
		$producttotest->fetch($productid);

    	print __METHOD__." producttotest->stock_reel=".$producttotest->stock_reel."\n";
    	$this->assertEquals($producttotest->stock_reel, 28);	// 28 is result of stock movement defined into testMouvementCreate

    	print __METHOD__." producttotest->pmp=".$producttotest->pmp."\n";
    	$this->assertEquals($producttotest->pmp, 9.8);

    	return $localobject;
    }
}
