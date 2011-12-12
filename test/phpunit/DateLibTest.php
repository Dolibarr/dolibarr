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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *      \file       test/phpunit/DateLibTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/date.lib.php';

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
class DateLibTest extends PHPUnit_Framework_TestCase
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
	function DateLibTest()
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
	 */
    protected function tearDown()
    {
    	print __METHOD__."\n";
    }

   /**
     */
    public function testConvertTime2Seconds()
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$result=ConvertTime2Seconds(1,1,2);
    	print __METHOD__." result=".$result."\n";
		$this->assertEquals(3662,$result);

		return $result;
    }

    /**
     */
    public function testConvertSecondToTime()
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$result=ConvertSecondToTime(0,'all',86400);
    	print __METHOD__." result=".$result."\n";
		$this->assertEquals('0',$result);

		$result=ConvertSecondToTime(86400,'all',86400);
    	print __METHOD__." result=".$result."\n";
		$this->assertSame('1 '.$langs->trans("Day"),$result);


		return $result;
    }

    /**
    */
    public function testDolPrintDate()
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

    	// Check %Y-%m-%d %H:%M:%S format
        $result=dol_print_date(0,'%Y-%m-%d %H:%M:%S',true);
       	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('1970-01-01 00:00:00',$result);

    	// Check dayhour format for fr_FR
    	$outputlangs=new Translate('',$conf);
    	$outputlangs->setDefaultLang('fr_FR');
    	$outputlangs->load("main");

    	$result=dol_print_date(0+24*3600,'dayhour',true,$outputlangs);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('02/01/1970 00:00',$result);

    	// Check day format for en_US
    	$outputlangs=new Translate('',$conf);
    	$outputlangs->setDefaultLang('en_US');
    	$outputlangs->load("main");

    	$result=dol_print_date(0+24*3600,'day',true,$outputlangs);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('01/02/1970',$result);

    	// Check %a and %b format for en_US
    	$result=dol_print_date(0,'%a %b',true,$outputlangs);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('Thu jan',$result);

    	return $result;
    }

    /**
    */
    public function testDolTimePlusDuree()
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        // Check dayhour format for fr_FR
        $outputlangs=new Translate('',$conf);
        $outputlangs->setDefaultLang('fr_FR');
        $outputlangs->load("main");

        $result=dol_print_date(dol_time_plus_duree(dol_time_plus_duree(dol_time_plus_duree(0,1,'m'),1,'y'),1,'d'),'dayhour',true,$outputlangs);
       	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('02/02/1971 00:00',$result);

    	return $result;
    }

    /**
    */
    public function testDolGetFirstDay()
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

    }
}
?>