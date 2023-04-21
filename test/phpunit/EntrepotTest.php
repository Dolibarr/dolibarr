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
 *      \file       test/phpunit/EntrepotTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/product/stock/class/entrepot.class.php';

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
class EntrepotTest extends PHPUnit\Framework\TestCase
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @return EntrepotTest
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
	public static function setUpBeforeClass(): void
	{
		global $conf,$user,$langs,$db;

		if (!isModEnabled('stock')) {
			print __METHOD__." Module Stock must be enabled.\n"; die(1);
		}

		$db->begin();	// This is to have all actions inside a transaction even if test launched without suite.

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
	 * @return	void
	 */
	protected function setUp(): void
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
	protected function tearDown(): void
	{
		print __METHOD__."\n";
	}

	/**
	 * testEntrepotCreate
	 *
	 * @return	void
	 */
	public function testEntrepotCreate()
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$localobject=new Entrepot($this->savdb);
		$localobject->initAsSpecimen();
		$result=$localobject->create($user);

		print __METHOD__." result=".$result."\n";
		$this->assertGreaterThan(0, $result);

		return $result;
	}

	/**
	 * testEntrepotFetch
	 *
	 * @param	int		$id		Id entrepot
	 * @return	Entrepot
	 *
	 * @depends	testEntrepotCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testEntrepotFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$localobject=new Entrepot($this->savdb);
		$result=$localobject->fetch($id);
		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $localobject;
	}

	/**
	 * testEntrepotUpdate
	 *
	 * @param	Entrepot	$localobject	Entrepot
	 * @return	void
	 *
	 * @depends	testEntrepotFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testEntrepotUpdate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$localobject->note='New note after update';
		$result=$localobject->update($localobject->id, $user);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $localobject;
	}

	/**
	 * testEntrepotOther
	 *
	 * @param	Entrepot	$localobject	Entrepot
	 * @return	void
	 *
	 * @depends	testEntrepotUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testEntrepotOther($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$result = $localobject->get_full_arbo();
		$this->assertEquals('WAREHOUSE SPECIMEN', $result);

		return $localobject->id;
	}

	/**
	 * testEntrepotDelete
	 *
	 * @param		int		$id		Id of entrepot
	 * @return		void
	 *
	 * @depends	testEntrepotOther
	 * The depends says test is run only if previous is ok
	 */
	public function testEntrepotDelete($id)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$localobject=new Entrepot($this->savdb);
		$result=$localobject->fetch($id);

		$result=$localobject->delete($user);
		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0, $localobject->errorsToString());

		return $result;
	}
}
