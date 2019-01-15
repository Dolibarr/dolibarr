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
 *      \file       test/phpunit/AdminLibTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/admin.lib.php';

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
 * @remarks backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class AdminLibTest extends PHPUnit_Framework_TestCase
{
    protected $savconf;
    protected $savuser;
    protected $savlangs;
    protected $savdb;

    /**
     * Constructor
     * We save global variables into local variables
     *
     * @return AdminLibTest
     */
    function __construct()
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
     * testVersionCompare
     *
     * @return	void
     */
    public function testVersionCompare()
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $result=versioncompare(array(3,1,-4),array(3,1,1));
        print __METHOD__." result=".$result."\n";
        $this->assertEquals(-3,$result);
        $result=versioncompare(array(3,1,0),array(3,1,1));
        print __METHOD__." result=".$result."\n";
        $this->assertEquals(-3,$result);
        $result=versioncompare(array(3,1,0),array(3,2,0));
        print __METHOD__." result=".$result."\n";
        $this->assertEquals(-2,$result);
        $result=versioncompare(array(3,1,0),array(3,1,0));
        print __METHOD__." result=".$result."\n";
        $this->assertEquals(0,$result);

        return $result;
    }

    /**
     * testEnableModule
     *
     * @return  void
     */
    public function testEnableModule()
    {
    	global $conf, $db, $langs, $user;

		require_once dirname(__FILE__).'/../../htdocs/core/modules/modExpenseReport.class.php';
		print "Enable module modExpenseReport";
		$moduledescriptor=new modExpenseReport($db);
		$moduledescriptor->init();
		$conf->setValues($db);

		require_once dirname(__FILE__).'/../../htdocs/core/modules/modApi.class.php';
		print "Enable module modAPI";
		$moduledescriptor=new modApi($db);
		$moduledescriptor->init();
		$conf->setValues($db);
    }
}
