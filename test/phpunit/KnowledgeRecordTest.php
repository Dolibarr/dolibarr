<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2021 SuperAdmin
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
 * \file    test/phpunit/KnowledgeRecordTest.php
 * \ingroup knowledgemanagement
 * \brief   PHPUnit test for KnowledgeRecord class.
 */

global $conf, $user, $langs, $db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/knowledgemanagement/class/knowledgerecord.class.php';
$langs->load("main");

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->getrights();
}

$conf->global->MAIN_DISABLE_ALL_MAILS = 1;




/**
 * Class KnowledgeRecordTest
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class KnowledgeRecordTest extends PHPUnit\Framework\TestCase
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @return KnowledgeRecordTest
	 */
	public function __construct()
	{
		parent::__construct();

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
	 * @return void
	 */
	public static function setUpBeforeClass()
	{
		global $conf, $user, $langs, $db;
		$db->begin(); // This is to have all actions inside a transaction even if test launched without suite.

		if (empty($conf->knowledgemanagement->enabled)) {
			print __METHOD__." module knowledgemanagement must be enabled.\n"; die(1);
		}
	}

	/**
	 * Unit test setup
	 *
	 * @return void
	 */
	protected function setUp()
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
	 * @return void
	 */
	protected function tearDown()
	{
		print __METHOD__."\n";
	}

	/**
	 * Global test teardown
	 *
	 * @return void
	 */
	public static function tearDownAfterClass()
	{
		global $conf, $user, $langs, $db;
		$db->rollback();

		print __METHOD__."\n";
	}


	/**
	 * A sample test
	 *
	 * @return bool
	 */
	public function testSomething()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = true;

		print __METHOD__." result=".$result."\n";
		$this->assertTrue($result);

		return $result;
	}

	/**
	 * testKnowledgeRecordCreate
	 *
	 * @return int
	 */
	public function testKnowledgeRecordCreate()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new KnowledgeRecord($this->savdb);
		$localobject->initAsSpecimen();
		$result = $localobject->create($user);

		print __METHOD__." result=".$result."\n";
		$this->assertGreaterThanOrEqual(0, $result);

		return $result;
	}

	/**
	 * testKnowledgeRecordDelete
	 *
	 * @param	int		$id		Id of object
	 * @return	int
	 *
	 * @depends	testKnowledgeRecordCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testKnowledgeRecordDelete($id)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new KnowledgeRecord($this->savdb);
		print __METHOD__." id=".$id."\n";
		$result = $localobject->fetch($id);
		print __METHOD__." result=".$result."\n";
		$this->assertGreaterThanOrEqual(0, $result, 'fetch in testKnowledgeRecordDelete with id='.$id);

		$result = $localobject->delete($user);

		print __METHOD__." result=".$result."\n";
		$this->assertGreaterThanOrEqual(0, $result, 'delete in testKnowledgeRecordDelete');
		return $result;
	}
}
