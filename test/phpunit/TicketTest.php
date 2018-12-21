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
 *      \file       test/unit/TicketTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql'); // This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/user/class/usergroup.class.php';
require_once dirname(__FILE__).'/../../htdocs/ticket/class/ticket.class.php';

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
class TicketTest extends PHPUnit_Framework_TestCase
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
	 * testTicketCreate
	 *
	 * @return	int
	 */
	public function testTicketCreate()
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		// Try to create one with bad values
		$localobject=new Ticket($this->savdb);
		$localobject->initAsSpecimen();
		$localobject->ref = '';
		$result=$localobject->create($user);

		print __METHOD__." result=".$result."\n";
		$this->assertEquals(-3, $result, $localobject->error.join(',', $localobject->errors));

		// Try to create one with correct values
		$localobject=new Ticket($this->savdb);
		$localobject->initAsSpecimen();
		$result=$localobject->create($user);

		print __METHOD__." result=".$result."\n";
		$this->assertGreaterThan(0, $result, $localobject->error.join(',', $localobject->errors));

		return $result;
	}

	/**
	 * testTicketFetch
	 *
	 * @param	int		$id		Id of ticket
	 * @return	int
	 *
	 * @depends	testTicketCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testTicketFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$localobject=new Ticket($this->savdb);
		$result=$localobject->fetch($id);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertGreaterThan(0, $result);

		return $localobject;
	}

	/**
	 * testTicketmarkAsRead
	 *
	 * @param	Ticket		$localobject		Ticket
	 * @return	int
	 *
	 * @depends	testTicketFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testTicketmarkAsRead($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$result=$localobject->markAsRead($user);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$this->assertGreaterThan(0, $result);
		return $localobject;
	}

	/**
	 * testTicketsetProject
	 *
	 * @param	Ticket		$localobject		Ticket
	 * @return	int
	 *
	 * @depends	testTicketFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testTicketsetProject($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$project_id = 1;

		$result=$localobject->setProject($project_id);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$this->assertGreaterThan(0, $result);
		return $localobject;
	}

	/**
	 * testTicketsetContract
	 *
	 * @param	Ticket		$localobject		Ticket
	 * @return	int
	 *
	 * @depends	testTicketFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testTicketsetContract($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$contract_id = 1;

		$result=$localobject->setContract($contract_id);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$this->assertGreaterThan(0, $result);
		return $localobject;
	}

	/**
	 * testTicketsetProgression
	 *
	 * @param	Ticket		$localobject		Ticket
	 * @return	int
	 *
	 * @depends	testTicketFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testTicketsetProgression($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$percent = 80;

		$result=$localobject->setProgression($percent);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$this->assertGreaterThan(0, $result);
		return $localobject;
	}

	/**
	 * testTicketassignUser
	 *
	 * @param	Ticket		$localobject		Ticket
	 * @return	int
	 *
	 * @depends	testTicketFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testTicketassignUser($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$user_id_to_assign = 1;

		$result=$localobject->assignUser($user, $user_id_to_assign);
        ;
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$this->assertGreaterThan(0, $result);
		return $localobject;
	}

	/**
	 * testTicketassignUserOther
	 *
	 * @param	Ticket		$localobject		Ticket
	 * @return	int
	 *
	 * @depends	testTicketFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testTicketassignUserOther($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$user_id_to_assign = 2;

		$result=$localobject->assignUser($user, $user_id_to_assign);
		;
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$this->assertGreaterThan(0, $result);
		return $localobject;
	}

	/**
	 * testTicketcreateTicketLog
	 *
	 * @param	Ticket		$localobject		Ticket
	 * @return	int
	 *
	 * @depends	testTicketFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testTicketcreateTicketLog($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;


		$message = 'Test ticket log';
		$noemail = 1;
		$result=$localobject->createTicketLog($user, $message, $noemail);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$this->assertGreaterThan(0, $result);
		return $localobject;
	}

	/**
	 * testTicketclose
	 *
	 * @param	Ticket		$localobject		Ticket
	 * @return	int
	 *
	 * @depends	testTicketFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testTicketclose($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$result=$localobject->close();
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$this->assertGreaterThan(0, $result);
		return $localobject->id;
	}


	/**
	 * testTicketDelete
	 *
	 * @param	int		$id		Id of ticket
	 * @return	int
	 *
	 * @depends	testTicketclose
	 * The depends says test is run only if previous is ok
	 */
	public function testTicketDelete($id)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$localobject=new Ticket($this->savdb);
		$result=$localobject->fetch($id);
		$result=$localobject->delete($user);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertGreaterThan(0, $result);
		return $result;
	}
}
