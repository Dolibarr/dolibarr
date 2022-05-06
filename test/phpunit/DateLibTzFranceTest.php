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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *      \file       test/phpunit/DateLibTzFranceTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/date.lib.php';

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
class DateLibTzFranceTest extends PHPUnit\Framework\TestCase
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @return DateLibTest
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

	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass()
	{
		global $conf,$user,$langs,$db;

		if (getServerTimeZoneString() != 'Europe/Paris' && getServerTimeZoneString() != 'Europe/Berlin') {
			print "\n".__METHOD__." This PHPUnit test can be launched manually only onto a server with PHP timezone set to TZ=Europe/Paris, not a TZ=".getServerTimeZoneString().".\n";
			print "You can launch the test from command line with:\n";
			print "php -d date.timezone='Europe/Paris' phpunit DateLibTzFranceTest.php\n";
			die(1);
		}

		$db->begin();	// This is to have all actions inside a transaction even if test launched without suite.

		print __METHOD__."\n";
	}

	/**
	 * tearDownAfterClass
	 *
	 * @return	void
	 */
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
	 * testDolPrintDateTzFrance
	 * Same than official testDolPrintDate but with parameter tzoutput that is false='tzserver'.
	 * This test works only onto a server using TZ+1 Europe/Paris.
	 *
	 * You can use http://www.epochconverter.com/ to generate more tests.
	 *
	 * @return void
	 */
	public function testDolPrintDateTzFrance()
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		// Check %Y-%m-%d %H:%M:%S format
		$result=dol_print_date(0, '%Y-%m-%d %H:%M:%S', false);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('1970-01-01 01:00:00', $result);

		// Check %Y-%m-%d %H:%M:%S format
		$result=dol_print_date(16725225600, '%Y-%m-%d %H:%M:%S', false);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('2500-01-01 01:00:00', $result);

		// Check %Y-%m-%d %H:%M:%S format
		$result=dol_print_date(-1830384000, '%Y-%m-%d %H:%M:%S', false);	// http://www.epochconverter.com/
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('1912-01-01 01:00:00', $result);		// dol_print_date use a timezone, not epoch converter as it did not exists this year

		// Specific cas during war

		// 1940, no timezone
		$result=dol_print_date(-946771200, '%Y-%m-%d %H:%M:%S', false);	// http://www.epochconverter.com/
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('1940-01-01 01:00:00', $result);		//  dol_print_date use a modern timezone, not epoch converter as it did not exists this year

		// 1941, timezone is added by germany to +2 (same for 1942)
		$result=dol_print_date(-915148800, '%Y-%m-%d %H:%M:%S', false);	// http://www.epochconverter.com/
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('1941-01-01 01:00:00', $result);		// dol_print_date use a modern timezone, epoch converter use historic timezone

		// 1943, timezone is +1
		$result=dol_print_date(-852076800, '%Y-%m-%d %H:%M:%S', false);	// http://www.epochconverter.com/
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('1943-01-01 01:00:00', $result);

		// test with negative timezone
		$result=dol_print_date(-1, '%Y-%m-%d %H:%M:%S', false);	// http://www.epochconverter.com/
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('1970-01-01 00:59:59', $result);

		// Check dayhour format for fr_FR
		$outputlangs=new Translate('', $conf);
		$outputlangs->setDefaultLang('fr_FR');
		$outputlangs->load("main");

		$result=dol_print_date(0+24*3600, 'dayhour', false, $outputlangs);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('02/01/1970 01:00', $result);

		// Check day format for en_US
		$outputlangs=new Translate('', $conf);
		$outputlangs->setDefaultLang('en_US');
		$outputlangs->load("main");

		$result=dol_print_date(0+24*3600, 'day', false, $outputlangs);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('01/02/1970', $result);

		// Check %a and %b format for en_US
		$result=dol_print_date(0, '%a %b', false, $outputlangs);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('Thu Jan', $result);

		return $result;
	}
}
