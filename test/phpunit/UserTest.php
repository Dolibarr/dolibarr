<?php
/* Copyright (C) 2010-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023      Alexandre Janniaux   <alexandre.janniaux@gmail.com>
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
 *      \file       test/phpunit/UserTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/user/class/user.class.php';

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
class UserTest extends PHPUnit\Framework\TestCase
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
	 * @return UserTest
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

		if (getDolGlobalString('MAIN_MODULE_LDAP')) {
			print "\n".__METHOD__." module LDAP must be disabled.\n";
			die(1);
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
	 * testUserCreate
	 *
	 * @return  void
	 */
	public function testUserCreate()
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		print __METHOD__." USER_PASSWORD_GENERATED=".getDolGlobalString('USER_PASSWORD_GENERATED')."\n";

		$localobject=new User($db);
		$localobject->initAsSpecimen();
		$result=$localobject->create($user);

		$this->assertLessThan($result, 0, 'Creation of user has failed: '.$localobject->error);
		print __METHOD__." result=".$result."\n";
		return $result;
	}

	/**
	 * testUserFetch
	 *
	 * @param   int $id             Id of user
	 * @return  void
	 * @depends testUserCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testUserFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$localobject=new User($db);
		$result=$localobject->fetch($id);

		$this->assertLessThan($result, 0);
		print __METHOD__." id=".$id." result=".$result."\n";
		return $localobject;
	}

	/**
	 * testUserUpdate
	 *
	 * @param   User  $localobject     User
	 * @return  void
	 * @depends testUserFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testUserUpdate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$this->changeProperties($localobject);
		$result=$localobject->update($user);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		// Test everything are still same than specimen
		$newlocalobject=new User($db);
		$newlocalobject->initAsSpecimen();
		$this->changeProperties($newlocalobject);
		$this->assertEquals($this->objCompare($localobject, $newlocalobject, true, array('id','socid','societe_id','specimen','note','ref','pass','pass_indatabase','pass_indatabase_crypted','pass_temp','datec','datem','datelastlogin','datepreviouslogin','flagdelsessionsbefore','iplastlogin','ippreviouslogin','trackid')), array());    // Actual, Expected

		return $localobject;
	}

	/**
	 * testUserDisable
	 *
	 * @param   User  $localobject     User
	 * @return  void
	 * @depends testUserUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testUserDisable($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$result=$localobject->setstatus(0);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$this->assertLessThan($result, 0);

		return $localobject;
	}

	/**
	 * testUserOther
	 *
	 * @param   User  $localobject     User
	 * @return  void
	 * @depends testUserDisable
	 * The depends says test is run only if previous is ok
	 */
	public function testUserOther($localobject)
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

		return $localobject;
	}

	/**
	 * testUserHasRight
	 * @param	User  $localobject		 User
	 * @return  User  $localobject		 User
	 * @depends testUserOther
	 */
	public function testUserHasRight($localobject)
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

		print __METHOD__." id=". $localobject->id ."\n";
		//$this->assertNotEquals($user->date_creation, '');
		$localobject->addrights(0, 'supplier_proposal');
		$this->assertEquals($localobject->hasRight('member', ''), 0);
		$this->assertEquals($localobject->hasRight('member', 'member'), 0);
		$this->assertEquals($localobject->hasRight('product', 'member', 'read'), 0);
		$this->assertEquals($localobject->hasRight('member', 'member'), 0);
		$this->assertEquals($localobject->hasRight('produit', 'member', 'read'), 0);

		return $localobject;
	}

	/**
	 * testUserSetPassword
	 *
	 * @param   User  $localobject     User
	 * @return  void
	 * @depends testUserHasRight
	 * The depends says test is run only if previous is ok
	 */
	public function testUserSetPassword($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		// Test the 'none' password generator

		$conf->global->USER_PASSWORD_GENERATED = 'none';

		$localobject->error = '';
		$result = $localobject->setPassword($user, 'abcdef');
		print __METHOD__." set a small password with USER_PASSWORD_GENERATED = none\n";
		print __METHOD__." localobject->error=".$localobject->error."\n";
		$this->assertEquals('abcdef', $result);

		// Test the 'standard' password generator

		$conf->global->USER_PASSWORD_GENERATED = 'standard';

		$localobject->error = '';
		$result = $localobject->setPassword($user, '123456789AA');
		print __METHOD__." set a too small password with USER_PASSWORD_GENERATED = standard\n";
		print __METHOD__." localobject->error=".$localobject->error."\n";
		$this->assertEquals(-1, $result, 'We must receive a negative error code (pass too small) and we did not here');

		// Test the 'perso' password generator

		$conf->global->USER_PASSWORD_GENERATED = 'perso';
		$conf->global->USER_PASSWORD_PATTERN = '12;2;2;2;3;1';

		$localobject->error = '';
		$result = $localobject->setPassword($user, '1234567892BB');
		print __METHOD__." set a too small password with USER_PASSWORD_GENERATED = perso\n";
		print __METHOD__." localobject->error=".$localobject->error."\n";
		$this->assertEquals(-1, $result, 'We must receive a negative error code (pass too small) and we did not here');

		$localobject->error = '';
		$result = $localobject->setPassword($user, '$*34567890AB');
		print __METHOD__." set a good password\n";
		print __METHOD__." localobject->error=".$localobject->error."\n";
		$this->assertEquals('$*34567890AB', $result, 'We must get the password as it is valid (pass enough long) and we did not here');

		// Test uppercase : $chartofound = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";

		$localobject->error = '';
		$result = $localobject->setPassword($user, '$*123456789A');
		print __METHOD__." set a password without uppercase\n";
		print __METHOD__." localobject->error=".$localobject->error."\n";
		$this->assertEquals(-1, $result, 'We must receive a negative error code (pass without enough uppercase) and we did not here');

		$localobject->error = '';
		$result = $localobject->setPassword($user, '$*34567890CD');
		print __METHOD__." set a password with enough uppercase\n";
		print __METHOD__." localobject->error=".$localobject->error."\n";
		$this->assertEquals('$*34567890CD', $result, 'We must get the password as it is valid (pass with enough uppercase) and we did not here');

		// Test digits : $chartofound = "!@#$%&*()_-+={}[]\\|:;'/";

		$localobject->error = '';
		$result = $localobject->setPassword($user, '$*ABCDEFGHIJ');
		print __METHOD__." set a password without digits\n";
		print __METHOD__." localobject->error=".$localobject->error."\n";
		$this->assertEquals(-1, $result, 'We must receive a negative error code (pass without enough digits) and we did not here');

		$localobject->error = '';
		$result = $localobject->setPassword($user, '$*12ABCDEFGH');
		print __METHOD__." set a password with enough digits\n";
		print __METHOD__." localobject->error=".$localobject->error."\n";
		$this->assertEquals('$*12ABCDEFGH', $result, 'We must get the password as it is valid (pass with enough digits) and we did not here');

		// Test special chars : $chartofound = "!@#$%&*()_-+={}[]\\|:;'/";

		$localobject->error = '';
		$result = $localobject->setPassword($user, '1234567890AA');
		print __METHOD__." set a password without enough special chars\n";
		print __METHOD__." localobject->error=".$localobject->error."\n";
		$this->assertEquals(-1, $result, 'We must receive a negative error code (pass without enough special chars) and we did not here');

		$localobject->error = '';
		$result = $localobject->setPassword($user, '$*12345678AA');
		print __METHOD__." set a password with enough special chars\n";
		print __METHOD__." localobject->error=".$localobject->error."\n";
		$this->assertEquals('$*12345678AA', $result, 'We must get the password as it is valid (pass with enough special chars) and we did not here');

		// Test consecutive chars
		$localobject->error = '';
		$result = $localobject->setPassword($user, '$*1111567890AA');
		print __METHOD__." set a password with too many consecutive chars\n";
		print __METHOD__." localobject->error=".$localobject->error."\n";
		$this->assertEquals(-1, $result, 'We must receive a negative error code (pass has n consecutive similar chars) and we did not here');

		$localobject->error = '';
		$result = $localobject->setPassword($user, '$*11145678AA');
		print __METHOD__." set a password with noo too much consecutive chars\n";
		print __METHOD__." localobject->error=".$localobject->error."\n";
		$this->assertEquals('$*11145678AA', $result, 'We must get the password as it is valid (pass has not too much similar consecutive chars) and we did not here');


		return $localobject->id;
	}


	/**
	 * testUserDelete
	 *
	 * @param   int  $id      User id
	 * @return  void
	 * @depends testUserSetPassword
	 * The depends says test is run only if previous is ok
	 */
	public function testUserDelete($id)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$localobject=new User($db);
		$result=$localobject->fetch($id);
		$result=$localobject->delete($user);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $result;
	}

	/**
	 * testUserAddPermission
	 *
	 * @param   int  $id      User id
	 * @return  void
	 * @depends testUserDelete
	 * The depends says test is run only if previous is ok
	 */
	public function testUserAddPermission($id)
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$localobject=new User($db);
		$result=$localobject->fetch(1);			// Other tests use the user id 1
		$result=$localobject->addrights(0, 'supplier_proposal');

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $result;
	}



	/**
	 * Edit an object to test updates
	 *
	 * @param   Object   $localobject        Object User
	 * @return  void
	 */
	public function changeProperties(&$localobject)
	{
		$localobject->note_private='New note after update';
	}

	/**
	 * Compare all public properties values of 2 objects
	 *
	 * @param   Object      $oA                     Object operand 1
	 * @param   Object      $oB                     Object operand 2
	 * @param   boolean     $ignoretype             False will not report diff if type of value differs
	 * @param   array       $fieldstoignorearray    Array of fields to ignore in diff
	 * @return  array                               Array with differences
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
					$retAr[]=$sKey.' : '.(is_object($oVarsA[$sKey]) ? get_class($oVarsA[$sKey]) : $oVarsA[$sKey]).' <> '.(is_object($oVarsB[$sKey]) ? get_class($oVarsB[$sKey]) : $oVarsB[$sKey]);
				}
				if ($ignoretype && ($oVarsA[$sKey] != $oVarsB[$sKey])) {
					$retAr[]=$sKey.' : '.(is_object($oVarsA[$sKey]) ? get_class($oVarsA[$sKey]) : $oVarsA[$sKey]).' <> '.(is_object($oVarsB[$sKey]) ? get_class($oVarsB[$sKey]) : $oVarsB[$sKey]);
				}
			}
		}
		return $retAr;
	}
}
