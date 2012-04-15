<?php
/* Copyright (C) 2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       test/phpunit/_NumberingModulesTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';

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
class NumberingModulesTest extends PHPUnit_Framework_TestCase
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @return NumberingModulesTest
	 */
	function NumberingModulesTest()
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
		$db->begin();	// This is to have all actions inside a transaction even if test launched without suite.

    	print __METHOD__."\n";
    }
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
     * testFactureCreate
     *
     * @return int
     */
    public function testFactureMercure()
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		require_once dirname(__FILE__).'/../../htdocs/compta/facture/class/facture.class.php';
		require_once dirname(__FILE__).'/../../htdocs/core/modules/facture/mod_facture_mercure.php';

		//$conf->global->FACTURE_MERCURE_MASK_CREDIT='{yyyy}{mm}-{0000@3}';
		//$conf->global->FACTURE_MERCURE_MASK_INVOICE='{yyyy}{mm}-{0000@3}';
		$conf->global->FACTURE_MERCURE_MASK_CREDIT='{yyyy}-{0000@1}';
		$conf->global->FACTURE_MERCURE_MASK_INVOICE='{yyyy}-{0000@1}';

		$localobject=new Facture($this->savdb);
		$localobject->initAsSpecimen();
		$localobject->date=dol_mktime(0, 0, 0, 1, 1, 2012);
		$numbering=new mod_facture_mercure();
		$result=$numbering->getNextValue($mysoc, $localobject);
		print __METHOD__." result=".$result."\n";

		$localobject=new Facture($this->savdb);
		$localobject->initAsSpecimen();
		$localobject->date=dol_mktime(0, 0, 0, 1, 1, 2011);
		$numbering=new mod_facture_mercure();
		$result=$numbering->getNextValue($mysoc, $localobject);
    	print __METHOD__." result=".$result."\n";

    	$this->assertLessThan($result, 0);
    	return $result;
    }

}
?>