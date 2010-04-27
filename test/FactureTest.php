<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *      \file       test/FactureTest.php
 *		\ingroup    test
 *      \brief      This file is an example for a PHPUnit test
 *      \version    $Id$
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */
global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__).'/../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../htdocs/compta/facture/class/facture.class.php';

if (empty($user->id))
{
	print "Load permissions for admin user with login 'admin'\n";
	$user->fetch('admin');
	$user->getrights();
}


/**
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @covers DoliDb
 * @covers User
 * @covers Translate
 * @covers Conf
 * @covers Interfaces
 * @covers CommonObject
 * @covers Societe
 * @covers Contact
 * @covers Facture
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class FactureTest extends PHPUnit_Framework_TestCase
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @return FactureTest
	 */
	function FactureTest()
	{
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
    public static function tearDownAfterClass()
    {
    	global $conf,$user,$langs,$db;
		$db->rollback();

		print __METHOD__."\n";
    }

	/**
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
	 */
    protected function tearDown()
    {
    	print __METHOD__."\n";
    }

    /**
     */
    public function testFactureCreate()
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$result=$localobject->create($user);

    	$this->assertLessThan($result, 0);
    	print __METHOD__." result=".$result."\n";
    	return $result;
    }

    /**
     * @depends	testFactureCreate
     * The depends says test is run only if previous is ok
     */
    public function testFactureFetch($id)
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$localobject=new Facture($this->savdb);
    	$result=$localobject->fetch($id);

    	$this->assertLessThan($result, 0);
    	print __METHOD__." id=".$id." result=".$result."\n";
    	return $localobject;
    }

    /**
     * @depends	testFactureFetch
     * The depends says test is run only if previous is ok
     */
    public function testFactureUpdate($localobject)
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$localobject->note='New note after update';
    	$result=$localobject->update($user);

    	print __METHOD__." id=".$localobject->id." result=".$result."\n";
    	$this->assertLessThan($result, 0);
    	return $localobject;
    }

    /**
     * @depends	testFactureUpdate
     * The depends says test is run only if previous is ok
     */
    public function testFactureValid($localobject)
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

    	$result=$localobject->set_valid($user);
    	print __METHOD__." id=".$localobject->id." result=".$result."\n";

    	$this->assertLessThan($result, 0);
    	return $localobject->id;
    }

	/**
     * @depends	testFactureValid
     * The depends says test is run only if previous is ok
     */
    public function testFactureDelete($id)
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$localobject=new Facture($this->savdb);
    	$result=$localobject->fetch($id);
		$result=$localobject->delete($id);

		print __METHOD__." id=".$id." result=".$result."\n";
    	$this->assertLessThan($result, 0);
    	return $result;
    }


}
?>