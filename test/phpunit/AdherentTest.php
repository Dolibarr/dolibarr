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
 *      \file       test/phpunit/AdherentTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/adherents/class/adherent.class.php';

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
class AdherentTest extends PHPUnit_Framework_TestCase
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @return AdherentTest
	 */
	function AdherentTest()
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
     * testAdherentCreate
     *
     * @return void
     */
    public function testAdherentCreate()
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$localobject=new Adherent($this->savdb);
    	$localobject->initAsSpecimen();
     	$result=$localobject->create($user);
        print __METHOD__." result=".$result."\n";
    	$this->assertLessThan($result, 0);

    	return $result;
    }

    /**
     * testAdherentFetch
     *
     * @param	int		$id		Id of object to fecth
     * @return	object			Fetched object
     *
     * @depends	testAdherentCreate
     * The depends says test is run only if previous is ok
     */
    public function testAdherentFetch($id)
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$localobject=new Adherent($this->savdb);
    	$result=$localobject->fetch($id);
    	print __METHOD__." id=".$id." result=".$result."\n";
        $this->assertLessThan($result, 0);

        return $localobject;
    }

    /**
     * testAdherentUpdate
     *
     * @param	Adherent	$localobject	Member instance
     * @return	Adherent
     *
     * @depends	testAdherentFetch
     * The depends says test is run only if previous is ok
     */
    public function testAdherentUpdate($localobject)
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$localobject->login='newlogin';
		$localobject->societe='New company';
		$localobject->note='New note after update';
		//$localobject->note_public='New note public after update';
		$localobject->lastname='New name';
		$localobject->firstname='New firstname';
		$localobject->address='New address';
		$localobject->zip='New zip';
		$localobject->town='New town';
		$localobject->country_id=2;
		$localobject->statut=0;
		$localobject->phone='New tel pro';
		$localobject->phone_perso='New tel perso';
		$localobject->phone_mobile='New tel mobile';
		$localobject->email='newemail@newemail.com';
		$result=$localobject->update($user);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		$result=$localobject->update_note($localobject->note);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		//$result=$localobject->update_note_public($localobject->note_public);
		//print __METHOD__." id=".$localobject->id." result=".$result."\n";
		//$this->assertLessThan($result, 0);

		$newobject=new Adherent($this->savdb);
		$result=$newobject->fetch($localobject->id);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		$this->assertEquals($localobject->login, $newobject->login);
		$this->assertEquals($localobject->societe, $newobject->societe);
		$this->assertEquals($localobject->note, $newobject->note);
		//$this->assertEquals($localobject->note_public, $newobject->note_public);
		$this->assertEquals($localobject->lastname, $newobject->lastname);
		$this->assertEquals($localobject->firstname, $newobject->firstname);
		$this->assertEquals($localobject->address, $newobject->address);
		$this->assertEquals($localobject->zip, $newobject->zip);
		$this->assertEquals($localobject->town, $newobject->town);
		$this->assertEquals($localobject->country_id, $newobject->country_id);
		$this->assertEquals('BE', $newobject->country_code);
		$this->assertEquals($localobject->statut, $newobject->statut);
		$this->assertEquals($localobject->phone, $newobject->phone);
		$this->assertEquals($localobject->phone_perso, $newobject->phone_perso);
		$this->assertEquals($localobject->phone_mobile, $newobject->phone_mobile);
		$this->assertEquals($localobject->email, $newobject->email);

    	return $localobject;
    }

    /**
     * testAdherentValid
     *
     * @param	Adherent	$localobject	Member instance
     * @return	Adherent
     *
     * @depends	testAdherentUpdate
     * The depends says test is run only if previous is ok
     */
    public function testAdherentValid($localobject)
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

    	$result=$localobject->validate($user);
    	print __METHOD__." id=".$localobject->id." result=".$result."\n";
    	$this->assertLessThan($result, 0);

    	return $localobject;
    }

    /**
     * testAdherentOther
     *
     * @param	Adherent	$localobject	Member instance
     * @return	int							Id of object
     *
     * @depends testAdherentValid
     * The depends says test is run only if previous is ok
     */
    public function testAdherentOther($localobject)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        /*$result=$localobject->setstatus(0);
        print __METHOD__." id=".$localobject->id." result=".$result."\n";
        $this->assertLessThan($result, 0);
        */

        $localobject->info($localobject->id);
        print __METHOD__." localobject->date_creation=".$localobject->date_creation."\n";
        $this->assertNotEquals($localobject->date_creation, '');

        return $localobject->id;
    }

    /**
     * testAdherentDelete
     *
     * @param	int		$id		Id of object to delete
     * @return	void
     *
     * @depends	testAdherentOther
     * The depends says test is run only if previous is ok
     */
    public function testAdherentDelete($id)
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$localobject=new Adherent($this->savdb);
    	$result=$localobject->fetch($id);
		$result=$localobject->delete($id);
		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $result;
    }

}
?>