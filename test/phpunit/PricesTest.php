<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
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
 *      \file       test/phpunit/PricesTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/price.lib.php';
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
class PricesTest extends PHPUnit_Framework_TestCase
{
    protected $savconf;
    protected $savuser;
    protected $savlangs;
    protected $savdb;

    /**
     * Constructor
     * We save global variables into local variables
     *
     * @return CoreTest
     */
    function __construct()
    {
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
        //$db->begin();	// This is to have all actions inside a transaction even if test launched without suite.

        print __METHOD__."\n";
    }
    public static function tearDownAfterClass()
    {
        global $conf,$user,$langs,$db;
        //$db->rollback();

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
     * Test function calcul_price_total
     *
     * @return 	boolean
     * @see		http://wiki.dolibarr.org/index.php/Draft:VAT_calculation_and_rounding#Standard_usage
     */
    public function testCalculPriceTotal()
    {
		global $conf,$user,$langs,$db;
		$this->savconf=$conf;
		$this->savuser=$user;
		$this->savlangs=$langs;
		$this->savdb=$db;

		// qty=1, unit_price=1.24, discount_line=0, vat_rate=10, price_base_type='HT'
        $result1=calcul_price_total(1, 1.24, 0, 10, 0, 0, 0, 'HT', 0);
        print __METHOD__." result1=".join(', ',$result1)."\n";
        // result[0,1,2,3,4,5,6,7,8]	(total_ht, total_vat, total_ttc, pu_ht, pu_tva, pu_ttc, total_ht_without_discount, total_vat_without_discount, total_ttc_without_discount)
        $this->assertEquals(array(1.24, 0.12, 1.36, 1.24, 0.124, 1.364, 1.24, 0.12, 1.36, 0, 0, 0, 0, 0, 0, 0),$result1);

        // 10 * 10 HT - 0% discount with 10% vat and 1.4% localtax1 type 1, 0% localtax2 type 0
        $result2=calcul_price_total(10, 10, 0, 10, 1.4, 0, 0, 'HT', 0, 0, 1, 0);
		print __METHOD__." result2=".join(', ',$result2)."\n";
        $this->assertEquals(array(100, 10, 111.4, 10, 1, 11.14, 100, 10, 111.4, 1.4, 0, 0.14, 0, 0, 1.4, 0),$result2);

        // Old function for spain countries. To check backward compatibility.
        global $mysoc;
        $mysoc=new Societe($db);
        $mysoc->country_code='ES';
        $result3=calcul_price_total(10, 10, 0, 10, 1.4, 0, 0, 'HT', 0);	// 10 * 10 HT - 0% discount with 10% vat and 1.4% localtax1, 0% localtax2
        print __METHOD__." result3=".join(', ',$result3)."\n";
        $this->assertEquals(array(100, 10, 111.4, 10, 1, 11.14, 100, 10, 111.4, 1.4, 0, 0.14, 0, 0, 1.4, 0),$result3);

        return true;
    }


    /**
    * Test function addline and update_price
    *
    * @return 	boolean
    * @see		http://wiki.dolibarr.org/index.php/Draft:VAT_calculation_and_rounding#Standard_usage
    */
    public function testUpdatePrice()
    {
		//$this->sharedFixture
		global $conf,$user,$langs,$db;
		$this->savconf=$conf;
		$this->savuser=$user;
		$this->savlangs=$langs;
		$this->savdb=$db;

		// Two lines of 1.24 give 2.48 HT and 2.72 TTC with standard vat rounding mode
		$localobject=new Facture($this->savdb);
        $localobject->initAsSpecimen('nolines');
        $invoiceid=$localobject->create($user);

        $localobject->addline($invoiceid,'Desc',1.24,1,10,0,0,0,0,'','',0,0,0,'HT');
        $localobject->addline($invoiceid,'Desc',1.24,1,10,0,0,0,0,'','',0,0,0,'HT');

        $newlocalobject=new Facture($this->savdb);
        $newlocalobject->fetch($invoiceid);

        $this->assertEquals(2.48,$newlocalobject->total_ht);
        $this->assertEquals(0.24,$newlocalobject->total_tva);
        $this->assertEquals(2.72,$newlocalobject->total_ttc);


        // Two lines of 1.24 give 2.48 HT and 2.73 TTC with global vat rounding mode
        $localobject=new Facture($this->savdb);
        $localobject->initAsSpecimen('nolines');
        $invoiceid=$localobject->create($user);

        $localobject->addline($invoiceid,'Desc',1.24,1,10,0,0,0,0,'','',0,0,0,'HT');
        $localobject->addline($invoiceid,'Desc',1.24,1,10,0,0,0,0,'','',0,0,0,'HT');

        $newlocalobject=new Facture($this->savdb);
        $newlocalobject->fetch($invoiceid);

        $this->assertEquals(2.48,$newlocalobject->total_ht);
        //$this->assertEquals(0.25,$newlocalobject->total_tva);
        //$this->assertEquals(2.73,$newlocalobject->total_ttc);
    }

}
?>
