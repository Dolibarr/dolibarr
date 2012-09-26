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
 *      \file       test/phpunit/NumberingModulesTest.php
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
     * testFactureMercure
     *
     * @return int
     */
    public function testFactureMercure()
    {
    	global $conf,$user,$langs,$db,$mysoc;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		require_once dirname(__FILE__).'/../../htdocs/compta/facture/class/facture.class.php';
		require_once dirname(__FILE__).'/../../htdocs/core/modules/facture/mod_facture_mercure.php';

		// First we try with a simple mask, with no reset
		// and we test counter is still increase second year.
		$conf->global->FACTURE_MERCURE_MASK_CREDIT='{yyyy}-{0000}';
		$conf->global->FACTURE_MERCURE_MASK_INVOICE='{yyyy}-{0000}';

		$localobject=new Facture($this->savdb);
		$localobject->initAsSpecimen();
		$localobject->date=dol_mktime(12, 0, 0, 1, 1, 1915);	// we use year 1915 to be sure to not have existing invoice for this year
		$numbering=new mod_facture_mercure();
		$result=$numbering->getNextValue($mysoc, $localobject);
		$result2=$localobject->create($user,1);
		$result3=$localobject->validate($user, $result);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('1915-0001', $result);	// counter must start to 1

		$localobject=new Facture($this->savdb);
		$localobject->initAsSpecimen();
		$localobject->date=dol_mktime(12, 0, 0, 1, 1, 1916);	// we use following year for second invoice
		$numbering=new mod_facture_mercure();
		$result=$numbering->getNextValue($mysoc, $localobject);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('1916-0002', $result);	// counter must not be reset

		// Now we try with a reset
		$conf->global->FACTURE_MERCURE_MASK_CREDIT='{yyyy}-{0000@1}';
		$conf->global->FACTURE_MERCURE_MASK_INVOICE='{yyyy}-{0000@1}';

		$localobject=new Facture($this->savdb);
		$localobject->initAsSpecimen();
		$localobject->date=dol_mktime(12, 0, 0, 1, 1, 1910);	// we use year 1910 to be sure to not have existing invoice for this year
		$numbering=new mod_facture_mercure();
		$result=$numbering->getNextValue($mysoc, $localobject);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('1910-0001', $result);	// counter must start to 1

		// Same mask but we add month
		$conf->global->FACTURE_MERCURE_MASK_CREDIT='{yyyy}{mm}-{0000@1}';
		$conf->global->FACTURE_MERCURE_MASK_INVOICE='{yyyy}{mm}-{0000@1}';
		$localobject=new Facture($this->savdb);
		$localobject->initAsSpecimen();
		$localobject->date=dol_mktime(12, 0, 0, 1, 1, 1920);	// we use year 1920 to be sure to not have existing invoice for this year
		$numbering=new mod_facture_mercure();
		$result=$numbering->getNextValue($mysoc, $localobject);
		$result2=$localobject->create($user,1);
		$result3=$localobject->validate($user, $result);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('192001-0001', $result);	// counter must start to 1

		$localobject=new Facture($this->savdb);
		$localobject->initAsSpecimen();
		$localobject->date=dol_mktime(12, 0, 0, 1, 1, 1921);	// we use following year for second invoice
		$numbering=new mod_facture_mercure();
		$result=$numbering->getNextValue($mysoc, $localobject);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('192101-0001', $result);	// counter must be resete to 1


		// Now we try with a different fiscal month (forced by mask)
    	$conf->global->FACTURE_MERCURE_MASK_CREDIT='{yyyy}{mm}-{0000@6}';
    	$conf->global->FACTURE_MERCURE_MASK_INVOICE='{yyyy}{mm}-{0000@6}';

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 1, 1, 1930);	// we use year 1930 to be sure to not have existing invoice for this year
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	$result2=$localobject->create($user,1);
    	$result3=$localobject->validate($user, $result);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('193001-0001', $result);	// counter must start to 1

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 12, 1, 1930);	// we use same year but fiscal month after
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	$result2=$localobject->create($user,1);
    	$result3=$localobject->validate($user, $result);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('193012-0001', $result);	// counter must be reset to 1

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 1, 1, 1931);	// we use same fiscal year but different year
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	$result2=$localobject->create($user,1);
    	$result3=$localobject->validate($user, $result);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('193101-0002', $result);	// counter must be 2

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 12, 1, 1931);	// we use different discal year but same year
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('193112-0001', $result);	// counter must be reset to 1


    	// Now we try with a different fiscal month (defined by SOCIETE_FISCAL_MONTH_START)
    	$conf->global->SOCIETE_FISCAL_MONTH_START=6;
    	$conf->global->FACTURE_MERCURE_MASK_CREDIT='{yyyy}{mm}-{0000@0}';
    	$conf->global->FACTURE_MERCURE_MASK_INVOICE='{yyyy}{mm}-{0000@0}';

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 1, 1, 1940);	// we use year 1940 to be sure to not have existing invoice for this year
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	$result2=$localobject->create($user,1);
    	$result3=$localobject->validate($user, $result);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('194001-0001', $result);	// counter must start to 1

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 12, 1, 1940);	// we use same year but fiscal month after
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	$result2=$localobject->create($user,1);
    	$result3=$localobject->validate($user, $result);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('194012-0001', $result);	// counter must be reset to 1

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 1, 1, 1941);	// we use same fiscal year but different year
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	$result2=$localobject->create($user,1);
    	$result3=$localobject->validate($user, $result);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('194101-0002', $result);	// counter must be 2

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 12, 1, 1941);	// we use different discal year but same year
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('194112-0001', $result);	// counter must be reset to 1


    	// Now we try with a different fiscal month (defined by SOCIETE_FISCAL_MONTH_START) and we always want year of element
    	$conf->global->SOCIETE_FISCAL_MONTH_START=6;
    	$conf->global->FACTURE_MERCURE_MASK_CREDIT='{yyyy}{mm}-{0000@=}';
    	$conf->global->FACTURE_MERCURE_MASK_INVOICE='{yyyy}{mm}-{0000@=}';

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 1, 1, 1950);	// we use year 1950 to be sure to not have existing invoice for this year
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	$result2=$localobject->create($user,1);
    	$result3=$localobject->validate($user, $result);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('195001-0001', $result);	// counter must start to 1

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 12, 1, 1950);	// we use same year but fiscal month after
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	$result2=$localobject->create($user,1);
    	$result3=$localobject->validate($user, $result);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('195012-0001', $result);	// counter must be reset to 1

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 1, 1, 1951);	// we use same fiscal year but different year
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	$result2=$localobject->create($user,1);
    	$result3=$localobject->validate($user, $result);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('195101-0002', $result);	// counter must be 2

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 12, 1, 1951);	// we use different discal year but same year
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('195112-0001', $result);	// counter must be reset to 1


    	// Now we try with a different fiscal month (defined by SOCIETE_FISCAL_MONTH_START) and we always want start year
    	$conf->global->SOCIETE_FISCAL_MONTH_START=6;
    	$conf->global->FACTURE_MERCURE_MASK_CREDIT='{yyyy}{mm}-{0000@-}';
    	$conf->global->FACTURE_MERCURE_MASK_INVOICE='{yyyy}{mm}-{0000@-}';

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 1, 1, 1960);	// we use year 1960 to be sure to not have existing invoice for this year
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	$result2=$localobject->create($user,1);
    	$result3=$localobject->validate($user, $result);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('195901-0001', $result);	// counter must start to 1

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 12, 1, 1960);	// we use same year but fiscal month after
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	$result2=$localobject->create($user,1);
    	$result3=$localobject->validate($user, $result);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('196012-0001', $result);	// counter must be reset to 1

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 1, 1, 1961);	// we use same fiscal year but different year
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	$result2=$localobject->create($user,1);
    	$result3=$localobject->validate($user, $result);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('196001-0002', $result);	// counter must be 2

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 12, 1, 1961);	// we use different discal year but same year
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('196112-0001', $result);	// counter must be reset to 1


    	// Now we try with a different fiscal month (defined by SOCIETE_FISCAL_MONTH_START) and we always want end year
    	$conf->global->SOCIETE_FISCAL_MONTH_START=6;
    	$conf->global->FACTURE_MERCURE_MASK_CREDIT='{yyyy}{mm}-{0000@+}';
    	$conf->global->FACTURE_MERCURE_MASK_INVOICE='{yyyy}{mm}-{0000@+}';

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 1, 1, 1970);	// we use year 1970 to be sure to not have existing invoice for this year
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	$result2=$localobject->create($user,1);
    	$result3=$localobject->validate($user, $result);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('197001-0001', $result);	// counter must start to 1

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 12, 1, 1970);	// we use same year but fiscal month after
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	$result2=$localobject->create($user,1);
    	$result3=$localobject->validate($user, $result);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('197112-0001', $result);	// counter must be reset to 1

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 1, 1, 1971);	// we use same fiscal year but different year
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	$result2=$localobject->create($user,1);
    	$result3=$localobject->validate($user, $result);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('197101-0002', $result);	// counter must be 2

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 12, 1, 1971);	// we use different discal year but same year
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('197212-0001', $result);	// counter must be reset to 1

    	return $result;
    }

}
?>