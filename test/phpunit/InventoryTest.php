<?php
/* Copyright (C) 2010 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2018 Frédéric France       <frederic.france@netlogic.fr>
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
 *      \file       test/phpunit/InventoryTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/product/inventory/class/inventory.class.php';

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
class InventoryTest extends PHPUnit\Framework\TestCase
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @return InventoryTest
	 */
	public function __construct()
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

	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass()
	{
		global $conf,$user,$langs,$db;

		$db->begin();	// This is to have all actions inside a transaction even if test launched without suite.

		print __METHOD__."\n";
	}

	/**
	 * tearDownAfterClass
	 *
	 * @return	void
	 */
	public static function tearDownAfterClass()
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
	 * @return  void
	 */
	protected function tearDown()
	{
		print __METHOD__."\n";
	}

	/**
	 * testInventoryCreate
	 *
	 * @return int
	 */
	public function testInventoryCreate()
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$localobject=new Inventory($db);
		$localobject->initAsSpecimen();
		$result=$localobject->create($user);
		$this->assertLessThan($result, 0);
		print __METHOD__." result=".$result."\n";
		return $result;
	}

	/**
	 * testInventoryFetch
	 *
	 * @param   int $id     Id invoice
	 * @return  int
	 *
	 * @depends testInventoryCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testInventoryFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$localobject=new Inventory($this->savdb);
		$result=$localobject->fetch($id);

		$this->assertLessThan($result, 0);
		print __METHOD__." id=".$id." result=".$result."\n";
		return $localobject;
	}

	/**
	 * testInventoryUpdate
	 *
	 * @param   Inventory $localobject Invoice
	 * @return  int
	 *
	 * @depends testInventoryFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testInventoryUpdate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$localobject->status = 9;
		$localobject->title = 'test';
		$result=$localobject->update($user, $user);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $localobject;
	}


	/**
	 * testInventoryValidate
	 *
	 * @param   Inventory $localobject Invoice
	 * @return  void
	 *
	 * @depends testInventoryUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testInventoryValidate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$result=$localobject->validate($user);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$this->assertLessThan($result, 0);
		$this->assertEquals($localobject->status, '1');
		return $localobject;
	}

	/**
	 * testInventorySetDraft
	 *
	 * @param   Inventory $localobject Invoice
	 * @return  void
	 *
	 * @depends testInventoryValidate
	 * The depends says test is run only if previous is ok
	 */
	public function testInventorySetDraft($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$result=$localobject->setDraft($user);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$this->assertLessThan($result, 0);
		$this->assertEquals($localobject->status, '0');
		return $localobject;
	}

	/**
	 * testInventorySetRecorded
	 *
	 * @param   Inventory $localobject Invoice
	 * @return  void
	 *
	 * @depends testInventorySetDraft
	 * The depends says test is run only if previous is ok
	 */
	public function testInventorySetRecorded($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$result=$localobject->setRecorded($user);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$this->assertLessThan($result, 0);
		$this->assertEquals($localobject->status, '2');
		return $localobject;
	}

	/**
		 * testInventorySetCanceled
		 *
		 * @param   Inventory $localobject Invoice
		 * @return  void
		 *
		 * @depends testInventorySetRecorded
		 * The depends says test is run only if previous is ok
		 */
	public function testInventorySetCanceled($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$result=$localobject->setCanceled($user);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$this->assertLessThan($result, 0);
		$this->assertEquals($localobject->status, '9');
		return $localobject;
	}

	/**
	 * testInventoryOther
	 *
	 * @param   Inventory $localobject Invoice
	 * @return  int
	 * @depends testInventorySetRecorded
	 * The depends says test is run only if previous is ok
	 */
	public function testInventoryOther($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$localobject->info($localobject->id);
		print __METHOD__." localobject->date_creation=".$localobject->date_creation."\n";
		$this->assertNotEquals($localobject->date_creation, '');
		return $localobject->id;
	}

	/**
	 * testInventoryDelete
	 *
	 * @param   int $id     Id of invoice
	 * @return  int
	 * @depends testInventoryOther
	 * The depends says test is run only if previous is ok
	 */
	public function testInventoryDelete($id)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$localobject=new Inventory($this->savdb);
		$result=$localobject->fetch($id);
		$result=$localobject->delete($user);
		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $result;
	}

	/**
	 * Compare all public properties values of 2 objects
	 *
	 * @param   Object $oA                      Object operand 1
	 * @param   Object $oB                      Object operand 2
	 * @param   boolean $ignoretype             False will not report diff if type of value differs
	 * @param   array $fieldstoignorearray      Array of fields to ignore in diff
	 * @return  array                           Array with differences
	 */
	public function objCompare($oA, $oB, $ignoretype = true, $fieldstoignorearray = array('id'))
	{
		$retAr=array();

		if (get_class($oA) !== get_class($oB)) {
			$retAr[]="Supplied objects are not of same class.";
		} else {
			$oVarsA=get_object_vars($oA);
			$oVarsB=get_object_vars($oB);
			$aKeys=array_keys($oVarsA);
			foreach ($aKeys as $sKey) {
				if (in_array($sKey, $fieldstoignorearray)) {
					continue;
				}
				if (! $ignoretype && ($oVarsA[$sKey] !== $oVarsB[$sKey])) {
					$retAr[]=$sKey.' : '.(is_object($oVarsA[$sKey])?get_class($oVarsA[$sKey]):$oVarsA[$sKey]).' <> '.(is_object($oVarsB[$sKey])?get_class($oVarsB[$sKey]):$oVarsB[$sKey]);
				}
				if ($ignoretype && ($oVarsA[$sKey] != $oVarsB[$sKey])) {
					$retAr[]=$sKey.' : '.(is_object($oVarsA[$sKey])?get_class($oVarsA[$sKey]):$oVarsA[$sKey]).' <> '.(is_object($oVarsB[$sKey])?get_class($oVarsB[$sKey]):$oVarsB[$sKey]);
				}
			}
		}
		return $retAr;
	}
}
