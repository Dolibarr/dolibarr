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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *      \file       test/phpunit/FichinterTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/fichinter/class/fichinter.class.php';

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
class FichinterTest extends PHPUnit\Framework\TestCase
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
	 * testFichinterCreate
	 *
	 * @return	int
	 */
	public function testFichinterCreate()
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$localobject=new Fichinter($this->savdb);
		$localobject->initAsSpecimen();
		$result=$localobject->create($user);

		print __METHOD__." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $result;
	}

	/**
	 * testFichinterFetch
	 *
	 * @param	int		$id		Id of intervention
	 * @return	int
	 *
	 * @depends	testFichinterCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testFichinterFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$localobject=new Fichinter($this->savdb);
		$result=$localobject->fetch($id);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $localobject;
	}

	/**
	 * testFichinterValid
	 *
	 * @param	Fichinter		$localobject		Intervention
	 * @return	int
	 *
	 * @depends	testFichinterFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testFichinterValid($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$result=$localobject->setValid($user);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$this->assertLessThan($result, 0);
		return $localobject;
	}

	/**
	 * testFichinterValid
	 *
	 * @param	Fichinter	$localobject	Object intervention
	 * @return	int
	 *
	 * @depends testFichinterValid
	 * The depends says test is run only if previous is ok
	 */
	public function testFichinterOther($localobject)
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

		$localobject->info($localobject->id);
		print __METHOD__." localobject->date_creation=".$localobject->date_creation."\n";
		$this->assertNotEquals($localobject->date_creation, '');

		return $localobject->id;
	}

	/**
	 * testFichinterDelete
	 *
	 * @param	int		$id		Id of intervention
	 * @return	int
	 *
	 * @depends	testFichinterOther
	 * The depends says test is run only if previous is ok
	 */
	public function testFichinterDelete($id)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$localobject=new Fichinter($this->savdb);
		$result=$localobject->fetch($id);
		$result=$localobject->delete($user);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $result;
	}
}
