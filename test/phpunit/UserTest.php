<?php
/* Copyright (C) 2010-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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
     * @return UserTest
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

        if (! empty($conf->global->MAIN_MODULE_LDAP)) { print "\n".__METHOD__." module LDAP must be disabled.\n"; die(); }

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

        $localobject=new User($this->savdb);
        $localobject->initAsSpecimen();
        $result=$localobject->create($user);

        $this->assertLessThan($result, 0);
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

        $localobject=new User($this->savdb);
        $result=$localobject->fetch($id);

        $this->assertLessThan($result, 0);
        print __METHOD__." id=".$id." result=".$result."\n";
        return $localobject;
    }

    /**
     * testUserUpdate
     *
     * @param   Object  $localobject     User
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
        $newlocalobject=new User($this->savdb);
        $newlocalobject->initAsSpecimen();
        $this->changeProperties($newlocalobject);
        $this->assertEquals($this->objCompare($localobject, $newlocalobject, true, array('id','socid','societe_id','note','ref','pass','pass_indatabase','pass_indatabase_crypted','datec','datem','datelastlogin','datepreviouslogin')), array());    // Actual, Expected

        return $localobject;
    }

    /**
     * testUserDisable
     *
     * @param   Object  $localobject     User
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
     * @param   Object  $localobject     User
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

        return $localobject->id;
    }

    /**
     * testUserDelete
     *
     * @param   Object  $id      User
     * @return  void
     * @depends testUserOther
     * The depends says test is run only if previous is ok
     */
    public function testUserDelete($id)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $localobject=new User($this->savdb);
        $result=$localobject->fetch($id);
        $result=$localobject->delete($user);

        print __METHOD__." id=".$id." result=".$result."\n";
        $this->assertLessThan($result, 0);
        return $result;
    }

    /**
     * testUserAddPermission
     *
     * @param   Object  $id      User
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

    	$localobject=new User($this->savdb);
    	$result=$localobject->fetch(1);			// Other tests use the user id 1
    	$result=$localobject->addrights(0, 'supplier_proposal');

    	print __METHOD__." id=".$id." result=".$result."\n";
    	$this->assertLessThan($result, 0);
    	return $result;
    }



    /**
     * Edit an object to test updates
     *
     * @param   mixed   $localobject        Object User
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
            foreach($aKeys as $sKey) {
                if (in_array($sKey, $fieldstoignorearray))
                    continue;
                if (! $ignoretype && $oVarsA[$sKey] !== $oVarsB[$sKey]) {
                    $retAr[]=$sKey.' : '.(is_object($oVarsA[$sKey])?get_class($oVarsA[$sKey]):$oVarsA[$sKey]).' <> '.(is_object($oVarsB[$sKey])?get_class($oVarsB[$sKey]):$oVarsB[$sKey]);
                }
                if ($ignoretype && $oVarsA[$sKey] != $oVarsB[$sKey]) {
                    $retAr[]=$sKey.' : '.(is_object($oVarsA[$sKey])?get_class($oVarsA[$sKey]):$oVarsA[$sKey]).' <> '.(is_object($oVarsB[$sKey])?get_class($oVarsB[$sKey]):$oVarsB[$sKey]);
                }
            }
        }
        return $retAr;
    }
}
