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
 *      \file       test/phpunit/CategorieTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/categories/class/categorie.class.php';
require_once dirname(__FILE__).'/../../htdocs/product/class/product.class.php';

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
class CategorieTest extends PHPUnit\Framework\TestCase
{
    protected $savconf;
    protected $savuser;
    protected $savlangs;
    protected $savdb;

    /**
     * Constructor
     * We save global variables into local variables
     *
     * @return CategorieTest
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
     * testCategorieCreate
     *
     * @return int
     */
    public function testCategorieCreate()
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;


        // We create a category
        $localobject=new Categorie($this->savdb);
        $localobject->initAsSpecimen();

        // Check it does not exist (return 0)
        $resultCheck=$localobject->already_exists();
        print __METHOD__." resultCheck=".$resultCheck."\n";
        $this->assertEquals(0, $resultCheck);

        // Create
        $resultFirstCreate=$localobject->create($user);
        print __METHOD__." resultFirstCreate=".$resultFirstCreate."\n";
        $this->assertGreaterThan(0, $resultFirstCreate);

        // We try to create another one with same ref
        $localobject2=new Categorie($this->savdb);
        $localobject2->initAsSpecimen();

        // Check it does exist (return 1)
        $resultCheck=$localobject2->already_exists();
        print __METHOD__." resultCheck=".$resultCheck."\n";
        $this->assertGreaterThan(0, $resultCheck);

        $resultSecondCreate=$localobject2->create($user);
        print __METHOD__." result=".$resultSecondCreate."\n";
        $this->assertEquals(-4, $resultSecondCreate);

        return $resultFirstCreate;
    }

    /**
     * testCategorieProduct
     *
     * @param   int $id     Id of category
     * @return  int
     *
     * @depends testCategorieCreate
     * The depends says test is run only if previous is ok
     */
    public function testCategorieProduct($id)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $localobjecttmp=new Categorie($this->savdb);
        $localobjecttmp->initAsSpecimen();
        $localobjecttmp->label='Specimen Category for product';
        $localobjecttmp->type=0;    // product category
        $catid=$localobjecttmp->create($user);

        print __METHOD__." catid=".$catid."\n";
        $this->assertGreaterThan(0, $catid);

        // Try to create product linked to category
        $localobject2=new Product($this->savdb);
        $localobject2->initAsSpecimen();
        $localobject2->ref.='-CATEG';
        $localobject2->tva_npr=1;
        $result=$localobject2->create($user);
        $cat = new Categorie($this->savdb);
        $cat->id = $catid;
        $result=$cat->add_type($localobject2, "product");

        print __METHOD__." result=".$result."\n";
        $this->assertGreaterThan(0, $result);

        // Get list of categories for product
        $localcateg=new Categorie($this->savdb);
        $listofcateg=$localcateg->containing($localobject2->id, Categorie::TYPE_PRODUCT, 'label');
        $this->assertTrue(in_array('Specimen Category for product', $listofcateg), 'Categ not found linked to product when it should');

        return $id;
    }

    /**
     * testCategorieFetch
     *
     * @param   int $id     Id of category
     * @return  int
     *
     * @depends testCategorieProduct
     * The depends says test is run only if previous is ok
     */
    public function testCategorieFetch($id)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $localobject=new Categorie($this->savdb);
        $result=$localobject->fetch($id);

        print __METHOD__." id=".$id." result=".$result."\n";
        $this->assertGreaterThan(0, $result);
        return $localobject;
    }

    /**
     * testCategorieUpdate
     *
     * @param   Category        $localobject        Category
     * @return  int

     * @depends testCategorieFetch
     * The depends says test is run only if previous is ok
     */
    public function testCategorieUpdate($localobject)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $localobject->note='New note after update';
        $result=$localobject->update($user);

        print __METHOD__." id=".$localobject->id." result=".$result."\n";
        $this->assertGreaterThan(0, $result);
        return $localobject;
    }

    /**
     * testCategorieOther
     *
     * @param   Category    $localobject    Category
     * @return  int
     *
     * @depends testCategorieUpdate
     * The depends says test is run only if previous is ok
     */
    public function testCategorieOther($localobject)
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
        $localobject2=new Categorie($db);
        $localobject2->initAsSpecimen();

        $retarray=$localobject->liste_photos('/');
        print __METHOD__." retarray size=".count($retarray)."\n";
        $this->assertTrue(is_array($retarray));

        return $localobject->id;
    }

    /**
     * testCategorieDelete
     *
     * @param   int $id     Id of category
     * @return  int
     *
     * @depends	testCategorieOther
     * The depends says test is run only if previous is ok
     */
    public function testCategorieDelete($id)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $localobject=new Categorie($this->savdb);
        $result=$localobject->fetch($id);
        $result=$localobject->delete($user);

        print __METHOD__." id=".$id." result=".$result."\n";
        $this->assertGreaterThan(0, $result);
        return $result;
    }

    /**
     * testCategorieStatic
     *
     * @return  void
     *
     * @depends  testCategorieDelete
     */
    public function testCategorieStatic()
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $localobject=new Categorie($this->savdb);
        $retarray=$localobject->get_full_arbo(3);

        print __METHOD__." retarray size=".count($retarray)."\n";
        $this->assertTrue(is_array($retarray));
        return $retarray;
    }
}
