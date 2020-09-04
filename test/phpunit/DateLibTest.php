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
 *      \file       test/phpunit/DateLibTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
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
class DateLibTest extends PHPUnit\Framework\TestCase
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
     * testNumBetweenDay
     *
     * @return	void
     */
    public function testNumBetweenDay()
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		// With same hours
		$date1=dol_mktime(0, 0, 0, 1, 1, 2012);
		$date2=dol_mktime(0, 0, 0, 1, 2, 2012);

		$result=num_between_day($date1, $date2, 1);
    	print __METHOD__." result=".$result."\n";
		$this->assertEquals(2, $result);

		$result=num_between_day($date1, $date2, 0);
    	print __METHOD__." result=".$result."\n";
		$this->assertEquals(1, $result);

		// With different hours
		$date1=dol_mktime(0, 0, 0, 1, 1, 2012);
		$date2=dol_mktime(12, 0, 0, 1, 2, 2012);

		$result=num_between_day($date1, $date2, 1);
    	print __METHOD__." result=".$result."\n";
		$this->assertEquals(2, $result);

		$result=num_between_day($date1, $date2, 0);
    	print __METHOD__." result=".$result."\n";
		$this->assertEquals(1, $result);

		// With different date before and after sunlight hour (day to change sunlight hour is 2014-03-30)
		$date1=dol_mktime(0, 0, 0, 3, 28, 2014, true);
		$date2=dol_mktime(0, 0, 0, 3, 31, 2014, true);

		$result=num_between_day($date1, $date2, 1);
    	print __METHOD__." result=".$result."\n";
		$this->assertEquals(4, $result);

		$result=num_between_day($date1, $date2, 0);
    	print __METHOD__." result=".$result."\n";
		$this->assertEquals(3, $result);

		return $result;
    }

    /**
     * testNumPublicHoliday
     *
     * @return	void
     */
    public function testNumPublicHoliday()
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        // With same hours - Tuesday/Wednesday jan 2013
        $date1=dol_mktime(0, 0, 0, 1, 1, 2013);
        $date2=dol_mktime(0, 0, 0, 1, 2, 2013);
        $date3=dol_mktime(0, 0, 0, 1, 3, 2013);

        $result=num_public_holiday($date1, $date2, 'FR', 1);
        print __METHOD__." result=".$result."\n";
        $this->assertEquals(1, $result, 'NumPublicHoliday for Tuesday 1 - Wednesday 2 jan 2013 for FR');   // 1 closed days (country france)

        $result=num_public_holiday($date1, $date2, 'XX', 1);
        print __METHOD__." result=".$result."\n";
        $this->assertEquals(1, $result, 'NumPublicHoliday for Tuesday 1 - Wednesday 2 jan 2013 for XX');   // 1 closed days (country unknown)

        $result=num_public_holiday($date2, $date3, 'FR', 1);
        print __METHOD__." result=".$result."\n";
        $this->assertEquals(0, $result, 'NumPublicHoliday for Wednesday 2 - Thursday 3 jan 2013 for FR');   // no closed days

        // Check with easter monday
        $date1=dol_mktime(0, 0, 0, 4, 21, 2019);
        $date2=dol_mktime(0, 0, 0, 4, 23, 2019);

        $result=num_public_holiday($date1, $date2, 'XX', 1);
        print __METHOD__." result=".$result."\n";
        $this->assertEquals(1, $result, 'NumPublicHoliday including eastermonday for XX');   // 2 opened day, 1 closed days (sunday)

        $result=num_public_holiday($date1, $date2, 'FR', 1);
        print __METHOD__." result=".$result."\n";
        $this->assertEquals(2, $result, 'NumPublicHoliday including eastermonday for FR');   // 1 opened day, 2 closed days (sunday + easter monday)

        // Check for sunday/saturday - Friday 4 - Sunday 6 jan 2013
        $date1=dol_mktime(0, 0, 0, 1, 4, 2013);
        $date2=dol_mktime(0, 0, 0, 1, 6, 2013);

        $result=num_public_holiday($date1, $date2, 'FR', 1);
        print __METHOD__." result=".$result."\n";
        $this->assertEquals(2, $result, 'NumPublicHoliday for FR');   // 1 opened day, 2 closed days

        $result=num_public_holiday($date1, $date2, 'FR', 1, 1, 1);
        print __METHOD__." result=".$result."\n";
        $this->assertEquals(2, $result, 'NumPublicHoliday for FR');   // 1 opened day, 2 closed days

        $result=num_public_holiday($date1, $date2, 'FR', 1, 1, 0);
        print __METHOD__." result=".$result."\n";
        $this->assertEquals(1, $result, 'NumPublicHoliday for FR');   // 2 opened day, 1 closed days

        $result=num_public_holiday($date1, $date2, 'FR', 1, 0, 0);
        print __METHOD__." result=".$result."\n";
        $this->assertEquals(0, $result, 'NumPublicHoliday for FR');   // 3 opened day, 0 closed days

        $result=num_public_holiday($date1, $date2, 'XX', 1);
        print __METHOD__." result=".$result."\n";
        $this->assertEquals(2, $result, 'NumPublicHoliday for XX');   // 1 opened day, 2 closed days (even if country unknown)



        // Add more holiday with constant HOLIDAY_MORE_PUBLIC_HOLIDAYS
        $conf->global->HOLIDAY_MORE_PUBLIC_HOLIDAYS='12-13,2019-12-14';

        $date1=dol_mktime(0, 0, 0, 12, 13, 2018);
        $date2=dol_mktime(0, 0, 0, 12, 13, 2018);
        $result=num_public_holiday($date1, $date2, 'YY', 1);
        print __METHOD__." result=".$result."\n";
        $this->assertEquals(1, $result, 'NumPublicHoliday for YY the 2018-12-13');   // 0 opened day, 1 closed days (even if country unknown)

        $date1=dol_mktime(0, 0, 0, 12, 14, 2018);
        $date2=dol_mktime(0, 0, 0, 12, 14, 2018);
        $result=num_public_holiday($date1, $date2, 'YY', 1);
        print __METHOD__." result=".$result."\n";
        $this->assertEquals(0, $result, 'NumPublicHoliday for YY the 2018-12-14');   // 1 opened day, 0 closed days (even if country unknown)

        $date1=dol_mktime(0, 0, 0, 12, 14, 2019);
        $date2=dol_mktime(0, 0, 0, 12, 14, 2019);
        $result=num_public_holiday($date1, $date2, 'YY', 1);
        print __METHOD__." result=".$result."\n";
        $this->assertEquals(1, $result, 'NumPublicHoliday for YY the 2019-12-14');   // 0 opened day, 1 closed days (even if country unknown)
    }

    /**
     * testNumOpenDay
     *
     * @return	void
     */
    public function testNumOpenDay()
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        // With same hours - Tuesday/Wednesday jan 2013
        $date1=dol_mktime(0, 0, 0, 1, 1, 2013);
        $date2=dol_mktime(0, 0, 0, 1, 2, 2013);
        $date3=dol_mktime(0, 0, 0, 1, 3, 2013);

        $result=num_open_day($date1, $date2, 0, 1, 0, 'FR');
        print __METHOD__." result=".$result."\n";
        $this->assertEquals(1, $result, 'NumOpenDay Tuesday 1 - Wednesday 2 jan 2013 for FR');   // 1 opened days (country france)

        $result=num_open_day($date1, $date2, 0, 1, 0, 'XX');
        print __METHOD__." result=".$result."\n";
        $this->assertEquals(1, $result, 'NumOpenDay Tuesday 1 - Wednesday 2 jan 2013 for XX');   // 1 opened days (country unknown)

        $result=num_open_day($date2, $date3, 0, 1, 0, 'FR');
        print __METHOD__." result=".$result."\n";
        $this->assertEquals(2, $result, 'NumOpenDay Wednesday 2 - Thursday 3 jan 2013 for FR');   // 2 opened days

        // With same hours - Friday/Sunday jan 2013
        $date1=dol_mktime(0, 0, 0, 1, 4, 2013);
        $date2=dol_mktime(0, 0, 0, 1, 6, 2013);

        $result=num_open_day($date1, $date2, 0, 1, 0, 'FR');
        print __METHOD__." result=".$result."\n";
        $this->assertEquals(1, $result, 'NumOpenDay for FR');   // 1 opened day, 2 closed

        $result=num_open_day($date1, $date2, 'XX', 1);
        print __METHOD__." result=".$result."\n";
        $this->assertEquals(1, $result, 'NumOpenDay for XX');   // 1 opened day, 2 closes (even if country unknown)
    }

    /**
     * testConvertTime2Seconds
     *
     * @return	void
     */
    public function testConvertTime2Seconds()
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$result=convertTime2Seconds(1, 1, 2);
    	print __METHOD__." result=".$result."\n";
		$this->assertEquals(3662, $result);

		return $result;
    }

    /**
     * testConvertSecondToTime
     *
     * @return void
     */
    public function testConvertSecondToTime()
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$result=convertSecondToTime(0, 'all', 86400);
    	print __METHOD__." result=".$result."\n";
		$this->assertEquals('0', $result);

		$result=convertSecondToTime(86400, 'all', 86400);
    	print __METHOD__." result=".$result."\n";
		$this->assertSame('1 '.$langs->trans("Day"), $result);


		return $result;
    }

    /**
     * testDolPrintDate
     *
     * @return void
     */
    public function testDolPrintDate()
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

    	// Check %Y-%m-%d %H:%M:%S format
        $result=dol_print_date(0, '%Y-%m-%d %H:%M:%S', true);
       	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('1970-01-01 00:00:00', $result);

    	// Check %Y-%m-%d %H:%M:%S format
    	$result=dol_print_date(16725225600, '%Y-%m-%d %H:%M:%S', true);	// http://www.epochconverter.com/
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('2500-01-01 00:00:00', $result);

    	// Check %Y-%m-%d %H:%M:%S format
    	$result=dol_print_date(-1830384000, '%Y-%m-%d %H:%M:%S', true);	// http://www.epochconverter.com/
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('1912-01-01 00:00:00', $result);	// dol_print_date use TZ (good) but epoch converter does not use it.

    	// Check %Y-%m-%d %H:%M:%S format
    	$result=dol_print_date(-11676096000, '%Y-%m-%d %H:%M:%S', true);	// http://www.epochconverter.com/
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('1600-01-01 00:00:00', $result);

    	// test with negative timezone
    	$result=dol_print_date(-1, '%Y-%m-%d %H:%M:%S', true);	// http://www.epochconverter.com/
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('1969-12-31 23:59:59', $result);

    	// Check dayhour format for fr_FR
    	$outputlangs=new Translate('', $conf);
    	$outputlangs->setDefaultLang('fr_FR');
    	$outputlangs->load("main");

    	$result=dol_print_date(0+24*3600, 'dayhour', true, $outputlangs);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('02/01/1970 00:00', $result);

    	// Check %a and %b format for fr_FR
    	$result=dol_print_date(0, '%a %b %B', true, $outputlangs);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('Jeu Jan. Janvier', $result);

    	// Check day format for en_US
    	$outputlangs=new Translate('', $conf);
    	$outputlangs->setDefaultLang('en_US');
    	$outputlangs->load("main");

    	$result=dol_print_date(0+24*3600, 'day', true, $outputlangs);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('01/02/1970', $result);

    	// Check %a and %b format for en_US
    	$result=dol_print_date(0, '%a %b %B', true, $outputlangs);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('Thu Jan January', $result);

    	return $result;
    }

    /**
     * testDolTimePlusDuree
     *
     * @return int
     */
    public function testDolTimePlusDuree()
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        // Check dayhour format for fr_FR
        $outputlangs=new Translate('', $conf);
        $outputlangs->setDefaultLang('fr_FR');
        $outputlangs->load("main");

        $result=dol_print_date(dol_time_plus_duree(dol_time_plus_duree(dol_time_plus_duree(0, 1, 'm'), 1, 'y'), 1, 'd'), 'dayhour', true, $outputlangs);
       	print __METHOD__." result=".$result."\n";
    	$this->assertEquals('02/02/1971 00:00', $result);

    	return $result;
    }

    /**
     * testDolStringToTime
     *
     * @return int
     */
    public function testDolStringToTime()
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

		$stime='19700102';
		$result=dol_stringtotime($stime);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(86400, $result);

		$stime='1970-01-01T02:00:00Z';
        $result=dol_stringtotime($stime);
    	print __METHOD__." result=".$result."\n";
		$this->assertEquals(7200, $result);

        $stime='1970-01-01 02:00:00';
        $result=dol_stringtotime($stime);
    	print __METHOD__." result=".$result."\n";
		$this->assertEquals(7200, $result);

        $stime='19700101T020000Z';
        $result=dol_stringtotime($stime);
    	print __METHOD__." result=".$result."\n";
		$this->assertEquals(7200, $result);

		$stime='19700101020000';
		$result=dol_stringtotime($stime);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(7200, $result);

        return $result;
    }

    /**
     * testDolGetFirstDayWeek
     *
     * @return int
     */
    public function testDolGetFirstDayWeek()
    {
    	global $conf;

    	$day=3; $month=2; $year=2015;
    	$conf->global->MAIN_START_WEEK = 1;	// start on monday
   		$prev = dol_get_first_day_week($day, $month, $year);
		$this->assertEquals(2, (int) $prev['first_day']);		// monday for month 2, year 2014 is the 2

    	$day=3; $month=2; $year=2015;
    	$conf->global->MAIN_START_WEEK = 0;	// start on sunday
   		$prev = dol_get_first_day_week($day, $month, $year);
		$this->assertEquals(1, (int) $prev['first_day']);		// sunday for month 2, year 2015 is the 1st
    }
}
