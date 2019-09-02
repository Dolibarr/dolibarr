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
 *      \file       test/phpunit/ProductTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/product/class/product.class.php';

if (empty($user->id))
{
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
class ProductTest extends PHPUnit\Framework\TestCase
{
    protected $savconf;
    protected $savuser;
    protected $savlangs;
    protected $savdb;

    /**
     * Constructor
     * We save global variables into local variables
     *
     * @return ProductTest
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

        if (empty($conf->produit->enabled)) { print __METHOD__." Module Product must be enabled.\n"; die(); }

        $db->begin(); // This is to have all actions inside a transaction even if test launched without suite.

        print __METHOD__."\n";
    }
    // teardownafterclass
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
     * @return  void
     */
    protected function tearDown()
    {
        print __METHOD__."\n";
    }

    /**
     * testProductCreate
     *
     * @return  void
     */
    public function testProductCreate()
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $localobject=new Product($this->savdb);
        $localobject->initAsSpecimen();
        $result=$localobject->create($user);

        print __METHOD__." result=".$result."\n";
        $this->assertLessThanOrEqual($result, 0);

        return $result;
    }

    /**
     * testProductFetch
     *
     * @param   int $id     Id product
     * @return  Product
     *
     * @depends testProductCreate
     * The depends says test is run only if previous is ok
     */
    public function testProductFetch($id)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $localobject=new Product($this->savdb);
        $result=$localobject->fetch($id);
        print __METHOD__." id=".$id." result=".$result."\n";
        $this->assertLessThan($result, 0);

        return $localobject;
    }

    /**
     * testProductUpdate
     *
     * @param   Product $localobject    Product
     * @return  void
     *
     * @depends testProductFetch
     * The depends says test is run only if previous is ok
     */
    public function testProductUpdate($localobject)
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
     * testProductOther
     *
     * @param   Product $localobject    Product
     * @return  void
     *
     * @depends	testProductUpdate
     * The depends says test is run only if previous is ok
     */
    public function testProductOther($localobject)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;


        return $localobject->id;
    }

    /**
     * testProductDelete
     *
     * @param       int $id     Id of product
     * @return      void
     *
     * @depends testProductOther
     * The depends says test is run only if previous is ok
     */
    public function testProductDelete($id)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $localobject=new Product($this->savdb);
        $result=$localobject->fetch($id);

        $result=$localobject->delete($user);
        print __METHOD__." id=".$id." result=".$result."\n";
        $this->assertLessThan($result, 0);

        return $result;
    }

    /**
     * testProductStatic
     *
     * @return  void
     */
    public function testProductStatic()
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $localobject=new Product($db);


        return;
    }
}
