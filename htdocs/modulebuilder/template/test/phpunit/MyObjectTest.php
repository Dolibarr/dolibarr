<?php
/* Copyright (C) 2007-2017	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2023		Alexandre Janniaux			<alexandre.janniaux@gmail.com>
 * Copyright (C) ---Replace with your own copyright and developer email---
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    test/phpunit/MyObjectTest.php
 * \ingroup mymodule
 * \brief   PHPUnit test for MyObject class.
 */

global $conf, $user, $langs, $db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver

//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/mymodule/class/myobject.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;

$langs->load("main");


/**
 * Class MyObjectTest
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 * @phan-file-suppress PhanCompatibleVoidTypePHP70
 */
class MyObjectTest extends PHPUnit\Framework\TestCase  // @phan-suppress-current-line PhanUndeclaredExtendedClass
{
	/**
	 * @var Conf Saved configuration object
	 */
	protected $savconf;
	/**
	 * @var User Saved User object
	 */
	protected $savuser;
	/**
	 * @var Translate Saved translations object (from $langs)
	 */
	protected $savlangs;
	/**
	 * @var DoliDB Saved database object
	 */
	protected $savdb;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @param 	string	$name		Name
	 */
	public function __construct($name = '')
	{
		parent::__construct($name);  // @phan-suppress-current-line PhanUndeclaredClass

		//$this->sharedFixture
		global $conf, $user, $langs, $db;
		$this->savconf = $conf;
		$this->savuser = $user;
		$this->savlangs = $langs;
		$this->savdb = $db;

		print __METHOD__." db->type=".$db->type." user->id=".$user->id;
		//print " - db ".$db->db;
		print "\n";
	}

	/**
	 * Global test setup
	 *
	 * @return void No return value
	 */
	public static function setUpBeforeClass(): void
	{
		global $conf, $user, $langs, $db;
		$db->begin(); // This is to have all actions inside a transaction even if test launched without suite.

		print __METHOD__."\n";
	}

	/**
	 * Unit test setup
	 *
	 * @return void No return value
	 */
	protected function setUp(): void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		print __METHOD__."\n";
	}

	/**
	 * Unit test teardown
	 *
	 * @return void  No return value
	 */
	protected function tearDown(): void
	{
		print __METHOD__."\n";
	}

	/**
	 * Global test teardown
	 *
	 * @return void No return value
	 */
	public static function tearDownAfterClass(): void
	{
		global $conf, $user, $langs, $db;
		$db->rollback();

		print __METHOD__."\n";
	}


	/**
	 * A sample test
	 *
	 * @return bool
	 * @phan-suppress PhanUndeclaredMethod
	 */
	public function testSomething()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = true;

		print __METHOD__." result=".((int) $result)."\n";
		$this->assertTrue($result);

		return $result;
	}

	/**
	 * testMyObjectCreate
	 *
	 * @return int
	 * @phan-suppress PhanUndeclaredMethod
	 */
	public function testMyObjectCreate()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new MyObject($this->savdb);
		$localobject->initAsSpecimen();
		$result = $localobject->create($user);

		print __METHOD__." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $result;
	}

	/**
	 * testMyObjectDelete
	 *
	 * @param	int		$id		Id of object
	 * @return	int
	 *
	 * @depends	testMyObjectCreate
	 * The depends says test is run only if previous is ok
	 * @phan-suppress PhanUndeclaredMethod
	 */
	public function testMyObjectDelete($id)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new MyObject($this->savdb);
		$result = $localobject->fetch($id);
		$result = $localobject->delete($user);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $result;
	}
}  // @phan-suppress-current-line PhanUndeclaredClass
