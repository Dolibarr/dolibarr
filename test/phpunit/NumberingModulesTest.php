<?php
/* Copyright (C) 2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       test/phpunit/NumberingModulesTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
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
class NumberingModulesTest extends PHPUnit\Framework\TestCase
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
		$conf->global->FACTURE_ADDON='mercure';
		$conf->global->FACTURE_MERCURE_MASK_CREDIT='{yyyy}-{0000}';
		$conf->global->FACTURE_MERCURE_MASK_INVOICE='{yyyy}-{0000}';
		$conf->global->INVOICE_CAN_ALWAYS_BE_REMOVED=0;

		$localobject=new Facture($this->savdb);
		$localobject->initAsSpecimen();
		$localobject->date=dol_mktime(12, 0, 0, 1, 1, 1915);	// we use year 1915 to be sure to not have existing invoice for this year
		$numbering=new mod_facture_mercure();
		$result=$numbering->getNextValue($mysoc, $localobject);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('1915-0001', $result, 'Test for {yyyy}-{0000}, 1st invoice');				// counter must start to 1
		$result2=$localobject->create($user, 1);
		print __METHOD__." result2=".$result."\n";
		$result3=$localobject->validate($user, $result);		// create invoice by forcing ref
		print __METHOD__." result3=".$result."\n";
		$this->assertEquals(1, $result3, 'Test validation of invoice with forced ref is ok');	// counter must start to 1
		$result=$localobject->is_erasable();
		print __METHOD__." is_erasable=".$result."\n";
		$this->assertGreaterThanOrEqual(1, $result, 'Test for is_erasable, 1st invoice');						    // Can be deleted
		$localobject2=new Facture($this->savdb);
		$localobject2->initAsSpecimen();
		$localobject2->date=dol_mktime(12, 0, 0, 1, 1, 1916);	// we use following year for second invoice (there is no reset into mask)
		$numbering=new mod_facture_mercure();
		$result=$numbering->getNextValue($mysoc, $localobject2, 'last');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('1915-0001', $result, "Test to get last value with param 'last'");
		$result=$numbering->getNextValue($mysoc, $localobject2);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('1916-0002', $result);				// counter must be now 2 (not reseted)
		$result2=$localobject2->create($user, 1);
		print __METHOD__." result2=".$result."\n";
		$result3=$localobject2->validate($user, $result);		// create invoice by forcing ref
		print __METHOD__." result3=".$result."\n";
		$this->assertEquals(1, $result3, 'Test validation of invoice with forced ref is ok');	// counter must start to 1
		$result=$localobject2->is_erasable();
		print __METHOD__." is_erasable=".$result."\n";
		$this->assertGreaterThanOrEqual(1, $result);						// Can be deleted
		$result=$localobject->is_erasable();
		print __METHOD__." is_erasable=".$result."\n";
		$this->assertLessThanOrEqual(0, $result, 'Test for {yyyy}-{0000} that is_erasable is 0 for 1st invoice');						// 1 can no more be deleted (2 is more recent)

		// Now we try with a reset
		$conf->global->FACTURE_MERCURE_MASK_CREDIT='{yyyy}-{0000@1}';
		$conf->global->FACTURE_MERCURE_MASK_INVOICE='{yyyy}-{0000@1}';

		$localobject=new Facture($this->savdb);
		$localobject->initAsSpecimen();
		$localobject->date=dol_mktime(12, 0, 0, 1, 1, 1910);	// we use year 1910 to be sure to not have existing invoice for this year
		$numbering=new mod_facture_mercure();
		$result=$numbering->getNextValue($mysoc, $localobject);
		$result2=$localobject->create($user, 1);
		$result3=$localobject->validate($user, $result);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('1910-0001', $result, 'Test for {yyyy}-{0000@1} 1st invoice');				// counter must start to 1
		$localobject2=new Facture($this->savdb);
		$localobject2->initAsSpecimen();
		$localobject2->date=dol_mktime(12, 0, 0, 1, 1, 1910);	// we use same year for second invoice (and there is a reset required)
		$numbering=new mod_facture_mercure();
		$result=$numbering->getNextValue($mysoc, $localobject2);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('1910-0002', $result, 'Test for {yyyy}-{0000@1} 2nd invoice, same day');	// counter must be now 2
		$localobject3=new Facture($this->savdb);
		$localobject3->initAsSpecimen();
		$localobject3->date=dol_mktime(12, 0, 0, 1, 1, 1911);	// we use next year for third invoice (and there is a reset required)
		$numbering=new mod_facture_mercure();
		$result=$numbering->getNextValue($mysoc, $localobject3);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('1911-0001', $result, 'Test for {yyyy}-{0000@1} 3nd invoice, same day');	// counter must be now 1

		// Same but we add month after year
		$conf->global->FACTURE_MERCURE_MASK_CREDIT='{yyyy}{mm}-{0000@1}';
		$conf->global->FACTURE_MERCURE_MASK_INVOICE='{yyyy}{mm}-{0000@1}';
		$localobject=new Facture($this->savdb);
		$localobject->initAsSpecimen();
		$localobject->date=dol_mktime(12, 0, 0, 1, 1, 1920);	// we use year 1920 to be sure to not have existing invoice for this year
		$numbering=new mod_facture_mercure();
		$result=$numbering->getNextValue($mysoc, $localobject);
		$result2=$localobject->create($user, 1);
		$result3=$localobject->validate($user, $result);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('192001-0001', $result, 'Test for {yyyy}{mm}-{0000@1} 1st invoice');			// counter must start to 1
		$result=$localobject->is_erasable();
		print __METHOD__." is_erasable=".$result."\n";
		$this->assertGreaterThanOrEqual(1, $result);						// Can be deleted
		$localobject2=new Facture($this->savdb);
		$localobject2->initAsSpecimen();
		$localobject2->date=dol_mktime(12, 0, 0, 1, 1, 1921);	// we use following year for second invoice (and there is a reset required)
		$numbering=new mod_facture_mercure();
		$result=$numbering->getNextValue($mysoc, $localobject2);
		$result2=$localobject2->create($user, 1);
		$result3=$localobject2->validate($user, $result);
		print __METHOD__." result=".$result."\n";
    	$this->assertEquals('192101-0001', $result);			// counter must be reseted to 1
    	$result=$localobject2->is_erasable();
    	print __METHOD__." is_erasable=".$result."\n";
    	$this->assertGreaterThanOrEqual(1, $result);						// Can be deleted
    	$result=$localobject->is_erasable();
    	print __METHOD__." is_erasable=".$result."\n";
    	$this->assertGreaterThanOrEqual(1, $result);						// Case 1 can be deleted (because there was a reset for case 2)

		// Same but we add month before year and use a year on 2 digits
		$conf->global->FACTURE_MERCURE_MASK_CREDIT='[mm}{yy}-{0000@1}';
		$conf->global->FACTURE_MERCURE_MASK_INVOICE='{mm}{yy}-{0000@1}';
		$localobject=new Facture($this->savdb);
		$localobject->initAsSpecimen();
		$localobject->date=dol_mktime(12, 0, 0, 1, 1, 1925);	// we use year 1925 to be sure to not have existing invoice for this year
		$numbering=new mod_facture_mercure();
		$result=$numbering->getNextValue($mysoc, $localobject);
		$result2=$localobject->create($user, 1);
		$result3=$localobject->validate($user, $result);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('0125-0001', $result, 'Test for {mm}{yy}-{0000@1} 1st invoice');				// counter must start to 1
		$result=$localobject->is_erasable();					// This call get getNextNumRef with param 'last'
		print __METHOD__." is_erasable=".$result."\n";
		$this->assertGreaterThanOrEqual(1, $result);						// Can be deleted
		$localobject2=new Facture($this->savdb);
		$localobject2->initAsSpecimen();
		$localobject2->date=dol_mktime(12, 0, 0, 1, 1, 1925);	// we use same year 1925 for second invoice (and there is a reset required)
		$numbering=new mod_facture_mercure();
		$result=$numbering->getNextValue($mysoc, $localobject2);
		$result2=$localobject2->create($user, 1);
		$result3=$localobject2->validate($user, $result);
		print __METHOD__." result=".$result."\n";
    	$this->assertEquals('0125-0002', $result, 'Test for {mm}{yy}-{0000@1} 2st invoice');			// counter must be now 2
    	$result=$localobject2->is_erasable();
    	print __METHOD__." is_erasable=".$result."\n";
    	$this->assertGreaterThanOrEqual(1, $result);						// Can be deleted
    	$result=$localobject->is_erasable();
    	print __METHOD__." is_erasable=".$result."\n";
    	$this->assertLessThanOrEqual(0, $result);						// Case 1 can not be deleted (because there is an invoice 2)
		$localobject3=new Facture($this->savdb);
		$localobject3->initAsSpecimen();
		$localobject3->date=dol_mktime(12, 0, 0, 1, 1, 1926);	// we use following year for third invoice (and there is a reset required)
		$numbering=new mod_facture_mercure();
		$result=$numbering->getNextValue($mysoc, $localobject3);
		print __METHOD__." result=".$result."\n";
    	$this->assertEquals('0126-0001', $result, 'Test for {mm}{yy}-{0000@1} 3rd invoice');			// counter must be now 1

    	// Try an offset when an invoice already exists
    	$conf->global->FACTURE_MERCURE_MASK_CREDIT='{yyyy}{mm}-{0000+9990}';
    	$conf->global->FACTURE_MERCURE_MASK_INVOICE='{yyyy}{mm}-{0000+9990}';
    	$result=$numbering->getNextValue($mysoc, $localobject2);

		// Now we try with a different fiscal month (forced by mask)
    	$conf->global->FACTURE_MERCURE_MASK_CREDIT='{yyyy}{mm}-{0000@6}';
    	$conf->global->FACTURE_MERCURE_MASK_INVOICE='{yyyy}{mm}-{0000@6}';

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 1, 1, 1930);	// we use year 1930 to be sure to not have existing invoice for this year
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject, 'last');
    	print __METHOD__." result for last=".$result."\n";
    	$this->assertEquals('', $result);						// no existing ref into reset range
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	$result2=$localobject->create($user, 1);
    	$result3=$localobject->validate($user, $result);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('193001-0001', $result);			// counter must start to 1
    	$result=$numbering->getNextValue($mysoc, $localobject, 'last');
    	print __METHOD__." result for last=".$result."\n";
    	$this->assertEquals('193001-0001', $result);			// last ref into reset range should be same than last created

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 12, 1, 1930);	// we use same year but fiscal month after
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject, 'last');
    	print __METHOD__." result for last=".$result."\n";
    	$this->assertEquals('', $result);						// last ref into reset range should be ''
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	$result2=$localobject->create($user, 1);
    	$result3=$localobject->validate($user, $result);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('193012-0001', $result);	// counter must be reset to 1

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 1, 1, 1931);	// we use same fiscal year but different year
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	$result2=$localobject->create($user, 1);
    	$result3=$localobject->validate($user, $result);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('193101-0002', $result);	// counter must be 2

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 12, 1, 1931);	// we use different fiscal year but same year
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
    	$result2=$localobject->create($user, 1);
    	$result3=$localobject->validate($user, $result);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('194001-0001', $result);	// counter must start to 1

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 12, 1, 1940);	// we use same year but fiscal month after
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	$result2=$localobject->create($user, 1);
    	$result3=$localobject->validate($user, $result);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('194012-0001', $result);	// counter must be reset to 1

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 1, 1, 1941);	// we use same fiscal year but different year
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	$result2=$localobject->create($user, 1);
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
    	$result2=$localobject->create($user, 1);
    	$result3=$localobject->validate($user, $result);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('195001-0001', $result);	// counter must start to 1

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 12, 1, 1950);	// we use same year but fiscal month after
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	$result2=$localobject->create($user, 1);
    	$result3=$localobject->validate($user, $result);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('195012-0001', $result);	// counter must be reset to 1

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 1, 1, 1951);	// we use same fiscal year but different year
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	$result2=$localobject->create($user, 1);
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
    	$result2=$localobject->create($user, 1);
    	$result3=$localobject->validate($user, $result);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('195901-0001', $result);	// counter must start to 1

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 12, 1, 1960);	// we use same year but fiscal month after
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	$result2=$localobject->create($user, 1);
    	$result3=$localobject->validate($user, $result);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('196012-0001', $result);	// counter must be reset to 1

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 1, 1, 1961);	// we use same fiscal year but different year
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	$result2=$localobject->create($user, 1);
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
    	$result2=$localobject->create($user, 1);
    	$result3=$localobject->validate($user, $result);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('197001-0001', $result);	// counter must start to 1

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 12, 1, 1970);	// we use same year but fiscal month after
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	$result2=$localobject->create($user, 1);
    	$result3=$localobject->validate($user, $result);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('197112-0001', $result);	// counter must be reset to 1

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 1, 1, 1971);	// we use same fiscal year but different year
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	$result2=$localobject->create($user, 1);
    	$result3=$localobject->validate($user, $result);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('197101-0002', $result);	// counter must be 2

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 12, 1, 1971);	// we use different fiscal year but same year
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('197212-0001', $result);	// counter must be reset to 1

    	// Now we try with a reset every month (@99)
    	$conf->global->SOCIETE_FISCAL_MONTH_START=6;
    	$conf->global->FACTURE_MERCURE_MASK_CREDIT='{yyyy}{mm}-{0000@99}';
    	$conf->global->FACTURE_MERCURE_MASK_INVOICE='{yyyy}{mm}-{0000@99}';

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 1, 1, 1980);	// we use year 1980 to be sure to not have existing invoice for this year
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	$result2=$localobject->create($user, 1);
    	$result3=$localobject->validate($user, $result);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('198001-0001', $result);	// counter must start to 1

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 1, 1, 1980);	// we use year 1980 to be sure to not have existing invoice for this year
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	$result2=$localobject->create($user, 1);
    	$result3=$localobject->validate($user, $result);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('198001-0002', $result);	// counter must start to 2

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 2, 1, 1980);	// we use year 1980 to be sure to not have existing invoice for this year
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	$result2=$localobject->create($user, 1);
    	$result3=$localobject->validate($user, $result);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('198002-0001', $result);	// counter must start to 1

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 1, 1, 1981);	// we use year 1981 to be sure to not have existing invoice for this year
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($mysoc, $localobject);
    	$result2=$localobject->create($user, 1);
    	$result3=$localobject->validate($user, $result);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('198101-0001', $result);	// counter must start to 1

    	// Test with {t} tag
    	$conf->global->SOCIETE_FISCAL_MONTH_START=1;
    	$conf->global->FACTURE_MERCURE_MASK_CREDIT='{t}{yyyy}{mm}-{0000}';
    	$conf->global->FACTURE_MERCURE_MASK_INVOICE='{t}{yyyy}{mm}-{0000}';

    	$tmpthirdparty=new Societe($this->savdb);
    	$tmpthirdparty->initAsSpecimen();
    	$tmpthirdparty->typent_code = 'TE_ABC';

    	$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->date=dol_mktime(12, 0, 0, 1, 1, 1982);	// we use year 1982 to be sure to not have existing invoice for this year
    	$numbering=new mod_facture_mercure();
    	$result=$numbering->getNextValue($tmpthirdparty, $localobject);
    	$result2=$localobject->create($user, 1);
    	$result3=$localobject->validate($user, $result);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('A198201-0001', $result);	// counter must start to 1



    	return $result;
    }
}
