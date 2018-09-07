<?php
/* Copyright (C) 2013 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       test/phpunit/ScriptsTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/security.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/security2.lib.php';

if (! defined('NOREQUIREUSER'))  define('NOREQUIREUSER','1');
if (! defined('NOREQUIREDB'))    define('NOREQUIREDB','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');
if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');
if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1');
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1'); // If there is no menu to show
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1'); // If we don't need to load the html.form.class.php
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined("NOLOGIN"))        define("NOLOGIN",'1');       // If this page is public (can be called outside logged session)

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
class ScriptsTest extends PHPUnit_Framework_TestCase
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @return SecurityTest
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
     * testBank
     *
     * @return string
     */
    public function testBank()
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$script=dirname(__FILE__).'/../../scripts/bank/export-bank-receipts.php BANKDUMMY RECEIPTDUMMY excel2007 lang=fr_FR';
		$result=exec($script, $output, $returnvar);

		print __METHOD__." result=".$result."\n";
		print __METHOD__." output=".join("\n",$output)."\n";
		print __METHOD__." returnvar=".$returnvar."\n";
		$this->assertEquals($result,'Failed to find bank account with ref BANKDUMMY.');
		$this->assertEquals($returnvar,255);

        return $result;
    }

    /**
     * testCompany
     *
     * @depends	testBank
     * @return string
     */
    public function testCompany()
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		/*
		$script=dirname(__FILE__).'/../../scripts/company/sync_contacts_dolibarr_2ldap now';
		$result=exec($script, $output, $returnvar);

		print __METHOD__." result=".$result."\n";
		print __METHOD__." output=".join("\n",$output)."\n";
		print __METHOD__." returnvar=".$returnvar."\n";
		$this->assertEquals($result,'Failed to find bank account with ref BANKDUMMY.');
		$this->assertEquals($returnvar,255);
		*/
        return '';
    }

    /**
     * testContracts
     *
     * @depends	testCompany
     * @return string
     */
    public function testContracts()
    {
    	global $conf,$user,$langs,$db;
    	$conf=$this->savconf;
    	$user=$this->savuser;
    	$langs=$this->savlangs;
    	$db=$this->savdb;

    	$script=dirname(__FILE__).'/../../scripts/contracts/email_expire_services_to_customers.php test thirdparties';
    	$result=exec($script, $output, $returnvar);
    	print __METHOD__." result=".$result."\n";
    	print __METHOD__." output=".join("\n",$output)."\n";
    	print __METHOD__." returnvar=".$returnvar."\n";
    	$this->assertEquals($returnvar,0,'email_expire_services_to_customers.php thirdparties');

    	$script=dirname(__FILE__).'/../../scripts/contracts/email_expire_services_to_customers.php test contacts -30';
    	$result=exec($script, $output, $returnvar);
    	print __METHOD__." result=".$result."\n";
    	print __METHOD__." output=".join("\n",$output)."\n";
    	print __METHOD__." returnvar=".$returnvar."\n";
    	$this->assertEquals($returnvar,0,'email_expire_services_to_customers.php contacts');

    	$script=dirname(__FILE__).'/../../scripts/contracts/email_expire_services_to_representatives.php test -30';
    	$result=exec($script, $output, $returnvar);
    	print __METHOD__." result=".$result."\n";
    	print __METHOD__." output=".join("\n",$output)."\n";
    	print __METHOD__." returnvar=".$returnvar."\n";
    	$this->assertEquals($returnvar,0,'email_expire_services_to_representatives.php');

    	return $result;
    }

    /**
     * testInvoices
     *
     * @depends	testContracts
     * @return string
     */
    public function testInvoices()
    {
    	global $conf,$user,$langs,$db;
    	$conf=$this->savconf;
    	$user=$this->savuser;
    	$langs=$this->savlangs;
    	$db=$this->savdb;

    	$script=dirname(__FILE__).'/../../scripts/invoices/email_unpaid_invoices_to_customers.php test thirdparties';
    	$result=exec($script, $output, $returnvar);
    	print __METHOD__." result=".$result."\n";
    	print __METHOD__." output=".join("\n",$output)."\n";
    	print __METHOD__." returnvar=".$returnvar."\n";
    	$this->assertEquals($returnvar,0,'email_unpaid_invoices_to_customers.php thirdparties');

    	$script=dirname(__FILE__).'/../../scripts/invoices/email_unpaid_invoices_to_customers.php test contacts -30';
    	$result=exec($script, $output, $returnvar);
    	print __METHOD__." result=".$result."\n";
    	print __METHOD__." output=".join("\n",$output)."\n";
    	print __METHOD__." returnvar=".$returnvar."\n";
    	$this->assertEquals($returnvar,0,'email_unpaid_invoices_to_customers.php contacts');

    	$script=dirname(__FILE__).'/../../scripts/invoices/email_unpaid_invoices_to_representatives.php test thirdparties';
    	$result=exec($script, $output, $returnvar);
    	print __METHOD__." result=".$result."\n";
    	print __METHOD__." output=".join("\n",$output)."\n";
    	print __METHOD__." returnvar=".$returnvar."\n";
    	$this->assertEquals($returnvar,0,'email_unpaid_invoices_to_customers.php thirdparties');

    	return $result;
    }
}
