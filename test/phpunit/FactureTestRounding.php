<?php
/* Copyright (C) 2012-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       test/phpunit/FactureTestRounding.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/compta/facture/class/facture.class.php';

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
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class FactureTestRounding extends PHPUnit\Framework\TestCase
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @return FactureTest
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
     * testFactureRoundingCreate1
     * Test according to page http://wiki.dolibarr.org/index.php/Draft:VAT_calculation_and_rounding#Standard_usage
     *
     * @return int
     */
    public function testFactureRoundingCreate1()
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

		$conf->global->MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND=0;

        $localobject=new Facture($this->savdb);
        $localobject->initAsSpecimen();
        $localobject->lines=array();
        unset($localobject->total_ht);
        unset($localobject->total_ttc);
        unset($localobject->total_tva);
        $result=$localobject->create($user);

        // Add two lines
        for ($i=0; $i<2; $i++)
        {
            $localobject->addline('Description '.$i, 1.24, 1, 10);
        }

        $newlocalobject=new Facture($this->savdb);
        $newlocalobject->fetch($result);
        //var_dump($newlocalobject);

        $this->assertEquals($newlocalobject->total_ht, 2.48);
        $this->assertEquals($newlocalobject->total_tva, 0.24);
        $this->assertEquals($newlocalobject->total_ttc, 2.72);
        return $result;
    }


    /**
     * testFactureRoundingCreate2
     *
     * @return int
     *
     * @depends	testFactureRoundingCreate1
     * Test according to page http://wiki.dolibarr.org/index.php/Draft:VAT_calculation_and_rounding#Standard_usage
     */
    public function testFactureRoundingCreate2()
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $conf->global->MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND=0;

        $localobject=new Facture($this->savdb);
        $localobject->initAsSpecimen();
        $localobject->lines=array();
        unset($localobject->total_ht);
        unset($localobject->total_ttc);
        unset($localobject->total_vat);
        $result=$localobject->create($user);

        // Add two lines
        for ($i=0; $i<2; $i++)
        {
            $localobject->addline('Description '.$i, 1.24, 1, 10);
        }

        $newlocalobject=new Facture($this->savdb);
        $newlocalobject->fetch($result);
        //var_dump($newlocalobject);

        $this->assertEquals($newlocalobject->total_ht, 2.48);
        //$this->assertEquals($newlocalobject->total_tva, 0.25);
        //$this->assertEquals($newlocalobject->total_ttc, 2.73);
        return $result;
    }


    /**
     * testFactureAddLine1
     *
     * @return	void
     */
    public function testFactureAddLine1()
    {
    	global $conf,$user,$langs,$db;
    	$conf=$this->savconf;
    	$user=$this->savuser;
    	$langs=$this->savlangs;
    	$db=$this->savdb;

    	// With option MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND = 0
    	$conf->global->MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND=0;

    	$localobject1a=new Facture($this->savdb);
    	$localobject1a->initAsSpecimen('nolines');
    	$facid=$localobject1a->create($user);
    	$localobject1a->addline('Line 1', 6.36, 15, 21);	// This include update_price
    	print __METHOD__." id=".$facid." total_ttc=".$localobject1a->total_ttc."\n";
    	$this->assertEquals(95.40, $localobject1a->total_ht);
    	$this->assertEquals(20.03, $localobject1a->total_tva);
    	$this->assertEquals(115.43, $localobject1a->total_ttc);

    	// With option MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND = 1
    	$conf->global->MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND=1;

    	$localobject1b=new Facture($this->savdb);
    	$localobject1b->initAsSpecimen('nolines');
    	$facid=$localobject1b->create($user);
    	$localobject1b->addline('Line 1', 6.36, 15, 21);	// This include update_price
    	print __METHOD__." id=".$facid." total_ttc=".$localobject1b->total_ttc."\n";
    	$this->assertEquals(95.40, $localobject1b->total_ht, 'testFactureAddLine1 total_ht');
    	$this->assertEquals(20.03, $localobject1b->total_tva, 'testFactureAddLine1 total_tva');
    	$this->assertEquals(115.43, $localobject1b->total_ttc, 'testFactureAddLine1 total_ttc');
    }

    /**
     * testFactureAddLine2
     *
     * @return	void
     *
     * @depends	testFactureAddLine1
     * The depends says test is run only if previous is ok
     */
    public function testFactureAddLine2()
    {
    	global $conf,$user,$langs,$db;
    	$conf=$this->savconf;
    	$user=$this->savuser;
    	$langs=$this->savlangs;
    	$db=$this->savdb;

    	// With option MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND = 0
    	$conf->global->MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND=0;

    	$localobject2=new Facture($this->savdb);
    	$localobject2->initAsSpecimen('nolines');
    	$facid=$localobject2->create($user);
    	$localobject2->addline('Line 1', 6.36, 5, 21);
    	$localobject2->addline('Line 2', 6.36, 5, 21);
    	$localobject2->addline('Line 3', 6.36, 5, 21);
    	print __METHOD__." id=".$facid." total_ttc=".$localobject2->total_ttc."\n";
    	$this->assertEquals(95.40, $localobject2->total_ht);
    	$this->assertEquals(20.04, $localobject2->total_tva);
    	$this->assertEquals(115.44, $localobject2->total_ttc);

    	// With option MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND = 1
    	$conf->global->MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND=1;

    	$localobject2=new Facture($this->savdb);
    	$localobject2->initAsSpecimen('nolines');
    	$facid=$localobject2->create($user);
    	$localobject2->addline('Line 1', 6.36, 5, 21);
    	$localobject2->addline('Line 2', 6.36, 5, 21);
    	$localobject2->addline('Line 3', 6.36, 5, 21);
    	print __METHOD__." id=".$facid." total_ttc=".$localobject2->total_ttc."\n";
    	$this->assertEquals(95.40, $localobject2->total_ht);
    	$this->assertEquals(20.03, $localobject2->total_tva);
    	$this->assertEquals(115.43, $localobject2->total_ttc);
    }

    /**
     * testFactureAddLine3
     *
     * @return	void
     *
     * @depends	testFactureAddLine2
     * The depends says test is run only if previous is ok
     */
    public function testFactureAddLine3()
    {
    	global $conf,$user,$langs,$db;
    	$conf=$this->savconf;
    	$user=$this->savuser;
    	$langs=$this->savlangs;
    	$db=$this->savdb;

    	// With option MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND = 0
    	$conf->global->MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND=0;

    	$localobject3=new Facture($this->savdb);
    	$localobject3->initAsSpecimen('nolines');
    	$facid=$localobject3->create($user);
    	$localobject3->addline('Line 1', 6.36, 3, 21);
    	$localobject3->addline('Line 2', 6.36, 3, 21);
    	$localobject3->addline('Line 3', 6.36, 3, 21);
    	$localobject3->addline('Line 4', 6.36, 3, 21);
    	$localobject3->addline('Line 5', 6.36, 3, 21);
    	print __METHOD__." id=".$facid." total_ttc=".$localobject3->total_ttc."\n";
    	$this->assertEquals(95.40, $localobject3->total_ht);
    	$this->assertEquals(20.05, $localobject3->total_tva);
    	$this->assertEquals(115.45, $localobject3->total_ttc);

    	// With option MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND = 1
    	$conf->global->MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND=1;

    	$localobject3=new Facture($this->savdb);
    	$localobject3->initAsSpecimen('nolines');
    	$facid=$localobject3->create($user);
    	$localobject3->addline('Line 1', 6.36, 3, 21);
    	$localobject3->addline('Line 2', 6.36, 3, 21);
    	$localobject3->addline('Line 3', 6.36, 3, 21);
    	$localobject3->addline('Line 4', 6.36, 3, 21);
    	$localobject3->addline('Line 5', 6.36, 3, 21);
    	print __METHOD__." id=".$facid." total_ttc=".$localobject3->total_ttc."\n";
    	$this->assertEquals(95.40, $localobject3->total_ht);
    	$this->assertEquals(20.03, $localobject3->total_tva);
    	$this->assertEquals(115.43, $localobject3->total_ttc);
    }
}
