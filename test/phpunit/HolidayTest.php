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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *      \file       test/phpunit/HolidayTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/holiday/class/holiday.class.php';
$langs->load("dict");

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
class HolidayTest extends PHPUnit_Framework_TestCase
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @return HolidayTest
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
     * testHolidayCreate
     *
     * @return	int
     */
    public function testHolidayCreate()
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$localobject=new Holiday($this->savdb);
    	$localobject->initAsSpecimen();
    	$result=$localobject->create($user);

        print __METHOD__." result=".$result."\n";
    	$this->assertLessThan($result, 0);

    	return $result;
    }

    /**
     * testHolidayFetch
     *
     * @param	int		$id		Id of Holiday
     * @return	int
     * @depends	testHolidayCreate
     * The depends says test is run only if previous is ok
     */
    public function testHolidayFetch($id)
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$localobject=new Holiday($this->savdb);
    	$result=$localobject->fetch($id);

        print __METHOD__." id=".$id." result=".$result."\n";
    	$this->assertLessThan($result, 0);

    	return $localobject;
    }

    /**
     * testHolidayUpdate
     *
     * @param	Holiday		$localobject	Holiday
     * @return	int
     *
     * @depends	testHolidayFetch
     * The depends says test is run only if previous is ok
     */
    public function testHolidayUpdate($localobject)
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$localobject->oldcopy = clone $localobject;

		$localobject->note_private='New private note after update';
		$localobject->note_public='New public note after update';
		$localobject->lastname='New name';
		$localobject->firstname='New firstname';
		$localobject->address='New address';
		$localobject->zip='New zip';
		$localobject->town='New town';
    	$localobject->country_id=2;
    	//$localobject->status=0;
		$localobject->phone_pro='New tel pro';
		$localobject->phone_perso='New tel perso';
		$localobject->phone_mobile='New tel mobile';
		$localobject->fax='New fax';
		$localobject->email='newemail@newemail.com';
		$localobject->jabberid='New im id';
		$localobject->default_lang='es_ES';

		$result=$localobject->update($localobject->id,$user);
    	print __METHOD__." id=".$localobject->id." result=".$result."\n";
    	$this->assertLessThan($result, 0, 'Holiday::update error');

    	$result=$localobject->update_note($localobject->note_private,'_private');
    	print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0, 'Holiday::update_note (private) error');

		$result=$localobject->update_note($localobject->note_public, '_public');
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0, 'Holiday::update_note (public) error');


		$newobject=new Holiday($this->savdb);
    	$result=$newobject->fetch($localobject->id);
        print __METHOD__." id=".$localobject->id." result=".$result."\n";
    	$this->assertLessThan($result, 0, 'Holiday::fetch error');

    	print __METHOD__." old=".$localobject->note." new=".$newobject->note."\n";
    	$this->assertEquals($localobject->note, $newobject->note,'Holiday::update_note error compare note');
    	//print __METHOD__." old=".$localobject->note_public." new=".$newobject->note_public."\n";
    	//$this->assertEquals($localobject->note_public, $newobject->note_public);

    	return $localobject;
    }

    /**
     * testHolidayOther
     *
     * @param	Holiday		$localobject		Holiday
     * @return	void
     *
     * @depends	testHolidayUpdate
     * The depends says test is run only if previous is ok
     */
    public function testHolidayOther($localobject)
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		//$localobject->fetch($localobject->id);

		/*
        $result=$localobject->getNomUrl(1);
        print __METHOD__." id=".$localobject->id." result=".$result."\n";
        $this->assertNotEquals($result, '');

        $result=$localobject->getFullAddress(1);
        print __METHOD__." id=".$localobject->id." result=".$result."\n";
        $this->assertContains("New address\nNew zip New town\nBelgium", $result);

        $localobject->info($localobject->id);
        print __METHOD__." localobject->date_creation=".$localobject->date_creation."\n";
        $this->assertNotEquals($localobject->date_creation, '');
		*/

        return $localobject->id;
    }

    /**
     * testHolidayDelete
     *
     * @param	int		$id		Id of Holiday
     * @return	void
     *
     * @depends	testHolidayOther
     * The depends says test is run only if previous is ok
     */
    public function testHolidayDelete($id)
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$localobject=new Holiday($this->savdb);
    	$result=$localobject->fetch($id);

    	$result=$localobject->delete(0);
		print __METHOD__." id=".$id." result=".$result."\n";
    	$this->assertLessThan($result, 0);

    	return $result;
    }

    /**
     * testVerifDateHolidayCP
     *
     * @return void
     */
    public function testVerifDateHolidayCP()
    {
    	global $conf,$user,$langs,$db;
    	$conf=$this->savconf;
    	$user=$this->savuser;
    	$langs=$this->savlangs;
    	$db=$this->savdb;

    	// Create a leave request the 1st morning only
    	$localobjecta=new Holiday($this->savdb);
    	$localobjecta->initAsSpecimen();
    	$localobjecta->date_debut = dol_mktime(0, 0, 0, 1, 1, 2020);
    	$localobjecta->date_fin = dol_mktime(0, 0, 0, 1, 1, 2020);
    	$localobjecta->halfday = 1;
    	$result=$localobjecta->create($user);

    	// Create a leave request the 2 afternoon only
    	$localobjectb=new Holiday($this->savdb);
    	$localobjectb->initAsSpecimen();
    	$localobjectb->date_debut = dol_mktime(0, 0, 0, 1, 2, 2020);
    	$localobjectb->date_fin = dol_mktime(0, 0, 0, 1, 2, 2020);
    	$localobjectb->halfday = -1;
    	$result=$localobjectb->create($user);

    	$date_debut = dol_mktime(0, 0, 0, 1, 1, 2020);
    	$date_fin = dol_mktime(0, 0, 0, 1, 2, 2020);

    	$localobjectc=new Holiday($this->savdb);

    	$result=$localobjectc->verifDateHolidayCP($user->id, $date_debut, $date_debut, 0);
    	$this->assertFalse($result, 'result should be false, there is overlapping, full day is not available.');
    	$result=$localobjectc->verifDateHolidayCP($user->id, $date_debut, $date_fin, 0);
    	$this->assertFalse($result, 'result should be false, there is overlapping, full day is not available.');
    	$result=$localobjectc->verifDateHolidayCP($user->id, $date_fin, $date_fin, 0);
    	$this->assertFalse($result, 'result should be false, there is overlapping, full day is not available.');

    	$result=$localobjectc->verifDateHolidayCP($user->id, $date_debut, $date_debut, 1);
    	$this->assertFalse($result, 'result should be false, there is overlapping, morning of first day is not available.');
    	$result=$localobjectc->verifDateHolidayCP($user->id, $date_debut, $date_fin, 1);
    	$this->assertFalse($result, 'result should be false, there is overlapping, morning of first day is not available.');
    	$result=$localobjectc->verifDateHolidayCP($user->id, $date_fin, $date_fin, 1);
    	$this->assertTrue($result, 'result should be true, there is no overlapping');

    	$result=$localobjectc->verifDateHolidayCP($user->id, $date_debut, $date_debut, -1);
    	$this->assertTrue($result, 'result should be true, there is no overlapping');
    	$result=$localobjectc->verifDateHolidayCP($user->id, $date_debut, $date_fin, -1);
    	$this->assertFalse($result, 'result should be false, there is overlapping, afternoon of second day is not available');
    	$result=$localobjectc->verifDateHolidayCP($user->id, $date_fin, $date_fin, -1);
    	$this->assertFalse($result, 'result should be false, there is overlapping, afternoon of second day is not available');

    	$result=$localobjectc->verifDateHolidayCP($user->id, $date_debut, $date_fin, 2);	// start afternoon and end morning
    	$this->assertTrue($result, 'result should be true, there is no overlapping');
    }
}
