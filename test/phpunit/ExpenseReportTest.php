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
 *      \file       test/phpunit/ExpenseReportTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/expensereport/class/expensereport.class.php';

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
class ExpenseReportTest extends PHPUnit\Framework\TestCase
{
    protected $savconf;
    protected $savuser;
    protected $savlangs;
    protected $savdb;

    /**
     * Constructor
     * We save global variables into local variables
     *
     * @return ExpenseReportTest
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
        $db->begin(); // This is to have all actions inside a transaction even if test launched without suite.

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
        //print $db->getVersion()."\n";
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
     * testExpenseReportCreate
     *
     * @return	void
     */
    public function testExpenseReportCreate()
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        // Create supplier order with a too low quantity
        $localobject=new ExpenseReport($db);
        $localobject->initAsSpecimen();         // Init a specimen with lines
        $localobject->status = 0;
        $localobject->fk_statut = 0;
        $localobject->date_fin = null;  // Force bad value

        $result=$localobject->create($user);
        print __METHOD__." result=".$result."\n";
        $this->assertEquals(-1, $result, "Error on test ExpenseReport create 1 : ".$localobject->error);       // must be -1 because of missing mandatory fields

        $sql="DELETE FROM ".MAIN_DB_PREFIX."expensereport where ref=''";
        $db->query($sql);

        // Create supplier order
        $localobject2=new ExpenseReport($db);
        $localobject2->initAsSpecimen();        // Init a specimen with lines
        $localobject2->status = 0;
        $localobject2->fk_statut = 0;

        $result=$localobject2->create($user);
        print __METHOD__." result=".$result."\n";
        $this->assertGreaterThanOrEqual(0, $result, "Error on test ExpenseReport create 2 : ".$localobject2->error);

        return $result;
    }


    /**
     * testExpenseReportFetch
     *
     * @param   int $id     Id of supplier order
     * @return  void
     *
     * @depends testExpenseReportCreate
     * The depends says test is run only if previous is ok
     */
    public function testExpenseReportFetch($id)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $localobject=new ExpenseReport($this->savdb);
        $result=$localobject->fetch($id);

        print __METHOD__." id=".$id." result=".$result."\n";
        $this->assertLessThan($result, 0);
        return $localobject;
    }

    /**
     * testExpenseReportValid
     *
     * @param   Object $localobject     Supplier order
     * @return  void
     *
     * @depends testExpenseReportFetch
     * The depends says test is run only if previous is ok
     */
    public function testExpenseReportValid($localobject)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $result=$localobject->setValidate($user);

        print __METHOD__." id=".$localobject->id." result=".$result."\n";
        $this->assertLessThan($result, 0);
        return $localobject;
    }

    /**
     * testExpenseReportApprove
     *
     * @param   Object $localobject Supplier order
     * @return  void
     *
     * @depends testExpenseReportValid
     * The depends says test is run only if previous is ok
     */
    public function testExpenseReportApprove($localobject)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $result=$localobject->setApproved($user);

        print __METHOD__." id=".$localobject->id." result=".$result."\n";
        $this->assertLessThan($result, 0);
        return $localobject;
    }

    /**
     * testExpenseReportCancel
     *
     * @param   Object  $localobject        Supplier order
     * @return  void
     *
     * @depends testExpenseReportApprove
     * The depends says test is run only if previous is ok
     */
    public function testExpenseReportCancel($localobject)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $result=$localobject->set_cancel($user, 'Because...');

        print __METHOD__." id=".$localobject->id." result=".$result."\n";
        $this->assertLessThan($result, 0);
        return $localobject;
    }

    /**
     * testExpenseReportOther
     *
     * @param   Object $localobject     Supplier order
     * @return  void
     *
     * @depends testExpenseReportCancel
     * The depends says test is run only if previous is ok
     */
    public function testExpenseReportOther($localobject)
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

        /*$localobject->info($localobject->id);
        print __METHOD__." localobject->date_creation=".$localobject->date_creation."\n";
        $this->assertNotEquals($localobject->date_creation, '');
        */

        return $localobject->id;
    }

    /**
     * testExpenseReportDelete
     *
     * @param   int $id     Id of order
     * @return  void
     *
     * @depends testExpenseReportOther
     * The depends says test is run only if previous is ok
     */
    public function testExpenseReportDelete($id)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $localobject=new ExpenseReport($this->savdb);
        $result=$localobject->fetch($id);
        $result=$localobject->delete($user);

        print __METHOD__." id=".$id." result=".$result."\n";
        $this->assertLessThan($result, 0);
        return $result;
    }
}
